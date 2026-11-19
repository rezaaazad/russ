<?php
/**
 * Homepage v2 (wireframe layout): options, helpers, admin tab.
 *
 * The layout is chosen by the `home_layout` option (v2 | classic). The query
 * string ?home=v2 or ?home=classic previews either one without saving.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the wireframe homepage should render.
 *
 * @return bool
 */
function liferuss_home_v2_active() {
	static $active = null;
	if ( null !== $active ) {
		return $active;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$query = isset( $_GET['home'] ) ? sanitize_key( wp_unslash( $_GET['home'] ) ) : '';
	if ( 'v2' === $query ) {
		$active = true;
	} elseif ( 'classic' === $query ) {
		$active = false;
	} else {
		$active = ( 'v2' === (string) liferuss_opt( 'home_layout', 'v2' ) );
	}
	return $active;
}

/**
 * True when the layout was forced through the query string (preview only).
 *
 * @return bool
 */
function liferuss_home_v2_is_preview() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['home'] ) && in_array( sanitize_key( wp_unslash( $_GET['home'] ) ), array( 'v2', 'classic' ), true );
}

/**
 * Preview URLs never get indexed.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function liferuss_home_v2_robots( $robots ) {
	if ( is_front_page() && liferuss_home_v2_is_preview() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'liferuss_home_v2_robots', 20 );

/**
 * Whether this request is the "all offers" listing page.
 *
 * @return bool
 */
function liferuss_hv2_is_hub() {
	return is_page_template( 'templates/offers.php' );
}

/**
 * The offers page shows the translated section title (breadcrumb, document title, h1).
 *
 * @param string $title Title.
 * @param int    $id    Post id.
 * @return string
 */
function liferuss_hv2_hub_title( $title, $id = 0 ) {
	if ( $id && 'templates/offers.php' === get_post_meta( (int) $id, '_wp_page_template', true ) ) {
		$own = trim( (string) liferuss_opt( 'hv2_offers_title', '' ) );
		if ( '' !== $own ) {
			return $own;
		}
	}
	return $title;
}
add_filter( 'the_title', 'liferuss_hv2_hub_title', 20, 2 );

/**
 * Create the "all offers" page once (slug: offers). Existing pages are never overwritten.
 */
function liferuss_hv2_ensure_hub() {
	if ( '1' === (string) get_option( 'liferuss_hv2_hub_page', '' ) ) {
		return;
	}
	if ( ! is_admin() || '1' === (string) get_option( 'liferuss_hv2_hub_lock', '' ) ) {
		return;
	}
	update_option( 'liferuss_hv2_hub_lock', '1', false );
	$id = liferuss_ensure_page(
		'پیشنهادهای ما برای مهاجرت',
		'offers',
		'<p>همهٔ مسیرهای تحصیل، ویزا و مهاجرت لایف روس در یک صفحه.</p>',
		'templates/offers.php'
	);
	if ( $id ) {
		update_option( 'liferuss_hv2_hub_page', '1', false );
	}
	delete_option( 'liferuss_hv2_hub_lock' );
}
add_action( 'admin_init', 'liferuss_hv2_ensure_hub', 60 );

/**
 * Body class for scoping.
 *
 * @param array $classes Classes.
 * @return array
 */
function liferuss_home_v2_body_class( $classes ) {
	if ( is_front_page() && liferuss_home_v2_active() ) {
		$classes[] = 'home-v2';
	}
	if ( liferuss_hv2_is_hub() ) {
		$classes[] = 'hv2-page';
	}
	return $classes;
}
add_filter( 'body_class', 'liferuss_home_v2_body_class' );

/**
 * Assets for the wireframe homepage only.
 */
