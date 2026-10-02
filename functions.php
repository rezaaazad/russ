<?php
/**
 * لایف روس theme bootstrap.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIFERUSS_VERSION', '1.14.1' );
define( 'LIFERUSS_DIR', get_template_directory() );
define( 'LIFERUSS_URI', get_template_directory_uri() );

require_once LIFERUSS_DIR . '/inc/landing-defaults.php';
require_once LIFERUSS_DIR . '/inc/defaults.php';
require_once LIFERUSS_DIR . '/inc/landing-i18n.php';
require_once LIFERUSS_DIR . '/inc/i18n-defaults.php';
require_once LIFERUSS_DIR . '/inc/i18n.php';
require_once LIFERUSS_DIR . '/inc/helpers.php';
require_once LIFERUSS_DIR . '/inc/photos.php';
require_once LIFERUSS_DIR . '/inc/options.php';
require_once LIFERUSS_DIR . '/inc/landings.php';
require_once LIFERUSS_DIR . '/inc/customizer.php';
require_once LIFERUSS_DIR . '/inc/consultation.php';
require_once LIFERUSS_DIR . '/inc/setup.php';
require_once LIFERUSS_DIR . '/inc/seo.php';
require_once LIFERUSS_DIR . '/inc/catalog.php';
require_once LIFERUSS_DIR . '/inc/paths.php';
require_once LIFERUSS_DIR . '/inc/magazine.php';
require_once LIFERUSS_DIR . '/inc/home-sections.php';
require_once LIFERUSS_DIR . '/inc/compare.php';
require_once LIFERUSS_DIR . '/inc/admin-landings.php';
require_once LIFERUSS_DIR . '/inc/admin-options.php';
require_once LIFERUSS_DIR . '/inc/ux.php';

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
	wp_enqueue_script(
		'liferuss-finder',
		LIFERUSS_URI . '/assets/js/finder.js',
		array(),
		LIFERUSS_VERSION,
		true
	);
	wp_localize_script(
		'liferuss-finder',
		'liferussFinder',
		array(
			'suggestUrl'  => rest_url( 'liferuss/v1/search/suggest' ),
			'compareBase' => liferuss_url( '/compare/' ),
			'uniBase'     => liferuss_url( '/universities/' ),
			'searchBase'  => liferuss_url( '/search/' ),
			'contactUrl'  => liferuss_url( '/contact/' ),
			'strings'     => array(
				'add'    => 'مقایسه',
				'remove' => 'حذف از مقایسه',
				'need'   => 'حداقل یک دانشگاه دیگر اضافه کنید',
				'full'   => 'حداکثر ۴ دانشگاه',
				'open'   => 'مشاهده مقایسه',
			),
		)
	);
	$lead_url  = '';
	$turnstile = '';
	if ( defined( 'LIFERUSS_CORE_VERSION' ) && class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$lead_url  = rest_url( 'liferuss/v1/leads' );
		$forms     = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$turnstile = isset( $forms['turnstile_site_key'] ) ? (string) $forms['turnstile_site_key'] : '';
	}
	if ( $turnstile ) {
		wp_enqueue_script(
			'cloudflare-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			array(),
			null,
			true
		);
	}
	wp_localize_script(
		'liferuss-theme',
		'liferussTheme',
		array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'leadUrl'          => $lead_url,
			'restNonce'        => wp_create_nonce( 'wp_rest' ),
			'turnstileSiteKey' => $turnstile,
			'nonce'            => wp_create_nonce( 'liferuss_consult' ),
			'dir'              => liferuss_lang_meta( 'dir' ),
			'strings' => array(
				'openMenu'   => liferuss_t( 'open_menu' ),
				'closeMenu'  => liferuss_t( 'close_menu' ),
				'openFloat'  => liferuss_t( 'float_open' ),
				'closeFloat' => liferuss_t( 'float_close' ),
				'formOk'     => liferuss_t( 'form_ok_short' ),
				'formErr'    => liferuss_t( 'form_err_short' ),
				'formNet'    => liferuss_t( 'form_net' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_assets' );

/**
 * Header and hero paint before theme.css. The rest of the sheet loads without blocking.
 *
 * @param string $html   Tag.
 * @param string $handle Handle.
 * @return string
 */
