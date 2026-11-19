<?php
/**
 * Brand files served from the theme, including the three root icon URLs.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute URL of a file in assets/brand.
 *
 * @param string $file File name.
 */
function liferuss_brand_uri( $file ) {
	return trailingslashit( LIFERUSS_URI ) . 'assets/brand/' . ltrim( (string) $file, '/' );
}

/**
 * Theme directory path of a brand file.
 *
 * @param string $file File name.
 */
function liferuss_brand_path( $file ) {
	return trailingslashit( LIFERUSS_DIR ) . 'assets/brand/' . ltrim( (string) $file, '/' );
}

/**
 * Answer /favicon.ico, /apple-touch-icon.png, and /site.webmanifest from the theme.
 */
function liferuss_brand_root_files() {
	if ( is_admin() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	$path = untrailingslashit( $path );
	if ( '/site.webmanifest' === $path ) {
		liferuss_brand_manifest();
	}
	$map = array(
		'/favicon.ico'           => array( 'favicon.ico', 'image/x-icon' ),
		'/apple-touch-icon.png'  => array( 'apple-touch-icon.png', 'image/png' ),
	);
	if ( ! isset( $map[ $path ] ) ) {
		return;
	}
	$file = liferuss_brand_path( $map[ $path ][0] );
	if ( ! is_readable( $file ) ) {
		return;
	}
	header( 'Content-Type: ' . $map[ $path ][1] );
	header( 'X-Robots-Tag: noindex' );
	readfile( $file );
	exit;
}
add_action( 'template_redirect', 'liferuss_brand_root_files', 0 );

/**
 * Manifest with absolute icon URLs so /site.webmanifest does not look at the server root.
 */
function liferuss_brand_manifest() {
	$base = trailingslashit( liferuss_brand_uri( '' ) );
	$body = array(
		'name'             => 'لایف روس',
		'short_name'       => 'LifeRuss',
		'lang'             => 'fa',
		'dir'              => 'rtl',
		'theme_color'      => '#0B2341',
		'background_color' => '#FFFFFF',
		'display'          => 'standalone',
		'icons'            => array(
			array(
				'src'   => $base . 'icon-192.png',
				'sizes' => '192x192',
				'type'  => 'image/png',
			),
			array(
				'src'   => $base . 'icon-512.png',
				'sizes' => '512x512',
				'type'  => 'image/png',
			),
			array(
				'src'     => $base . 'icon-maskable-512.png',
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'maskable',
			),
		),
	);
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	echo wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

/**
 * Icons in the document head. WordPress site_icon output stays removed.
 */
function liferuss_brand_head_icons() {
	if ( is_admin() ) {
		return;
	}
	$icons = array(
		array(
			'rel'   => 'icon',
			'href'  => 'favicon.ico',
			'sizes' => 'any',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/svg+xml',
			'href'  => 'favicon.svg',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-32.png',
			'sizes' => '32x32',
		),
		array(
			'rel'   => 'apple-touch-icon',
			'href'  => 'apple-touch-icon.png',
		),
	);
	foreach ( $icons as $icon ) {
		$attrs = ' rel="' . esc_attr( $icon['rel'] ) . '" href="' . esc_url( liferuss_brand_uri( $icon['href'] ) ) . '"';
		if ( ! empty( $icon['type'] ) ) {
			$attrs .= ' type="' . esc_attr( $icon['type'] ) . '"';
		}
		if ( ! empty( $icon['sizes'] ) ) {
			$attrs .= ' sizes="' . esc_attr( $icon['sizes'] ) . '"';
		}
		echo '<link' . $attrs . '>' . "\n";
	}
	echo '<link rel="manifest" href="' . esc_url( home_url( '/site.webmanifest' ) ) . '">' . "\n";
	echo '<meta name="theme-color" content="#0B2341">' . "\n";
}
add_action( 'wp_head', 'liferuss_brand_head_icons', 1 );

/**
 * Copy icon-512 into the media library once and point site_icon at it.
 */
function liferuss_maybe_site_icon() {
	if ( '1' === (string) get_option( 'lr_brand_v2_icon', '' ) ) {
		return;
	}
	$current = (int) get_option( 'site_icon' );
	if ( $current && '1' === (string) get_post_meta( $current, '_lr_brand_v2_icon', true ) ) {
		update_option( 'lr_brand_v2_icon', '1', false );
		return;
	}
	$file = liferuss_brand_path( 'icon-512.png' );
	if ( ! is_readable( $file ) ) {
		return;
	}
	$bits = file_get_contents( $file );
	if ( false === $bits || '' === $bits ) {
		return;
	}
	$upload = wp_upload_bits( 'liferuss-icon-512.png', null, $bits );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return;
	}
	$attachment = array(
		'post_mime_type' => 'image/png',
		'post_title'     => 'لایف روس',
		'post_status'    => 'inherit',
	);
	$id = wp_insert_attachment( $attachment, $upload['file'] );
	if ( ! $id || is_wp_error( $id ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_lr_brand_v2_icon', '1' );
	update_option( 'site_icon', (int) $id );
	update_option( 'lr_brand_v2_icon', '1', false );
}
add_action( 'after_switch_theme', 'liferuss_maybe_site_icon' );
add_action( 'init', 'liferuss_maybe_site_icon', 30 );