function liferuss_home_v2_assets() {
	if ( ! liferuss_hv2_is_hub() && ( ! is_front_page() || ! liferuss_home_v2_active() ) ) {
		return;
	}
	$css = LIFERUSS_DIR . '/assets/css/home-v2.css';
	$js  = LIFERUSS_DIR . '/assets/js/home-v2.js';
	wp_enqueue_style(
		'liferuss-home-v2',
		LIFERUSS_URI . '/assets/css/home-v2.css',
		array( 'liferuss-theme' ),
		file_exists( $css ) ? (string) filemtime( $css ) : LIFERUSS_VERSION
	);
	wp_enqueue_script(
		'liferuss-home-v2',
		LIFERUSS_URI . '/assets/js/home-v2.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : LIFERUSS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_home_v2_assets', 20 );

/**
 * Defer the small homepage script.
 *
 * @param string $tag    Tag.
 * @param string $handle Handle.
 * @return string
 */
function liferuss_home_v2_defer( $tag, $handle ) {
	if ( 'liferuss-home-v2' === $handle && false === strpos( $tag, ' defer' ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'liferuss_home_v2_defer', 10, 2 );

/**
 * Fixed section order, as drawn in the wireframe (after header + announcement).
 *
 * @return string[]
 */
function liferuss_home_v2_sections() {
	return array( 'hero', 'licenses', 'services', 'offers', 'pay', 'cases', 'testimonials', 'instagram', 'app', 'blog' );
}

/**
 * Default option values for the wireframe homepage.
 *
 * @return array
 */
function liferuss_home_v2_default_options() {
	return array(
		'home_layout'          => 'v2',

		'hv2_hero_cta_text'    => 'اولین قدم را بردارید',
		'hv2_hero_image_id'    => 0,
		'hv2_hero_video'       => '',
		'hv2_hero_video_label' => 'پخش ویدیوی معرفی',

		'hv2_services_title'    => 'خدمات مؤسسه ما',
		'hv2_services_subtitle' => 'از مشاوره تا استقرار، مسیر را شفاف و گام‌به‌گام همراهی می‌کنیم.',
		'hv2_services'          => array(
			array( 'enabled' => '1', 'title' => 'تحصیل', 'text' => 'پذیرش در دانشگاه‌های دولتی و پزشکی روسیه، از انتخاب رشته تا ثبت‌نام.', 'icon' => 'cap', 'link' => '/study-russia/' ),
			array( 'enabled' => '1', 'title' => 'ویزا', 'text' => 'پیگیری دعوت‌نامه، وقت سفارت و ویزای تحصیلی تا صدور مهر ورود.', 'icon' => 'passport', 'link' => '/study-russia/visa/' ),
			array( 'enabled' => '1', 'title' => 'تجارت و تأمین', 'text' => 'سورسینگ، خرید و صادرات کالا بین ایران و روسیه با پیگیری شفاف.', 'icon' => 'handshake', 'link' => '/trade/' ),
			array( 'enabled' => '1', 'title' => 'کار', 'text' => 'راهنمای مسیرهای کار و اقامت در روسیه برای متقاضیان مهاجرت.', 'icon' => 'suitcase', 'link' => '/immigration/work/' ),
		),

		'hv2_offers_title'    => 'پیشنهادهای ما برای مهاجرت',
		'hv2_offers_subtitle' => 'مسیر مناسب خود را انتخاب کنید و جزئیات هر مسیر را در صفحهٔ مربوط ببینید.',
		'hv2_offers_btn'      => 'مشاهده همه',
		'hv2_offers_btn_link' => '/offers/',
		'hv2_offers'          => array(
			array( 'enabled' => '1', 'tag' => 'تحصیل', 'title' => 'تحصیل در روسیه', 'text' => 'کارشناسی تا دکتری در دانشگاه‌های دولتی و پزشکی روسیه.', 'link' => '/study-russia/', 'photo' => 'saint-basil', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'تحصیل', 'title' => 'پذیرش مستقیم', 'text' => 'ورود مستقیم به رشتهٔ اصلی برای متقاضیان واجد شرایط.', 'link' => '/direct-course/', 'photo' => 'saint-petersburg', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'تحصیل', 'title' => 'پادفک', 'text' => 'سال آمادگی زبان روسی و دروس پیش‌نیاز پیش از رشتهٔ اصلی.', 'link' => '/padfak/', 'photo' => 'matryoshka-samovar', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'ویزا', 'title' => 'ویزای تحصیلی', 'text' => 'دعوت‌نامه، وقت سفارت و پیگیری تا صدور ویزا.', 'link' => '/study-russia/visa/', 'photo' => 'moscow-night', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'مهاجرت', 'title' => 'کار در روسیه', 'text' => 'مسیرها و شرایط کار برای متقاضیان مهاجرت.', 'link' => '/immigration/work/', 'photo' => 'russian-couple', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'مهاجرت', 'title' => 'اقامت در روسیه', 'text' => 'راهنمای انواع اقامت و مراحل دریافت آن.', 'link' => '/immigration/residence/', 'photo' => 'borscht', 'image_id' => 0 ),
			array( 'enabled' => '1', 'tag' => 'مهاجرت', 'title' => 'تابعیت روسیه', 'text' => 'شرایط و مراحل دریافت تابعیت از مسیرهای قانونی.', 'link' => '/immigration/citizenship/', 'photo' => 'saint-petersburg', 'image_id' => 0 ),
		),

		'hv2_pay_enabled'  => '1',
		'hv2_pay_title'    => 'مسیر مهاجرت را با *پرداخت اقساط* شروع کنید',
		'hv2_pay_subtitle' => 'هزینه‌ها را شفاف و متناسب با مراحل پرونده برنامه‌ریزی کنید. شرایط دقیق را مشاور در جلسهٔ مشاوره اعلام می‌کند.',
		'hv2_pay_cta'      => 'درخواست مشاوره',
		'hv2_pay_cta_link' => '#consultation',
		'hv2_pay_items'    => array(
			array( 'title' => 'پرداخت مرحله‌ای', 'text' => 'هزینه‌ها را همگام با پیشرفت مراحل پرونده پرداخت کنید.', 'icon' => 'steps' ),
			array( 'title' => 'اقساط مناسب', 'text' => 'شرایط اقساط پس از بررسی پرونده و در جلسهٔ مشاوره مشخص می‌شود.', 'icon' => 'calendar' ),
			array( 'title' => 'مدیریت هزینه', 'text' => 'برآورد شفاف هزینه‌ها را پیش از شروع مسیر دریافت می‌کنید.', 'icon' => 'pie' ),
		),

		'hv2_cases_title'    => 'پرونده‌های موفق',
		'hv2_cases_subtitle' => 'نمونه‌هایی از مسیرهایی که با همراهی لایف روس به نتیجه رسیده‌اند.',
		'hv2_cases_btn'      => 'مشاهده همه',
		'hv2_cases_btn_link' => '',

		'hv2_tm_title'    => 'نظرات کاربران ما',
		'hv2_tm_subtitle' => 'تجربهٔ کسانی که مسیر خود را با لایف روس طی کرده‌اند.',

		'hv2_ig_enabled' => '1',
		'hv2_ig_title'   => 'جامعه ما در *اینستاگرام*',
		'hv2_ig_text'    => 'تجربه‌ها، اخبار و نکات کاربردی مهاجرت و تحصیل در روسیه را در صفحهٔ ما دنبال کنید.',
		'hv2_ig_btn'     => 'دنبال کردن در اینستاگرام',
		'hv2_ig_url'     => '',

		'hv2_app_enabled' => '1',
		'hv2_app_title'   => 'اپلیکیشن رایگان آموزش زبان',
		'hv2_app_text'    => 'یادگیری زبان روسی را با اپلیکیشن رایگان ما از همین امروز شروع کنید.',
		'hv2_app_btn'     => 'دریافت اپلیکیشن',
		'hv2_app_url'     => '',

		'hv2_blog_title' => 'آخرین مطالب مهاجرتی',
		'hv2_blog_btn'   => 'مشاهده همه',

		'footer_trust_html' => '',
	);
}

/**
 * Link helper: internal paths get the language prefix, absolute URLs stay.
 *
 * @param string $link Raw link.
 * @return string
 */
function liferuss_hv2_href( $link ) {
	$link = trim( (string) $link );
	if ( '' === $link ) {
		return '';
	}
	return liferuss_cta_url( $link );
}

/**
 * Escape a heading and turn *word* into a gold-underlined keyword.
 *
 * @param string $text Raw heading from options.
 * @return string Safe HTML.
 */
function liferuss_hv2_mark( $text ) {
	$safe = esc_html( (string) $text );
	return (string) preg_replace( '/\*([^*]+)\*/u', '<mark class="hv2-hl">$1</mark>', $safe );
}

/**
 * Whether a homepage block flag is on.
 *
 * @param string $key Option key.
 * @return bool
 */
function liferuss_hv2_on( $key ) {
	return '1' === (string) liferuss_opt( $key, '1' );
}

/**
 * Inline SVG for this homepage. Falls back to the shared icon set.
 *
 * @param string $name Icon name.
 * @return string
 */
function liferuss_hv2_icon( $name ) {
	$icons = array(
		'steps'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h4v-4h4v-4h4V7h4"/><path d="M4 19V5"/></svg>',
		'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5.5" width="16" height="14.5" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/><path d="m9.5 15 1.8 1.8 3.4-3.6"/></svg>',
		'pie'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5v8.5h8.5A8.5 8.5 0 0 0 12 3.5z"/><path d="M10.5 5.2A8.5 8.5 0 1 0 18.8 13.5"/></svg>',
		'play'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 5.8v12.4a.8.8 0 0 0 1.2.7l10-6.2a.8.8 0 0 0 0-1.4l-10-6.2a.8.8 0 0 0-1.2.7z" fill="currentColor" stroke="none"/></svg>',
		'download' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11m0 0 4-4m-4 4-4-4"/><path d="M5 19.5h14"/></svg>',
		'app'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.8" width="10" height="18.4" rx="2.4"/><path d="M10.5 18.2h3"/><path d="m10 9.6 2 2 2-2M12 7.4v4.2" /></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : liferuss_icon( $name );
}

/**
 * Resolve a video URL into how the hero should play it.
 *
 * @param string $url Raw URL.
 * @return array{type: string, src: string}|null
 */
function liferuss_hv2_video( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
		return null;
	}
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( preg_match( '#\.(mp4|webm|ogv)$#i', $path ) ) {
		return array( 'type' => 'file', 'src' => esc_url_raw( $url ) );
	}
	if ( preg_match( '#(?:youtube\.com|youtu\.be)$#', $host ) ) {
		$id = '';
		if ( 'youtu.be' === $host ) {
			$id = trim( $path, '/' );
		} elseif ( preg_match( '#/(?:embed|shorts)/([\w-]{6,})#', $path, $m ) ) {
			$id = $m[1];
		} else {
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $q );
			$id = isset( $q['v'] ) ? (string) $q['v'] : '';
		}
		$id = preg_replace( '/[^\w-]/', '', $id );
		if ( '' !== $id ) {
			return array( 'type' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0' );
		}
	}
	if ( preg_match( '#aparat\.com$#', $host ) && preg_match( '#/v/([\w]+)#', $path, $m ) ) {
		return array( 'type' => 'iframe', 'src' => 'https://www.aparat.com/video/video/embed/videohash/' . $m[1] . '/vt/frame?autoplay=true' );
	}
	return array( 'type' => 'link', 'src' => esc_url_raw( $url ) );
}

/**
 * One photo for a card: uploaded attachment first, else a bundled catalog photo.
 *
 * @param int    $image_id Attachment ID.
 * @param string $slug     Bundled photo slug.
 * @param string $alt      Alt text (empty for decorative).
 * @param string $sizes    Sizes attribute.
 * @param string $class    CSS class.
 * @param bool   $priority Eager + high priority.
 */
function liferuss_hv2_photo( $image_id, $slug, $alt, $sizes, $class = '', $priority = false ) {
	$image_id = absint( $image_id );
	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		$attr = array(
			'class'    => $class,
			'alt'      => $alt,
			'sizes'    => $sizes,
			'decoding' => 'async',
			'loading'  => $priority ? 'eager' : 'lazy',
		);
		if ( $priority ) {
			$attr['fetchpriority'] = 'high';
		}
		echo wp_get_attachment_image( $image_id, 'liferuss-wide', false, $attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	liferuss_photo_markup(
		(string) $slug,
		array(
			'alt'      => $alt,
			'sizes'    => $sizes,
			'class'    => $class,
			'priority' => $priority,
			'lazy'     => ! $priority,
		)
	);
}

/**
 * Instagram URL: dedicated override, else the shared contact option.
 *
 * @return string
 */
function liferuss_hv2_instagram_url() {
	$own = trim( (string) liferuss_opt( 'hv2_ig_url', '' ) );
	if ( '' !== $own ) {
		return esc_url_raw( $own );
	}
	$shared = liferuss_social_url( liferuss_public_contact( 'instagram' ), 'instagram' );
	return $shared ? esc_url_raw( $shared ) : '';
}

/**
 * Sanitize this homepage's option keys inside the main sanitizer.
 *
 * @param array $out   Merged options.
 * @param array $input Raw submitted options.
 * @return array
 */
function liferuss_home_v2_sanitize( $out, $input ) {
	$text = array(
		'hv2_hero_cta_text', 'hv2_hero_video_label', 'hv2_services_title', 'hv2_services_subtitle',
		'hv2_offers_title', 'hv2_offers_subtitle', 'hv2_offers_btn', 'hv2_pay_title', 'hv2_pay_subtitle', 'hv2_pay_cta',
		'hv2_cases_title', 'hv2_cases_subtitle', 'hv2_cases_btn', 'hv2_tm_title', 'hv2_tm_subtitle',
		'hv2_ig_title', 'hv2_ig_text', 'hv2_ig_btn', 'hv2_app_title', 'hv2_app_text', 'hv2_app_btn',
		'hv2_blog_title', 'hv2_blog_btn',
	);
	foreach ( $text as $key ) {
		if ( isset( $out[ $key ] ) ) {
			$out[ $key ] = sanitize_textarea_field( (string) $out[ $key ] );
		}
	}
	foreach ( array( 'hv2_hero_video', 'hv2_ig_url', 'hv2_app_url' ) as $key ) {
		if ( isset( $out[ $key ] ) ) {
			$out[ $key ] = $out[ $key ] ? esc_url_raw( (string) $out[ $key ] ) : '';
		}
	}
	foreach ( array( 'hv2_offers_btn_link', 'hv2_pay_cta_link', 'hv2_cases_btn_link' ) as $key ) {
		if ( isset( $out[ $key ] ) ) {
			$out[ $key ] = sanitize_text_field( (string) $out[ $key ] );
		}
	}
	$out['hv2_hero_image_id'] = absint( $out['hv2_hero_image_id'] ?? 0 );
	$out['home_layout']       = ( isset( $out['home_layout'] ) && 'v2' === $out['home_layout'] ) ? 'v2' : 'classic';
	// Unchecked boxes are absent from the POST. Only treat absence as "off"
	// when this tab was actually submitted, so programmatic saves keep the flags.
	$tab_posted = array_key_exists( 'hv2_pay_title', (array) $input );
	foreach ( array( 'hv2_pay_enabled', 'hv2_ig_enabled', 'hv2_app_enabled' ) as $flag ) {
		if ( ! empty( $input[ $flag ] ) ) {
			$out[ $flag ] = '1';
		} elseif ( $tab_posted ) {
			$out[ $flag ] = '0';
		}
	}

	$out['hv2_services']  = liferuss_sanitize_repeater(
		$out['hv2_services'] ?? array(),
		array( 'enabled' => 'text', 'title' => 'text', 'text' => 'text', 'icon' => 'text', 'link' => 'text' )
	);
	$out['hv2_offers']    = liferuss_sanitize_repeater(
		$out['hv2_offers'] ?? array(),
		array( 'enabled' => 'text', 'tag' => 'text', 'title' => 'text', 'text' => 'text', 'link' => 'text', 'photo' => 'text', 'image_id' => 'int' )
	);
	$out['hv2_pay_items'] = liferuss_sanitize_repeater(
		$out['hv2_pay_items'] ?? array(),
		array( 'title' => 'text', 'text' => 'text', 'icon' => 'text' )
	);

	// Trust badge (e-Namad etc.): only a link with an image is allowed.
	$badge = isset( $out['footer_trust_html'] ) ? (string) $out['footer_trust_html'] : '';
	$out['footer_trust_html'] = liferuss_home_v2_kses_badge( $badge );
	return $out;
}

/**
 * Allowed markup for the trust badge.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function liferuss_home_v2_kses_badge( $html ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return '';
	}
	$allowed = array(
		'a'   => array( 'href' => true, 'target' => true, 'rel' => true, 'referrerpolicy' => true, 'title' => true, 'class' => true ),
		'img' => array( 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'referrerpolicy' => true, 'loading' => true, 'class' => true, 'code' => true, 'style' => true ),
	);
	return wp_kses( $html, $allowed, array( 'https', 'http' ) );
}

/**
 * Trust badge for the footer (empty until the owner pastes it).
 *
 * @return string
 */
function liferuss_footer_trust_badge() {
	$html = trim( (string) liferuss_opt( 'footer_trust_html', '' ) );
	if ( '' === $html ) {
		return '';
	}
	return liferuss_home_v2_kses_badge( $html );
}

/**
 * Admin tab entry.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function liferuss_home_v2_admin_tab( $tabs ) {
	$tabs['homev2'] = 'صفحهٔ اصلی (طرح جدید)';
	return $tabs;
}

/**
 * Tab body inside the main options form.
 *
 * @param array $o Options.
 */
function liferuss_home_v2_admin_panel( $o ) {
	$d = liferuss_home_v2_default_options();
	$g = static function ( $key ) use ( $o, $d ) {
		return $o[ $key ] ?? ( $d[ $key ] ?? '' );
	};
	?>
	<div id="tab-homev2" class="liferuss-tab">
		<h2>صفحهٔ اصلی — طرح وایرفریم</h2>
		<p class="description">ترتیب بخش‌ها ثابت و مطابق وایرفریم است: هیرو، خدمات، پیشنهادها، پرداخت اقساط، پرونده‌های موفق، نظرات، اینستاگرام، اپلیکیشن، مطالب. نوار اطلاعیه از زبانهٔ «هدر» تنظیم می‌شود. پرونده‌های موفق و نظرات از «نمونه‌های انجام‌شده» و «نظرات مشتریان» در منوی لایف روس می‌آیند و تا وقتی نوشتهٔ واقعی نباشد چاپ نمی‌شوند. کلید اینستاگرام و اپلیکیشن بدون لینک، فقط دکمه را پنهان می‌کنند. پیش‌نمایش: <code>/?home=v2</code> و طرح قبلی: <code>/?home=classic</code>.</p>
		<?php
		liferuss_admin_table_start();
		echo '<tr><th scope="row"><label for="lr_home_layout">طرح صفحهٔ اصلی</label></th><td><select id="lr_home_layout" name="liferuss_options[home_layout]">';
		printf( '<option value="v2"%s>طرح جدید (وایرفریم)</option>', selected( (string) $g( 'home_layout' ), 'v2', false ) );
		printf( '<option value="classic"%s>طرح قبلی</option>', selected( (string) $g( 'home_layout' ), 'classic', false ) );
		echo '</select></td></tr>';
		liferuss_admin_field( 'hv2_hero_cta_text', $g( 'hv2_hero_cta_text' ), 'هیرو: متن دکمه (لینک از زبانهٔ هیرو، دکمهٔ اول)' );
		liferuss_admin_media( 'hv2_hero_image_id', $g( 'hv2_hero_image_id' ), 'هیرو: تصویر معرفی' );
		liferuss_admin_field( 'hv2_hero_video', $g( 'hv2_hero_video' ), 'هیرو: لینک ویدیو (mp4، یوتیوب یا آپارات؛ خالی = فقط تصویر)', 'url' );
		liferuss_admin_field( 'hv2_hero_video_label', $g( 'hv2_hero_video_label' ), 'هیرو: برچسب دکمهٔ پخش' );
		liferuss_admin_field( 'hv2_services_title', $g( 'hv2_services_title' ), 'خدمات: عنوان' );
		liferuss_admin_field( 'hv2_services_subtitle', $g( 'hv2_services_subtitle' ), 'خدمات: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_offers_title', $g( 'hv2_offers_title' ), 'پیشنهادها: عنوان' );
		liferuss_admin_field( 'hv2_offers_subtitle', $g( 'hv2_offers_subtitle' ), 'پیشنهادها: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_offers_btn', $g( 'hv2_offers_btn' ), 'پیشنهادها: متن دکمهٔ «مشاهده همه»' );
		liferuss_admin_field( 'hv2_offers_btn_link', $g( 'hv2_offers_btn_link' ), 'پیشنهادها: لینک دکمه' );
		liferuss_admin_field( 'hv2_pay_enabled', $g( 'hv2_pay_enabled' ), 'نمایش بخش پرداخت اقساط', 'checkbox' );
		liferuss_admin_field( 'hv2_pay_title', $g( 'hv2_pay_title' ), 'اقساط: عنوان' );
		liferuss_admin_field( 'hv2_pay_subtitle', $g( 'hv2_pay_subtitle' ), 'اقساط: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_pay_cta', $g( 'hv2_pay_cta' ), 'اقساط: متن دکمه' );
		liferuss_admin_field( 'hv2_pay_cta_link', $g( 'hv2_pay_cta_link' ), 'اقساط: لینک دکمه' );
		liferuss_admin_field( 'hv2_cases_title', $g( 'hv2_cases_title' ), 'پرونده‌های موفق: عنوان' );
		liferuss_admin_field( 'hv2_cases_subtitle', $g( 'hv2_cases_subtitle' ), 'پرونده‌های موفق: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_cases_btn', $g( 'hv2_cases_btn' ), 'پرونده‌های موفق: متن دکمه' );
		liferuss_admin_field( 'hv2_cases_btn_link', $g( 'hv2_cases_btn_link' ), 'پرونده‌های موفق: لینک دکمه (خالی = بدون دکمه)' );
		liferuss_admin_field( 'hv2_tm_title', $g( 'hv2_tm_title' ), 'نظرات: عنوان' );
		liferuss_admin_field( 'hv2_tm_subtitle', $g( 'hv2_tm_subtitle' ), 'نظرات: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_ig_enabled', $g( 'hv2_ig_enabled' ), 'نمایش بخش اینستاگرام', 'checkbox' );
		liferuss_admin_field( 'hv2_ig_title', $g( 'hv2_ig_title' ), 'اینستاگرام: عنوان' );
		liferuss_admin_field( 'hv2_ig_text', $g( 'hv2_ig_text' ), 'اینستاگرام: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_ig_btn', $g( 'hv2_ig_btn' ), 'اینستاگرام: متن دکمه' );
		liferuss_admin_field( 'hv2_ig_url', $g( 'hv2_ig_url' ), 'اینستاگرام: لینک صفحه (خالی = از زبانهٔ «تماس و شبکه‌ها»)', 'url' );
		liferuss_admin_field( 'hv2_app_enabled', $g( 'hv2_app_enabled' ), 'نمایش بنر اپلیکیشن', 'checkbox' );
		liferuss_admin_field( 'hv2_app_title', $g( 'hv2_app_title' ), 'اپلیکیشن: عنوان' );
		liferuss_admin_field( 'hv2_app_text', $g( 'hv2_app_text' ), 'اپلیکیشن: توضیح', 'textarea' );
		liferuss_admin_field( 'hv2_app_btn', $g( 'hv2_app_btn' ), 'اپلیکیشن: متن دکمه' );
		liferuss_admin_field( 'hv2_app_url', $g( 'hv2_app_url' ), 'اپلیکیشن: لینک دانلود (خالی = دکمه نمایش داده نمی‌شود)', 'url' );
		liferuss_admin_field( 'hv2_blog_title', $g( 'hv2_blog_title' ), 'مطالب: عنوان' );
		liferuss_admin_field( 'hv2_blog_btn', $g( 'hv2_blog_btn' ), 'مطالب: متن دکمه' );
		liferuss_admin_field( 'footer_trust_html', $g( 'footer_trust_html' ), 'فوتر: کد نماد اعتماد (مثلاً اینماد؛ فقط لینک و تصویر)', 'textarea' );
		liferuss_admin_table_end();

		echo '<h3>کارت‌های خدمات (۴ عدد)</h3><div class="liferuss-grid">';
		foreach ( (array) $g( 'hv2_services' ) as $i => $item ) {
			echo '<div class="liferuss-card"><h3>کارت ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
			liferuss_admin_subfield( "hv2_services][$i][enabled", $item['enabled'] ?? '1', 'فعال', 'checkbox' );
			liferuss_admin_subfield( "hv2_services][$i][title", $item['title'] ?? '', 'عنوان' );
			liferuss_admin_subfield( "hv2_services][$i][text", $item['text'] ?? '', 'توضیح', 'textarea' );
			liferuss_admin_subfield( "hv2_services][$i][icon", $item['icon'] ?? '', 'آیکون (cap, passport, handshake, suitcase, wallet, docs …)' );
			liferuss_admin_subfield( "hv2_services][$i][link", $item['link'] ?? '', 'لینک' );
			echo '</div>';
		}
		echo '</div><h3>کارت‌های پیشنهاد (۷ عدد؛ ردیف‌ها ۲ / ۳ / ۲)</h3><div class="liferuss-grid">';
		foreach ( (array) $g( 'hv2_offers' ) as $i => $item ) {
			echo '<div class="liferuss-card"><h3>کارت ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
			liferuss_admin_subfield( "hv2_offers][$i][enabled", $item['enabled'] ?? '1', 'فعال', 'checkbox' );
			liferuss_admin_subfield( "hv2_offers][$i][tag", $item['tag'] ?? '', 'برچسب کوچک' );
			liferuss_admin_subfield( "hv2_offers][$i][title", $item['title'] ?? '', 'عنوان' );
			liferuss_admin_subfield( "hv2_offers][$i][text", $item['text'] ?? '', 'توضیح', 'textarea' );
			liferuss_admin_subfield( "hv2_offers][$i][link", $item['link'] ?? '', 'لینک' );
			liferuss_admin_subfield( "hv2_offers][$i][photo", $item['photo'] ?? '', 'تصویر پیش‌فرض قالب (saint-basil, saint-petersburg, moscow-night, russian-couple, matryoshka-samovar, borscht)' );
			liferuss_admin_media_inline( "hv2_offers][$i][image_id", $item['image_id'] ?? 0, 'یا تصویر دلخواه' );
			echo '</div>';
		}
		echo '</div><h3>سه مزیت پرداخت</h3><div class="liferuss-grid">';
		foreach ( (array) $g( 'hv2_pay_items' ) as $i => $item ) {
			echo '<div class="liferuss-card"><h3>مورد ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
			liferuss_admin_subfield( "hv2_pay_items][$i][title", $item['title'] ?? '', 'عنوان' );
			liferuss_admin_subfield( "hv2_pay_items][$i][text", $item['text'] ?? '', 'توضیح', 'textarea' );
			liferuss_admin_subfield( "hv2_pay_items][$i][icon", $item['icon'] ?? '', 'آیکون (steps, calendar, pie, wallet …)' );
			echo '</div>';
		}
		echo '</div>';
		?>
	</div>
	<?php
}
