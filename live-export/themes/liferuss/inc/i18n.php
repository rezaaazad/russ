<?php
/**
 * Persian-only front helpers. Old /ru, /ar, and /en URLs redirect here.
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
	);
}

/**
 * Whether a language code is supported.
 *
 * @param string $lang Code.
 * @return bool
 */
function liferuss_is_valid_lang( $lang ) {
	return 'fa' === $lang;
}

/**
 * Send /ru, /ar, and /en, with or without a path, to the Persian URL.
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

	if ( preg_match( '#^/(en|ru|ar)(?:/(.*))?$#', $rel, $match ) ) {
		$rest   = ( isset( $match[2] ) && '' !== $match[2] ) ? '/' . ltrim( $match[2], '/' ) : '/';
		$target = home_url( $rest );
		if ( $query ) {
			$target .= '?' . $query;
		}
		wp_safe_redirect( $target, 301 );
		exit;
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
	unset( $lang );
	$path = '/' . ltrim( (string) $path, '/' );
	if ( '/freight' === untrailingslashit( $path ) ) {
		$path = '/cargo/';
	}
	if ( '' === $path || '/index.php' === $path ) {
		$path = '/';
	}
	$home = untrailingslashit( home_url( '/' ) );
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
	if ( function_exists( 'liferuss_ui_extra' ) ) {
		$extra = liferuss_ui_extra();
		if ( isset( $extra[ $lang ][ $key ] ) && '' !== $extra[ $lang ][ $key ] ) {
			return $extra[ $lang ][ $key ];
		}
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
			'search_open'        => 'جستجو',
			'search_close'       => 'بستن جستجو',
			'submenu_toggle'     => 'باز و بسته کردن زیرمنو',
			'nav_home'           => 'خانه',
			'nav_about'          => 'درباره ما',
			'nav_universities'   => 'دانشگاه‌ها',
			'nav_services'       => 'خدمات',
			'nav_costs'          => 'هزینه‌ها',
			'nav_blog'           => 'وبلاگ',
			'nav_freight'        => 'باربری و ارسال',
			'nav_trade'          => 'تجارت و تأمین',
			'nav_contact'        => 'تماس با ما',
			'nav_account'        => 'حساب من',
			'nav_study'          => 'تحصیل در روسیه',
			'nav_scholarships'   => 'بورسیه‌ها',
			'nav_podfak'         => 'پادفک',
			'nav_direct'         => 'پذیرش مستقیم',
			'nav_immigration'    => 'مهاجرت',
			'nav_admission'      => 'درخواست پذیرش',
			'nav_exchange'       => 'استعلام نرخ',
			'nav_cargo'          => 'کارگو',
			'nav_guide'          => 'راهنمای روسیه',
			'nav_cities'         => 'شهرها',
			'nav_study_menu'     => 'تحصیل',
			'nav_language'       => 'زبان',
			'nav_academy'        => 'آکادمی',
			'nav_language_course'=> 'آموزش زبان روسی',
			'nav_migration'      => 'مهاجرت',
			'nav_magazine'       => 'مجله',
			'nav_fields'         => 'رشته‌ها',
			'nav_compare'        => 'مقایسه',
			'reading_min'        => 'دقیقه',
			'toc'                => 'فهرست',
			'author_fallback'    => 'تیم محتوای لایف روس',
			'related_posts'      => 'نوشته‌های مرتبط',
			'magazine_cta'       => 'ثبت درخواست',
			'path_home_title'    => 'مسیرهای تحصیل و مهاجرت',
			'path_live_title'    => 'از کاتالوگ دانشگاه',
			'path_prep_title'    => 'دانشگاه‌ها، مدت و شهریه',
			'path_empty'         => 'موردی در کاتالوگ منتشر نشده است.',
			'path_count'         => 'دانشگاه',
			'path_tuition'       => 'شهریه (دلار)',
			'path_months'        => 'ماه',
			'path_all_unis'      => 'فهرست دانشگاه‌ها',
			'path_faq'           => 'پرسش‌های متداول',
			'path_quotes'        => 'تجربه دانشجویان',
			'path_guides'        => 'دانستنی‌های روسیه',
			'path_posts'         => 'از مجله',
			'path_scholarship_lead' => 'بورسیه‌ها و سهمیه‌هایی که منبع و مهلت‌شان بررسی شده است. شرایط هر سال عوض می‌شود؛ برای پروندهٔ خودتان فرم مشاوره را بفرستید.',
			'path_deadline'      => 'مهلت',
			'path_coverage'      => 'پوشش',
			'path_eligibility'   => 'شرایط',
			'path_source'        => 'منبع',
			'path_verified'      => 'آخرین بررسی',
			'form_admission_title' => 'درخواست پذیرش',
			'form_admission_intro' => 'نام، شماره و مقطع را بفرستید تا درخواست پذیرش ثبت شود.',
			'form_exchange_title'  => 'استعلام نرخ',
			'form_exchange_intro'  => 'فقط استعلام نرخ. مبلغ و ارز مبدأ و مقصد را بنویسید.',
			'form_immigration_title' => 'مشاوره مهاجرت',
			'form_immigration_intro' => 'نام و شماره را بفرستید تا درخواست مهاجرت ثبت شود.',
			'form_imm_type'      => 'نوع درخواست',
			'form_nationality'   => 'ملیت',
			'form_current_city'  => 'شهر فعلی',
			'form_visa_status'   => 'وضعیت ویزا',
			'form_visa_expiry'   => 'تاریخ انقضا',
			'form_documents'     => 'مدارک',
			'form_amount'        => 'مبلغ',
			'form_currency_from' => 'از ارز',
			'form_currency_to'   => 'به ارز',
			'form_notes'         => 'توضیح',
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
			'uni_page_eye'       => 'دانش و فرصت',
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
	);
}

/**
 * Old switcher call. The site is Persian only, so this prints nothing.
 *
 * @param string $placement Unused.
 */
