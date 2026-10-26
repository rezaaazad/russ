<?php
/**
 * Public UX guards for theme 1.12.0: digits, demo rows, empty hubs, admin review.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra UI strings that were still falling back to Persian on /en and /ru.
 *
 * @return array<string, array<string, string>>
 */
function liferuss_ui_extra() {
	return array(
		'fa' => array(
			'account_login'      => 'ورود',
			'account_mine'       => 'حساب من',
			'account_aria_guest' => 'ورود به حساب',
			'account_aria_user'  => 'حساب کاربری',
			'catalog_empty_title'=> 'این فهرست هنوز منتشر نشده',
			'catalog_empty_lead' => 'دانشگاه‌ها و بورسیه‌ها بعد از ورود داده‌های تأییدشده اینجا دیده می‌شوند. تا آن زمان مسیر رشته، شهر و هزینه را در مشاوره رایگان مشخص کنید.',
			'catalog_empty_filter_title' => 'با این فیلتر موردی پیدا نشد',
			'catalog_empty_filter_lead'  => 'فیلتر را بردارید یا مسیر را در مشاوره مشخص کنید.',
			'catalog_empty_cta'  => 'درخواست مشاوره',
			'catalog_empty_reset'=> 'پاک کردن فیلتر',
			'drawer_consult'     => 'دریافت مشاوره رایگان',
			'file_choose'        => 'انتخاب فایل',
			'file_none'          => 'فایلی انتخاب نشده',
			'table_scroll'       => '← بکشید',
			'contact_send'       => 'ارسال پیام',
			'filter_more'        => 'فیلترها',
			'lesson_save_login'  => 'برای ذخیره پیشرفت وارد شوید',
			'plans_empty_title'  => 'طرح اشتراک فعالی نیست',
			'plans_empty_lead'   => 'تا اعلام طرح‌ها، از مشاوره یا دوره‌های منتشرشده شروع کنید.',
		),
		'en' => array(
			'path_home_title'    => 'Study and immigration paths',
			'account_login'      => 'Log in',
			'account_mine'       => 'My account',
			'account_aria_guest' => 'Log in to your account',
			'account_aria_user'  => 'Your account',
			'catalog_empty_title'=> 'This list is not published yet',
			'catalog_empty_lead' => 'Universities and scholarships appear here after confirmed data is imported. Until then, a free consultation can match a field, city, and budget.',
			'catalog_empty_filter_title' => 'Nothing matches this filter',
			'catalog_empty_filter_lead'  => 'Clear the filter, or set the path in a consultation.',
			'catalog_empty_cta'  => 'Request a consultation',
			'catalog_empty_reset'=> 'Clear filters',
			'drawer_consult'     => 'Free consultation',
			'file_choose'        => 'Choose file',
			'file_none'          => 'No file chosen',
			'table_scroll'       => 'Scroll →',
			'contact_send'       => 'Send message',
			'filter_more'        => 'Filters',
			'lesson_save_login'  => 'Log in to save progress',
			'plans_empty_title'  => 'No subscription plans yet',
			'plans_empty_lead'   => 'Until plans are published, start with a consultation or an available course.',
			'nav_podfak'         => 'Preparatory year',
			'nav_direct'         => 'Direct admission',
			'nav_immigration'    => 'Immigration',
			'nav_study'          => 'Study in Russia',
		),
		'ru' => array(
			'path_home_title'    => 'Пути учёбы и переезда',
			'account_login'      => 'Войти',
			'account_mine'       => 'Мой кабинет',
			'account_aria_guest' => 'Войти в кабинет',
			'account_aria_user'  => 'Личный кабинет',
			'catalog_empty_title'=> 'Список ещё не опубликован',
			'catalog_empty_lead' => 'Вузы и стипендии появятся здесь после загрузки проверенных данных. До этого бесплатная консультация поможет выбрать направление, город и бюджет.',
			'catalog_empty_filter_title' => 'По этому фильтру ничего нет',
			'catalog_empty_filter_lead'  => 'Сбросьте фильтр или уточните маршрут на консультации.',
			'catalog_empty_cta'  => 'Запросить консультацию',
			'catalog_empty_reset'=> 'Сбросить фильтр',
			'drawer_consult'     => 'Бесплатная консультация',
			'file_choose'        => 'Выбрать файл',
			'file_none'          => 'Файл не выбран',
			'table_scroll'       => 'Листайте →',
			'contact_send'       => 'Отправить',
			'filter_more'        => 'Фильтры',
			'lesson_save_login'  => 'Войдите, чтобы сохранить прогресс',
			'plans_empty_title'  => 'Планов подписки пока нет',
			'plans_empty_lead'   => 'Пока планы не опубликованы, начните с консультации или открытого курса.',
			'nav_podfak'         => 'Подфак',
			'nav_direct'         => 'Прямое поступление',
			'nav_immigration'    => 'Переезд',
			'nav_study'          => 'Учёба в России',
		),
		'ar' => array(
			'path_home_title'    => 'مسارات الدراسة والهجرة',
			'account_login'      => 'دخول',
			'account_mine'       => 'حسابي',
			'account_aria_guest' => 'الدخول إلى الحساب',
			'account_aria_user'  => 'حساب المستخدم',
			'catalog_empty_title'=> 'هذه القائمة غير منشورة بعد',
			'catalog_empty_lead' => 'تظهر الجامعات والمنح بعد إدخال بيانات مؤكدة. إلى ذلك الحين، تحدد الاستشارة المجانية التخصص والمدينة والميزانية.',
			'catalog_empty_filter_title' => 'لا توجد نتيجة بهذا التصفية',
			'catalog_empty_filter_lead'  => 'أزل التصفية أو حدّد المسار في الاستشارة.',
			'catalog_empty_cta'  => 'طلب استشارة',
			'catalog_empty_reset'=> 'مسح التصفية',
			'drawer_consult'     => 'استشارة مجانية',
			'file_choose'        => 'اختيار ملف',
			'file_none'          => 'لم يُختر ملف',
			'table_scroll'       => 'اسحب →',
			'contact_send'       => 'إرسال الرسالة',
			'filter_more'        => 'عوامل التصفية',
			'lesson_save_login'  => 'سجّل الدخول لحفظ التقدم',
			'plans_empty_title'  => 'لا خطط اشتراك بعد',
			'plans_empty_lead'   => 'إلى أن تُنشر الخطط، ابدأ باستشارة أو بدورة متاحة.',
			'nav_podfak'         => 'السنة التحضيرية',
			'nav_direct'         => 'قبول مباشر',
			'nav_immigration'    => 'الهجرة',
			'nav_study'          => 'الدراسة في روسيا',
		),
	);
}

