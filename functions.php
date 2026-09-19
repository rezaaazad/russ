<?php
/**
 * لایف روس theme bootstrap.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIFERUSS_VERSION', '1.4.2' );
define( 'LIFERUSS_DIR', get_template_directory() );
define( 'LIFERUSS_URI', get_template_directory_uri() );

require_once LIFERUSS_DIR . '/inc/landing-defaults.php';
require_once LIFERUSS_DIR . '/inc/defaults.php';
require_once LIFERUSS_DIR . '/inc/landing-i18n.php';
require_once LIFERUSS_DIR . '/inc/i18n-defaults.php';
require_once LIFERUSS_DIR . '/inc/i18n.php';
require_once LIFERUSS_DIR . '/inc/helpers.php';
require_once LIFERUSS_DIR . '/inc/options.php';
require_once LIFERUSS_DIR . '/inc/landings.php';
require_once LIFERUSS_DIR . '/inc/customizer.php';
require_once LIFERUSS_DIR . '/inc/consultation.php';
require_once LIFERUSS_DIR . '/inc/setup.php';
require_once LIFERUSS_DIR . '/inc/seo.php';
require_once LIFERUSS_DIR . '/inc/admin-landings.php';
require_once LIFERUSS_DIR . '/inc/admin-options.php';

/**
 * Theme supports, menus, and image sizes.
 */
function liferuss_setup_theme() {
	load_theme_textdomain( 'liferuss', LIFERUSS_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 72,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => 'منوی اصلی',
			'footer'  => 'منوی فوتر',
		)
	);

	add_image_size( 'liferuss-card', 720, 440, true );
	add_image_size( 'liferuss-wide', 1280, 720, true );
}
add_action( 'after_setup_theme', 'liferuss_setup_theme' );

/**
 * Content width.
 */
function liferuss_content_width() {
	$GLOBALS['content_width'] = 1120;
}
add_action( 'after_setup_theme', 'liferuss_content_width', 0 );

/**
 * Enqueue local Vazirmatn, lean CSS, and deferred JS.
 */
function liferuss_assets() {
	wp_enqueue_style(
		'liferuss-theme',
		LIFERUSS_URI . '/assets/css/theme.css',
		array(),
		LIFERUSS_VERSION
	);
	wp_enqueue_script(
		'liferuss-theme',
		LIFERUSS_URI . '/assets/js/theme.js',
		array(),
		LIFERUSS_VERSION,
		true
	);
	wp_localize_script(
		'liferuss-theme',
		'liferussTheme',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'liferuss_consult' ),
			'dir'     => liferuss_lang_meta( 'dir' ),
			'strings' => array(
				'openMenu'  => liferuss_t( 'open_menu' ),
				'closeMenu' => liferuss_t( 'close_menu' ),
				'formOk'    => liferuss_t( 'form_ok_short' ),
				'formErr'   => liferuss_t( 'form_err_short' ),
				'formNet'   => liferuss_t( 'form_net' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_assets' );

/**
 * Preload the 700-weight font and LCP hero image.
 */
function liferuss_preload() {
	$font = LIFERUSS_URI . '/assets/fonts/vazirmatn-700.woff2';
	echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url( $font ) . '" crossorigin>' . "\n";
	if ( is_front_page() ) {
		$hero_id = absint( liferuss_opt( 'hero_bg_id' ) );
		if ( ! $hero_id ) {
			$hero_id = absint( liferuss_opt( 'hero_image_id' ) );
		}
		liferuss_print_image_preload( $hero_id, 'st-basil.jpg', '100vw' );
	} elseif ( is_page_template( 'templates/freight.php' ) ) {
		liferuss_print_image_preload(
			liferuss_opt( 'freight_hero_image_id' ),
			liferuss_opt( 'freight_hero_image', 'st-basil.jpg' ),
			'(max-width: 860px) 92vw, 560px'
		);
	} elseif ( is_page_template( 'templates/trade.php' ) ) {
		liferuss_print_image_preload(
			liferuss_opt( 'trade_hero_image_id' ),
			liferuss_opt( 'trade_hero_image', 'st-basil.jpg' ),
			'(max-width: 860px) 92vw, 560px'
		);
	}
}
add_action( 'wp_head', 'liferuss_preload', 1 );

/**
 * Defer the theme script.
 *
 * @param string $tag    Tag.
 * @param string $handle Handle.
 * @return string
 */
function liferuss_defer_script( $tag, $handle ) {
	if ( 'liferuss-theme' === $handle && false === strpos( $tag, ' defer' ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'liferuss_defer_script', 10, 2 );

/**
 * Blog sidebar.
 */
function liferuss_widgets() {
	register_sidebar(
		array(
			'name'          => 'سایدبار وبلاگ',
			'id'            => 'sidebar-1',
			'description'   => 'ستون کناری نوشته‌ها',
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'liferuss_widgets' );

/**
 * Resource hints (only local font origin is already same-host).
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Relation.
 * @return array
 */
function liferuss_resource_hints( $urls, $relation_type ) {
	return $urls;
}
add_filter( 'wp_resource_hints', 'liferuss_resource_hints', 10, 2 );

/**
 * Excerpt length.
 *
 * @return int
 */
function liferuss_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'liferuss_excerpt_length' );

/**
 * Excerpt more.
 *
 * @return string
 */
function liferuss_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'liferuss_excerpt_more' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function liferuss_body_class( $classes ) {
	$classes[] = 'liferuss-theme';
	if ( is_front_page() ) {
		$classes[] = 'is-front';
	}
	return $classes;
}
add_filter( 'body_class', 'liferuss_body_class' );
