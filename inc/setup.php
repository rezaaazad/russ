<?php
/**
 * First-activation pages, menu, and reading settings.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seed demo pages and menu once.
 */
function liferuss_maybe_seed() {
	if ( get_option( 'liferuss_seeded' ) ) {
		return;
	}

	$home_id = liferuss_ensure_page(
		'خانه',
		'home',
		'',
		''
	);
	$about_id = liferuss_ensure_page(
		'درباره ما',
		'about',
		liferuss_about_content(),
		'templates/about.php'
	);
	$uni_id = liferuss_ensure_page(
		'دانشگاه‌ها',
		'universities',
		'<p>دانشگاه‌های منتخب لایف روس برای متقاضیان ایرانی.</p>',
		'templates/universities.php'
	);
	$svc_id = liferuss_ensure_page(
		'خدمات',
		'services',
		'<p>خدمات پذیرش، پادفک، ویزا، خوابگاه، ترجمه و استقرار.</p>',
		'templates/services.php'
	);
	$cost_id = liferuss_ensure_page(
		'هزینه‌ها',
		'costs',
		'<p>تصویر شفاف از شهریه، زندگی و خوابگاه در روسیه.</p>',
		'templates/costs.php'
	);
	$contact_id = liferuss_ensure_page(
		'تماس با ما',
		'contact',
		'<p>فرم مشاوره رایگان لایف روس.</p>',
		'templates/contact.php'
	);
	$freight_id = liferuss_ensure_page(
		'باربری و ارسال',
		'freight',
		'<p>ارسال کارگو، نمونه کالا، وسایل شخصی و مدارک دانشجویی بین ایران و روسیه.</p>',
		'templates/freight.php'
	);
	$trade_id = liferuss_ensure_page(
		'تجارت و تأمین کالا',
		'trade',
		'<p>سورسینگ، خرید از روسیه و صادرات محصولات ایران.</p>',
		'templates/trade.php'
	);
	$blog_id = liferuss_ensure_page(
		'وبلاگ',
		'blog',
		'',
		''
	);

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
	update_option( 'page_for_posts', $blog_id );

	$menu_name = 'منوی اصلی لایف روس';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : 0;

	// Prefer an already-assigned primary menu so we never create a second nav source.
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! empty( $locations['primary'] ) ) {
		$assigned = wp_get_nav_menu_object( (int) $locations['primary'] );
		if ( $assigned ) {
			$menu_id = (int) $assigned->term_id;
		}
	}

	if ( ! $menu_id ) {
		$menu_id = (int) wp_create_nav_menu( $menu_name );
	}

	$existing_items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	$item_count     = is_array( $existing_items ) ? count( $existing_items ) : 0;

	// Only seed when the menu is completely empty — never re-add beside existing items.
	if ( $menu_id && 0 === $item_count ) {
		$items = array(
			array( 'title' => 'خانه', 'object' => $home_id ),
			array( 'title' => 'درباره ما', 'object' => $about_id ),
			array( 'title' => 'دانشگاه‌ها', 'object' => $uni_id ),
			array( 'title' => 'خدمات', 'object' => $svc_id ),
			array( 'title' => 'باربری و ارسال', 'object' => $freight_id ),
			array( 'title' => 'تجارت و تأمین', 'object' => $trade_id ),
			array( 'title' => 'هزینه‌ها', 'object' => $cost_id ),
			array( 'title' => 'وبلاگ', 'object' => $blog_id ),
			array( 'title' => 'تماس با ما', 'object' => $contact_id ),
		);
		$order = 1;
		foreach ( $items as $item ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item['object'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $order,
				)
			);
			++$order;
		}
	}

	$locations            = is_array( $locations ) ? $locations : array();
	$locations['primary'] = $menu_id;
	$locations['footer']  = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	liferuss_seed_posts();

	update_option( 'liferuss_seeded', 1 );
}
add_action( 'after_switch_theme', 'liferuss_maybe_seed' );
add_action( 'init', 'liferuss_maybe_seed', 30 );

/**
 * One-time rebrand of seeded content to لایف روس.
 */
function liferuss_maybe_rebrand() {
	if ( get_option( 'liferuss_brand_v2' ) ) {
		return;
	}
	update_option( 'blogname', 'لایف روس' );
	update_option( 'blogdescription', 'مشاوره تحصیل و پذیرش در روسیه' );

	update_option( 'liferuss_brand_v2', 1 );
}
add_action( 'init', 'liferuss_maybe_rebrand', 40 );


/**
 * One-time cleanup: remove duplicate primary-menu items (same object_id / title).
 */
