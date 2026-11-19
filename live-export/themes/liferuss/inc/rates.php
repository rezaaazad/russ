<?php
/**
 * Market-reference board. Figures come from LifeRuss\Core\Currency\Market.
 * The browser does not call localmoscow.com.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persian copy for the rate board. This section is not translated.
 *
 * @param string $key Copy key.
 */
function liferuss_rate_fa( $key ) {
	static $map = null;
	if ( null === $map ) {
		$map = array(
		'rates_title' => 'نرخ روز',
		'rates_notice' => 'این اعداد مرجع بازار هستند. نرخ نهایی معامله در پاسخ استعلام اعلام می‌شود.',
		'rates_col_name' => 'نرخ',
		'rates_col_value' => 'قیمت',
		'rates_col_buy' => 'خرید',
		'rates_col_sell' => 'فروش',
		'rates_col_source' => 'منبع',
		'rates_col_age' => 'زمان',
		'rates_stale' => 'کهنه',
		'rates_empty' => 'الان عدد معتبری از منبع نرسیده است. استعلام را بفرستید تا نرخ را در پاسخ بنویسیم.',
		'rates_fallback' => 'منبع لحظه‌ای جواب نداد. آخرین جدول سالم نشان داده می‌شود.',
		'rates_age' => 'به‌روزرسانی %s دقیقه پیش',
		'rates_age_unknown' => 'زمان ثبت نشده',
		'rates_inquiry' => 'ثبت استعلام',
		'rates_group_tehran' => 'دلار تهران، هرات و حواله',
		'rates_group_board' => 'یورو، درهم، یوان، لیر و روبل',
		'rates_group_world' => 'پوند، مرجع دلاری',
		'rates_group_tether' => 'تتر ایران',
		'rates_group_moscow' => 'تتر و روبل نقد مسکو',
		'rates_group_banks' => 'بانک مرکزی روسیه، فورا بانک و میر بیزینس',
		'rates_group_metal' => 'طلا و سکه',
		'rates_unit_toman' => 'تومان',
		'rates_unit_rial' => 'ریال',
		'rates_calculator' => 'ماشین‌حساب مرجع',
		'conv_title' => 'تبدیل مرجع',
		'conv_amount' => 'مبلغ',
		'conv_from' => 'از',
		'conv_to' => 'به',
		'conv_result' => 'معادل مرجع',
		'conv_missing' => 'برای این زوج نرخ تومانی در جدول نیست.',
		'conv_same' => 'هر دو طرف یکی است.',
		'conv_irr' => 'تومان',
		'conv_usd' => 'دلار',
		'conv_eur' => 'یورو',
		'conv_rub' => 'روبل',
		'conv_usdt' => 'تتر',
		);
	}
	return isset( $map[ $key ] ) ? $map[ $key ] : $key;
}

/**
 * Styles for the board. Loaded in the head so the table does not jump in.
 */