function liferuss_async_style( $html, $handle ) {
	if ( 'liferuss-theme' !== $handle ) {
		return $html;
	}
	return '';
}

/**
 * Load theme.css after the hero has painted so it is not on the LCP path.
 */
function liferuss_footer_css() {
	if ( is_admin() ) {
		return;
	}
	$href = LIFERUSS_URI . '/assets/css/theme.css?ver=' . rawurlencode( LIFERUSS_VERSION );
	echo '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>';
	echo '<script id="lr-css-loader">';
	echo '(function(){var href=' . wp_json_encode( $href ) . ';function go(){var l=document.createElement("link");l.rel="preload";l.as="style";l.href=href;l.onload=function(){this.onload=null;this.rel="stylesheet";document.documentElement.classList.add("lr-css");};document.head.appendChild(l);}function later(){setTimeout(go,200);}var img=document.querySelector("img.hero-lcp");if(img&&!img.complete){img.addEventListener("load",later,{once:true});setTimeout(later,2500);}else{later();}})();';
	echo '</script>' . "\n";
}
add_action( 'wp_footer', 'liferuss_footer_css', 1 );
add_filter( 'style_loader_tag', 'liferuss_async_style', 10, 2 );

/**
 * Critical above-the-fold CSS for the current template.
 */
function liferuss_critical_css() {
	$home = is_front_page();
	$uni  = is_singular( 'lr_university' );
	echo '<style id="lr-critical">';
	echo ':root{--navy:#0b2341;--navy-mid:#14325a;--navy-deep:#071627;--navy-soft:#e8eef6;--gold:#e8b923;--gold-soft:#fff4c8;--white:#fff;--bg:#f5f7fb;--text:#1b2a3a;--muted:#5b6b7c;--line:#e4eaf2;--shadow:0 12px 36px rgba(11,35,65,.08);--radius:18px;--radius-sm:12px;--header:64px;--max:1180px}';
	echo '*,*::before,*::after{box-sizing:border-box}html{max-width:100%;overflow-x:clip}body.liferuss-theme{margin:0;font-family:Tahoma,sans-serif;color:var(--text);background:#fff;line-height:1.8;direction:rtl;text-align:right;max-width:100%;overflow-x:clip}';
	echo 'img{max-width:100%}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}h1,h2,h3{font-weight:700;line-height:1.35;color:var(--navy);margin:0 0 .6em}p{margin:0 0 1em}';
	echo 'html:not(.lr-css) .site-footer,html:not(.lr-css) .lr-bottom-nav,html:not(.lr-css) .lr-float,html:not(.lr-css) .site-main>*:not(.hero):not(.page-hero){display:none}';
	echo '.container{width:min(var(--max),calc(100% - 32px));max-width:100%;min-width:0;margin-inline:auto}';
	echo '.skip-link{position:absolute;right:12px;top:-60px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px)}';
	echo '.site-header{position:sticky;top:0;z-index:50;background:var(--navy);border-bottom:1px solid rgba(255,255,255,.08)}';
	echo '.header-inner{min-height:var(--header);display:flex;flex-wrap:nowrap;align-items:center;gap:12px}';
	echo '.brand{display:flex;align-items:center;gap:10px;min-width:max-content;flex:0 0 auto;color:#fff}.brand-mark,.brand-cap,.brand img{width:42px;height:42px;display:block;object-fit:contain;flex-shrink:0}.brand-text{display:flex;flex-direction:column;line-height:1.15}.brand-text strong{color:#fff;font-size:1.1rem;white-space:nowrap}.brand-text small{color:rgba(255,255,255,.65);font-size:.7rem}';
	echo '.site-nav{margin-inline:auto;min-width:0}.nav-list{display:flex;flex-wrap:nowrap;align-items:center;gap:4px 14px;list-style:none;margin:0;padding:0}.nav-list a{display:block;padding:8px 2px;color:rgba(255,255,255,.92);font-weight:600;font-size:.9rem;white-space:nowrap}';
	echo '.header-actions{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;flex:0 1 auto;min-width:0;margin-inline-start:auto}.header-actions .btn{padding:10px 16px;font-size:.88rem;white-space:nowrap}';
	echo '.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:999px;padding:12px 22px;font-weight:700}.btn-gold{background:var(--gold);color:var(--navy)}.btn svg{width:18px;height:18px}';
	echo '.nav-toggle{display:none;width:44px;height:44px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.06);padding:0}.nav-toggle-bars,.nav-toggle-bars::before,.nav-toggle-bars::after{display:block;width:18px;height:2px;background:#fff;border-radius:2px;position:relative}.nav-toggle-bars::before,.nav-toggle-bars::after{content:"";position:absolute;right:0}.nav-toggle-bars::before{top:-6px}.nav-toggle-bars::after{top:6px}';
	echo '.header-account{position:relative;display:inline-flex;align-items:center;justify-content:center;flex:0 0 44px;width:44px;height:44px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.06);color:#fff}.header-account svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8}.header-phone{display:none}.header-search{display:flex}.header-search-toggle{display:none}';
	echo '.lang-switch--header{flex-shrink:0}.lang-switch--header ul{display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;gap:0;margin:0;padding:0;list-style:none}.lang-switch--header a{display:inline-flex;padding:2px 6px;border:0;background:transparent;color:rgba(255,255,255,.78);font-size:.72rem;font-weight:700;white-space:nowrap;min-width:0;min-height:0}.lang-switch--header .lang-name{display:none}.lang-switch--drawer{display:none}.menu-item--home{display:none}';
	echo '.hero-media,.hero-lcp,.hero-media picture{position:absolute;inset:0;width:100%;height:100%}.hero-lcp{object-fit:cover;z-index:0}.hero-overlay{position:absolute;inset:0;z-index:1;pointer-events:none}';
	if ( $home ) {
		echo '.hero{position:relative;padding:64px 0 0;min-height:560px;display:flex;flex-direction:column;justify-content:flex-end;background:var(--navy);color:#fff;overflow:hidden}';
		echo '.hero-overlay{background:linear-gradient(to left,rgba(7,22,39,.9) 0%,rgba(7,22,39,.72) 42%,rgba(11,35,65,.46) 100%),linear-gradient(180deg,rgba(7,22,39,.34),rgba(7,22,39,.5))}';
		echo '.hero-grid{position:relative;z-index:2;display:grid;grid-template-columns:1.15fr .85fr;gap:36px;align-items:center;padding-bottom:40px}';
		echo '.hero .eyebrow{display:inline-block;background:var(--gold);color:var(--navy);padding:6px 14px;border-radius:999px;font-size:.78rem;font-weight:700;margin-bottom:18px}.hero h1{font-size:clamp(1.9rem,4.2vw,3.2rem);margin-bottom:16px;color:#fff}.hero-lead{font-size:1.05rem;color:rgba(255,255,255,.88);max-width:34em}.hero-actions{display:flex;flex-wrap:wrap;gap:12px}';
		echo '.btn-ghost-light{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.55)}.hero-visual{position:relative;min-height:420px}.hero-student{position:relative;width:min(320px,88%);height:380px;border-radius:28px;overflow:hidden;margin-inline-start:auto}.hero-cathedral{display:none}';
		echo '.hero-trust{position:relative;z-index:2;background:rgba(7,22,39,.82);border-top:1px solid rgba(255,255,255,.1)}.trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:18px 0}.trust-item{display:flex;gap:12px;align-items:flex-start}.trust-item strong{color:#fff;font-size:.95rem}.trust-item p{margin:0;color:rgba(255,255,255,.7);font-size:.82rem}.icon-circle{width:44px;height:44px;border-radius:50%;border:1.5px solid var(--gold);color:var(--gold);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}';
	} else {
		echo '.page-hero{position:relative;background:linear-gradient(180deg,#0b2341,#14325a);color:#fff;padding:64px 0 56px;min-height:360px;overflow:hidden}';
		echo '.page-hero-photo{background-color:#0b2341}.page-hero-photo .container{position:relative;z-index:2}';
		echo '.page-hero .hero-overlay{background:linear-gradient(to left,rgba(7,22,39,.94) 0%,rgba(7,22,39,.86) 48%,rgba(11,35,65,.58) 100%),linear-gradient(180deg,rgba(7,22,39,.62),rgba(7,22,39,.34) 46%,rgba(7,22,39,.55))}';
		echo '.page-hero h1,.page-hero .eyebrow,.page-hero p{color:#fff}.page-hero h1{font-size:clamp(1.45rem,2.2vw,2rem)}.page-hero .eyebrow{color:var(--gold);font-weight:700;margin-bottom:10px}.page-hero p{max-width:40em;color:#d7e0ec}';
		echo '.breadcrumbs ol{display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0;margin:16px 0 0}.breadcrumbs li{display:inline-flex;align-items:center;gap:8px}.page-hero .breadcrumbs a,.page-hero .breadcrumbs span{color:#d7e0ec}';
		echo '.btn-ghost-light{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.55)}.lr-uni-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.lr-uni-actions form{margin:0}';
		echo '.section{padding:52px 0}.page-body{padding:48px 0 72px}.prose{max-width:760px}.prose p{color:var(--muted)}';
		echo '.lr-filters{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:24px}.lr-filters label{display:grid;gap:4px;font-weight:700}.lr-filters input,.lr-filters select{width:100%;min-height:48px;border:1px solid var(--line);border-radius:12px;padding:12px 14px;background:var(--bg);color:var(--text)}';
	}
	if ( $uni ) {
		echo '.lr-fact-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;list-style:none;margin:0 0 20px;padding:0}.lr-fact-row li{background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px;min-width:0}.lr-fact-row span{display:block;color:var(--muted);font-size:.82rem}.lr-fact-row strong{display:block;color:var(--navy);font-size:1.02rem;margin-top:4px;overflow-wrap:anywhere}';
		echo '.lr-uni-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;margin:0 0 16px}.lr-uni-toc{display:flex;gap:8px;overflow-x:auto;position:sticky;top:var(--header);z-index:30;background:#fff;padding:8px 0}';
	}
	echo '@media(min-width:861px){.header-inner{width:min(1440px,calc(100% - 32px))}.site-header .header-search{display:none}.site-header .header-search-toggle{display:inline-flex;width:44px;height:44px;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.06);color:#fff}.drawer-consult,.drawer-phone{display:none}';
	if ( $uni ) {
		echo '.section{padding:72px 0}';
	} else {
		echo '.section{padding:72px 0}';
	}
	echo '}';
	echo '@media(max-width:860px){.site-nav{position:fixed;top:var(--header);right:0;left:0;bottom:0;z-index:80;background:var(--navy-deep);opacity:0;visibility:hidden;pointer-events:none;margin:0}.header-inner{height:64px;min-height:64px;max-height:64px;justify-content:space-between;gap:8px}.brand{flex:1 1 auto;min-width:0}.brand-text strong{font-size:.95rem;max-width:9rem;overflow:hidden;text-overflow:ellipsis}.nav-toggle{display:inline-flex;align-items:center;justify-content:center}.header-actions .btn,.lang-switch--header{display:none}.header-search{display:none}.header-search-toggle{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.06);color:#fff}.menu-item--home{display:list-item}';
	if ( $home ) {
		echo '.hero{min-height:550px;padding-top:40px}.hero-grid,.trust-grid{grid-template-columns:1fr}.hero-visual{min-height:0}.hero-trust{height:188px;overflow:hidden}';
	}
	echo '}';
	echo '@media(max-width:640px){.container{width:min(var(--max),calc(100% - 24px))}';
	if ( $home ) {
		echo '.hero{padding:36px 0 0;min-height:550px}.hero h1{font-size:clamp(1.55rem,7vw,2.1rem)}.hero-visual{display:none}.hero-overlay{background:linear-gradient(180deg,rgba(7,22,39,.42),rgba(7,22,39,.62) 55%,rgba(7,22,39,.78))}';
	}
	echo '.brand-text small{display:none}}';
	if ( $uni ) {
		echo '@media(min-width:900px){.lr-fact-row{grid-template-columns:repeat(3,minmax(0,1fr))}.lr-uni-layout{display:grid;grid-template-columns:220px minmax(0,1fr);gap:28px;align-items:start}.lr-uni-toc{position:sticky;top:calc(var(--header) + 16px);flex-direction:column;overflow:visible;padding:14px;border:1px solid var(--line);border-radius:16px;background:#fff}}';
		echo '@media(min-width:1200px){.lr-fact-row{grid-template-columns:repeat(6,minmax(0,1fr))}}';
	}
	echo '</style>' . "\n";
}
add_action( 'wp_head', 'liferuss_critical_css', 2 );

