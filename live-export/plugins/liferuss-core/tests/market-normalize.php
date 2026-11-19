<?php
/**
 * Shape checks for Market::normalize. Run: php tests/market-normalize.php
 *
 * @package LifeRussCore
 */

define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );

require_once dirname( __DIR__ ) . '/includes/CRM/Jalali.php';
require_once dirname( __DIR__ ) . '/includes/Currency/Market.php';

use LifeRuss\Core\Currency\Market;

$failed = 0;

/**
 * @param bool   $ok      Assertion.
 * @param string $message Label.
 */
function lr_check( bool $ok, string $message ): void {
	global $failed;
	if ( $ok ) {
		echo "ok  {$message}\n";
		return;
	}
	echo "FAIL {$message}\n";
	++$failed;
}

$synthetic_market = array(
	'items' => array(
		array(
			'code'   => 'USD',
			'title'  => 'دلار تهران',
			'value'  => 100,
			'unit'   => 'تومان',
			'at'     => '2026-11-19T16:05:00.000Z',
			'state'  => 'live',
			'source' => 'نمونه',
		),
		array(
			'code'   => 'RAPIRA',
			'title'  => 'ریپرا',
			'locked' => true,
		),
		array(
			'code'   => 'EMPTY',
			'title'  => 'خالی',
			'value'  => null,
			'source' => 'نمونه',
		),
		array(
			'code'   => 'FORA',
			'title'  => 'فورا بانک',
			'buy'    => 80,
			'sell'   => 82,
			'value'  => 82,
			'unit'   => '₽',
			'at'     => '2026-11-19T12:00:00.000Z',
			'source' => 'Bankiros.ru',
			'pair'   => 'USD/RUB',
		),
	),
);
$synthetic_rates = array(
	'locked'  => array( 'RAPIRA' => true ),
	'sources' => array(
		'ttprice' => array(
			'label'  => 'TTPrice (تلگرام)',
			'dataAt' => '2026-11-19T16:10:00.000Z',
			'data'   => array(
				'rates' => array(
					'usdHavale' => array( 'price' => 110, 'at' => '2026-11-19T13:35:00.000Z' ),
					'eur'       => array( 'price' => 120, 'at' => '2026-11-19T16:05:00.000Z' ),
					'aed'       => array( 'price' => null, 'at' => '2026-11-19T16:05:00.000Z' ),
					'cny'       => array( 'price' => 40, 'at' => '2026-11-19T16:05:00.000Z' ),
				),
				'coins' => array(
					'gold' => array(
						array( 'key' => 'mesghal', 'price' => 9, 'at' => '2026-11-19T16:05:00.000Z' ),
					),
					'coin' => array(
						array( 'key' => 'bahar', 'price' => 8, 'at' => '2026-11-19T07:00:00.000Z' ),
						array( 'key' => 'emami', 'price' => null, 'at' => '2026-11-19T07:00:00.000Z' ),
					),
				),
			),
		),
		'bcSell' => array(
			'label' => 'BestChange API: USDT → RUB نقدی مسکو',
			'data'  => array(
				'best'       => array( 'rate' => 86.25 ),
				'apiUpdated' => '2026-11-19T16:13:50.000Z',
			),
		),
		'bcBuy'  => array(
			'label' => 'BestChange API: RUB نقدی مسکو → USDT',
			'data'  => array(
				'best'       => array( 'rate' => null ),
				'apiUpdated' => '2026-11-19T16:13:50.000Z',
			),
		),
		'fora'   => array(
			'label' => 'ФОРА-БАНК (Bankiros.ru)',
			'data'  => array(
				'updatedAt' => '2026-11-19T16:00:00.000Z',
				'rates'     => array(
					array( 'code' => 'EUR', 'buy' => 90, 'sell' => 92 ),
				),
			),
		),
		'xe'     => array(
			'label' => 'XE (xe.com)',
			'data'  => array(
				'timestamp'     => '2026-11-19T00:02:31.000Z',
				'xeDirectError' => 'HTTP 403',
				'viaLabel'      => 'ExchangeRate-API (open.er-api.com)',
				'rates'         => array(
					'GBP' => array( 'value' => 1.3 ),
				),
			),
		),
	),
);

$rows = Market::normalize( $synthetic_market, $synthetic_rates );
$by   = array();
foreach ( $rows as $row ) {
	$by[ $row['code'] ] = $row;
}

