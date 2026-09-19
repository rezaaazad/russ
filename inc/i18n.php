<?php
/**
 * Theme-native multilingual layer (fa / ru / ar / en).
 *
 * Path prefixes: / (fa), /ru/, /ar/, /en/. No WPML required.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registered languages.
 *
 * @return array<string, array<string, string>>
 */
function liferuss_languages() {
	return array(
		'fa' => array(
			'code'      => 'fa',
			'hreflang'  => 'fa',
			'html_lang' => 'fa',
			'locale'    => 'fa_IR',
			'og_locale' => 'fa_IR',
			'dir'       => 'rtl',
			'prefix'    => '',
			'short'     => 'FA',
			'native'    => 'فارسی',
			'english'   => 'Persian',
		),
		'ru' => array(
			'code'      => 'ru',
			'hreflang'  => 'ru',
			'html_lang' => 'ru',
			'locale'    => 'ru_RU',
			'og_locale' => 'ru_RU',
			'dir'       => 'ltr',
			'prefix'    => 'ru',
			'short'     => 'RU',
			'native'    => 'Русский',
			'english'   => 'Russian',
		),
		'ar' => array(
			'code'      => 'ar',
			'hreflang'  => 'ar',
			'html_lang' => 'ar',
			'locale'    => 'ar',
			'og_locale' => 'ar_AR',
			'dir'       => 'rtl',
			'prefix'    => 'ar',
			'short'     => 'AR',
			'native'    => 'العربية',
			'english'   => 'Arabic',
		),
		'en' => array(
			'code'      => 'en',
			'hreflang'  => 'en',
			'html_lang' => 'en',
			'locale'    => 'en_US',
			'og_locale' => 'en_US',
			'dir'       => 'ltr',
			'prefix'    => 'en',
			'short'     => 'EN',
			'native'    => 'English',
			'english'   => 'English',
		),
	);
}

/**
 * Whether a language code is supported.
 *
 * @param string $lang Code.
 * @return bool
 */
function liferuss_is_valid_lang( $lang ) {
	return isset( liferuss_languages()[ $lang ] );
}

/**
 * Detect and strip /en|/ru|/ar prefixes before WordPress routes the request.
 */
