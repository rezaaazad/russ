<?php
/**
 * Market-reference quotes from Local.Moscow.
 *
 * The server fetches Local.Moscow. The browser may call only liferuss/v1/rates,
 * and that response is marked uncacheable. Page cache and object cache stay on.
 * Tuition conversion stays in Rates and the lr_fx_update cron is not used here.
 *
 * /api/market is the board. It does not list every pair the exchange page needs
 * (حواله، یورو، درهم، یوان، دلار بانک مرکزی، تتر/روبل مسکو). Those rows are read
 * from /api/rates and shaped like market items. /api/history is a 24-hour series
 * and is not pulled for the page.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Currency;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch, cache, and normalize the public rate board.
 */
class Market {

	public const MARKET_URL = 'https://localmoscow.com/api/market';

	public const RATES_URL = 'https://localmoscow.com/api/rates';

	public const TRANSIENT = 'lr_market_board';

	public const OPTION = 'lr_market_last_good';

	public const STALE_MS = 10800000;

	/**
	 * Codes the site is allowed to show, in table order, with a group key.
	 *
	 * @var array<string, string>
	 */
	public const GROUPS = array(
		'USD'           => 'tehran',
		'HERAT'         => 'tehran',
		'HAVALE'        => 'tehran',
		'EUR'           => 'board',
		'AED'           => 'board',
		'CNY'           => 'board',
		'TRY'           => 'board',
		'RUB'           => 'board',
		'GBP'           => 'world',
		'USDT'          => 'tether',
		'TETHERFREE'    => 'tether',
		'USDT_RUB_SELL' => 'moscow',
		'USDT_RUB_BUY'  => 'moscow',
		'CBR'           => 'banks',
		'FORA'          => 'banks',
		'FORA_EUR'      => 'banks',
		'FORA_CNY'      => 'banks',
		'MBB'           => 'banks',
		'GOLD'          => 'metal',
		'MESGHAL'       => 'metal',
		'COIN'          => 'metal',
		'COIN_BAHAR'    => 'metal',
		'COIN_HALF'     => 'metal',
		'COIN_QUARTER'  => 'metal',
		'COIN_GERAMI'   => 'metal',
	);