/**
 * UTM, landing page, referrer, and Turnstile fields shared by every public form.
 */
function liferuss_form_tracking_fields() {
	$keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );
	foreach ( $keys as $key ) {
		echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="">';
	}
	echo '<input type="hidden" name="landing_page" value="">';
	echo '<input type="hidden" name="referrer" value="">';
	$site = '';
	if ( class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$site  = isset( $forms['turnstile_site_key'] ) ? (string) $forms['turnstile_site_key'] : '';
	}
	if ( '' !== $site ) {
		echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site ) . '"></div>';
	}
}

/**
 * Preload the 700-weight font and LCP hero image.
 */
function liferuss_preload() {
	$id       = 0;
	$fallback = '';
	$sizes    = '100vw';
	if ( is_front_page() ) {
		$id = absint( liferuss_opt( 'hero_bg_id' ) );
		if ( ! $id ) {
			$id = absint( liferuss_opt( 'hero_image_id' ) );
		}
		$fallback = 'saint-basil.jpg';
	} elseif ( is_page_template( 'templates/freight.php' ) ) {
		$id       = absint( liferuss_opt( 'freight_hero_image_id' ) );
		$fallback = (string) liferuss_opt( 'freight_hero_image', 'moscow-night.jpg' );
		$sizes    = '(max-width: 860px) 92vw, 560px';
	} elseif ( is_page_template( 'templates/trade.php' ) ) {
		$id       = absint( liferuss_opt( 'trade_hero_image_id' ) );
		$fallback = (string) liferuss_opt( 'trade_hero_image', 'moscow-night.jpg' );
		$sizes    = '(max-width: 860px) 92vw, 560px';
	} elseif ( ! is_admin() ) {
		$fallback = liferuss_photo_file( liferuss_photo_scene() );
	}
	if ( $id || $fallback ) {
		liferuss_print_image_preload( $id, $fallback, $sizes );
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
	if ( '1' === (string) liferuss_opt( 'float_widget_enabled', '1' ) ) {
		$classes[] = 'has-float-widget';
	}
	if ( '1' === (string) liferuss_opt( 'bottom_nav_enabled', '1' ) ) {
		$classes[] = 'has-bottom-nav';
	}
	return $classes;
}
add_filter( 'body_class', 'liferuss_body_class' );