function liferuss_boot_language() {
	static $booted = false;
	if ( $booted ) {
		return;
	}
	$booted = true;

	if ( empty( $GLOBALS['liferuss_lang'] ) ) {
		$GLOBALS['liferuss_lang'] = 'fa';
	}
	if ( empty( $GLOBALS['liferuss_path'] ) ) {
		$GLOBALS['liferuss_path'] = '/';
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( ! empty( $_POST['consult_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = sanitize_key( wp_unslash( $_POST['consult_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( liferuss_is_valid_lang( $posted ) ) {
			$GLOBALS['liferuss_lang'] = $posted;
		}
	}

	$raw = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path  = (string) wp_parse_url( $raw, PHP_URL_PATH );
	$query = (string) wp_parse_url( $raw, PHP_URL_QUERY );

	$home_path = '/';
	if ( function_exists( 'home_url' ) ) {
		$parsed_home = wp_parse_url( home_url( '/' ) );
		if ( ! empty( $parsed_home['path'] ) ) {
			$home_path = trailingslashit( $parsed_home['path'] );
		}
	}

	if ( preg_match( '#/(wp-admin|wp-content|wp-includes|wp-json)(/|$)#', $path ) || preg_match( '#/(wp-login\.php|xmlrpc\.php|wp-sitemap)#', $path ) ) {
		return;
	}

	$rel = $path;
	if ( '/' !== $home_path && 0 === strpos( $path, untrailingslashit( $home_path ) ) ) {
		$rel = substr( $path, strlen( untrailingslashit( $home_path ) ) );
		if ( '' === $rel ) {
			$rel = '/';
		}
	}

	if ( preg_match( '#^/(en|ru|ar)(/.*)?$#', $rel, $match ) ) {
		$GLOBALS['liferuss_lang'] = $match[1];
		$rest                     = isset( $match[2] ) && '' !== $match[2] ? $match[2] : '/';
		$GLOBALS['liferuss_path'] = $rest;
		$new_path                 = ( '/' !== $home_path ) ? untrailingslashit( $home_path ) . $rest : $rest;
		$_SERVER['REQUEST_URI']   = $new_path . ( $query ? '?' . $query : '' );
		if ( ! empty( $_SERVER['PATH_INFO'] ) && preg_match( '#^/(en|ru|ar)(/.*)?$#', (string) $_SERVER['PATH_INFO'] ) ) {
			$_SERVER['PATH_INFO'] = $rest;
		}
		if ( ! empty( $_SERVER['REDIRECT_URL'] ) ) {
			$_SERVER['REDIRECT_URL'] = $new_path;
		}
		if ( ! empty( $_SERVER['PHP_SELF'] ) && preg_match( '#/(en|ru|ar)(/|$)#', (string) $_SERVER['PHP_SELF'] ) ) {
			$_SERVER['PHP_SELF'] = ( '/' === $new_path ) ? '/index.php' : $new_path;
		}
		return;
	}

	$GLOBALS['liferuss_path'] = $rel ? $rel : '/';
}
add_action( 'init', 'liferuss_boot_language', 0 );
add_filter(
	'do_parse_request',
	static function ( $do ) {
		liferuss_boot_language();
		return $do;
	},
	1
);

/**
 * Active language code.
 *
 * @return string
 */
function liferuss_current_lang() {
	if ( ! empty( $GLOBALS['liferuss_lang'] ) && liferuss_is_valid_lang( $GLOBALS['liferuss_lang'] ) ) {
		return $GLOBALS['liferuss_lang'];
	}
	return 'fa';
}

/**
 * Force language (AJAX / form handlers).
 *
 * @param string $lang Code.
 */
function liferuss_force_lang( $lang ) {
	if ( liferuss_is_valid_lang( $lang ) ) {
		$GLOBALS['liferuss_lang'] = $lang;
	}
}

/**
 * Current language meta.
 *
 * @param string $key Meta key.
 * @return string
 */
function liferuss_lang_meta( $key ) {
	$langs = liferuss_languages();
	$lang  = liferuss_current_lang();
	return isset( $langs[ $lang ][ $key ] ) ? $langs[ $lang ][ $key ] : '';
}

/**
 * Path of the current view without a language prefix.
 *
 * @return string
 */
function liferuss_current_path() {
	if ( ! empty( $GLOBALS['liferuss_path'] ) ) {
		return $GLOBALS['liferuss_path'];
	}
	return '/';
}

/**
 * Language-prefixed front URL.
 *
 * @param string      $path Path beginning with /.
 * @param string|null $lang Language.
 * @return string
 */
function liferuss_url( $path = '/', $lang = null ) {
	$lang = $lang ? $lang : liferuss_current_lang();
	if ( ! liferuss_is_valid_lang( $lang ) ) {
		$lang = 'fa';
	}
	$path = '/' . ltrim( (string) $path, '/' );
	if ( '' === $path || '/index.php' === $path ) {
		$path = '/';
	}
	$home   = untrailingslashit( home_url( '/' ) );
	$prefix = liferuss_languages()[ $lang ]['prefix'];
	if ( $prefix ) {
		return $home . '/' . $prefix . ( '/' === $path ? '/' : $path );
	}
	return $home . ( '/' === $path ? '/' : $path );
}

/**
 * Home URL for a language.
 *
 * @param string|null $lang Language.
 * @return string
 */
function liferuss_home( $lang = null ) {
	return liferuss_url( '/', $lang );
}

/**
 * Insert or replace a language prefix on an absolute URL.
 *
 * @param string $url  Absolute URL.
 * @param string $lang Language.
 * @return string
 */
function liferuss_localize_url( $url, $lang = null ) {
	$lang = $lang ? $lang : liferuss_current_lang();
	if ( ! $url ) {
		return liferuss_home( $lang );
	}
	$home = untrailingslashit( home_url( '/' ) );
	if ( 0 !== strpos( $url, $home ) ) {
		return $url;
	}
	$rest = substr( $url, strlen( $home ) );
	if ( false === $rest ) {
		$rest = '/';
	}
	$path  = (string) wp_parse_url( $rest, PHP_URL_PATH );
	$query = (string) wp_parse_url( $rest, PHP_URL_QUERY );
	$frag  = (string) wp_parse_url( $url, PHP_URL_FRAGMENT );
	if ( preg_match( '#^/(en|ru|ar)(/.*)?$#', $path, $match ) ) {
		$path = isset( $match[2] ) && '' !== $match[2] ? $match[2] : '/';
	}
	if ( '' === $path ) {
		$path = '/';
	}
	$out = liferuss_url( $path, $lang );
	if ( $query ) {
		$out .= ( false === strpos( $out, '?' ) ? '?' : '&' ) . $query;
	}
	if ( $frag ) {
		$out .= '#' . $frag;
	}
	return $out;
}

/**
 * Equivalent URL of the current page in another language.
 *
 * @param string $lang Target language.
 * @return string
 */
function liferuss_switch_url( $lang ) {
	return liferuss_url( liferuss_current_path(), $lang );
}

/**
 * Packaged + saved overlay for one language (never FA — FA lives in main options).
 *
 * @param string $lang Language.
 * @return array
 */
function liferuss_lang_overlay( $lang ) {
	static $cache = array();
	if ( isset( $cache[ $lang ] ) ) {
		return $cache[ $lang ];
	}
	if ( 'fa' === $lang || ! liferuss_is_valid_lang( $lang ) ) {
		$cache[ $lang ] = array();
		return $cache[ $lang ];
	}
	$packaged = function_exists( 'liferuss_i18n_packaged' ) ? liferuss_i18n_packaged( $lang ) : array();
	$saved    = get_option( 'liferuss_options', array() );
	$custom   = ( is_array( $saved ) && isset( $saved['i18n'][ $lang ] ) && is_array( $saved['i18n'][ $lang ] ) )
		? $saved['i18n'][ $lang ]
		: array();
	$cache[ $lang ] = liferuss_merge_nonempty( $packaged, $custom );
	return $cache[ $lang ];
}

/**
 * Merge overlay values, skipping empty strings so packaged defaults remain.
 *
 * @param array $base Base.
 * @param array $over Overlay.
 * @return array
 */
function liferuss_merge_nonempty( $base, $over ) {
	if ( ! is_array( $base ) ) {
		$base = array();
	}
	foreach ( (array) $over as $key => $value ) {
		if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
			$base[ $key ] = liferuss_merge_nonempty( $base[ $key ], $value );
		} elseif ( '' !== $value && null !== $value ) {
			$base[ $key ] = $value;
		}
	}
	return $base;
}

/**
 * Merge translated list rows onto the Persian structure (icons/images stay shared).
 *
 * @param array $fa Persian rows.
 * @param array $tr Translated rows.
 * @return array
 */
function liferuss_merge_translated_list( $fa, $tr ) {
	foreach ( $fa as $i => $item ) {
		if ( ! isset( $tr[ $i ] ) || ! is_array( $tr[ $i ] ) ) {
			continue;
		}
		foreach ( $tr[ $i ] as $key => $value ) {
			if ( '' !== $value && null !== $value ) {
				$fa[ $i ][ $key ] = $value;
			}
		}
	}
	return $fa;
}

/**
 * UI chrome string (nav, 404, breadcrumbs, form errors).
 *
 * @param string $key Key.
 * @return string
 */
function liferuss_t( $key ) {
	$lang    = liferuss_current_lang();
	$strings = liferuss_ui_strings();
	if ( isset( $strings[ $lang ][ $key ] ) && '' !== $strings[ $lang ][ $key ] ) {
		return $strings[ $lang ][ $key ];
	}
	if ( isset( $strings['fa'][ $key ] ) ) {
		return $strings['fa'][ $key ];
	}
	return $key;
}

/**
 * Hard-coded UI copy for all four languages.
 *
 * @return array<string, array<string, string>>
 */
function liferuss_ui_strings() {
	return array(
		'fa' => array(
			'skip_link'          => 'رفتن به محتوا',
			'nav_aria'           => 'منوی اصلی',
			'open_menu'          => 'باز کردن منو',
			'close_menu'         => 'بستن منو',
			'nav_home'           => 'خانه',
			'nav_about'          => 'درباره ما',
			'nav_universities'   => 'دانشگاه‌ها',
			'nav_services'       => 'خدمات',
			'nav_costs'          => 'هزینه‌ها',
			'nav_blog'           => 'وبلاگ',
			'nav_freight'        => 'باربری و ارسال',
			'nav_trade'          => 'تجارت و تأمین',
			'nav_contact'        => 'تماس با ما',
			'footer_quick'       => 'دسترسی سریع',
			'footer_services'    => 'خدمات',
			'footer_contact'     => 'تماس با ما',
			'channel_whatsapp'   => 'واتساپ',
			'channel_telegram'   => 'تلگرام',
			'channel_instagram'  => 'اینستاگرام',
			'channel_phone'      => 'تلفنی',
			'channels_aria'      => 'راه‌های ارتباطی',
			'trust_aria'         => 'اعتماد دانشجویان',
			'stories_aria'       => 'آمار دانشجویان',
			'slider_prev'        => 'قبلی',
			'slider_next'        => 'بعدی',
			'crumb_home'         => 'خانه',
			'crumb_blog'         => 'وبلاگ',
			'crumb_search'       => 'جستجو',
			'crumb_404'          => '۴۰۴',
			'crumb_aria'         => 'مسیر صفحه',
			'page_404_title'     => 'این صفحه پیدا نشد',
			'page_404_text'      => 'آدرس را بررسی کنید یا از منو به صفحه مورد نظر بروید.',
			'page_404_back'      => 'بازگشت به خانه',
			'search_eyebrow'     => 'جستجو',
			'search_results'     => 'نتایج جستجو',
			'search_title'       => 'جستجو در سایت',
			'search_empty_title' => 'نتیجه‌ای پیدا نشد',
			'search_empty_text'  => 'عبارت دیگری را امتحان کنید یا از منو به بخش خدمات بروید.',
			'blog_eyebrow'       => 'دانش و تجربه',
			'blog_title'         => 'وبلاگ لایف روس',
			'blog_intro'         => 'راهنمای پذیرش، هزینه، پادفک و زندگی دانشجویی در روسیه.',
			'read_more'          => 'ادامه مطلب',
			'blog_empty_title'   => 'هنوز نوشته‌ای منتشر نشده',
			'blog_empty_text'    => 'به‌زودی راهنماهای تحصیل در روسیه اینجا قرار می‌گیرد.',
			'post_prev'          => 'نوشته قبلی',
			'post_next'          => 'نوشته بعدی',
			'hero_alt_basil'     => 'نمایی از کلیسای سنت باسیل در مسکو',
			'hero_alt_student'   => 'دانشجوی فارغ‌التحصیل',
			'form_alt'           => 'دانشجو با کتاب در مسیر تحصیل',
			'form_nonce'         => 'نشست منقضی شده است. صفحه را تازه کنید و دوباره بفرستید.',
			'form_need_name'     => 'لطفاً نام و نام خانوادگی را وارد کنید.',
			'form_need_phone'    => 'شماره تماس معتبر وارد کنید.',
			'form_need_level'    => 'مقطع تحصیلی را انتخاب کنید.',
			'form_bad_file'      => 'فقط فایل‌های JPG، PNG یا PDF تا ۱۰ مگابایت مجاز است.',
			'form_big_file'      => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
			'form_save_fail'     => 'ثبت درخواست ممکن نشد. لطفاً دوباره تلاش کنید.',
			'form_ok_short'      => 'ثبت شد.',
			'form_err_short'     => 'ارسال ناموفق بود.',
			'form_net'           => 'ارتباط برقرار نشد. دوباره تلاش کنید.',
			'level_placeholder'  => 'مقطع تحصیلی را انتخاب کنید',
			'level_diploma'      => 'دیپلم / پیش‌دانشگاهی',
			'level_bachelor'     => 'کارشناسی',
			'level_master'       => 'کارشناسی ارشد',
			'level_phd'          => 'دکتری',
			'level_medicine'     => 'پزشکی',
			'level_dentistry'    => 'دندانپزشکی',
			'level_pharmacy'     => 'داروسازی',
			'level_padfak'       => 'پادفک / دوره آمادگی',
			'about_h1'           => 'مؤسسه مشاوره تحصیل در روسیه',
			'about_lead'         => 'ما فروشگاه نیستیم؛ مسیر پذیرش، ویزا و استقرار را برای متقاضی ایرانی شفاف و قابل پیگیری می‌کنیم.',
			'about_v1_title'     => 'شفافیت هزینه',
			'about_v1_text'      => 'شهریه، خوابگاه و زندگی را قبل از پرداخت هر هزینه‌ای روی کاغذ می‌آوریم.',
			'about_v2_title'     => 'مشاوره تخصصی',
			'about_v2_text'      => 'انتخاب دانشگاه بر اساس رشته، زبان تدریس و وضعیت تأیید مدارک شما.',
			'about_v3_title'     => 'همراهی تا استقرار',
			'about_v3_text'      => 'از ترجمه تا فرودگاه مسکو یا سن‌پترزبورگ یک تیم پاسخگو کنار شماست.',
			'services_page_eye'  => 'همراهی کامل',
			'services_page_h1'   => 'خدمات لایف روس',
			'services_page_lead' => 'پذیرش، پادفک، ویزا، خوابگاه، ترجمه مدارک و استقبال فرودگاه — بدون فروش کالا.',
			'uni_page_eye'       => 'Knowledge · Opportunity',
			'uni_page_h1'        => 'دانشگاه‌های برتر روسیه',
			'uni_page_lead'      => 'مجموعه‌ای از دانشگاه‌های دولتی و پزشکی که برای متقاضیان ایرانی بیشترین تقاضا را دارند.',
			'costs_page_eye'     => 'تصمیم آگاهانه',
			'costs_page_h1'      => 'هزینه و شرایط تحصیل در روسیه',
			'costs_page_lead'    => 'اعداد زیر میانگین سال جاری هستند و پس از انتخاب رشته، زبان و شهر دقیق می‌شوند.',
			'contact_page_eye'   => 'همین امروز',
			'contact_page_h1'    => 'مشاوره رایگان تحصیل در روسیه',
			'contact_page_lead'  => 'فرم را پر کنید یا از واتساپ، تلگرام و اینستاگرام پیام بگذارید.',
			'contact_form_title' => 'ارسال پیام',
			'contact_form_intro' => 'نام، تلفن و پیام خود را بفرستید؛ تیم پشتیبانی پاسخ می‌دهد.',
			'contact_message_label' => 'پیام',
			'contact_message_ph' => 'پیام شما',
			'contact_form_success' => 'پیام شما ثبت شد. به‌زودی تماس می‌گیریم.',
			'faq_q1'             => 'هزینه تحصیل در روسیه چقدر است؟',
			'faq_a1'             => 'شهریه معمولاً سالانه ۲۰۰۰ تا ۷۰۰۰ دلار است؛ خوابگاه ماهانه ۲۰ تا ۵۰ دلار و زندگی حدود ۲۰۰ تا ۴۰۰ دلار.',
			'faq_q2'             => 'آیا برای تحصیل در روسیه به پادفک نیاز است؟',
			'faq_a2'             => 'برای برنامه‌های روسی‌زبان معمولاً دوره پادفک لازم است. برنامه‌های انگلیسی‌زبان اغلب بدون پادفک امکان‌پذیرند.',
			'faq_q3'             => 'لایف روس چه خدماتی ارائه می‌دهد؟',
			'faq_a3'             => 'پذیرش تحصیلی، پادفک، ویزا، خوابگاه، ترجمه مدارک و استقبال فرودگاه — بدون فروش کالا.',
			'service_type'       => 'مشاوره تحصیل و پذیرش در روسیه',
			'lang_switcher'      => 'انتخاب زبان',
			'float_open'         => 'راه‌های ارتباطی',
			'float_close'        => 'بستن راه‌های ارتباطی',
			'float_dialog'       => 'راه‌های ارتباطی',
			'bottom_nav_aria'    => 'ناوبری سریع',
		),
		'en' => array(
			'skip_link'          => 'Skip to content',
			'nav_aria'           => 'Primary menu',
			'open_menu'          => 'Open menu',
			'close_menu'         => 'Close menu',
			'nav_home'           => 'Home',
			'nav_about'          => 'About',
			'nav_universities'   => 'Universities',
			'nav_services'       => 'Services',
			'nav_costs'          => 'Costs',
			'nav_blog'           => 'Blog',
			'nav_freight'        => 'Freight',
			'nav_trade'          => 'Trade',
			'nav_contact'        => 'Contact',
			'footer_quick'       => 'Quick links',
			'footer_services'    => 'Services',
			'footer_contact'     => 'Contact us',
			'channel_whatsapp'   => 'WhatsApp',
			'channel_telegram'   => 'Telegram',
			'channel_instagram'  => 'Instagram',
			'channel_phone'      => 'Call',
			'channels_aria'      => 'Contact channels',
			'trust_aria'         => 'Student trust',
			'stories_aria'       => 'Student statistics',
			'slider_prev'        => 'Previous',
			'slider_next'        => 'Next',
			'crumb_home'         => 'Home',
			'crumb_blog'         => 'Blog',
			'crumb_search'       => 'Search',
			'crumb_404'          => '404',
			'crumb_aria'         => 'Breadcrumb',
			'page_404_title'     => 'This page could not be found',
			'page_404_text'      => 'Check the address or use the menu to reach the page you need.',
			'page_404_back'      => 'Back to home',
			'search_eyebrow'     => 'Search',
			'search_results'     => 'Search results',
			'search_title'       => 'Search the site',
			'search_empty_title' => 'No results found',
			'search_empty_text'  => 'Try another phrase or open the services section from the menu.',
			'blog_eyebrow'       => 'Knowledge & experience',
			'blog_title'         => 'LifeRuss blog',
			'blog_intro'         => 'Guides on admission, costs, preparatory year, and student life in Russia.',
			'read_more'          => 'Read more',
			'blog_empty_title'   => 'No posts yet',
			'blog_empty_text'    => 'Guides to studying in Russia will appear here soon.',
			'post_prev'          => 'Previous post',
			'post_next'          => 'Next post',
			'hero_alt_basil'     => 'Saint Basil’s Cathedral in Moscow',
			'hero_alt_student'   => 'Graduating student',
			'form_alt'           => 'Student with books on the path to university',
			'form_nonce'         => 'Your session expired. Refresh the page and try again.',
			'form_need_name'     => 'Please enter your full name.',
			'form_need_phone'    => 'Enter a valid phone number.',
			'form_need_level'    => 'Please choose a study level.',
			'form_bad_file'      => 'Only JPG, PNG, or PDF files up to 10 MB are allowed.',
			'form_big_file'      => 'The file must be 10 MB or smaller.',
			'form_save_fail'     => 'We could not save your request. Please try again.',
			'form_ok_short'      => 'Submitted.',
			'form_err_short'     => 'Submission failed.',
			'form_net'           => 'Connection failed. Please try again.',
			'level_placeholder'  => 'Choose a study level',
			'level_diploma'      => 'High school diploma',
			'level_bachelor'     => 'Bachelor’s',
			'level_master'       => 'Master’s',
			'level_phd'          => 'PhD',
			'level_medicine'     => 'Medicine',
			'level_dentistry'    => 'Dentistry',
			'level_pharmacy'     => 'Pharmacy',
			'level_padfak'       => 'Preparatory year (Padfak)',
			'about_h1'           => 'Study-abroad advisory for Russia',
			'about_lead'         => 'We are not a shop. We make admission, visa, and settling-in in Russia transparent and followable.',
			'about_v1_title'     => 'Clear costs',
			'about_v1_text'      => 'Tuition, housing, and living costs are written down before you pay anything.',
			'about_v2_title'     => 'Specialist advice',
			'about_v2_text'      => 'University choice based on your major, language of study, and documents.',
			'about_v3_title'     => 'With you until you settle',
			'about_v3_text'      => 'From translation to the airport in Moscow or Saint Petersburg, one team answers you.',
			'services_page_eye'  => 'Full support',
			'services_page_h1'   => 'LifeRuss services',
			'services_page_lead' => 'Admission, preparatory year, visa, dormitory, document translation, and airport pickup — no retail.',
			'uni_page_eye'       => 'Knowledge · Opportunity',
			'uni_page_h1'        => 'Leading universities in Russia',
			'uni_page_lead'      => 'State and medical universities most often chosen by international applicants we advise.',
			'costs_page_eye'     => 'Informed decisions',
			'costs_page_h1'      => 'Cost of studying in Russia',
			'costs_page_lead'    => 'Figures below are current averages and become exact after major, language, and city are chosen.',
			'contact_page_eye'   => 'Start today',
			'contact_page_h1'    => 'Free consultation on studying in Russia',
			'contact_page_lead'  => 'Fill in the form or message us on WhatsApp, Telegram, or Instagram.',
			'contact_form_title' => 'Send a message',
			'contact_form_intro' => 'Leave your name, phone, and message. Support will get back to you.',
			'contact_message_label' => 'Message',
			'contact_message_ph' => 'Your message',
			'contact_form_success' => 'Your message was saved. We will contact you shortly.',
			'faq_q1'             => 'How much does it cost to study in Russia?',
			'faq_a1'             => 'Tuition is usually USD 2,000–7,000 a year; dormitories USD 20–50 a month and living costs about USD 200–400.',
			'faq_q2'             => 'Do I need a preparatory year (Padfak) to study in Russia?',
			'faq_a2'             => 'Russian-taught programmes usually require Padfak. English-taught programmes often do not.',
			'faq_q3'             => 'What does LifeRuss offer?',
			'faq_a3'             => 'University admission, preparatory year, visa, dormitory, document translation, and airport pickup — not retail goods.',
			'service_type'       => 'Study-abroad and admission advisory for Russia',
			'lang_switcher'      => 'Language',
			'float_open'         => 'Contact options',
			'float_close'        => 'Close contact options',
			'float_dialog'       => 'Contact options',
			'bottom_nav_aria'    => 'Quick navigation',
		),
		'ru' => array(
			'skip_link'          => 'Перейти к содержанию',
			'nav_aria'           => 'Главное меню',
			'open_menu'          => 'Открыть меню',
			'close_menu'         => 'Закрыть меню',
			'nav_home'           => 'Главная',
			'nav_about'          => 'О нас',
			'nav_universities'   => 'Университеты',
			'nav_services'       => 'Услуги',
			'nav_costs'          => 'Стоимость',
			'nav_blog'           => 'Блог',
			'nav_freight'        => 'Грузы',
			'nav_trade'          => 'Торговля',
			'nav_contact'        => 'Контакты',
			'footer_quick'       => 'Быстрые ссылки',
			'footer_services'    => 'Услуги',
			'footer_contact'     => 'Связаться с нами',
			'channel_whatsapp'   => 'WhatsApp',
			'channel_telegram'   => 'Telegram',
			'channel_instagram'  => 'Instagram',
			'channel_phone'      => 'Звонок',
			'channels_aria'      => 'Способы связи',
			'trust_aria'         => 'Доверие студентов',
			'stories_aria'       => 'Статистика студентов',
			'slider_prev'        => 'Назад',
			'slider_next'        => 'Далее',
			'crumb_home'         => 'Главная',
			'crumb_blog'         => 'Блог',
			'crumb_search'       => 'Поиск',
			'crumb_404'          => '404',
			'crumb_aria'         => 'Навигационная цепочка',
			'page_404_title'     => 'Страница не найдена',
			'page_404_text'      => 'Проверьте адрес или выберите нужный раздел в меню.',
			'page_404_back'      => 'На главную',
			'search_eyebrow'     => 'Поиск',
			'search_results'     => 'Результаты поиска',
			'search_title'       => 'Поиск по сайту',
			'search_empty_title' => 'Ничего не найдено',
			'search_empty_text'  => 'Попробуйте другой запрос или откройте раздел услуг.',
			'blog_eyebrow'       => 'Знания и опыт',
			'blog_title'         => 'Блог LifeRuss',
			'blog_intro'         => 'Гайды по поступлению, расходам, подготовительному факультету и студенческой жизни в России.',
			'read_more'          => 'Читать далее',
			'blog_empty_title'   => 'Пока нет записей',
			'blog_empty_text'    => 'Скоро здесь появятся материалы об учёбе в России.',
			'post_prev'          => 'Предыдущая запись',
			'post_next'          => 'Следующая запись',
			'hero_alt_basil'     => 'Собор Василия Блаженного в Москве',
			'hero_alt_student'   => 'Выпускник',
			'form_alt'           => 'Студент с книгами на пути к учёбе',
			'form_nonce'         => 'Сессия истекла. Обновите страницу и отправьте форму снова.',
			'form_need_name'     => 'Укажите имя и фамилию.',
			'form_need_phone'    => 'Введите корректный номер телефона.',
			'form_need_level'    => 'Выберите уровень обучения.',
			'form_bad_file'      => 'Допустимы только JPG, PNG или PDF до 10 МБ.',
			'form_big_file'      => 'Файл не должен быть больше 10 МБ.',
			'form_save_fail'     => 'Не удалось сохранить заявку. Попробуйте ещё раз.',
			'form_ok_short'      => 'Отправлено.',
			'form_err_short'     => 'Ошибка отправки.',
			'form_net'           => 'Нет связи. Попробуйте ещё раз.',
			'level_placeholder'  => 'Выберите уровень обучения',
			'level_diploma'      => 'Аттестат / среднее образование',
			'level_bachelor'     => 'Бакалавриат',
			'level_master'       => 'Магистратура',
			'level_phd'          => 'Аспирантура',
			'level_medicine'     => 'Медицина',
			'level_dentistry'    => 'Стоматология',
			'level_pharmacy'     => 'Фармация',
			'level_padfak'       => 'Подготовительный факультет (падфак)',
			'about_h1'           => 'Консультации по учёбе в России',
			'about_lead'         => 'Мы не магазин. Делаем поступление, визу и обустройство в России прозрачными и понятными.',
			'about_v1_title'     => 'Прозрачные расходы',
			'about_v1_text'      => 'Обучение, общежитие и жизнь считаем до любых платежей.',
			'about_v2_title'     => 'Профильная консультация',
			'about_v2_text'      => 'Выбор вуза по специальности, языку обучения и комплекту документов.',
			'about_v3_title'     => 'До заселения',
			'about_v3_text'      => 'От перевода документов до аэропорта Москвы или Санкт-Петербурга — одна команда на связи.',
			'services_page_eye'  => 'Полное сопровождение',
			'services_page_h1'   => 'Услуги LifeRuss',
			'services_page_lead' => 'Поступление, падфак, виза, общежитие, перевод документов и встреча в аэропорту — без торговли товарами.',
			'uni_page_eye'       => 'Knowledge · Opportunity',
			'uni_page_h1'        => 'Ведущие университеты России',
			'uni_page_lead'      => 'Государственные и медицинские вузы, которые чаще всего выбирают наши абитуриенты.',
			'costs_page_eye'     => 'Осознанное решение',
			'costs_page_h1'      => 'Стоимость учёбы в России',
			'costs_page_lead'    => 'Цифры — средние за текущий год; точная сумма зависит от специальности, языка и города.',
			'contact_page_eye'   => 'Начните сегодня',
			'contact_page_h1'    => 'Бесплатная консультация по учёбе в России',
			'contact_page_lead'  => 'Заполните форму или напишите в WhatsApp, Telegram или Instagram.',
			'contact_form_title' => 'Написать нам',
			'contact_form_intro' => 'Оставьте имя, телефон и сообщение — поддержка ответит.',
			'contact_message_label' => 'Сообщение',
			'contact_message_ph' => 'Ваше сообщение',
			'contact_form_success' => 'Сообщение сохранено. Мы скоро свяжемся с вами.',
			'faq_q1'             => 'Сколько стоит учёба в России?',
			'faq_a1'             => 'Обучение обычно 2000–7000 долларов в год; общежитие 20–50 долларов в месяц, жизнь около 200–400 долларов.',
			'faq_q2'             => 'Нужен ли падфак для учёбы в России?',
			'faq_a2'             => 'Для программ на русском падфак обычно обязателен. Англоязычные программы часто без падфака.',
			'faq_q3'             => 'Какие услуги оказывает LifeRuss?',
			'faq_a3'             => 'Поступление, падфак, виза, общежитие, перевод документов и встреча в аэропорту — без продажи товаров.',
			'service_type'       => 'Консультации по поступлению и учёбе в России',
			'lang_switcher'      => 'Язык',
			'float_open'         => 'Способы связи',
			'float_close'        => 'Закрыть способы связи',
			'float_dialog'       => 'Способы связи',
			'bottom_nav_aria'    => 'Быстрая навигация',
		),
		'ar' => array(
			'skip_link'          => 'تخطي إلى المحتوى',
			'nav_aria'           => 'القائمة الرئيسية',
			'open_menu'          => 'فتح القائمة',
			'close_menu'         => 'إغلاق القائمة',
			'nav_home'           => 'الرئيسية',
			'nav_about'          => 'من نحن',
			'nav_universities'   => 'الجامعات',
			'nav_services'       => 'الخدمات',
			'nav_costs'          => 'التكاليف',
			'nav_blog'           => 'المدونة',
			'nav_freight'        => 'الشحن',
			'nav_trade'          => 'التجارة',
			'nav_contact'        => 'اتصل بنا',
			'footer_quick'       => 'روابط سريعة',
			'footer_services'    => 'الخدمات',
			'footer_contact'     => 'اتصل بنا',
			'channel_whatsapp'   => 'واتساب',
			'channel_telegram'   => 'تيليغرام',
			'channel_instagram'  => 'إنستغرام',
			'channel_phone'      => 'هاتف',
			'channels_aria'      => 'قنوات التواصل',
			'trust_aria'         => 'ثقة الطلاب',
			'stories_aria'       => 'إحصاءات الطلاب',
			'slider_prev'        => 'السابق',
			'slider_next'        => 'التالي',
			'crumb_home'         => 'الرئيسية',
			'crumb_blog'         => 'المدونة',
			'crumb_search'       => 'بحث',
			'crumb_404'          => '٤٠٤',
			'crumb_aria'         => 'مسار الصفحة',
			'page_404_title'     => 'تعذر العثور على هذه الصفحة',
			'page_404_text'      => 'تحقق من العنوان أو استخدم القائمة للوصول إلى الصفحة المطلوبة.',
			'page_404_back'      => 'العودة إلى الرئيسية',
			'search_eyebrow'     => 'بحث',
			'search_results'     => 'نتائج البحث',
			'search_title'       => 'البحث في الموقع',
			'search_empty_title' => 'لا توجد نتائج',
			'search_empty_text'  => 'جرّب عبارة أخرى أو افتح قسم الخدمات من القائمة.',
			'blog_eyebrow'       => 'معرفة وخبرة',
			'blog_title'         => 'مدونة لایف روس',
			'blog_intro'         => 'أدلة القبول والتكاليف والسنة التحضيرية والحياة الطلابية في روسيا.',
			'read_more'          => 'اقرأ المزيد',
			'blog_empty_title'   => 'لا توجد مقالات بعد',
			'blog_empty_text'    => 'ستظهر هنا قريباً أدلة الدراسة في روسيا.',
			'post_prev'          => 'المقال السابق',
			'post_next'          => 'المقال التالي',
			'hero_alt_basil'     => 'كاتدرائية القديس باسيل في موسكو',
			'hero_alt_student'   => 'طالب خريج',
			'form_alt'           => 'طالب مع كتب في طريق الدراسة',
			'form_nonce'         => 'انتهت الجلسة. حدّث الصفحة وأعد الإرسال.',
			'form_need_name'     => 'يرجى إدخال الاسم الكامل.',
			'form_need_phone'    => 'أدخل رقم هاتف صالحاً.',
			'form_need_level'    => 'يرجى اختيار المرحلة الدراسية.',
			'form_bad_file'      => 'يُسمح فقط بملفات JPG أو PNG أو PDF حتى ١٠ ميغابايت.',
			'form_big_file'      => 'يجب ألا يتجاوز حجم الملف ١٠ ميغابايت.',
			'form_save_fail'     => 'تعذر حفظ الطلب. حاول مرة أخرى.',
			'form_ok_short'      => 'تم الإرسال.',
			'form_err_short'     => 'فشل الإرسال.',
			'form_net'           => 'تعذر الاتصال. حاول مرة أخرى.',
			'level_placeholder'  => 'اختر المرحلة الدراسية',
			'level_diploma'      => 'الثانوية / الدبلوم',
			'level_bachelor'     => 'البكالوريوس',
			'level_master'       => 'الماجستير',
			'level_phd'          => 'الدكتوراه',
			'level_medicine'     => 'الطب',
			'level_dentistry'    => 'طب الأسنان',
			'level_pharmacy'     => 'الصيدلة',
			'level_padfak'       => 'السنة التحضيرية (بادفاك)',
			'about_h1'           => 'استشارات الدراسة في روسيا',
			'about_lead'         => 'لسنا متجراً. نجعل القبول والتأشيرة والاستقرار في روسيا واضحاً وقابلاً للمتابعة.',
			'about_v1_title'     => 'شفافية التكاليف',
			'about_v1_text'      => 'نوضح الرسوم والسكن والمعيشة قبل أي دفعة.',
			'about_v2_title'     => 'استشارة متخصصة',
			'about_v2_text'      => 'اختيار الجامعة حسب التخصص ولغة الدراسة والوثائق.',
			'about_v3_title'     => 'مرافقة حتى الاستقرار',
			'about_v3_text'      => 'من الترجمة إلى المطار في موسكو أو سانت بطرسبرغ — فريق واحد يرد عليكم.',
			'services_page_eye'  => 'مرافقة كاملة',
			'services_page_h1'   => 'خدمات لایف روس',
			'services_page_lead' => 'القبول والسنة التحضيرية والتأشيرة والسكن وترجمة الوثائق والاستقبال في المطار — دون بيع سلع.',
			'uni_page_eye'       => 'Knowledge · Opportunity',
			'uni_page_h1'        => 'أبرز جامعات روسيا',
			'uni_page_lead'      => 'جامعات حكومية وطبية يكثر اختيارها لدى المتقدمين الذين نستشيرهم.',
			'costs_page_eye'     => 'قرار واعٍ',
			'costs_page_h1'      => 'تكلفة الدراسة في روسيا',
			'costs_page_lead'    => 'الأرقام أدناه متوسطات العام الحالي وتصبح دقيقة بعد اختيار التخصص واللغة والمدينة.',
			'contact_page_eye'   => 'ابدأ اليوم',
			'contact_page_h1'    => 'استشارة مجانية للدراسة في روسيا',
			'contact_page_lead'  => 'املأ النموذج أو راسلنا عبر واتساب أو تيليغرام أو إنستغرام.',
			'contact_form_title' => 'إرسال رسالة',
			'contact_form_intro' => 'اترك الاسم والهاتف والرسالة وسيتواصل الدعم معك.',
			'contact_message_label' => 'الرسالة',
			'contact_message_ph' => 'رسالتك',
			'contact_form_success' => 'تم حفظ رسالتك. سنتواصل معك قريباً.',
			'faq_q1'             => 'كم تكلفة الدراسة في روسيا؟',
			'faq_a1'             => 'الرسوم عادةً ٢٠٠٠ إلى ٧٠٠٠ دولار سنوياً؛ السكن ٢٠ إلى ٥٠ دولاراً شهرياً والمعيشة نحو ٢٠٠ إلى ٤٠٠ دولار.',
			'faq_q2'             => 'هل أحتاج السنة التحضيرية (بادفاك) للدراسة في روسيا؟',
			'faq_a2'             => 'البرامج باللغة الروسية تحتاج عادةً إلى بادفاك. البرامج بالإنجليزية غالباً بدون بادفاك.',
			'faq_q3'             => 'ماذا تقدم لایف روس؟',
			'faq_a3'             => 'القبول الجامعي والسنة التحضيرية والتأشيرة والسكن وترجمة الوثائق والاستقبال في المطار — دون بيع سلع.',
			'service_type'       => 'استشارات الدراسة والقبول في روسيا',
			'lang_switcher'      => 'اللغة',
			'float_open'         => 'طرق التواصل',
			'float_close'        => 'إغلاق طرق التواصل',
			'float_dialog'       => 'طرق التواصل',
			'bottom_nav_aria'    => 'تنقل سريع',
		),
	);
}

/**
 * Language switcher markup.
 *
 * @param string $placement header|footer.
 */
function liferuss_language_switcher( $placement = 'header' ) {
	$current = liferuss_current_lang();
	$class   = 'lang-switch lang-switch--' . sanitize_html_class( $placement );
	echo '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( liferuss_t( 'lang_switcher' ) ) . '">';
	echo '<ul>';
	foreach ( liferuss_languages() as $code => $meta ) {
		$url   = liferuss_switch_url( $code );
		$is    = $code === $current;
		$label = $meta['short'] . ' ' . $meta['native'];
		printf(
			'<li><a href="%s" hreflang="%s" lang="%s"%s><span class="lang-code">%s</span><span class="lang-name">%s</span></a></li>',
			esc_url( $url ),
			esc_attr( $meta['hreflang'] ),
			esc_attr( $meta['html_lang'] ),
			$is ? ' aria-current="true" class="is-active"' : '',
			esc_html( $meta['short'] ),
			esc_html( $meta['native'] )
		);
	}
	echo '</ul></nav>';
}

/**
 * Known page slugs → UI string keys.
 *
 * @return array<string, string>
 */
function liferuss_page_nav_keys() {
	return array(
		'home'          => 'nav_home',
		'about'         => 'nav_about',
		'universities'  => 'nav_universities',
		'services'      => 'nav_services',
		'freight'       => 'nav_freight',
		'trade'         => 'nav_trade',
		'costs'         => 'nav_costs',
		'blog'          => 'nav_blog',
		'contact'       => 'nav_contact',
	);
}

/**
 * Translate a seeded page title on the front end.
 *
 * @param string $title   Title.
 * @param int    $post_id Post ID.
 * @return string
 */
function liferuss_filter_the_title( $title, $post_id = 0 ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $title;
	}
	if ( 'fa' === liferuss_current_lang() ) {
		return $title;
	}
	$post = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		return $title;
	}
	$keys = liferuss_page_nav_keys();
	if ( isset( $keys[ $post->post_name ] ) ) {
		return liferuss_t( $keys[ $post->post_name ] );
	}
	return $title;
}
add_filter( 'the_title', 'liferuss_filter_the_title', 10, 2 );

