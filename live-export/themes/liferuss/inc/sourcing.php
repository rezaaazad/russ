<?php
/**
 * «بازرگانی و سورسینگ» page, menu item, and the section 11 defaults.
 *
 * The hero paragraph, country list, upload ceiling, form note, and the
 * consultation link are defaults Amin still has to confirm. They are options,
 * so they can be edited without a code change. No phone number is stored here.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product files: five files, 20 MB each, before the server's own PHP ceiling.
 */
const LIFERUSS_SOURCING_MAX_FILES = 5;
const LIFERUSS_SOURCING_MAX_BYTES = 20971520;

/**
 * Page slug.
 */
function liferuss_sourcing_slug() {
	return 'bazargani';
}

/**
 * Option value, or the marked default.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default.
 * @return mixed
 */
function liferuss_sourcing_opt( $key, $default ) {
	$stored = get_option( $key, null );
	if ( null === $stored || '' === $stored ) {
		return $default;
	}
	return $stored;
}

/**
 * Hero paragraph, approved as written in the spec.
 */
function liferuss_sourcing_hero_lead() {
	return 'لایف روس درخواست تأمین شما را بررسی می‌کند و با تأمین‌کننده‌های مناسب هماهنگ می‌کند.';
}

/**
 * Sentence under the submit button. Suggested copy, pending confirmation.
 */
function liferuss_sourcing_form_note() {
	return (string) liferuss_sourcing_opt(
		'lr_sourcing_form_note',
		'پس از ارسال، درخواست شما در بخش بازرگانی لایف روس بررسی می‌شود.'
	);
}

/**
 * Where «مشاوره با بخش بازرگانی» goes. Edited in the core plugin form settings.
 * The default is the existing contact page. No phone number.
 */
function liferuss_sourcing_consult_url() {
	$path = '/contact/';
	if ( class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$saved = isset( $forms['sourcing_consult_url'] ) ? trim( (string) $forms['sourcing_consult_url'] ) : '';
		if ( '' !== $saved && ! str_starts_with( strtolower( $saved ), 'tel:' ) ) {
			$path = $saved;
		}
	}
	return liferuss_cta_url( $path );
}

/**
 * Bytes in a PHP ini size such as 20M.
 *
 * @param mixed $value ini value.
 */
function liferuss_sourcing_ini_bytes( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}
	$unit = strtolower( substr( $value, -1 ) );
	$num  = (float) $value;
	if ( 'g' === $unit ) {
		return (int) ( $num * 1024 * 1024 * 1024 );
	}
	if ( 'm' === $unit ) {
		return (int) ( $num * 1024 * 1024 );
	}
	if ( 'k' === $unit ) {
		return (int) ( $num * 1024 );
	}
	return (int) $num;
}

/**
 * Per-file ceiling actually enforced: 20 MB, or less when PHP is configured lower.
 */
function liferuss_sourcing_upload_bytes() {
	$cap  = LIFERUSS_SOURCING_MAX_BYTES;
	$up   = liferuss_sourcing_ini_bytes( ini_get( 'upload_max_filesize' ) );
	$post = liferuss_sourcing_ini_bytes( ini_get( 'post_max_size' ) );
	if ( $up > 0 ) {
		$cap = min( $cap, $up );
	}
	if ( $post > 0 ) {
		$cap = min( $cap, $post );
	}
	return $cap;
}

/**
 * True when PHP will not accept the full 20 MB file.
 */
function liferuss_sourcing_upload_capped() {
	return liferuss_sourcing_upload_bytes() < LIFERUSS_SOURCING_MAX_BYTES;
}

/**
 * Origin and destination. Only Iran and Russia.
 *
 * @return string[]
 */
function liferuss_sourcing_countries() {
	return array( 'ایران', 'روسیه' );
}

/**
 * Eight product categories.
 *
 * @return array<string, array{label: string, file: string, alt: string}>
 */