function liferuss_language_switcher( $placement = 'header' ) {
	unset( $placement );
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
		'cargo'         => 'nav_cargo',
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
	if ( 'custom' === $item->type && function_exists( 'liferuss_path_links' ) ) {
		$path = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
		if ( preg_match( '#^/(en|ru|ar)(/.*)?$#', $path, $match ) ) {
			$path = isset( $match[2] ) && '' !== $match[2] ? untrailingslashit( $match[2] ) : '';
		}
		$known = liferuss_path_links();
		if ( function_exists( 'liferuss_service_home_links' ) ) {
			$known = array_merge( $known, liferuss_service_home_links() );
		}
		foreach ( $known as $link ) {
			if ( untrailingslashit( $link['url'] ) === $path ) {
				return $link['title'];
			}
		}
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
	if ( ! in_array( $lang, array( 'fa', 'en', 'ru', 'ar' ), true ) ) {
		return;
	}
	wp_safe_redirect( remove_query_arg( 'lang' ), 301 );
	exit;
}
add_action( 'template_redirect', 'liferuss_redirect_query_lang', 0 );

/**
 * Body class for the active language and direction.
 *
 * WordPress adds `rtl` whenever the site locale is RTL (Persian), even on
 * /ru/ and /en/. Strip that on LTR theme languages so chrome is not flipped.
 *
 * @param array $classes Classes.
 * @return array
 */
function liferuss_language_body_class( $classes ) {
	$lang = liferuss_current_lang();
	$meta = liferuss_languages()[ $lang ];
	$dir  = isset( $meta['dir'] ) ? $meta['dir'] : 'rtl';

	$classes   = array_values( array_diff( (array) $classes, array( 'rtl', 'ltr', 'dir-rtl', 'dir-ltr' ) ) );
	$classes[] = 'lang-' . $lang;
	$classes[] = 'dir-' . $dir;
	$classes[] = ( 'ltr' === $dir ) ? 'ltr' : 'rtl';

	return $classes;
}
add_filter( 'body_class', 'liferuss_language_body_class', 99 );

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