/**
 * Translate assigned menu labels for known pages.
 *
 * @param string  $title Title.
 * @param WP_Post $item  Item.
 * @return string
 */
function liferuss_filter_menu_title( $title, $item ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $title;
	}
	if ( 'page' === $item->object ) {
		return liferuss_filter_the_title( $title, (int) $item->object_id );
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'liferuss_filter_menu_title', 10, 2 );

/**
 * Prefix front-end permalinks (not admin / REST / feeds).
 *
 * @param string $url URL.
 * @return string
 */
function liferuss_filter_public_link( $url ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $url;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $url;
	}
	$lang = liferuss_current_lang();
	if ( 'fa' === $lang ) {
		return $url;
	}
	return liferuss_localize_url( $url, $lang );
}
add_filter( 'page_link', 'liferuss_filter_public_link' );
add_filter( 'post_link', 'liferuss_filter_public_link' );
add_filter( 'post_type_link', 'liferuss_filter_public_link' );
add_filter( 'term_link', 'liferuss_filter_public_link' );
add_filter( 'year_link', 'liferuss_filter_public_link' );
add_filter( 'month_link', 'liferuss_filter_public_link' );
add_filter( 'day_link', 'liferuss_filter_public_link' );
add_filter( 'author_link', 'liferuss_filter_public_link' );

