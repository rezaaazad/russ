<?php
/**
 * Default Theme Options for لایف روس.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full default options tree.
 *
 * @return array
 */
function liferuss_default_options() {
	$core = array(
		'brand_name'     => 'لایف روس',
		'brand_name_en'  => 'LifeRuss',
		'tagline'        => 'مشاوره تحصیل، پذیرش و استقرار در روسیه',
		'logo_id'        => 0,
		'favicon_id'     => 0,
		'color_primary'  => '#0b2341',
		'color_secondary'=> '#e8b923',

		'header_cta_text'  => 'دریافت مشاوره رایگان',
		'header_cta_link'  => '#consultation',
		'header_phone'     => '+98 21 9100 2450',
		'header_show_phone'=> '0',

		'hero_eyebrow'      => 'Higher Education · A Brighter Tomorrow',
		'hero_headline'     => 'تحصیل در روسیه، شروع آینده‌ای روشن',
		'hero_subheadline'  => 'از پذیرش تخصصی تا ویزا، خوابگاه و مشاوره استقرار در کنار شما هستیم تا مسیر تحصیل در دانشگاه‌های برتر روسیه را با اطمینان طی کنید.',
		'hero_cta1_text'    => 'دریافت مشاوره رایگان',
		'hero_cta1_link'    => '#consultation',
		'hero_cta2_text'    => 'خدمات ما',
		'hero_cta2_link'    => '#services',
		'hero_quote'        => 'آینده‌ای روشن از همین‌جا شروع می‌شود.',
		'hero_quote_cite'   => 'Knowledge Bridge',
		'hero_bg_id'        => 0,
		'hero_image_id'     => 0,
		'hero_student_id'   => 0,
		'trust'             => array(
			array( 'title' => '+۵۰۰ دانشجوی موفق', 'text' => 'پذیرش و استقرار در دانشگاه‌های روسیه', 'icon' => 'users' ),
			array( 'title' => 'پشتیبانی کامل', 'text' => 'از اولین قدم تا استقرار', 'icon' => 'shield' ),
			array( 'title' => 'مشاوره تخصصی', 'text' => 'توسط تیم مجرب اعزام دانشجو', 'icon' => 'chat' ),
			array( 'title' => 'پاسخگویی سریع', 'text' => 'در همه روزهای هفته', 'icon' => 'bolt' ),
		),

		'services_enabled'  => '1',
		'services_eyebrow'  => 'از پذیرش تا استقرار',
		'services_title'    => 'خدمات ما',
		'services_subtitle' => 'تمام مراحل تحصیل در روسیه را یک‌جا و شفاف همراهی می‌کنیم.',
		'services'          => array(
			array( 'enabled' => '1', 'title' => 'پذیرش تحصیلی', 'text' => 'اخذ پذیرش از دانشگاه‌های دولتی، پزشکی و فنی روسیه مطابق رشته و بودجه شما.', 'icon' => 'cap', 'image_id' => 0 ),
			array( 'enabled' => '1', 'title' => 'پادفک', 'text' => 'ثبت‌نام دوره آمادگی زبان روسی و دروس پیش‌نیاز قبل از ورود به رشته اصلی.', 'icon' => 'book', 'image_id' => 0 ),
			array( 'enabled' => '1', 'title' => 'ویزا', 'text' => 'پیگیری دعوتنامه، وقت سفارت و ویزای تحصیلی تا صدور مهر ورود.', 'icon' => 'passport', 'image_id' => 0 ),
			array( 'enabled' => '1', 'title' => 'خوابگاه', 'text' => 'رزرو خوابگاه دانشجویی نزدیک دانشگاه و معرفی گزینه‌های اقامت مطمئن.', 'icon' => 'home', 'image_id' => 0 ),
			array( 'enabled' => '1', 'title' => 'ترجمه مدارک', 'text' => 'ترجمه رسمی، تأیید و آماده‌سازی پرونده تحصیلی طبق استاندارد دانشگاه.', 'icon' => 'docs', 'image_id' => 0 ),
			array( 'enabled' => '1', 'title' => 'استقبال فرودگاه', 'text' => 'استقبال در فرودگاه، ترانسفر، افتتاح حساب و همراهی روزهای اول استقرار.', 'icon' => 'plane', 'image_id' => 0 ),
		),

		'universities_eyebrow'  => 'Knowledge · Opportunity',
		'universities_title'    => 'دانشگاه‌های برتر روسیه',
		'universities_subtitle' => 'پذیرش از دانشگاه‌های دولتی و پزشکی معتبر برای متقاضیان ایرانی.',
		'universities_link_text'=> 'مشاهده همه دانشگاه‌ها',
		'universities'          => array(
			array( 'name' => 'دانشگاه دولتی مسکو', 'latin' => 'MSU', 'city' => 'مسکو', 'rank' => 'رتبه جهانی ۸۷', 'focus' => 'علوم پایه، پزشکی و مهندسی', 'image' => 'universities/msu.jpg', 'image_id' => 0, 'link' => '' ),
			array( 'name' => 'دانشگاه سنت‌پترزبورگ', 'latin' => 'SPBU', 'city' => 'سن‌پترزبورگ', 'rank' => 'رتبه جهانی ۳۱۵', 'focus' => 'علوم انسانی، حقوق و پزشکی', 'image' => 'universities/spbu.jpg', 'image_id' => 0, 'link' => '' ),
			array( 'name' => 'دانشگاه عالی اقتصاد', 'latin' => 'HSE', 'city' => 'مسکو', 'rank' => 'رتبه جهانی ۳۲۰', 'focus' => 'اقتصاد، مدیریت و علوم اجتماعی', 'image' => 'universities/hse.jpg', 'image_id' => 0, 'link' => '' ),
			array( 'name' => 'دانشگاه پزشکی سچنوف', 'latin' => 'Sechenov', 'city' => 'مسکو', 'rank' => 'رتبه جهانی ۴۰۱', 'focus' => 'پزشکی، دندانپزشکی و داروسازی', 'image' => 'universities/sechenov.jpg', 'image_id' => 0, 'link' => '' ),
			array( 'name' => 'دانشگاه دوستی ملل', 'latin' => 'RUDN', 'city' => 'مسکو', 'rank' => 'رتبه جهانی ۳۶۰', 'focus' => 'پزشکی و رشته‌های بین‌المللی', 'image' => 'universities/rudn.jpg', 'image_id' => 0, 'link' => '' ),
			array( 'name' => 'دانشگاه فنی باومان', 'latin' => 'Bauman', 'city' => 'مسکو', 'rank' => 'رتبه جهانی ۲۸۲', 'focus' => 'مهندسی مکانیک و هوافضا', 'image' => 'universities/bauman.jpg', 'image_id' => 0, 'link' => '' ),
		),

		'majors_eyebrow'  => 'انتخاب مسیر',
		'majors_title'    => 'رشته‌های محبوب',
		'majors_subtitle' => 'از میان پرتقاضاترین رشته‌های دانشجویان ایرانی در روسیه.',
		'majors'          => array(
			array( 'title' => 'پزشکی', 'icon' => 'medicine', 'link' => '#consultation' ),
			array( 'title' => 'دندانپزشکی', 'icon' => 'dental', 'link' => '#consultation' ),
			array( 'title' => 'داروسازی', 'icon' => 'pharma', 'link' => '#consultation' ),
			array( 'title' => 'مهندسی', 'icon' => 'engineer', 'link' => '#consultation' ),
			array( 'title' => 'هنر', 'icon' => 'art', 'link' => '#consultation' ),
			array( 'title' => 'زبان روسی', 'icon' => 'language', 'link' => '#consultation' ),
		),

		'costs_eyebrow'  => 'شفاف و به‌روز',
		'costs_title'    => 'هزینه و شرایط',
		'costs_subtitle' => 'اعداد تقریبی سال جاری؛ رقم دقیق پس از انتخاب رشته و شهر مشخص می‌شود.',
		'costs'          => array(
			array( 'title' => 'هزینه زندگی', 'value' => 'ماهانه ۲۰۰ تا ۴۰۰ دلار', 'note' => 'بسته به شهر', 'icon' => 'wallet' ),
			array( 'title' => 'هزینه خوابگاه', 'value' => 'ماهانه ۲۰ تا ۵۰ دلار', 'note' => 'خوابگاه دانشجویی', 'icon' => 'bed' ),
			array( 'title' => 'شهریه دانشگاه', 'value' => 'سالانه ۲۰۰۰ تا ۷۰۰۰ دلار', 'note' => 'بسته به رشته و دانشگاه', 'icon' => 'tuition' ),
			array( 'title' => 'مدارک ترجمه', 'value' => 'دیپلم، ریزنمرات و گذرنامه', 'note' => 'ترجمه رسمی و تأیید', 'icon' => 'stamp' ),
			array( 'title' => 'زبان تحصیل', 'value' => 'روسی یا انگلیسی', 'note' => 'با امکان دوره پادفک', 'icon' => 'speech' ),
		),

		'roadmap_eyebrow'  => 'مسیر روشن',
		'roadmap_title'    => 'مراحل پذیرش و استقرار در روسیه',
		'roadmap_subtitle' => 'شش گام مشخص؛ هر مرحله مسئول مشخص و زمان‌بندی شفاف دارد.',
		'roadmap'          => array(
			array( 'num' => '۱', 'title' => 'مشاوره', 'text' => 'بررسی هدف، معدل و بودجه در جلسه رایگان.' ),
			array( 'num' => '۲', 'title' => 'انتخاب مسیر', 'text' => 'انتخاب دانشگاه، رشته و زبان تحصیل.' ),
			array( 'num' => '۳', 'title' => 'جمع‌آوری مدارک', 'text' => 'ترجمه، تأیید و تکمیل پرونده پذیرش.' ),
			array( 'num' => '۴', 'title' => 'پذیرش', 'text' => 'ارسال پرونده و دریافت نامه پذیرش.' ),
			array( 'num' => '۵', 'title' => 'ویزا', 'text' => 'دعوتنامه، انگشت‌نگاری و ویزای تحصیلی.' ),
			array( 'num' => '۶', 'title' => 'سفر و استقرار', 'text' => 'استقبال فرودگاه، خوابگاه و ثبت‌نام.' ),
		),

		'testimonials_eyebrow'  => 'صدای دانشجویان',
		'testimonials_title'    => 'تجربه دانشجویان',
		'testimonials_subtitle' => 'اعتماد بیش از ۵۰۰ دانشجویی که مسیر روسیه را با ما تمام کرده‌اند.',
		'testimonials'          => array(
			array( 'name' => 'غزاله محمدی', 'meta' => 'پزشکی — سچنوف', 'quote' => 'از ترجمه مدارک تا خوابگاه مسکو همراهم بودند. مسیر پذیرش شفاف بود و هیچ مرحله‌ای را تنها نماندم.', 'image' => 'students/student-1.jpg', 'image_id' => 0, 'rating' => '5' ),
			array( 'name' => 'سارا نعمتی', 'meta' => 'دندانپزشکی — سن‌پترزبورگ', 'quote' => 'مشاوره‌شان واقع‌بینانه بود؛ دانشگاه و شهریه را دقیق توضیح دادند و ویزا بدون رفت‌وبرگشت اضافه صادر شد.', 'image' => 'students/student-2.jpg', 'image_id' => 0, 'rating' => '5' ),
			array( 'name' => 'محمد رضایی', 'meta' => 'مهندسی — باومان', 'quote' => 'استقبال فرودگاه و ثبت‌نام دانشگاه را خودشان هماهنگ کردند. برای خانواده ما این همراهی خیلی اطمینان‌بخش بود.', 'image' => 'students/student-3.jpg', 'image_id' => 0, 'rating' => '5' ),
		),
		'stat_value' => '+۵۰۰',
		'stat_text'  => 'دانشجوی موفق در مسیر تحصیل و استقرار در روسیه',

		'form_eyebrow'     => 'مشاوره رایگان',
		'form_title'       => 'قدم اول را با ما بردارید',
		'form_intro'       => 'نام، شماره و مقطع را بفرستید تا مشاور لایف روس مسیر پذیرش، هزینه و دانشگاه مناسب را برایتان روشن کند.',
		'form_name_label'  => 'نام و نام خانوادگی',
		'form_name_ph'     => 'مثلاً سارا احمدی',
		'form_phone_label' => 'شماره تماس',
		'form_phone_ph'    => '۰۹۱۲xxxxxxx',
		'form_level_label' => 'مقطع تحصیلی',
		'form_submit'      => 'دریافت مشاوره رایگان',
		'form_note'        => 'اطلاعات شما فقط برای تماس مشاور استفاده می‌شود.',
		'form_success'     => 'درخواست شما ثبت شد. مشاوران لایف روس در کوتاه‌ترین زمان با شما تماس می‌گیرند.',
		'form_email'       => 'info@liferuss.com',
		'form_image_id'    => 0,

		'phone'      => '+98 21 9100 2450',
		'phone_alt'  => '+7 495 120 3344',
		'email'      => 'info@liferuss.com',
		'address'    => 'تهران، خیابان ولیعصر — دفتر مسکو، خیابان تورسکایا',
		'whatsapp'   => '+989120000000',
		'telegram'   => 'https://t.me/liferuss',
		'instagram'  => 'https://instagram.com/liferuss',
		'linkedin'   => 'https://www.linkedin.com/company/liferuss',
		'youtube'    => 'https://www.youtube.com/@liferuss',

		'footer_about'     => 'مشاوره تحصیل در روسیه؛ از انتخاب دانشگاه تا پذیرش، ویزا و استقرار. مسیر روشن برای آینده‌ای مطمئن.',
		'footer_copyright' => '© {year} لایف روس · liferuss.com — همه حقوق محفوظ است.',
		'footer_en'        => 'Knowledge Bridge · Higher Education · A Brighter Tomorrow',
		'footer_links'     => array(
			array( 'label' => 'خانه', 'url' => '/' ),
			array( 'label' => 'درباره ما', 'url' => '/about/' ),
			array( 'label' => 'دانشگاه‌ها', 'url' => '/universities/' ),
			array( 'label' => 'خدمات', 'url' => '/services/' ),
			array( 'label' => 'باربری و ارسال', 'url' => '/freight/' ),
			array( 'label' => 'تجارت و تأمین', 'url' => '/trade/' ),
			array( 'label' => 'هزینه‌ها', 'url' => '/costs/' ),
			array( 'label' => 'تماس با ما', 'url' => '/contact/' ),
		),

		'seo_title'       => 'لایف روس | مشاوره تحصیل و پذیرش در روسیه',
		'seo_description' => 'لایف روس (LifeRuss) مؤسسه مشاوره تحصیل در روسیه است. پذیرش، پادفک، ویزا، خوابگاه، ترجمه مدارک و استقرار — liferuss.com',
		'og_image_id'     => 0,
		'org_name'        => 'لایف روس',
		'org_legal'       => 'LifeRuss',
		'org_url'         => 'https://liferuss.com',
		'i18n'            => array(
			'en' => array(),
			'ru' => array(),
			'ar' => array(),
		),
	);

	if ( function_exists( 'liferuss_landing_default_options' ) ) {
		return array_merge( $core, liferuss_landing_default_options() );
	}
	return $core;
}