function liferuss_sourcing_categories() {
	return array(
		'pistachio' => array(
			'label' => 'پسته و خشکبار',
			'file'  => 'pistachio-nuts.webp',
			'alt'   => 'کاسهٔ پستهٔ پوست‌کنده',
		),
		'dates'     => array(
			'label' => 'خرما',
			'file'  => 'dates.webp',
			'alt'   => 'خرما در ظرف',
		),
		'food'      => array(
			'label' => 'مواد غذایی',
			'file'  => 'food-products.webp',
			'alt'   => 'مواد غذایی بسته‌بندی‌شده',
		),
		'raw'       => array(
			'label' => 'مواد اولیه',
			'file'  => 'raw-materials.webp',
			'alt'   => 'مواد اولیهٔ صنعتی',
		),
		'spices'    => array(
			'label' => 'ادویه‌جات',
			'file'  => 'spices.webp',
			'alt'   => 'ادویه‌های رنگی در کاسه',
		),
		'grains'    => array(
			'label' => 'غلات',
			'file'  => 'grains.webp',
			'alt'   => 'غلات و حبوبات',
		),
		'cosmetics' => array(
			'label' => 'محصولات آرایشی و بهداشتی',
			'file'  => 'cosmetics.webp',
			'alt'   => 'محصولات آرایشی و بهداشتی',
		),
		'russian'   => array(
			'label' => 'کالاهای روسی',
			'file'  => 'russian-goods.webp',
			'alt'   => 'کالاهای روسی',
		),
	);
}

/**
 * Register the page once, from the admin only.
 */
function liferuss_sourcing_ensure() {
	if ( ! is_admin() ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_sourcing_ready', '' ) ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_sourcing_lock', '' ) ) {
		return;
	}
	update_option( 'lr_sourcing_lock', '1', false );
	$page_id = liferuss_ensure_page( 'بازرگانی و سورسینگ', liferuss_sourcing_slug(), '', 'templates/sourcing.php' );
	if ( ! $page_id ) {
		delete_option( 'lr_sourcing_lock' );
		return;
	}
	liferuss_sourcing_menu_item( $page_id );
	update_option( 'lr_sourcing_ready', '1', false );
	delete_option( 'lr_sourcing_lock' );
}
add_action( 'admin_init', 'liferuss_sourcing_ensure', 60 );

/**
 * Add the menu item when the primary menu does not already have this page.
 *
 * @param int $page_id Page id.
 */
function liferuss_sourcing_menu_item( $page_id ) {
	$page_id   = (int) $page_id;
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menu_id   = ! empty( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( ! $menu_id ) {
		$menu = wp_get_nav_menu_object( 'منوی اصلی لایف روس' );
		$menu_id = $menu ? (int) $menu->term_id : 0;
	}
	if ( ! $menu_id ) {
		return;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			$title = isset( $item->title ) ? (string) $item->title : '';
			if ( 'بازرگانی و سورسینگ' === $title || (int) $item->object_id === $page_id ) {
				return;
			}
		}
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => 'بازرگانی و سورسینگ',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
}

/**
 * Page stylesheet.
 */
function liferuss_sourcing_assets() {
	if ( ! is_page_template( 'templates/sourcing.php' ) ) {
		return;
	}
	wp_enqueue_style(
		'liferuss-sourcing',
		LIFERUSS_URI . '/assets/css/sourcing.css',
		array( 'liferuss-theme' ),
		LIFERUSS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_sourcing_assets' );

/**
 * Higgsfield picture. Below-the-fold images are lazy.
 *
 * @param string $file   File name.
 * @param string $alt    Persian alt. Empty only when the text is real HTML elsewhere.
 * @param int    $width  Intrinsic width.
 * @param int    $height Intrinsic height.
 * @param bool   $eager  Hero only.
 * @param string $sizes  Sizes attribute.
 */
function liferuss_sourcing_picture( $file, $alt, $width, $height, $eager, $sizes ) {
	$dir  = LIFERUSS_DIR . '/assets/images/sourcing/';
	$uri  = LIFERUSS_URI . '/assets/images/sourcing/';
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