/**
 * Contact keys whose packaged defaults must not render as if they were real.
 *
 * @return string[]
 */
function liferuss_placeholder_contact_keys() {
	return array(
		'phone',
		'phone_alt',
		'whatsapp',
		'telegram',
		'instagram',
		'linkedin',
		'youtube',
		'header_phone',
		'email',
		'address',
	);
}

/**
 * Packaged contact strings that must not render as if Reza had saved them.
 *
 * @return array<string, string[]>
 */
function liferuss_legacy_contact_values() {
	return array(
		'header_phone' => array( '+98 21 9100 2450' ),
		'phone'        => array( '+98 21 9100 2450' ),
		'phone_alt'    => array( '+7 495 120 3344' ),
		'email'        => array( 'info@liferuss.com' ),
		'address'      => array( 'تهران، خیابان ولیعصر — دفتر مسکو، خیابان تورسکایا' ),
		'whatsapp'     => array( '+989120000000', '989120000000' ),
		'telegram'     => array( 'https://t.me/liferuss' ),
		'instagram'    => array( 'https://instagram.com/liferuss' ),
		'linkedin'     => array( 'https://www.linkedin.com/company/liferuss' ),
		'youtube'      => array( 'https://www.youtube.com/@liferuss' ),
	);
}

/**
 * Example mobile number for placeholders. Not a published contact.
 *
 * @return string
 */
function liferuss_phone_example() {
	$lang = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	if ( 'ar' === $lang ) {
		return '٠٩١٢١٢٣٤٥٦٧';
	}
	if ( 'fa' !== $lang ) {
		return '09121234567';
	}
	return '۰۹۱۲۱۲۳۴۵۶۷';
}