function liferuss_rates_assets() {
	wp_enqueue_style(
		'liferuss-rates',
		LIFERUSS_URI . '/assets/css/rates.css',
		array( 'liferuss-theme' ),
		LIFERUSS_VERSION
	);
	wp_enqueue_script(
		'liferuss-rates-live',
		LIFERUSS_URI . '/assets/js/rates-live.js',
		array(),
		LIFERUSS_VERSION,
		true
	);
	wp_localize_script(
		'liferuss-rates-live',
		'lrRatesLive',
		array(
			'url' => rest_url( 'liferuss/v1/rates' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_rates_assets' );

/**
 * [lr_rates]
 *
 * @return string
 */
function liferuss_rates_shortcode() {
	ob_start();
	liferuss_the_rates_table();
	return (string) ob_get_clean();
}
add_shortcode( 'lr_rates', 'liferuss_rates_shortcode' );

/**
 * Put the board on /exchange/ when the page body does not already contain the shortcode.
 *
 * @param string $content Post content.
 * @return string
 */
function liferuss_rates_prepend( $content ) {
	if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post || 'exchange' !== $post->post_name || (int) $post->post_parent !== 0 ) {
		return $content;
	}
	if ( str_contains( $content, '[lr_rates]' ) ) {
		return $content;
	}
	return '[lr_rates]' . $content;
}
add_filter( 'the_content', 'liferuss_rates_prepend', 7 );

/**
 * Print the grouped table.
 */
function liferuss_the_rates_table() {
	$board = liferuss_rates_board();
	$items = $board['items'];
	echo '<div class="lr-rates" data-lr-live="board">';
	echo '<h2 class="lr-rates__title">' . esc_html( liferuss_rate_fa( 'rates_title' ) ) . '</h2>';
	echo '<p class="lr-rates__notice">' . esc_html( liferuss_rates_notice( $board ) ) . '</p>';
	if ( ! empty( $board['fallback'] ) && $items ) {
		echo '<p class="lr-rates__fallback">' . esc_html( liferuss_rate_fa( 'rates_fallback' ) ) . '</p>';
	}
	if ( ! $items ) {
		echo '<p class="lr-rates__empty">' . esc_html( liferuss_rate_fa( 'rates_empty' ) ) . '</p>';
		echo '</div>';
		return;
	}
	$groups = array();
	foreach ( $items as $item ) {
		$groups[ (string) $item['group'] ][] = $item;
	}
	foreach ( $groups as $group => $rows ) {
		$key = 'rates_group_' . $group;
		echo '<section class="lr-rates__group">';
		echo '<h3>' . esc_html( liferuss_rate_fa( $key ) ) . '</h3>';
		echo '<div class="lr-rates__scroll">';
		echo '<table class="lr-rates__table">';
		echo '<thead><tr>';
		foreach ( array( 'name', 'value', 'buy', 'sell', 'source', 'age' ) as $col ) {
			echo '<th scope="col">' . esc_html( liferuss_rate_fa( 'rates_col_' . $col ) ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $item ) {
			liferuss_rates_row( $item );
		}
		echo '</tbody></table></div></section>';
	}
	echo '<p class="lr-rates__inquiry">';
	echo '<a class="btn btn-gold" href="#consultation">' . esc_html( liferuss_rate_fa( 'rates_inquiry' ) ) . '</a> ';
	echo '<a class="btn btn-ghost" href="' . esc_url( liferuss_url( '/exchange/calculator/' ) ) . '">' . esc_html( liferuss_rate_fa( 'rates_calculator' ) ) . '</a>';
	echo '</p>';
	echo '</div>';
}

/**
 * One body row.
 *
 * @param array<string, mixed> $item Normalized quote.
 */
function liferuss_rates_row( array $item ) {
	$stale = isset( $item['state'] ) && 'stale' === $item['state'];
	$unit  = (string) ( $item['unit'] ?? '' );
	echo '<tr data-lr-code="' . esc_attr( (string) $item['code'] ) . '" data-lr-unit="' . esc_attr( $unit ) . '"' . ( $stale ? ' class="is-stale"' : '' ) . '>';
	echo '<th scope="row" data-label="' . esc_attr( liferuss_rate_fa( 'rates_col_name' ) ) . '">';
	echo esc_html( (string) $item['title'] );
	if ( ! empty( $item['pair'] ) ) {
		echo ' <span class="lr-rates__pair" dir="ltr">' . esc_html( (string) $item['pair'] ) . '</span>';
	}
	echo ' <span class="lr-rates__badge"' . ( $stale ? '' : ' hidden' ) . '>' . esc_html( liferuss_rate_fa( 'rates_stale' ) ) . '</span>';
	echo '</th>';
	foreach ( array( 'value', 'buy', 'sell' ) as $key ) {
		echo '<td data-lr-field="' . esc_attr( $key ) . '" data-label="' . esc_attr( liferuss_rate_fa( 'rates_col_' . $key ) ) . '">' . esc_html( liferuss_rates_amount( $item[ $key ] ?? null, $unit ) ) . '</td>';
	}
	echo '<td data-lr-field="source" data-label="' . esc_attr( liferuss_rate_fa( 'rates_col_source' ) ) . '">' . esc_html( (string) $item['source'] ) . '</td>';
	echo '<td data-lr-field="age" data-label="' . esc_attr( liferuss_rate_fa( 'rates_col_age' ) ) . '">';
	echo '<span data-lr-field="age-text">' . esc_html( liferuss_rates_age( $item ) ) . '</span>';
	echo '<span class="lr-rates__clock" data-lr-field="clock" dir="ltr">' . esc_html( liferuss_rates_clock( (string) ( $item['atText'] ?? '' ) ) ) . '</span>';
	echo '</td></tr>';
}

/**
 * Board from the plugin, or an empty board when the plugin is inactive.
 *
 * @return array{items: array<int, array<string, mixed>>, fallback: bool, notice: string}
 */
function liferuss_rates_board() {
	if ( ! class_exists( '\LifeRuss\Core\Currency\Market' ) ) {
		return array(
			'items'    => array(),
			'fallback' => false,
			'notice'   => '',
		);
	}
	$board = \LifeRuss\Core\Currency\Market::present();
	return array(
		'items'    => isset( $board['items'] ) && is_array( $board['items'] ) ? $board['items'] : array(),
		'fallback' => ! empty( $board['fallback'] ),
		'notice'   => (string) ( $board['notice'] ?? '' ),
	);
}

/**
 * Disclaimer shown above the board.
 *
 * @param array<string, mixed> $board Board payload.
 */
function liferuss_rates_notice( array $board ) {
	if ( ! empty( $board['notice'] ) ) {
		return (string) $board['notice'];
	}
	return liferuss_rate_fa( 'rates_notice' );
}

/**
 * Age line. Persian keeps the plugin sentence, including its Jalali digits.
 *
 * @param array<string, mixed> $item Quote.
 */
function liferuss_rates_age( array $item ) {
	if ( ! empty( $item['ageText'] ) ) {
		return (string) $item['ageText'];
	}
	if ( ! isset( $item['ageMs'] ) || null === $item['ageMs'] || '' === $item['ageMs'] ) {
		return liferuss_rate_fa( 'rates_age_unknown' );
	}
	$minutes = (string) (int) floor( ( (int) $item['ageMs'] ) / 60000 );
	return sprintf( liferuss_rate_fa( 'rates_age' ), liferuss_local_digits( $minutes ) );
}

/**
 * Tehran clock already formatted by the Jalali helper.
 *
 * @param string $text Jalali clock from the plugin.
 */
function liferuss_rates_clock( $text ) {
	return (string) $text;
}

/**
 * Display amount. Null stays a dash. No figure is invented.
 *
 * @param mixed  $value Raw number.
 * @param string $unit  Unit from the feed.
 */
function liferuss_rates_amount( $value, $unit ) {
	if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
		return '—';
	}
	$number = (float) $value;
	$cash   = in_array( $unit, array( 'تومان', 'ریال' ), true );
	if ( $cash ) {
		$text = number_format( $number, 0, '.', ',' );
	} else {
		$text = rtrim( rtrim( number_format( $number, 4, '.', '' ), '0' ), '.' );
	}
	return liferuss_local_digits( $text ) . ' ' . liferuss_rates_unit( $unit );
}

/**
 * Unit text as the feed sent it.
 *
 * @param string $unit Feed unit.
 */
function liferuss_rates_unit( $unit ) {
	return (string) $unit;
}

/**
 * Bundled heroes. A file is added by the pull request that owns that screen.
 * An attachment in _lr_slot_image replaces the file.
 *
 * @return array<string, array<string, mixed>>
 */
function liferuss_rate_heroes() {
	return array(
		'exchange'           => array(
			'file'  => 'exchange-hero.webp',
			'alt'   => 'استعلام نرخ روز ارز',
			'w'     => 1920,
			'h'     => 1086,
			'eager' => true,
			'hub'   => true,
		),
		'calculator'         => array(
			'file'  => 'exchange-calculator-hero.webp',
			'alt'   => 'ماشین‌حساب نرخ مرجع',
			'w'     => 1920,
			'h'     => 1086,
			'eager' => false,
		),
		'transfer-to-russia' => array(
			'file'  => 'exchange-transfer-to-russia-hero.webp',
			'alt'   => 'حواله به روسیه',
			'w'     => 1920,
			'h'     => 1086,
			'eager' => false,
		),
		'tuition-payment'    => array(
			'file'  => 'exchange-tuition-payment-hero.webp',
			'alt'   => 'پرداخت شهریه',
			'w'     => 1920,
			'h'     => 1086,
			'eager' => false,
		),
		'currencies'         => array(
			'file'  => 'exchange-currencies-hero.webp',
			'alt'   => 'انواع ارز',
			'w'     => 1920,
			'h'     => 1086,
			'eager' => false,
		),
	);
}

/**
 * Hero behind the page title. The photograph’s open area is on the left; the scrim covers the right, where the Persian text sits.
 */
function liferuss_rate_page_hero() {
	if ( ! is_page() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$map = liferuss_rate_heroes();
	if ( ! isset( $map[ $post->post_name ] ) ) {
		return;
	}
	$item = $map[ $post->post_name ];
	if ( ! empty( $item['hub'] ) && (int) $post->post_parent !== 0 ) {
		return;
	}
	$width  = (int) $item['w'];
	$height = (int) $item['h'];
	$alt    = (string) $item['alt'];
	$image  = (int) get_post_meta( $post->ID, '_lr_slot_image', true );
	$path   = LIFERUSS_DIR . '/assets/images/rates/' . $item['file'];
	$has_file = is_readable( $path );
	if ( ! $has_file && ! ( $image && wp_attachment_is_image( $image ) ) ) {
		return;
	}
	echo '<span class="lr-hero__scrim" aria-hidden="true"></span>';
	if ( $image && wp_attachment_is_image( $image ) ) {
		$meta_w = (int) get_post_meta( $post->ID, '_lr_slot_w', true );
		$meta_h = (int) get_post_meta( $post->ID, '_lr_slot_h', true );
		if ( $meta_w > 0 ) {
			$width = $meta_w;
		}
		if ( $meta_h > 0 ) {
			$height = $meta_h;
		}
		echo wp_get_attachment_image(
			$image,
			'large',
			false,
			array(
				'class'         => 'lr-hero__img',
				'alt'           => $alt,
				'width'         => $width,
				'height'        => $height,
				'sizes'         => '(max-width: 720px) 100vw, 1120px',
				'loading'       => empty( $item['eager'] ) ? 'lazy' : false,
				'fetchpriority' => empty( $item['eager'] ) ? false : 'high',
				'decoding'      => 'async',
			)
		);
		return;
	}
	$src = LIFERUSS_URI . '/assets/images/rates/' . $item['file'];
	echo '<img class="lr-hero__img" src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '" sizes="(max-width: 720px) 100vw, 1120px" decoding="async"';
	if ( ! empty( $item['eager'] ) ) {
		echo ' fetchpriority="high"';
	} else {
		echo ' loading="lazy"';
	}
	echo '>';
}

/**
 * [lr_converter] Reference conversion among toman, USD, EUR, RUB, and USDT.
 *
 * @return string
 */
function liferuss_converter_shortcode() {
	wp_enqueue_script(
		'liferuss-converter',
		LIFERUSS_URI . '/assets/js/converter.js',
		array(),
		LIFERUSS_VERSION,
		true
	);
	$payload = liferuss_converter_payload();
	ob_start();
	echo '<div class="lr-conv" data-lr-converter data-quotes="' . esc_attr( wp_json_encode( $payload ) ) . '">';
	echo '<h2 class="lr-conv__title">' . esc_html( liferuss_rate_fa( 'conv_title' ) ) . '</h2>';
	echo '<p class="lr-conv__notice">' . esc_html( (string) $payload['notice'] ) . '</p>';
	if ( ! empty( $payload['fallback'] ) ) {
		echo '<p class="lr-conv__notice">' . esc_html( liferuss_rate_fa( 'rates_fallback' ) ) . '</p>';
	}
	echo '<form class="lr-conv__form" action="#lr-converter" id="lr-converter">';
	echo '<label><span>' . esc_html( liferuss_rate_fa( 'conv_amount' ) ) . '</span>';
	echo '<input type="number" name="amount" min="0" step="any" inputmode="decimal" value="1" required></label>';
	echo '<label><span>' . esc_html( liferuss_rate_fa( 'conv_from' ) ) . '</span><select name="from">';
	liferuss_converter_options( $payload['quotes'], 'IRR' );
	echo '</select></label>';
	echo '<label><span>' . esc_html( liferuss_rate_fa( 'conv_to' ) ) . '</span><select name="to">';
	liferuss_converter_options( $payload['quotes'], 'USD' );
	echo '</select></label>';
	echo '<p class="lr-conv__result" role="status" aria-live="polite"></p>';
	echo '<p class="lr-conv__meta"></p>';
	echo '</form>';
	echo '<p class="lr-rates__inquiry"><a class="btn btn-gold" href="' . esc_url( liferuss_url( '/exchange/#consultation' ) ) . '">' . esc_html( liferuss_rate_fa( 'rates_inquiry' ) ) . '</a></p>';
	echo '</div>';
	return (string) ob_get_clean();
}
add_shortcode( 'lr_converter', 'liferuss_converter_shortcode' );

/**
 * Currency options that have a toman quote. Toman itself is always listed.
 *
 * @param array<string, array<string, mixed>> $quotes Quotes.
 * @param string                               $selected Selected code.
 */
function liferuss_converter_options( array $quotes, $selected ) {
	$order = array( 'IRR', 'USD', 'EUR', 'RUB', 'USDT' );
	foreach ( $order as $code ) {
		if ( ! isset( $quotes[ $code ] ) ) {
			continue;
		}
		echo '<option value="' . esc_attr( $code ) . '" ' . selected( $selected, $code, false ) . '>' . esc_html( liferuss_rate_fa( 'conv_' . strtolower( $code ) ) ) . '</option>';
	}
}

/**
 * Toman quotes for the five converter currencies. Missing pairs are omitted.
 *
 * @return array<string, mixed>
 */
function liferuss_converter_payload() {
	$board  = liferuss_rates_board();
	$quotes = array(
		'IRR' => array(
			'toman'  => 1,
			'source' => '',
			'state'  => 'live',
			'age'    => '',
		),
	);
	$want   = array( 'USD' => true, 'EUR' => true, 'RUB' => true, 'USDT' => true );
	foreach ( $board['items'] as $item ) {
		$code = (string) ( $item['code'] ?? '' );
		if ( ! isset( $want[ $code ] ) || 'تومان' !== (string) ( $item['unit'] ?? '' ) ) {
			continue;
		}
		if ( ! is_numeric( $item['value'] ?? null ) || (float) $item['value'] <= 0 ) {
			continue;
		}
		$quotes[ $code ] = array(
			'toman'  => (float) $item['value'],
			'source' => (string) $item['source'],
			'state'  => (string) ( $item['state'] ?? '' ),
			'age'    => liferuss_rates_age( $item ),
		);
	}
	$notice = liferuss_rates_notice( $board );
	return array(
		'quotes'   => $quotes,
		'notice'   => $notice,
		'fallback' => ! empty( $board['fallback'] ),
		'labels'   => array(
			'result'  => liferuss_rate_fa( 'conv_result' ),
			'missing' => liferuss_rate_fa( 'conv_missing' ),
			'same'    => liferuss_rate_fa( 'conv_same' ),
			'stale'   => liferuss_rate_fa( 'rates_stale' ),
		),
	);
}

/**
 * Publish /exchange/calculator/ once the exchange hub exists.
 */
function liferuss_ensure_calculator_page() {
	if ( '1' === (string) get_option( 'lr_exchange_calculator', '' ) ) {
		return;
	}
	$parent = get_page_by_path( 'exchange' );
	if ( ! $parent instanceof WP_Post ) {
		return;
	}
	$found = get_page_by_path( 'exchange/calculator' );
	if ( $found instanceof WP_Post ) {
		update_option( 'lr_exchange_calculator', '1', false );
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'ماشین‌حساب نرخ مرجع',
			'post_name'    => 'calculator',
			'post_parent'  => (int) $parent->ID,
			'post_content' => '[lr_converter]',
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return;
	}
	update_post_meta( (int) $id, '_wp_page_template', 'templates/path.php' );
	update_post_meta( (int) $id, '_lr_eyebrow', 'مرجع' );
	update_post_meta( (int) $id, '_lr_lead', 'تبدیل بین تومان، دلار، یورو، روبل و تتر با همان جدول نرخ روز. حاصل، قیمت معامله نیست.' );
	update_post_meta( (int) $id, '_lr_form', 'exchange' );
	update_post_meta( (int) $id, '_lr_schema', 'service' );
	update_option( 'lr_exchange_calculator', '1', false );
}
add_action( 'init', 'liferuss_ensure_calculator_page', 50 );
