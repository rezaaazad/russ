<?php
/**
 * «باربری، کارگو و ارسال» at /cargo/.
 *
 * The page is created or retargeted on admin_init only, behind a lock that
 * is written before any post update. No remote HTTP call runs here.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggested hero paragraph from the spec.
 */
function liferuss_cargo_hero_lead() {
	return 'لایف روس درخواست ارسال شما را بررسی می‌کند و روش مناسب حمل را به شما اعلام می‌کند.';
}

/**
 * Sentence under the submit button, from the spec.
 */
function liferuss_cargo_form_note() {
	return 'پس از ارسال، درخواست شما بررسی می‌شود و روش و هزینهٔ ارسال به شما اعلام می‌شود.';
}

/**
 * Consultation button. Edited in the core form settings. No phone link.
 */
function liferuss_cargo_consult_url() {
	$path = '/contact/';
	if ( class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$saved = isset( $forms['cargo_consult_url'] ) ? trim( (string) $forms['cargo_consult_url'] ) : '';
		if ( '' !== $saved && ! str_starts_with( strtolower( $saved ), 'tel:' ) ) {
			$path = $saved;
		}
	}
	return liferuss_cta_url( $path );
}

/**
 * Goods the form and the tiles share. پوشاک stays.
 *
 * @return array<string, array{label: string, file: string, alt: string}>
 */
function liferuss_cargo_goods() {
	return array(
		'documents'  => array(
			'label' => 'مدارک',
			'file'  => 'documents.webp',
			'alt'   => 'پوشه و مدارک',
		),
		'personal'   => array(
			'label' => 'وسایل شخصی',
			'file'  => 'personal-items.webp',
			'alt'   => 'چمدان و وسایل شخصی',
		),
		'samples'    => array(
			'label' => 'نمونه کالا',
			'file'  => 'product-samples.webp',
			'alt'   => 'نمونهٔ کالا برای بازرگان',
		),
		'student'    => array(
			'label' => 'بسته دانشجویی',
			'file'  => 'student-package.webp',
			'alt'   => 'بستهٔ دانشجویی',
		),
		'cosmetics'  => array(
			'label' => 'لوازم آرایشی',
			'file'  => 'cosmetics.webp',
			'alt'   => 'لوازم آرایشی',
		),
		'parts'      => array(
			'label' => 'قطعات و کالا',
			'file'  => 'parts.webp',
			'alt'   => 'قطعات و کالای صنعتی',
		),
		'commercial' => array(
			'label' => 'بار تجاری',
			'file'  => 'commercial-cargo.webp',
			'alt'   => 'بار تجاری روی پالت',
		),
		'clothing'   => array(
			'label' => 'پوشاک',
			'file'  => 'clothing.webp',
			'alt'   => 'پوشاک',
		),
	);
}

/**
 * Point /cargo/ at the new template once. Admin only.
 */
function liferuss_cargo_lane_ensure() {
	if ( ! is_admin() ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_cargo_lane_ready', '' ) ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_cargo_lane_lock', '' ) ) {
		return;
	}
	update_option( 'lr_cargo_lane_lock', '1', false );
	$page_id = liferuss_ensure_page( 'باربری، کارگو و ارسال', 'cargo', '', 'templates/cargo-lane.php' );
	if ( ! $page_id ) {
		delete_option( 'lr_cargo_lane_lock' );
		return;
	}
	liferuss_cargo_lane_menu_item( $page_id );
	update_option( 'lr_cargo_lane_ready', '1', false );
	delete_option( 'lr_cargo_lane_lock' );
}
add_action( 'admin_init', 'liferuss_cargo_lane_ensure', 61 );

/**
 * Add a services-menu item when this page is not already linked.
 *
 * @param int $page_id Page id.
 */
function liferuss_cargo_lane_menu_item( $page_id ) {
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
			'menu-item-title'     => 'باربری و کارگو',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
}

/**
 * Styles shared with the sourcing page, plus the cargo highlight.
 */
function liferuss_cargo_lane_assets() {
	if ( ! is_page_template( 'templates/cargo-lane.php' ) ) {
		return;
	}
	wp_enqueue_style(
		'liferuss-sourcing',
		LIFERUSS_URI . '/assets/css/sourcing.css',
		array( 'liferuss-theme' ),
		LIFERUSS_VERSION
	);
	wp_enqueue_style(
		'liferuss-cargo-lane',
		LIFERUSS_URI . '/assets/css/cargo-lane.css',
		array( 'liferuss-sourcing' ),
		LIFERUSS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_cargo_lane_assets' );

/**
 * Picture from the cargo image folder.
 *
 * @param string $file   File name.
 * @param string $alt    Persian alt.
 * @param int    $width  Intrinsic width.
 * @param int    $height Intrinsic height.
 * @param bool   $eager  Hero only.
 * @param string $sizes  Sizes attribute.
 */
function liferuss_cargo_picture( $file, $alt, $width, $height, $eager, $sizes ) {
	$dir  = LIFERUSS_DIR . '/assets/images/cargo/';
	$uri  = LIFERUSS_URI . '/assets/images/cargo/';
	$stem = (string) pathinfo( $file, PATHINFO_FILENAME );
	$set  = array();
	foreach ( array( 480, 800, 1200 ) as $variant ) {
		$name = $stem . '-' . $variant . '.webp';
		if ( is_readable( $dir . $name ) ) {
			$set[] = esc_url( $uri . $name ) . ' ' . $variant . 'w';
		}
	}
	if ( is_readable( $dir . $file ) ) {
		$set[] = esc_url( $uri . $file ) . ' ' . (int) $width . 'w';
	}
	echo '<img src="' . esc_url( $uri . $file ) . '" alt="' . esc_attr( $alt ) . '" width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '"';
	if ( $set ) {
		echo ' srcset="' . esc_attr( implode( ', ', $set ) ) . '" sizes="' . esc_attr( $sizes ) . '"';
	}
	if ( $eager ) {
		echo ' fetchpriority="high" decoding="async"';
	} else {
		echo ' loading="lazy" decoding="async"';
	}
	echo '>';
}