/**
 * True when a contact value is empty or still a packaged sample.
 *
 * @param string $key   Option key.
 * @param mixed  $value Value.
 * @return bool
 */
function liferuss_contact_value_is_packaged( $key, $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return true;
	}
	$legacy = liferuss_legacy_contact_values();
	return isset( $legacy[ $key ] ) && in_array( $value, $legacy[ $key ], true );
}

/**
 * True when the saved theme value is still a packaged sample.
 *
 * @param string $key Option key.
 * @return bool
 */
function liferuss_contact_is_default( $key ) {
	$opts = liferuss_options();
	return liferuss_contact_value_is_packaged( $key, $opts[ $key ] ?? '' );
}

/**
 * Blank a public contact field that is still the packaged sample.
 *
 * @param mixed  $value Current value.
 * @param string $key   Option key.
 * @return mixed
 */
function liferuss_blank_default_contact( $value, $key ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $value;
	}
	if ( get_option( 'liferuss_ux_1142' ) ) {
		return $value;
	}
	if ( ! in_array( $key, liferuss_placeholder_contact_keys(), true ) ) {
		return $value;
	}
	if ( ! liferuss_contact_value_is_packaged( $key, $value ) ) {
		return $value;
	}
	return '';
}

/**
 * Core settings contact group, used when the theme field is empty.
 *
 * @param string $key Theme option key.
 * @return string
 */