/**
 * Keep language prefix on canonical URLs.
 *
 * @param string $url URL.
 * @return string
 */
function liferuss_filter_canonical( $url ) {
	return liferuss_localize_url( $url, liferuss_current_lang() );
}
add_filter( 'get_canonical_url', 'liferuss_filter_canonical' );
add_filter( 'wpseo_canonical', 'liferuss_filter_canonical' );

/**
 * Stop canonical redirect from dropping /en|/ru|/ar.
 *
 * @param string|false $redirect Redirect URL.
 * @return string|false
 */
function liferuss_filter_redirect_canonical( $redirect ) {
	if ( ! $redirect || 'fa' === liferuss_current_lang() ) {
		return $redirect;
	}
	return liferuss_localize_url( $redirect, liferuss_current_lang() );
}
add_filter( 'redirect_canonical', 'liferuss_filter_redirect_canonical' );

/**
 * Redirect ?lang=xx to the pretty prefix.
 */
function liferuss_redirect_query_lang() {
	if ( is_admin() || ! isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$lang = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! liferuss_is_valid_lang( $lang ) ) {
		return;
	}
	$target = liferuss_url( liferuss_current_path(), $lang );
	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'liferuss_redirect_query_lang', 0 );

/**
 * Body class for the active language and direction.
 *
 * @param array $classes Classes.
 * @return array
 */
function liferuss_language_body_class( $classes ) {
	$lang = liferuss_current_lang();
	$meta = liferuss_languages()[ $lang ];
	$classes[] = 'lang-' . $lang;
	$classes[] = 'dir-' . $meta['dir'];
	return $classes;
}
add_filter( 'body_class', 'liferuss_language_body_class' );

/**
 * Keep the search form on the same language home.
 *
 * @param string $form Markup.
 * @return string
 */
function liferuss_filter_search_form( $form ) {
	$lang = liferuss_current_lang();
	if ( 'fa' === $lang ) {
		return $form;
	}
	$from = preg_quote( esc_url( home_url( '/' ) ), '#' );
	$to   = esc_url( liferuss_home( $lang ) );
	return preg_replace( '#action="' . $from . '"#', 'action="' . $to . '"', $form );
}
add_filter( 'get_search_form', 'liferuss_filter_search_form' );
