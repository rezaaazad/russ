<?php
/**
 * Helpers for freight / trade landing pages and homepage cards.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enabled items from a repeater option (skips enabled=0).
 *
 * @param string $key Option key.
 * @return array
 */
function liferuss_enabled_items( $key ) {
	$items = liferuss_opt( $key, array() );
	$out   = array();
	foreach ( (array) $items as $item ) {
		if ( isset( $item['enabled'] ) && '0' === (string) $item['enabled'] ) {
			continue;
		}
		$out[] = $item;
	}
	return $out;
}

/**
 * Whether a landing section flag is on (default on).
 *
 * @param string $key Option key.
 * @return bool
 */
function liferuss_section_on( $key ) {
	return '1' === (string) liferuss_opt( $key, '1' );
}

/**
 * Headline with an optional gold accent substring.
 *
 * @param string $headline Full headline.
 * @param string $accent   Substring to highlight.
 * @return string Safe HTML.
 */
function liferuss_accent_headline( $headline, $accent ) {
	$headline = (string) $headline;
	$accent   = (string) $accent;
	$safe     = esc_html( $headline );
	if ( '' === $accent ) {
		return $safe;
	}
	$needle = esc_html( $accent );
	if ( '' === $needle || false === strpos( $safe, $needle ) ) {
		return $safe;
	}
	return str_replace( $needle, '<span class="gold-text">' . $needle . '</span>', $safe );
}

/**
 * Body classes for landing templates.
 *
 * @param array $classes Classes.
 * @return array
 */
function liferuss_landing_body_class( $classes ) {
	if ( is_page_template( 'templates/freight.php' ) ) {
		$classes[] = 'is-landing';
		$classes[] = 'is-freight';
	}
	if ( is_page_template( 'templates/trade.php' ) ) {
		$classes[] = 'is-landing';
		$classes[] = 'is-trade';
	}
	return $classes;
}
add_filter( 'body_class', 'liferuss_landing_body_class' );

/**
 * Seed freight / trade pages once (also for already-activated sites).
 */
function liferuss_maybe_seed_landings() {
	if ( get_option( 'liferuss_landings_seeded' ) ) {
		return;
	}
	if ( ! function_exists( 'liferuss_ensure_page' ) ) {
		return;
	}

	$freight_id = liferuss_ensure_page(
		'باربری و ارسال',
		'freight',
		'<p>ارسال کارگو، نمونه کالا، وسایل شخصی و مدارک دانشجویی بین ایران و روسیه.</p>',
		'templates/freight.php'
	);
	$trade_id = liferuss_ensure_page(
		'تجارت و تأمین کالا',
		'trade',
		'<p>سورسینگ، خرید از روسیه و صادرات محصولات ایران.</p>',
		'templates/trade.php'
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! empty( $locations['primary'] ) ) {
		$menu_id = (int) $locations['primary'];
		$items   = wp_get_nav_menu_items( $menu_id );
		$have    = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( 'page' === $item->object ) {
					$have[ (int) $item->object_id ] = true;
				}
			}
		}
		$position = is_array( $items ) ? count( $items ) + 1 : 1;
		foreach ( array( $freight_id => 'باربری و ارسال', $trade_id => 'تجارت و تأمین' ) as $page_id => $title ) {
			if ( ! $page_id || isset( $have[ (int) $page_id ] ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $position,
				)
			);
			++$position;
		}
	}

	update_option( 'liferuss_landings_seeded', 1 );
}
add_action( 'after_switch_theme', 'liferuss_maybe_seed_landings' );
add_action( 'init', 'liferuss_maybe_seed_landings', 35 );