function liferuss_contact_from_core( $key ) {
	if ( ! class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		return '';
	}
	$map = array(
		'phone'        => 'phone',
		'header_phone' => 'phone',
		'whatsapp'     => 'whatsapp',
		'telegram'     => 'telegram',
		'instagram'    => 'instagram',
		'email'        => 'email',
		'address'      => 'address',
	);
	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}
	$contact = \LifeRuss\Core\Settings\Settings::get( 'contact' );
	$value   = trim( (string) ( $contact[ $map[ $key ] ] ?? '' ) );
	if ( '' === $value && 'email' === $key ) {
		$general = \LifeRuss\Core\Settings\Settings::get( 'general' );
		$value   = trim( (string) ( $general['support_email'] ?? '' ) );
	}
	if ( liferuss_contact_value_is_packaged( $key, $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Contact value visitors may see: theme field, then core settings.
 *
 * @param string $key Option key.
 * @return string
 */
function liferuss_public_contact( $key ) {
	$opts  = liferuss_options();
	$value = isset( $opts[ $key ] ) ? trim( (string) $opts[ $key ] ) : '';
	if ( ! get_option( 'liferuss_ux_1142' ) && liferuss_contact_value_is_packaged( $key, $value ) ) {
		$value = '';
	}
	if ( '' === $value ) {
		$value = liferuss_contact_from_core( $key );
	}
	return $value;
}

/**
 * WhatsApp, Telegram, Instagram, phone, and email links that are actually set.
 *
 * @return array<int, array{href: string, icon: string, label: string, external: bool}>
 */
function liferuss_contact_links() {
	$links = array();
	$phone = liferuss_public_contact( 'phone' );
	$wa    = liferuss_public_contact( 'whatsapp' );
	$tg    = liferuss_social_url( liferuss_public_contact( 'telegram' ), 'telegram' );
	$ig    = liferuss_social_url( liferuss_public_contact( 'instagram' ), 'instagram' );
	$mail  = liferuss_public_contact( 'email' );
	if ( $wa && liferuss_whatsapp_url( $wa ) ) {
		$links[] = array(
			'href'     => liferuss_whatsapp_url( $wa ),
			'icon'     => 'whatsapp',
			'label'    => liferuss_t( 'channel_whatsapp' ),
			'external' => true,
		);
	}
	if ( $tg ) {
		$links[] = array(
			'href'     => $tg,
			'icon'     => 'telegram',
			'label'    => liferuss_t( 'channel_telegram' ),
			'external' => true,
		);
	}
	if ( $ig ) {
		$links[] = array(
			'href'     => $ig,
			'icon'     => 'instagram',
			'label'    => liferuss_t( 'channel_instagram' ),
			'external' => true,
		);
	}
	if ( $phone ) {
		$links[] = array(
			'href'     => 'tel:' . preg_replace( '/\s+/', '', $phone ),
			'icon'     => 'phone',
			'label'    => liferuss_t( 'channel_phone' ),
			'external' => false,
		);
	}
	if ( $mail && is_email( $mail ) ) {
		$links[] = array(
			'href'     => 'mailto:' . $mail,
			'icon'     => 'mail',
			'label'    => $mail,
			'external' => false,
		);
	}
	return $links;
}

/**
 * Channel buttons. Nothing is printed when every contact field is empty.
 */
function liferuss_the_contact_channels() {
	$links = liferuss_contact_links();
	if ( ! $links ) {
		return;
	}
	echo '<div class="consult-channels">';
	echo '<p class="screen-reader-text">' . esc_html( liferuss_t( 'channels_aria' ) ) . '</p>';
	foreach ( $links as $link ) {
		echo '<a class="channel" href="' . esc_url( $link['href'] ) . '"';
		if ( $link['external'] ) {
			echo ' target="_blank" rel="noopener noreferrer"';
		}
		echo '>';
		echo liferuss_icon( $link['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<span>' . esc_html( $link['label'] ) . '</span></a>';
	}
	echo '</div>';
}

/**
 * Contact hero sentence. Names a network only when that field is set.
 *
 * @return string
 */
function liferuss_contact_page_lead() {
	$lang  = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	$names = array(
		'fa' => array( 'whatsapp' => 'واتساپ', 'telegram' => 'تلگرام', 'instagram' => 'اینستاگرام' ),
		'en' => array( 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'instagram' => 'Instagram' ),
		'ru' => array( 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'instagram' => 'Instagram' ),
		'ar' => array( 'whatsapp' => 'واتساب', 'telegram' => 'تيليغرام', 'instagram' => 'إنستغرام' ),
	);
	$pack = $names[ $lang ] ?? $names['fa'];
	$have = array();
	foreach ( $pack as $key => $label ) {
		if ( '' !== liferuss_public_contact( $key ) ) {
			$have[] = $label;
		}
	}
	if ( ! $have ) {
		$plain = array(
			'fa' => 'فرم را پر کنید تا تیم پشتیبانی با شما تماس بگیرد.',
			'en' => 'Fill in the form and the support team will contact you.',
			'ru' => 'Заполните форму — служба поддержки свяжется с вами.',
			'ar' => 'املأ النموذج ليتواصل معك فريق الدعم.',
		);
		return $plain[ $lang ] ?? $plain['fa'];
	}
	$list = implode( 'fa' === $lang || 'ar' === $lang ? '، ' : ', ', $have );
	$with = array(
		'fa' => 'فرم را پر کنید یا از ' . $list . ' پیام بگذارید.',
		'en' => 'Fill in the form or message us on ' . $list . '.',
		'ru' => 'Заполните форму или напишите в ' . $list . '.',
		'ar' => 'املأ النموذج أو راسلنا عبر ' . $list . '.',
	);
	return $with[ $lang ] ?? $with['fa'];
}

/**
 * ASCII digits to Persian or Arabic-Indic digits.
 *
 * @param string $ascii Digits and separators.
 * @return string
 */
function liferuss_local_digits( $ascii ) {
	$ascii = (string) $ascii;
	$lang  = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	if ( 'ar' === $lang ) {
		return strtr( $ascii, array( '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩' ) );
	}
	if ( 'fa' !== $lang ) {
		return $ascii;
	}
	return strtr( $ascii, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/**
 * Localize number_format_i18n on Persian and Arabic pages.
 *
 * @param string $formatted Formatted number.
 * @return string
 */
function liferuss_filter_number_format( $formatted ) {
	$lang = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	if ( 'fa' !== $lang && 'ar' !== $lang ) {
		return $formatted;
	}
	return liferuss_local_digits( $formatted );
}
add_filter( 'number_format_i18n', 'liferuss_filter_number_format' );

/**
 * Jalali date for Persian post dates. ISO formats stay Gregorian.
 *
 * @param string       $the_date Formatted date.
 * @param string       $format   Requested format.
 * @param int|WP_Post  $post     Post.
 * @return string
 */
function liferuss_filter_post_date( $the_date, $format, $post ) {
	if ( ! function_exists( 'liferuss_current_lang' ) || 'fa' !== liferuss_current_lang() ) {
		return $the_date;
	}
	if ( in_array( $format, array( 'c', 'U', 'Y-m-d', 'Y-m-d\TH:i:s' ), true ) ) {
		return $the_date;
	}
	if ( ! class_exists( '\LifeRuss\Core\CRM\Jalali' ) ) {
		return liferuss_local_digits( $the_date );
	}
	$utc   = get_post_time( 'Y-m-d H:i:s', true, $post );
	$plain = \LifeRuss\Core\CRM\Jalali::plain( (string) $utc );
	$plain = preg_replace( '/\s+\d{2}:\d{2}$/', '', $plain );
	return liferuss_local_digits( (string) $plain );
}
add_filter( 'get_the_date', 'liferuss_filter_post_date', 10, 3 );

/**
 * Published count that ignores demo-flagged posts.
 *
 * @param string $post_type Post type.
 * @return int
 */
function liferuss_public_count( $post_type ) {
	static $cache = array();
	if ( isset( $cache[ $post_type ] ) ) {
		return $cache[ $post_type ];
	}
	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => liferuss_not_demo_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
	$cache[ $post_type ] = (int) $query->found_posts;
	return $cache[ $post_type ];
}

/**
 * Meta query that drops `_lr_demo=1` unless WP_DEBUG is on.
 *
 * @return array<int, array<string, mixed>>
 */
function liferuss_not_demo_meta_query() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		return array();
	}
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_lr_demo',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_lr_demo',
			'value'   => '1',
			'compare' => '!=',
		),
	);
}

/**
 * Hide demo entities from public queries and from search results.
 *
 * @param WP_Query $query Query.
 */
function liferuss_hide_demo_query( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query ) {
		return;
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		return;
	}
	if ( ! $query->is_main_query() && 'lr_search' !== $query->get( 'lr_public' ) ) {
		return;
	}
	$meta = $query->get( 'meta_query' );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	$guard = liferuss_not_demo_meta_query();
	if ( $guard ) {
		$meta[] = $guard;
		$query->set( 'meta_query', $meta );
	}
}
add_action( 'pre_get_posts', 'liferuss_hide_demo_query' );

/**
 * A singular demo post is not a public URL.
 */
function liferuss_demo_singular_404() {
	if ( is_admin() || ! is_singular() ) {
		return;
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( $post_id && '1' === (string) get_post_meta( $post_id, '_lr_demo', true ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'liferuss_demo_singular_404', 1 );

/**
 * Drop empty hubs and the plans item from the designed menu.
 *
 * @param array<int, array<string, mixed>> $tree Nav tree.
 * @return array<int, array<string, mixed>>
 */
function liferuss_filter_nav_tree( $tree ) {
	$hide = array();
	if ( liferuss_public_count( 'lr_field' ) < 1 ) {
		$hide[] = '/fields/';
	}
	if ( liferuss_public_count( 'lr_city' ) < 1 ) {
		$hide[] = '/cities/';
	}
	if ( liferuss_public_count( 'lr_scholarship' ) < 1 ) {
		$hide[] = '/scholarships/';
	}
	if ( liferuss_public_count( 'lr_university' ) < 2 ) {
		$hide[] = '/compare/';
	}
	if ( ! liferuss_has_public_plans() ) {
		$hide[] = '/academy/plans/';
	}
	foreach ( $tree as $index => $item ) {
		if ( empty( $item['children'] ) || ! is_array( $item['children'] ) ) {
			continue;
		}
		$tree[ $index ]['children'] = array_values(
			array_filter(
				$item['children'],
				static function ( $child ) use ( $hide ) {
					$url = isset( $child['url'] ) ? (string) $child['url'] : '';
					foreach ( $hide as $needle ) {
						if ( str_contains( $url, $needle ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	}
	return $tree;
}

/**
 * Whether a published subscription plan exists.
 *
 * @return bool
 */
function liferuss_has_public_plans() {
	if ( ! class_exists( '\LifeRuss\Core\Academy\Db' ) ) {
		return false;
	}
	$rows = \LifeRuss\Core\Academy\Db::published( 'subscription_plans' );
	return is_array( $rows ) && count( $rows ) > 0;
}

/**
 * Filter an assigned WordPress menu the same way as the fallback.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @return array<int, WP_Post>
 */
function liferuss_filter_menu_objects( $items ) {
	if ( ! is_array( $items ) || is_admin() ) {
		return $items;
	}
	$hide = array();
	if ( liferuss_public_count( 'lr_field' ) < 1 ) {
		$hide[] = '/fields/';
	}
	if ( liferuss_public_count( 'lr_city' ) < 1 ) {
		$hide[] = '/cities/';
	}
	if ( liferuss_public_count( 'lr_scholarship' ) < 1 ) {
		$hide[] = '/scholarships/';
	}
	if ( liferuss_public_count( 'lr_university' ) < 2 ) {
		$hide[] = '/compare/';
	}
	if ( ! liferuss_has_public_plans() ) {
		$hide[] = '/academy/plans/';
	}
	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $hide ) {
				$url = isset( $item->url ) ? (string) $item->url : '';
				foreach ( $hide as $needle ) {
					if ( str_contains( $url, $needle ) ) {
						return false;
					}
				}
				return true;
			}
		)
	);
}
add_filter( 'wp_nav_menu_objects', 'liferuss_filter_menu_objects' );

/**
 * Level archives are internal filters, not landing pages.
 *
 * @param string[] $robots Robots directives.
 * @return string[]
 */
function liferuss_level_noindex( $robots ) {
	if ( is_tax( 'lr_level' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'liferuss_level_noindex' );

/**
 * Drop the level taxonomy from the core sitemap.
 *
 * @param WP_Taxonomy[] $taxonomies Taxonomies.
 * @return WP_Taxonomy[]
 */
function liferuss_sitemap_taxonomies( $taxonomies ) {
	unset( $taxonomies['lr_level'] );
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'liferuss_sitemap_taxonomies' );

/**
 * Eyebrow that repeats the heading is noise.
 *
 * @param string $eyebrow Eyebrow.
 * @param string $heading Heading.
 */
function liferuss_maybe_eyebrow( $eyebrow, $heading ) {
	$eyebrow = trim( (string) $eyebrow );
	$heading = trim( (string) $heading );
	if ( '' === $eyebrow ) {
		return;
	}
	if ( $eyebrow === $heading || ( '' !== $heading && str_contains( $heading, $eyebrow ) ) ) {
		return;
	}
	echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
}

/**
 * Open a horizontally scrollable table.
 */
function liferuss_table_scroll_open() {
	echo '<div class="table-scroll">';
	echo '<p class="table-scroll-hint">' . esc_html( liferuss_t( 'table_scroll' ) ) . '</p>';
}

/**
 * Close the scroll wrapper.
 */
function liferuss_table_scroll_close() {
	echo '</div>';
}

/**
 * Homepage major link: a real subject page when it exists.
 *
 * @param array<string, string> $major Major row.
 * @return string
 */
function liferuss_major_href( $major ) {
	$map  = array(
		'medicine' => 'study-russia/medicine',
		'dental'   => 'study-russia/dentistry',
		'pharma'   => 'study-russia/pharmacy',
		'engineer' => 'study-russia/engineering',
		'art'      => 'study-russia/art',
		'language' => 'academy/courses/russian-language',
	);
	$icon = isset( $major['icon'] ) ? (string) $major['icon'] : '';
	if ( isset( $map[ $icon ] ) ) {
		if ( 'language' === $icon ) {
			return liferuss_url( '/academy/courses/russian-language/' );
		}
		$page = get_page_by_path( $map[ $icon ] );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return (string) get_permalink( $page );
		}
	}
	$link = isset( $major['link'] ) ? (string) $major['link'] : '#consultation';
	return liferuss_cta_url( $link );
}

/**
 * Service card destination.
 *
 * @param string $icon Icon key.
 * @return string
 */
function liferuss_service_href( $icon ) {
	$map = array(
		'cap'      => '/admission/',
		'book'     => '/padfak/',
		'passport' => '/study-russia/visa/',
		'home'     => '/russia-guide/',
		'docs'     => '/study-russia/documents/',
		'plane'    => '/services/',
	);
	$path = $map[ $icon ] ?? '/services/';
	return liferuss_url( $path );
}

/**
 * Admin notice while contact details are still the packaged defaults.
 */
function liferuss_default_contact_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! in_array( $page, array( 'liferuss', 'lr-academy', 'liferuss-options' ), true ) ) {
		return;
	}
	if ( (int) get_user_meta( get_current_user_id(), 'lr_notice_contacts', true ) > time() ) {
		return;
	}
	$labels  = array(
		'phone'     => 'تلفن',
		'whatsapp'  => 'واتساپ',
		'telegram'  => 'تلگرام',
		'instagram' => 'اینستاگرام',
		'email'     => 'ایمیل',
	);
	$pending = array();
	foreach ( $labels as $key => $label ) {
		if ( '' === liferuss_public_contact( $key ) ) {
			$pending[] = $label;
		}
	}
	if ( ! $pending ) {
		return;
	}
	$url = admin_url( 'themes.php?page=liferuss-options#tab-contact' );
	$bye = wp_nonce_url( admin_url( 'admin-post.php?action=lr_dismiss_notice&key=contacts' ), 'lr_dismiss_notice' );
	echo '<div class="notice notice-warning lr-compact-notice"><p>';
	echo esc_html( 'راه‌های تماس عمومی هنوز خالی است: ' . implode( '، ', $pending ) . '. تا این فیلدها در تنظیمات قالب یا تنظیمات هسته پر نشوند، در صفحه تماس، فوتر و دکمه شناور نشان داده نمی‌شوند. ' );
	echo '<a href="' . esc_url( $url ) . '">' . esc_html( 'تنظیمات قالب' ) . '</a>';
	echo ' <a href="' . esc_url( $bye ) . '">' . esc_html( 'بستن برای ۷ روز' ) . '</a>';
	echo '</p></div>';
}
add_action( 'admin_notices', 'liferuss_default_contact_notice' );

/**
 * Replace the known negative exchange lead once, only when it still matches the seed.
 */
function liferuss_soften_exchange_lead() {
	if ( '1' === (string) get_option( 'lr_exchange_lead_tone', '' ) ) {
		return;
	}
	$page = get_page_by_path( 'exchange' );
	if ( $page instanceof WP_Post ) {
		$lead = (string) get_post_meta( $page->ID, '_lr_lead', true );
		$old  = 'این صفحه نرخ را برای تصمیم شما نشان می‌دهد و درخواست استعلام ثبت می‌کند. تبدیل خودکار و سفارش پرداخت اینجا انجام نمی‌شود.';
		if ( $lead === $old ) {
			update_post_meta( $page->ID, '_lr_lead', 'نرخ روز را می‌بینید و استعلام را ثبت می‌کنید تا مبلغ، ارز و روش را برایتان روشن کنیم.' );
		}
		$eye = (string) get_post_meta( $page->ID, '_lr_eyebrow', true );
		if ( 'فقط استعلام' === $eye ) {
			update_post_meta( $page->ID, '_lr_eyebrow', 'نرخ روز' );
		}
	}
	update_option( 'lr_exchange_lead_tone', '1', false );
}
add_action( 'init', 'liferuss_soften_exchange_lead', 40 );

/**
 * True when a homepage block has no translation for the active language.
 *
 * @param string $option_key Overlay key.
 * @return bool
 */
function liferuss_section_untranslated( $option_key ) {
	if ( ! function_exists( 'liferuss_current_lang' ) || 'fa' === liferuss_current_lang() ) {
		return false;
	}
	if ( ! function_exists( 'liferuss_lang_overlay' ) ) {
		return true;
	}
	$overlay = liferuss_lang_overlay( liferuss_current_lang() );
	return ! is_array( $overlay ) || ! array_key_exists( $option_key, $overlay );
}