lr_check( isset( $by['USD'] ) && 100.0 === $by['USD']['value'] && 'نمونه' === $by['USD']['source'], 'keeps a market row and its source' );
lr_check( ! isset( $by['RAPIRA'] ), 'drops locked RAPIRA' );
lr_check( isset( $by['HAVALE'] ) && 'TTPrice (تلگرام)' === $by['HAVALE']['source'], 'havaleh comes from /api/rates' );
lr_check( isset( $by['EUR'] ) && ! isset( $by['AED'] ), 'drops a null price and keeps a real one' );
lr_check( isset( $by['CNY'] ), 'yuan is filled from the rates feed' );
lr_check( isset( $by['USDT_RUB_SELL'] ) && ! isset( $by['USDT_RUB_BUY'] ), 'moscow buy is hidden when the rate is null' );
lr_check( isset( $by['FORA'] ) && isset( $by['FORA_EUR'] ) && null === $by['FORA_EUR']['value'] && 90.0 === $by['FORA_EUR']['buy'], 'fora euro keeps buy and sell without a made-up mid' );
lr_check( isset( $by['GBP'] ) && 'ExchangeRate-API (open.er-api.com)' === $by['GBP']['source'], 'names the source that actually answered' );
lr_check( isset( $by['MESGHAL'] ) && isset( $by['COIN_BAHAR'] ) && ! isset( $by['COIN'] ), 'coin with a null price is omitted' );

$now   = strtotime( '2026-11-19T16:14:37Z' ) * 1000;
$shown = Market::decorate( $rows, $now );
$age   = array();
foreach ( $shown as $row ) {
	$age[ $row['code'] ] = $row;
}
lr_check( isset( $age['USD'] ) && 'live' === $age['USD']['state'] && str_contains( $age['USD']['ageText'], 'به‌روزرسانی ' ) && str_contains( $age['USD']['ageText'], 'دقیقه پیش' ), 'fresh row gets a Persian minute age' );
lr_check( isset( $age['FORA'] ) && 'stale' === $age['FORA']['state'], 'a quote older than three hours is stale' );
lr_check( isset( $age['USD']['atText'] ) && '' !== $age['USD']['atText'] && str_contains( $age['USD']['atText'], '۱۴۰۵' ), 'Tehran clock uses the Jalali helper' );
lr_check( false === $age['USD']['locked'], 'visible rows are not locked' );

$market_file = '/tmp/market.json';
$rates_file  = '/home/ubuntu/.cursor/projects/agent-repos-russ/agent-tools/aab788e0-2d9e-4405-8904-f3d94d0a323f.txt';
if ( is_readable( $market_file ) && is_readable( $rates_file ) ) {
	$live_market = json_decode( (string) file_get_contents( $market_file ), true );
	$live_rates  = json_decode( (string) file_get_contents( $rates_file ), true );
	$live        = Market::normalize( is_array( $live_market ) ? $live_market : null, is_array( $live_rates ) ? $live_rates : null );
	$codes       = array();
	foreach ( $live as $row ) {
		$codes[ $row['code'] ] = $row;
		lr_check( false === $row['locked'], 'live ' . $row['code'] . ' is not locked' );
		lr_check( '' !== $row['source'], 'live ' . $row['code'] . ' names a source' );
		lr_check( null !== $row['value'] || null !== $row['buy'] || null !== $row['sell'], 'live ' . $row['code'] . ' has a figure' );
	}
	foreach ( array( 'USD', 'HERAT', 'HAVALE', 'EUR', 'AED', 'CNY', 'TRY', 'RUB', 'USDT', 'USDT_RUB_SELL', 'USDT_RUB_BUY', 'CBR', 'FORA', 'MBB', 'GOLD', 'COIN' ) as $code ) {
		lr_check( isset( $codes[ $code ] ), 'live board includes ' . $code );
	}
	lr_check( ! isset( $codes['RAPIRA'] ), 'live board hides RAPIRA' );
	echo 'live codes: ' . implode( ',', array_keys( $codes ) ) . "\n";
} else {
	echo "skip live fixture (json not on disk)\n";
}

if ( $failed > 0 ) {
	echo "{$failed} failed\n";
	exit( 1 );
}
echo "all passed\n";
exit( 0 );