function liferuss_maybe_dedupe_primary_menu() {
	if ( get_option( 'liferuss_menu_deduped_v1' ) ) {
		return;
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		update_option( 'liferuss_menu_deduped_v1', 1 );
		return;
	}

	$menu_id = (int) $locations['primary'];
	$items   = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) || count( $items ) < 2 ) {
		update_option( 'liferuss_menu_deduped_v1', 1 );
		return;
	}

	$seen = array();
	foreach ( $items as $item ) {
		$key = $item->type . ':' . (string) $item->object_id . ':' . mb_strtolower( trim( (string) $item->title ) );
		if ( isset( $seen[ $key ] ) ) {
			wp_delete_post( (int) $item->ID, true );
			continue;
		}
		$seen[ $key ] = true;
	}

	update_option( 'liferuss_menu_deduped_v1', 1 );
}
add_action( 'init', 'liferuss_maybe_dedupe_primary_menu', 45 );


/**
 * Flush rewrite rules once after multilingual 1.2.0.
 */
function liferuss_maybe_flush_i18n() {
	if ( get_option( 'liferuss_version' ) === LIFERUSS_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'liferuss_version', LIFERUSS_VERSION );
}
add_action( 'init', 'liferuss_maybe_flush_i18n', 50 );

/**
 * Create a page if the slug is free.
 *
 * @param string $title    Title.
 * @param string $slug     Slug.
 * @param string $content  Content.
 * @param string $template Relative template path.
 * @return int
 */
function liferuss_ensure_page( $title, $slug, $content, $template ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		if ( $template ) {
			update_post_meta( $existing->ID, '_wp_page_template', $template );
		}
		return (int) $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
		)
	);

	if ( $page_id && $template ) {
		update_post_meta( $page_id, '_wp_page_template', $template );
	}

	return (int) $page_id;
}

/**
 * Seed a few educational posts.
 */
function liferuss_seed_posts() {
	if ( get_option( 'liferuss_posts_seeded' ) ) {
		return;
	}

	$posts = array(
		array(
			'title'   => 'پادفک چیست و چه کسانی به آن نیاز دارند؟',
			'content' => '<p>دوره پادفک مسیر آمادگی زبان روسی و دروس پایه است. بیشتر متقاضیان رشته‌های پزشکی و فنی که می‌خواهند به زبان روسی درس بخوانند، یک سال پادفک می‌گذرانند و سپس وارد رشته اصلی می‌شوند.</p><p>لایف روس دانشگاه، شهر و برنامه پادفک را بر اساس هدف تحصیلی شما پیشنهاد می‌کند و ثبت‌نام تا استقرار را همراهی می‌کند.</p>',
		),
		array(
			'title'   => 'هزینه تحصیل پزشکی در روسیه در یک نگاه',
			'content' => '<p>شهریه پزشکی در دانشگاه‌های دولتی معمولاً بین ۳۵۰۰ تا ۷۰۰۰ دلار در سال است. خوابگاه دانشجویی اغلب کمتر از ۵۰ دلار در ماه تمام می‌شود و هزینه زندگی دانشجویی در شهرهای بزرگ حدود ۲۰۰ تا ۴۰۰ دلار است.</p><p>برای عدد دقیق، رشته، زبان تدریس و شهر باید مشخص شود؛ فرم مشاوره همین کار را برای شما انجام می‌دهد.</p>',
		),
		array(
			'title'   => 'مدارک لازم برای پذیرش و ویزای تحصیلی روسیه',
			'content' => '<p>پرونده معمول شامل گذرنامه معتبر، مدرک دیپلم و ریزنمرات، ترجمه رسمی، عکس و فرم درخواست دانشگاه است. پس از پذیرش، دعوتنامه صادر می‌شود و نوبت ویزای تحصیلی می‌رسد.</p><p>تیم لایف روس ترجمه، ارسال پرونده و پیگیری دعوتنامه را یک‌جا انجام می‌دهد.</p>',
		),
	);

	foreach ( $posts as $post ) {
		$exists = new WP_Query(
			array(
				'post_type'      => 'post',
				'title'          => $post['title'],
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		if ( $exists->have_posts() ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_title'   => $post['title'],
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_content' => $post['content'],
			)
		);
	}

	update_option( 'liferuss_posts_seeded', 1 );
}

/**
 * About page default content.
 *
 * @return string
 */
function liferuss_about_content() {
	return '<p>لایف روس یک مؤسسه مشاوره تحصیل در روسیه است؛ فروشگاه نیستیم. کار ما همراهی متقاضی ایرانی است از انتخاب رشته تا پذیرش، ویزا، خوابگاه و روزهای اول زندگی در مسکو یا سن‌پترزبورگ.</p><p>تمرکز ما روی دانشگاه‌های معتبر، مسیر شفاف هزینه‌ها و پاسخگویی واقعی است. بیش از ۵۰۰ دانشجو این مسیر را با ما طی کرده‌اند.</p>';
}
