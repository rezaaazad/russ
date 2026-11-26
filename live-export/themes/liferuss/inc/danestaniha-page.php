<?php
/**
 * «دانستنی‌های روسیه» at /danestaniha/.
 *
 * The page is created on admin_init only. The lock is written before the
 * page insert. No remote HTTP call runs here.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hero photo. Replace this one file to swap the picture.
 */
function liferuss_danestaniha_hero_file() {
	return 'hero-russia.webp';
}

/**
 * Consultation button. Edited in the core form settings. No phone link.
 */
function liferuss_danestaniha_consult_url() {
	$path = '/contact/';
	if ( class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$saved = isset( $forms['danestaniha_consult_url'] ) ? trim( (string) $forms['danestaniha_consult_url'] ) : '';
		if ( '' !== $saved && ! str_starts_with( strtolower( $saved ), 'tel:' ) ) {
			$path = $saved;
		}
	}
	return liferuss_cta_url( $path );
}

/**
 * Request-type choices. Labels follow the spec; the approved layout only shows the closed select.
 *
 * @return array<string, string>
 */
function liferuss_danestaniha_kinds() {
	return array(
		'travel' => 'مشاوره سفر به روسیه',
		'study'  => 'مشاوره تحصیل',
		'life'   => 'زندگی و اقامت',
		'trade'  => 'تجارت و بازرگانی',
		'other'  => 'سایر',
	);
}

/**
 * Consultation topics.
 *
 * @return array<string, string>
 */
function liferuss_danestaniha_topics() {
	return array(
		'souvenirs' => 'سوغات و خرید',
		'cities'    => 'شهرها و جاذبه‌ها',
		'food'      => 'غذاها',
		'culture'   => 'فرهنگ و آداب',
		'weather'   => 'آب‌وهوا و زمان سفر',
		'transport' => 'حمل‌ونقل',
		'other'     => 'سایر',
	);
}

/**
 * Category archive when it has a published post, otherwise the form on this page.
 *
 * @param string $slug Category slug.
 */
function liferuss_danestaniha_topic_url( $slug ) {
	$term = get_term_by( 'slug', (string) $slug, 'category' );
	if ( $term instanceof WP_Term && (int) $term->count > 0 ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return '#danestaniha-form';
}

/**
 * "View all" only when one of these categories already has a published post.
 */
function liferuss_danestaniha_all_url() {
	$slugs = array( 'russia-souvenirs', 'russia-cities', 'russia-food', 'russia-culture', 'russia-attractions', 'russia-weather', 'russia-transport' );
	$any   = false;
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term instanceof WP_Term && (int) $term->count > 0 ) {
			$any = true;
			break;
		}
	}
	if ( ! $any ) {
		return '#danestaniha-form';
	}
	$counts = wp_count_posts( 'post' );
	$blog   = (int) get_option( 'page_for_posts' );
	if ( $blog && $counts && (int) $counts->publish > 0 ) {
		return get_permalink( $blog );
	}
	return '#danestaniha-form';
}

/**
 * Public URL of one page image.
 *
 * @param string $file File name.
 */
function liferuss_danestaniha_img( $file ) {
	return trailingslashit( LIFERUSS_URI ) . 'assets/images/danestaniha/' . ltrim( (string) $file, '/' );
}

/**
 * Create /danestaniha/ once. Admin only.
 */
function liferuss_danestaniha_ensure() {
	if ( ! is_admin() ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_danestaniha_ready', '' ) ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_danestaniha_lock', '' ) ) {
		return;
	}
	update_option( 'lr_danestaniha_lock', '1', false );
	$page_id = liferuss_ensure_page( 'دانستنی‌های روسیه', 'danestaniha', '', 'templates/danestaniha.php' );
	if ( ! $page_id ) {
		delete_option( 'lr_danestaniha_lock' );
		return;
	}
	liferuss_danestaniha_menu_item( $page_id );
	update_option( 'lr_danestaniha_ready', '1', false );
	delete_option( 'lr_danestaniha_lock' );
}
add_action( 'admin_init', 'liferuss_danestaniha_ensure', 64 );

/**
 * Add the page to the assigned menu when it is not already linked.
 *
 * @param int $page_id Page id.
 */
function liferuss_danestaniha_menu_item( $page_id ) {
	$page_id   = (int) $page_id;
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menu_id   = ! empty( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( ! $menu_id ) {
		$menu    = wp_get_nav_menu_object( 'منوی اصلی لایف روس' );
		$menu_id = $menu ? (int) $menu->term_id : 0;
	}
	if ( ! $menu_id ) {
		return;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( (int) $item->object_id === $page_id ) {
				return;
			}
		}
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => 'دانستنی‌های روسیه',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
}

/**
 * Page styles.
 */
function liferuss_danestaniha_assets() {
	if ( ! is_page_template( 'templates/danestaniha.php' ) ) {
		return;
	}
	wp_enqueue_style(
		'liferuss-danestaniha',
		LIFERUSS_URI . '/assets/css/danestaniha.css',
		array( 'liferuss-theme' ),
		LIFERUSS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_danestaniha_assets' );