	/**
	 * Register the internal rates route. No cron.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register' ) );
	}

	/**
	 * GET /wp-json/liferuss/v1/rates
	 */
	public static function register(): void {
		register_rest_route(
			'liferuss/v1',
			'/rates',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Same-origin board for the browser. Nginx page cache and Redis stay on for the site.
	 * This response is not stored: the HTML shell keeps the last figures, and the script
	 * reads this route so a cached page does not freeze the prices.
	 */
	public static function rest(): \WP_REST_Response {
		nocache_headers();
		$response = new \WP_REST_Response( self::present() );
		$response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', 'Wed, 11 Jan 1984 05:00:00 GMT' );
		$response->header( 'X-Accel-Expires', '0' );
		return $response;
	}

	/**
	 * Board for templates and the inquiry snapshot.
	 *
	 * @return array{items: array<int, array<string, mixed>>, fetchedAt: string, fallback: bool, notice: string}
	 */
	public static function present(): array {
		$stored = self::load();
		$now    = (int) round( microtime( true ) * 1000 );
		return array(
			'items'     => self::decorate( $stored['items'], $now ),
			'fetchedAt' => (string) $stored['fetchedAt'],
			'fallback'  => (bool) $stored['fallback'],
			'notice'    => self::notice(),
		);
	}

	/**
	 * Persian sentence shown next to the board and stored with an inquiry.
	 */
	public static function notice(): string {
		return 'این اعداد مرجع بازار هستند. نرخ نهایی معامله در پاسخ استعلام اعلام می‌شود.';
	}

	/**
	 * Compact JSON for an exchange request. Empty when the board has no rows.
	 *
	 * @return array<string, mixed>
	 */
	public static function snapshot(): array {
		$board = self::present();
		$rows  = array();
		foreach ( $board['items'] as $item ) {
			$rows[] = array(
				'code'   => $item['code'],
				'title'  => $item['title'],
				'value'  => $item['value'],
				'buy'    => $item['buy'],
				'sell'   => $item['sell'],
				'unit'   => $item['unit'],
				'at'     => $item['at'],
				'state'  => $item['state'],
				'source' => $item['source'],
			);
		}
		return array(
			'items'     => $rows,
			'fetchedAt' => $board['fetchedAt'],
			'fallback'  => $board['fallback'],
			'notice'    => $board['notice'],
		);
	}

	/**
	 * Build whitelist rows from the two JSON documents.
	 *
	 * @param array<string, mixed>|null $market /api/market body.
	 * @param array<string, mixed>|null $rates  /api/rates body.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize( ?array $market, ?array $rates ): array {
		$rates  = is_array( $rates ) ? $rates : array();
		$locked = array();
		if ( isset( $rates['locked'] ) && is_array( $rates['locked'] ) ) {
			foreach ( $rates['locked'] as $code => $flag ) {
				if ( $flag ) {
					$locked[ strtoupper( (string) $code ) ] = true;
				}
			}
		}

		$by_code = array();
		$items   = ( is_array( $market ) && isset( $market['items'] ) && is_array( $market['items'] ) ) ? $market['items'] : array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$code = strtoupper( (string) ( $item['code'] ?? '' ) );
			if ( ! isset( self::GROUPS[ $code ] ) ) {
				continue;
			}
			if ( ! empty( $item['locked'] ) ) {
				$locked[ $code ] = true;
				continue;
			}
			$row = self::row(
				$code,
				(string) ( $item['title'] ?? '' ),
				$item['value'] ?? null,
				$item['buy'] ?? null,
				$item['sell'] ?? null,
				(string) ( $item['unit'] ?? '' ),
				isset( $item['at'] ) ? (string) $item['at'] : '',
				(string) ( $item['source'] ?? '' ),
				(string) ( $item['state'] ?? '' ),
				(string) ( $item['pair'] ?? '' ),
				$locked
			);
			if ( null !== $row ) {
				$by_code[ $code ] = $row;
			}
		}

		$sources = ( isset( $rates['sources'] ) && is_array( $rates['sources'] ) ) ? $rates['sources'] : array();
		self::from_ttprice( $by_code, $sources, $locked );
		self::from_tfree( $by_code, $sources, $locked );
		self::from_moscow( $by_code, $sources, $locked );
		self::from_cbr( $by_code, $sources, $locked );
		self::from_fora( $by_code, $sources, $locked );
		self::from_mbb( $by_code, $sources, $locked );
		self::from_xe( $by_code, $sources, $locked );
		self::from_coins( $by_code, $sources, $locked );

		$ordered = array();
		foreach ( self::GROUPS as $code => $group ) {
			unset( $group );
			if ( isset( $by_code[ $code ] ) ) {
				$ordered[] = $by_code[ $code ];
			}
		}
		return $ordered;
	}

	/**
	 * Recompute age, Tehran clock, and the stale flag. Locked rows never reach here.
	 *
	 * @param array<int, array<string, mixed>> $items Stored rows.
	 * @param int                               $now_ms Unix epoch in milliseconds.
	 * @return array<int, array<string, mixed>>
	 */
	public static function decorate( array $items, int $now_ms ): array {
		$out = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['code'] ) ) {
				continue;
			}
			$code = (string) $item['code'];
			if ( ! isset( self::GROUPS[ $code ] ) || ! empty( $item['locked'] ) ) {
				continue;
			}
			if ( null === ( $item['value'] ?? null ) && null === ( $item['buy'] ?? null ) && null === ( $item['sell'] ?? null ) ) {
				continue;
			}
			$at_ms = self::millis( (string) ( $item['at'] ?? '' ) );
			$age   = null === $at_ms ? null : max( 0, $now_ms - $at_ms );
			$state = (string) ( $item['feedState'] ?? '' );
			if ( ! in_array( $state, array( 'live', 'recent', 'old' ), true ) ) {
				$state = 'live';
			}
			if ( null === $age || $age > self::STALE_MS ) {
				$state = 'stale';
			}
			$minutes = null === $age ? null : (int) floor( $age / 60000 );
			$item['group']   = self::GROUPS[ $code ];
			$item['ageMs']   = $age;
			$item['state']   = $state;
			$item['ageText'] = null === $minutes ? '' : self::age_text( $minutes );
			$item['atText']  = self::tehran_text( (string) ( $item['at'] ?? '' ) );
			$item['locked']  = false;
			unset( $item['feedState'] );
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Relative age. Digits go through the Jalali helper.
	 *
	 * @param int $minutes Whole minutes, already clamped at zero.
	 */
	public static function age_text( int $minutes ): string {
		return 'به‌روزرسانی ' . Jalali::fa_digits( (string) max( 0, $minutes ) ) . ' دقیقه پیش';
	}

	/**
	 * Cached board, or a fresh pull, or the last good option.
	 *
	 * @return array{items: array<int, array<string, mixed>>, fetchedAt: string, fallback: bool}
	 */
	private static function load(): array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['items'] ) && is_array( $cached['items'] ) ) {
			$cached['fetchedAt'] = (string) ( $cached['fetchedAt'] ?? '' );
			$cached['fallback']  = ! empty( $cached['fallback'] );
			return $cached;
		}

		$fresh = self::pull();
		if ( $fresh['items'] ) {
			set_transient( self::TRANSIENT, $fresh, 5 * MINUTE_IN_SECONDS );
			update_option( self::OPTION, $fresh, false );
			return $fresh;
		}

		$last = get_option( self::OPTION, array() );
		if ( is_array( $last ) && ! empty( $last['items'] ) && is_array( $last['items'] ) ) {
			$last['fetchedAt'] = (string) ( $last['fetchedAt'] ?? '' );
			$last['fallback']  = true;
			set_transient( self::TRANSIENT, $last, 5 * MINUTE_IN_SECONDS );
			return $last;
		}

		$empty = array(
			'items'     => array(),
			'fetchedAt' => '',
			'fallback'  => true,
		);
		set_transient( self::TRANSIENT, $empty, MINUTE_IN_SECONDS );
		return $empty;
	}

	/**
	 * HTTP GET of both documents. A failed call yields null and the other side is still used.
	 *
	 * @return array{items: array<int, array<string, mixed>>, fetchedAt: string, fallback: bool}
	 */
	private static function pull(): array {
		$injected = apply_filters( 'lr_market_bodies', null );
		if ( is_array( $injected ) ) {
			$market = isset( $injected['market'] ) && is_array( $injected['market'] ) ? $injected['market'] : null;
			$rates  = isset( $injected['rates'] ) && is_array( $injected['rates'] ) ? $injected['rates'] : null;
		} else {
			$market = self::get_json( self::MARKET_URL );
			$rates  = self::get_json( self::RATES_URL );
		}
		return array(
			'items'     => self::normalize( $market, $rates ),
			'fetchedAt' => gmdate( 'Y-m-d H:i:s' ),
			'fallback'  => false,
		);
	}

	/**
	 * One JSON object, or null on any transport or parse failure.
	 *
	 * @param string $url Absolute URL.
	 * @return array<string, mixed>|null
	 */
	private static function get_json( string $url ): ?array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 2,
				'headers'     => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'LifeRuss/' . ( defined( 'LIFERUSS_CORE_VERSION' ) ? LIFERUSS_CORE_VERSION : '1' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Tehran free-market rows that /api/market sometimes omits.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_ttprice( array &$by_code, array $sources, array $locked ): void {
		$block = self::source( $sources, 'ttprice' );
		$data  = $block['data'];
		$rates = ( isset( $data['rates'] ) && is_array( $data['rates'] ) ) ? $data['rates'] : array();
		$map   = array(
			'usdTehran' => array( 'USD', 'دلار تهران' ),
			'usdHerat'  => array( 'HERAT', 'دلار هرات' ),
			'usdHavale' => array( 'HAVALE', 'دلار حواله' ),
			'eur'       => array( 'EUR', 'یورو' ),
			'aed'       => array( 'AED', 'درهم امارات' ),
			'cny'       => array( 'CNY', 'یوان چین' ),
			'try'       => array( 'TRY', 'لیر ترکیه' ),
			'rub'       => array( 'RUB', 'روبل' ),
		);
		foreach ( $map as $key => $meta ) {
			if ( isset( $by_code[ $meta[0] ] ) || empty( $rates[ $key ] ) || ! is_array( $rates[ $key ] ) ) {
				continue;
			}
			$quote = $rates[ $key ];
			$row   = self::row( $meta[0], $meta[1], $quote['price'] ?? null, null, null, 'تومان', (string) ( $quote['at'] ?? '' ), $block['label'], '', '', $locked );
			if ( null !== $row ) {
				$by_code[ $meta[0] ] = $row;
			}
		}
		if ( isset( $by_code['USDT'] ) ) {
			return;
		}
		$at  = '';
		$ats = ( isset( $data['itemAt'] ) && is_array( $data['itemAt'] ) ) ? $data['itemAt'] : array();
		if ( ! empty( $ats['usdtAvg'] ) ) {
			$at = (string) $ats['usdtAvg'];
		}
		$row = self::row( 'USDT', 'تتر', null, $data['usdtAvgBuy'] ?? null, $data['usdtAvgSell'] ?? null, 'تومان', $at, $block['label'], '', '', $locked );
		if ( null !== $row ) {
			$by_code['USDT'] = $row;
		}
	}

	/**
	 * Free-market tether when the market item is absent.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_tfree( array &$by_code, array $sources, array $locked ): void {
		if ( isset( $by_code['TETHERFREE'] ) ) {
			return;
		}
		$block = self::source( $sources, 'tfree' );
		$data  = $block['data'];
		$row   = self::row( 'TETHERFREE', 'تتر در بازار آزاد', $data['price'] ?? null, $data['buy'] ?? null, $data['sell'] ?? null, 'تومان', (string) ( $data['time'] ?? '' ), $block['label'], '', '', $locked );
		if ( null !== $row ) {
			$by_code['TETHERFREE'] = $row;
		}
	}

	/**
	 * BestChange cash USDT against Moscow cash rubles. Two directions, two rows.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_moscow( array &$by_code, array $sources, array $locked ): void {
		$sides = array(
			'bcSell' => array( 'USDT_RUB_SELL', 'تتر به روبل نقد مسکو' ),
			'bcBuy'  => array( 'USDT_RUB_BUY', 'روبل نقد مسکو به تتر' ),
		);
		foreach ( $sides as $key => $meta ) {
			if ( isset( $by_code[ $meta[0] ] ) ) {
				continue;
			}
			$block = self::source( $sources, $key );
			$best  = ( isset( $block['data']['best'] ) && is_array( $block['data']['best'] ) ) ? $block['data']['best'] : array();
			$at    = (string) ( $block['data']['apiUpdated'] ?? $block['dataAt'] );
			$row   = self::row( $meta[0], $meta[1], $best['rate'] ?? null, null, null, '₽', $at, $block['label'], '', 'USDT/RUB', $locked );
			if ( null !== $row ) {
				$by_code[ $meta[0] ] = $row;
			}
		}
	}

	/**
	 * Central Bank of Russia, USD per the official ruble quote.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_cbr( array &$by_code, array $sources, array $locked ): void {
		if ( isset( $by_code['CBR'] ) ) {
			return;
		}
		$block = self::source( $sources, 'cbr' );
		$usd   = ( isset( $block['data']['rates']['USD'] ) && is_array( $block['data']['rates']['USD'] ) ) ? $block['data']['rates']['USD'] : array();
		$value = $usd['value'] ?? ( $usd['rubPerUnit'] ?? null );
		$row   = self::row( 'CBR', 'دلار بانک مرکزی روسیه', $value, null, null, '₽', $block['dataAt'], $block['label'], '', 'USD/RUB', $locked );
		if ( null !== $row ) {
			$by_code['CBR'] = $row;
		}
	}

	/**
	 * Fora Bank cash desk. The market item is the dollar; euro and yuan come from the rates list.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_fora( array &$by_code, array $sources, array $locked ): void {
		$block = self::source( $sources, 'fora' );
		$list  = ( isset( $block['data']['rates'] ) && is_array( $block['data']['rates'] ) ) ? $block['data']['rates'] : array();
		$names = array(
			'USD' => array( 'FORA', 'فورا بانک — دلار' ),
			'EUR' => array( 'FORA_EUR', 'فورا بانک — یورو' ),
			'CNY' => array( 'FORA_CNY', 'فورا بانک — یوان' ),
		);
		$at = (string) ( $block['data']['updatedAt'] ?? $block['dataAt'] );
		foreach ( $list as $quote ) {
			if ( ! is_array( $quote ) ) {
				continue;
			}
			$iso = strtoupper( (string) ( $quote['code'] ?? '' ) );
			if ( ! isset( $names[ $iso ] ) || isset( $by_code[ $names[ $iso ][0] ] ) ) {
				continue;
			}
			$row = self::row( $names[ $iso ][0], $names[ $iso ][1], null, $quote['buy'] ?? null, $quote['sell'] ?? null, '₽', $at, $block['label'], '', $iso . '/RUB', $locked );
			if ( null !== $row ) {
				$by_code[ $names[ $iso ][0] ] = $row;
			}
		}
	}

	/**
	 * Mir Business Bank ruble against the rial, when the market item is absent.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_mbb( array &$by_code, array $sources, array $locked ): void {
		if ( isset( $by_code['MBB'] ) ) {
			return;
		}
		$block = self::source( $sources, 'mbb' );
		$data  = $block['data'];
		$unit  = (string) ( $data['unit'] ?? '' );
		if ( '' === $unit ) {
			$unit = 'ریال';
		}
		$row = self::row( 'MBB', 'میر بیزینس بانک', $data['mid'] ?? null, $data['buy'] ?? null, $data['sell'] ?? null, $unit, (string) ( $data['checkedAt'] ?? $block['dataAt'] ), $block['label'], '', (string) ( $data['pair'] ?? 'RUB/IRR' ), $locked );
		if ( null !== $row ) {
			$by_code['MBB'] = $row;
		}
	}

	/**
	 * Pound cross. The label follows the feed: XE when that source answered, otherwise the fallback named in the JSON.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_xe( array &$by_code, array $sources, array $locked ): void {
		if ( isset( $by_code['GBP'] ) ) {
			return;
		}
		$block = self::source( $sources, 'xe' );
		$gbp   = ( isset( $block['data']['rates']['GBP'] ) && is_array( $block['data']['rates']['GBP'] ) ) ? $block['data']['rates']['GBP'] : array();
		$label = $block['label'];
		if ( ! empty( $block['data']['xeDirectError'] ) && ! empty( $block['data']['viaLabel'] ) ) {
			$label = (string) $block['data']['viaLabel'];
		}
		$row = self::row( 'GBP', 'پوند', $gbp['value'] ?? null, null, null, 'USD', (string) ( $block['data']['timestamp'] ?? $block['dataAt'] ), $label, '', 'GBP/USD', $locked );
		if ( null !== $row ) {
			$by_code['GBP'] = $row;
		}
	}

	/**
	 * Iranian coin and gold keys. Ounce and silver are not on the whitelist.
	 *
	 * @param array<string, array<string, mixed>> $by_code Rows.
	 * @param array<string, mixed>                $sources Rate sources.
	 * @param array<string, bool>                 $locked  Locked codes.
	 */
	private static function from_coins( array &$by_code, array $sources, array $locked ): void {
		$block = self::source( $sources, 'ttprice' );
		$coins = ( isset( $block['data']['coins'] ) && is_array( $block['data']['coins'] ) ) ? $block['data']['coins'] : array();
		$map   = array(
			'gram18'  => array( 'GOLD', 'طلای ۱۸ عیار (گرم)' ),
			'mesghal' => array( 'MESGHAL', 'مثقال طلا' ),
			'emami'   => array( 'COIN', 'سکه تمام (امامی)' ),
			'bahar'   => array( 'COIN_BAHAR', 'سکه بهار آزادی' ),
			'half'    => array( 'COIN_HALF', 'نیم‌سکه' ),
			'quarter' => array( 'COIN_QUARTER', 'ربع‌سکه' ),
			'gerami'  => array( 'COIN_GERAMI', 'سکه گرمی' ),
		);
		$lists = array();
		if ( isset( $coins['gold'] ) && is_array( $coins['gold'] ) ) {
			$lists[] = $coins['gold'];
		}
		if ( isset( $coins['coin'] ) && is_array( $coins['coin'] ) ) {
			$lists[] = $coins['coin'];
		}
		foreach ( $lists as $list ) {
			foreach ( $list as $quote ) {
				if ( ! is_array( $quote ) ) {
					continue;
				}
				$key = (string) ( $quote['key'] ?? '' );
				if ( ! isset( $map[ $key ] ) || isset( $by_code[ $map[ $key ][0] ] ) ) {
					continue;
				}
				$row = self::row( $map[ $key ][0], $map[ $key ][1], $quote['price'] ?? null, null, null, 'تومان', (string) ( $quote['at'] ?? '' ), $block['label'], '', '', $locked );
				if ( null !== $row ) {
					$by_code[ $map[ $key ][0] ] = $row;
				}
			}
		}
	}

	/**
	 * Source label plus its data object. A missing source is an empty board, not an error row.
	 *
	 * @param array<string, mixed> $sources Sources map.
	 * @param string               $key     Source key.
	 * @return array{label: string, data: array<string, mixed>, dataAt: string}
	 */
	private static function source( array $sources, string $key ): array {
		$block = ( isset( $sources[ $key ] ) && is_array( $sources[ $key ] ) ) ? $sources[ $key ] : array();
		$data  = ( isset( $block['data'] ) && is_array( $block['data'] ) ) ? $block['data'] : array();
		$label = trim( (string) ( $block['label'] ?? '' ) );
		if ( '' === $label && isset( $data['sourceName'] ) ) {
			$label = trim( (string) $data['sourceName'] );
		}
		return array(
			'label'  => $label,
			'data'   => $data,
			'dataAt' => (string) ( $block['dataAt'] ?? '' ),
		);
	}

	/**
	 * One whitelist row, or null when locked or valueless.
	 *
	 * @param string              $code   Whitelist code.
	 * @param string              $title  Persian title.
	 * @param mixed               $value  Mid or single price.
	 * @param mixed               $buy    Buy.
	 * @param mixed               $sell   Sell.
	 * @param string              $unit   Unit label from the feed.
	 * @param string              $at     ISO timestamp.
	 * @param string              $source Source name.
	 * @param string              $state  Feed state, kept until decorate().
	 * @param string              $pair   Pair label.
	 * @param array<string, bool> $locked Locked codes.
	 * @return array<string, mixed>|null
	 */
	private static function row( string $code, string $title, $value, $buy, $sell, string $unit, string $at, string $source, string $state, string $pair, array $locked ): ?array {
		if ( ! isset( self::GROUPS[ $code ] ) || ! empty( $locked[ $code ] ) ) {
			return null;
		}
		$value = self::num( $value );
		$buy   = self::num( $buy );
		$sell  = self::num( $sell );
		if ( null === $value && null === $buy && null === $sell ) {
			return null;
		}
		$source = trim( $source );
		if ( '' === $source ) {
			$source = 'localmoscow.com';
		}
		return array(
			'code'      => $code,
			'group'     => self::GROUPS[ $code ],
			'title'     => '' !== trim( $title ) ? trim( $title ) : $code,
			'value'     => $value,
			'buy'       => $buy,
			'sell'      => $sell,
			'unit'      => trim( $unit ),
			'at'        => self::iso( $at ),
			'feedState' => $state,
			'pair'      => trim( $pair ),
			'source'    => $source,
			'locked'    => false,
		);
	}

	/**
	 * Finite number, or null.
	 *
	 * @param mixed $value Raw JSON value.
	 */
	private static function num( $value ): ?float {
		if ( null === $value || '' === $value || is_bool( $value ) || ! is_numeric( $value ) ) {
			return null;
		}
		$number = (float) $value;
		if ( ! is_finite( $number ) ) {
			return null;
		}
		return round( $number, 6 );
	}

	/**
	 * ISO-8601, or empty when the feed has no clock.
	 *
	 * @param string $at Raw timestamp.
	 */
	private static function iso( string $at ): string {
		$at = trim( $at );
		if ( '' === $at ) {
			return '';
		}
		$stamp = strtotime( $at );
		return $stamp ? gmdate( 'c', $stamp ) : '';
	}

	/**
	 * Epoch milliseconds for an ISO timestamp.
	 *
	 * @param string $at ISO timestamp.
	 */
	private static function millis( string $at ): ?int {
		$at = trim( $at );
		if ( '' === $at ) {
			return null;
		}
		$stamp = strtotime( $at );
		return $stamp ? $stamp * 1000 : null;
	}

	/**
	 * Jalali clock in Tehran for a stored ISO timestamp.
	 *
	 * @param string $at ISO timestamp.
	 */
	private static function tehran_text( string $at ): string {
		$stamp = self::millis( $at );
		if ( null === $stamp ) {
			return '';
		}
		$mysql = gmdate( 'Y-m-d H:i:s', (int) floor( $stamp / 1000 ) );
		return Jalali::fa_digits( Jalali::plain( $mysql ) );
	}
}
