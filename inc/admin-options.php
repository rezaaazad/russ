<?php
/**
 * Tabbed Theme Options (Settings API).
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register option and admin page.
 */
function liferuss_register_settings() {
	register_setting(
		'liferuss_options_group',
		'liferuss_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'liferuss_sanitize_options',
			'default'           => liferuss_default_options(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'liferuss_register_settings' );

/**
 * Menu under Appearance.
 */
function liferuss_options_menu() {
	add_theme_page(
		'تنظیمات لایف روس',
		'تنظیمات لایف روس',
		'edit_theme_options',
		'liferuss-options',
		'liferuss_options_page'
	);
}
add_action( 'admin_menu', 'liferuss_options_menu' );

/**
 * Admin assets.
 *
 * @param string $hook Hook.
 */
function liferuss_admin_assets( $hook ) {
	if ( 'appearance_page_liferuss-options' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_style( 'liferuss-admin', LIFERUSS_URI . '/assets/admin/admin.css', array(), LIFERUSS_VERSION );
	wp_enqueue_script( 'liferuss-admin', LIFERUSS_URI . '/assets/admin/admin.js', array( 'jquery', 'wp-color-picker' ), LIFERUSS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'liferuss_admin_assets' );

/**
 * Sanitize the full options array.
 *
 * @param mixed $input Raw.
 * @return array
 */
function liferuss_sanitize_options( $input ) {
	$defaults = liferuss_default_options();
	$current  = get_option( 'liferuss_options', array() );
	if ( ! is_array( $current ) ) {
		$current = array();
	}
	if ( ! is_array( $input ) ) {
		return liferuss_array_merge_recursive( $defaults, $current );
	}

	$out = liferuss_array_merge_recursive( $defaults, $current );
	$out = liferuss_array_merge_recursive( $out, $input );

	$text_keys = array(
		'brand_name', 'brand_name_en', 'tagline', 'header_cta_text', 'header_cta_link', 'header_phone',
		'hero_eyebrow', 'hero_headline', 'hero_subheadline', 'hero_cta1_text', 'hero_cta1_link',
		'hero_cta2_text', 'hero_cta2_link', 'hero_quote', 'hero_quote_cite',
		'services_eyebrow', 'services_title', 'services_subtitle',
		'universities_eyebrow', 'universities_title', 'universities_subtitle', 'universities_link_text',
		'majors_eyebrow', 'majors_title', 'majors_subtitle',
		'costs_eyebrow', 'costs_title', 'costs_subtitle',
		'roadmap_eyebrow', 'roadmap_title', 'roadmap_subtitle',
		'testimonials_eyebrow', 'testimonials_title', 'testimonials_subtitle', 'stat_value', 'stat_text',
		'form_eyebrow', 'form_title', 'form_intro', 'form_name_label', 'form_name_ph',
		'form_phone_label', 'form_phone_ph', 'form_level_label', 'form_submit', 'form_note', 'form_success',
		'phone', 'phone_alt', 'address', 'whatsapp',
		'footer_about', 'footer_copyright', 'footer_en',
		'seo_title', 'seo_description', 'org_name', 'org_legal',
	);
	if ( function_exists( 'liferuss_landing_text_keys' ) ) {
		$text_keys = array_merge( $text_keys, liferuss_landing_text_keys() );
	}
	foreach ( $text_keys as $key ) {
		if ( isset( $out[ $key ] ) ) {
			$out[ $key ] = sanitize_textarea_field( $out[ $key ] );
		}
	}

	$url_keys = array( 'telegram', 'instagram', 'linkedin', 'youtube', 'org_url', 'email', 'form_email' );
	foreach ( $url_keys as $key ) {
		if ( ! isset( $out[ $key ] ) ) {
			continue;
		}
		if ( 'email' === $key || 'form_email' === $key ) {
			$out[ $key ] = sanitize_email( $out[ $key ] );
		} else {
			$out[ $key ] = esc_url_raw( $out[ $key ] );
		}
	}

	foreach ( array( 'color_primary', 'color_secondary' ) as $ck ) {
		$out[ $ck ] = sanitize_hex_color( $out[ $ck ] ) ?: $defaults[ $ck ];
	}

	foreach ( array( 'logo_id', 'favicon_id', 'hero_bg_id', 'hero_image_id', 'hero_student_id', 'form_image_id', 'og_image_id', 'freight_hero_image_id', 'freight_form_banner_image_id', 'trade_hero_image_id', 'trade_form_banner_image_id' ) as $ik ) {
		$out[ $ik ] = absint( $out[ $ik ] ?? 0 );
	}

	$out['header_show_phone'] = empty( $input['header_show_phone'] ) ? '0' : '1';
	$out['services_enabled']  = empty( $input['services_enabled'] ) ? '0' : '1';
	if ( function_exists( 'liferuss_landing_flag_keys' ) ) {
		foreach ( liferuss_landing_flag_keys() as $flag ) {
			$out[ $flag ] = empty( $input[ $flag ] ) ? '0' : '1';
		}
	}

	$out['services'] = liferuss_sanitize_repeater(
		$out['services'] ?? array(),
		array( 'enabled' => 'text', 'title' => 'text', 'text' => 'text', 'icon' => 'text', 'image_id' => 'int' )
	);
	$out['universities'] = liferuss_sanitize_repeater(
		$out['universities'] ?? array(),
		array( 'name' => 'text', 'latin' => 'text', 'city' => 'text', 'rank' => 'text', 'focus' => 'text', 'image' => 'text', 'image_id' => 'int', 'link' => 'url' )
	);
	$out['majors'] = liferuss_sanitize_repeater(
		$out['majors'] ?? array(),
		array( 'title' => 'text', 'icon' => 'text', 'link' => 'url' )
	);
	$out['costs'] = liferuss_sanitize_repeater(
		$out['costs'] ?? array(),
		array( 'title' => 'text', 'value' => 'text', 'note' => 'text', 'icon' => 'text' )
	);
	$out['roadmap'] = liferuss_sanitize_repeater(
		$out['roadmap'] ?? array(),
		array( 'num' => 'text', 'title' => 'text', 'text' => 'text' )
	);
	$out['testimonials'] = liferuss_sanitize_repeater(
		$out['testimonials'] ?? array(),
		array( 'name' => 'text', 'meta' => 'text', 'quote' => 'text', 'image' => 'text', 'image_id' => 'int', 'rating' => 'text' )
	);
	$out['trust'] = liferuss_sanitize_repeater(
		$out['trust'] ?? array(),
		array( 'title' => 'text', 'text' => 'text', 'icon' => 'text' )
	);
	$out['footer_links'] = liferuss_sanitize_repeater(
		$out['footer_links'] ?? array(),
		array( 'label' => 'text', 'url' => 'text' )
	);
	if ( function_exists( 'liferuss_landing_repeater_schemas' ) ) {
		foreach ( liferuss_landing_repeater_schemas() as $rep_key => $schema ) {
			$out[ $rep_key ] = liferuss_sanitize_repeater( $out[ $rep_key ] ?? array(), $schema );
		}
	}

	$i18n_source   = isset( $input['i18n'] ) && is_array( $input['i18n'] ) ? $input['i18n'] : ( $current['i18n'] ?? array() );
	$out['i18n']   = liferuss_sanitize_i18n( $i18n_source );

	return $out;
}

/**
 * Sanitize per-language overlays (en / ru / ar).
 *
 * @param array $raw Raw i18n tree.
 * @return array
 */
function liferuss_sanitize_i18n( $raw ) {
	$clean     = array();
	$text_keys = array(
		'tagline', 'header_cta_text', 'hero_eyebrow', 'hero_headline', 'hero_subheadline',
		'hero_cta1_text', 'hero_cta2_text', 'hero_quote', 'hero_quote_cite',
		'services_eyebrow', 'services_title', 'services_subtitle',
		'universities_eyebrow', 'universities_title', 'universities_subtitle', 'universities_link_text',
		'majors_eyebrow', 'majors_title', 'majors_subtitle',
		'costs_eyebrow', 'costs_title', 'costs_subtitle',
		'roadmap_eyebrow', 'roadmap_title', 'roadmap_subtitle',
		'testimonials_eyebrow', 'testimonials_title', 'testimonials_subtitle', 'stat_value', 'stat_text',
		'form_eyebrow', 'form_title', 'form_intro', 'form_name_label', 'form_name_ph',
		'form_phone_label', 'form_phone_ph', 'form_level_label', 'form_submit', 'form_note', 'form_success',
		'address', 'footer_about', 'footer_copyright', 'footer_en',
		'seo_title', 'seo_description', 'seo_og_title', 'seo_og_description',
	);
	$repeaters = array(
		'trust'         => array( 'title' => 'text', 'text' => 'text' ),
		'services'      => array( 'title' => 'text', 'text' => 'text' ),
		'universities'  => array( 'name' => 'text', 'city' => 'text', 'rank' => 'text', 'focus' => 'text' ),
		'majors'        => array( 'title' => 'text' ),
		'costs'         => array( 'title' => 'text', 'value' => 'text', 'note' => 'text' ),
		'roadmap'       => array( 'num' => 'text', 'title' => 'text', 'text' => 'text' ),
		'testimonials'  => array( 'name' => 'text', 'meta' => 'text', 'quote' => 'text' ),
		'footer_links'  => array( 'label' => 'text' ),
	);
	if ( function_exists( 'liferuss_landing_i18n_text_keys' ) ) {
		$text_keys = array_merge( $text_keys, liferuss_landing_i18n_text_keys() );
	}
	if ( function_exists( 'liferuss_landing_i18n_repeaters' ) ) {
		$repeaters = array_merge( $repeaters, liferuss_landing_i18n_repeaters() );
	}

	foreach ( array( 'en', 'ru', 'ar' ) as $lang ) {
		$row = isset( $raw[ $lang ] ) && is_array( $raw[ $lang ] ) ? $raw[ $lang ] : array();
		$out = array();
		foreach ( $text_keys as $key ) {
			if ( isset( $row[ $key ] ) ) {
				$out[ $key ] = sanitize_textarea_field( $row[ $key ] );
			}
		}
		foreach ( $repeaters as $key => $schema ) {
			if ( isset( $row[ $key ] ) ) {
				$out[ $key ] = liferuss_sanitize_repeater( $row[ $key ], $schema );
			}
		}
		$clean[ $lang ] = $out;
	}
	return $clean;
}

/**
 * Sanitize a list of assoc items.
 *
 * @param array $items Items.
 * @param array $schema Field => type.
 * @return array
 */
function liferuss_sanitize_repeater( $items, $schema ) {
	$clean = array();
	foreach ( (array) $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$row = array();
		foreach ( $schema as $field => $type ) {
			$raw = $item[ $field ] ?? '';
			if ( 'int' === $type ) {
				$row[ $field ] = absint( $raw );
			} elseif ( 'url' === $type ) {
				$row[ $field ] = $raw ? esc_url_raw( $raw ) : '';
			} else {
				$row[ $field ] = sanitize_textarea_field( $raw );
			}
		}
		$clean[] = $row;
	}
	return $clean;
}

/**
 * Field helpers.
 *
 * @param string $name  Input name (inside liferuss_options).
 * @param mixed  $value Value.
 * @param string $label Label.
 * @param string $type  text|textarea|url|email|checkbox|color.
 */
function liferuss_admin_field( $name, $value, $label, $type = 'text' ) {
	$id = 'lr_' . preg_replace( '/[^a-z0-9_]+/i', '_', $name );
	echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
	if ( 'textarea' === $type ) {
		printf( '<textarea class="large-text" rows="3" id="%s" name="liferuss_options[%s]">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
	} elseif ( 'checkbox' === $type ) {
		printf( '<label><input type="checkbox" id="%s" name="liferuss_options[%s]" value="1"%s> %s</label>', esc_attr( $id ), esc_attr( $name ), checked( (string) $value, '1', false ), esc_html( $label ) );
	} elseif ( 'color' === $type ) {
		printf( '<input type="text" class="liferuss-color" id="%s" name="liferuss_options[%s]" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
	} else {
		$input = 'email' === $type ? 'email' : ( 'url' === $type ? 'url' : 'text' );
		printf( '<input type="%s" class="regular-text" id="%s" name="liferuss_options[%s]" value="%s">', esc_attr( $input ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
	}
	echo '</td></tr>';
}

/**
 * Nested field (repeaters).
 *
 * @param string $path  e.g. services][0][title.
 * @param mixed  $value Value.
 * @param string $label Label.
 * @param string $type  Type.
 */
function liferuss_admin_subfield( $path, $value, $label, $type = 'text' ) {
	printf( '<p><label>%s<br>', esc_html( $label ) );
	if ( 'textarea' === $type ) {
		printf( '<textarea class="large-text" rows="2" name="liferuss_options[%s]">%s</textarea>', esc_attr( $path ), esc_textarea( (string) $value ) );
	} elseif ( 'checkbox' === $type ) {
		printf( '<input type="hidden" name="liferuss_options[%s]" value="0">', esc_attr( $path ) );
		printf( '<label><input type="checkbox" name="liferuss_options[%s]" value="1"%s> فعال</label>', esc_attr( $path ), checked( (string) $value, '1', false ) );
	} else {
		printf( '<input type="text" class="regular-text" name="liferuss_options[%s]" value="%s">', esc_attr( $path ), esc_attr( (string) $value ) );
	}
	echo '</label></p>';
}

/**
 * Media field.
 *
 * @param string $name  Option key or nested path.
 * @param int    $id    Attachment ID.
 * @param string $label Label.
 */
function liferuss_admin_media( $name, $id, $label ) {
	$id  = absint( $id );
	$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><div class="liferuss-media-row">';
	printf( '<input type="hidden" name="liferuss_options[%s]" value="%d">', esc_attr( $name ), $id );
	echo '<div class="liferuss-preview">';
	if ( $src ) {
		echo '<img src="' . esc_url( $src ) . '" alt="">';
	}
	echo '</div>';
	echo '<button type="button" class="button liferuss-upload">انتخاب تصویر</button>';
	echo '<button type="button" class="button-link liferuss-remove">حذف</button>';
	echo '</div></td></tr>';
}

/**
 * Nested media field inside a card.
 *
 * @param string $path Path.
 * @param int    $id   ID.
 * @param string $label Label.
 */
function liferuss_admin_media_inline( $path, $id, $label ) {
	$id  = absint( $id );
	$src = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	echo '<p>' . esc_html( $label ) . '</p><div class="liferuss-media-row">';
	printf( '<input type="hidden" name="liferuss_options[%s]" value="%d">', esc_attr( $path ), $id );
	echo '<div class="liferuss-preview">';
	if ( $src ) {
		echo '<img src="' . esc_url( $src ) . '" alt="">';
	}
	echo '</div>';
	echo '<button type="button" class="button liferuss-upload">انتخاب تصویر</button>';
	echo '<button type="button" class="button-link liferuss-remove">حذف</button>';
	echo '</div>';
}

/**
 * Open a settings table.
 */
function liferuss_admin_table_start() {
	echo '<table class="form-table" role="presentation">';
}

/**
 * Close a settings table.
 */
function liferuss_admin_table_end() {
	echo '</table>';
}

/**
 * Render the options screen.
 */
function liferuss_options_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$o    = liferuss_options();
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	$tabs = array(
		'general'       => 'عمومی / برند',
		'header'        => 'هدر',
		'hero'          => 'هیرو',
		'services'      => 'خدمات',
		'universities'  => 'دانشگاه‌ها',
		'majors'        => 'رشته‌ها',
		'costs'         => 'هزینه‌ها',
		'roadmap'       => 'مسیر پذیرش',
		'testimonials'  => 'نظرات',
		'form'          => 'فرم مشاوره',
		'contact'       => 'تماس و شبکه‌ها',
		'footer'        => 'فوتر',
		'seo'           => 'سئو',
		'languages'     => 'زبان‌ها / Languages',
	);
	if ( function_exists( 'liferuss_landing_admin_tabs' ) ) {
		$tabs = array_merge( $tabs, liferuss_landing_admin_tabs() );
	}
	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = 'general';
	}
	?>
	<div class="wrap liferuss-options" dir="rtl" data-tab="<?php echo esc_attr( $tab ); ?>">
		<h1>تنظیمات قالب لایف روس</h1>
		<p>همهٔ متن‌ها، تصاویر، رنگ‌ها و لینک‌های سایت از همین صفحه مدیریت می‌شوند. زبانه‌های اصلی برای <strong>فارسی (پیش‌فرض)</strong> هستند. ترجمهٔ روسی، عربی و انگلیسی را در زبانهٔ «زبان‌ها» ویرایش کنید. اگر فیلدی خالی بماند، مقدار پیش‌فرض دمو نمایش داده می‌شود.</p>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'liferuss_options_group' ); ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $id => $label ) : ?>
					<a class="nav-tab<?php echo $tab === $id ? ' nav-tab-active' : ''; ?>" href="#tab-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div id="tab-general" class="liferuss-tab">
				<h2>عمومی و برند</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'brand_name', $o['brand_name'], 'نام برند (فارسی)' );
				liferuss_admin_field( 'brand_name_en', $o['brand_name_en'], 'نام انگلیسی' );
				liferuss_admin_field( 'tagline', $o['tagline'], 'شعار / توضیح کوتاه', 'textarea' );
				liferuss_admin_media( 'logo_id', $o['logo_id'], 'لوگوی هدر' );
				liferuss_admin_media( 'favicon_id', $o['favicon_id'], 'فاویکون' );
				liferuss_admin_field( 'color_primary', $o['color_primary'], 'رنگ اصلی (سرمه‌ای)', 'color' );
				liferuss_admin_field( 'color_secondary', $o['color_secondary'], 'رنگ دوم (طلایی)', 'color' );
				liferuss_admin_table_end();
				?>
			</div>

			<div id="tab-header" class="liferuss-tab">
				<h2>هدر</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_media( 'logo_id', $o['logo_id'], 'لوگو (مشترک با برند)' );
				liferuss_admin_field( 'header_cta_text', $o['header_cta_text'], 'متن دکمه CTA' );
				liferuss_admin_field( 'header_cta_link', $o['header_cta_link'], 'لینک دکمه CTA' );
				liferuss_admin_field( 'header_phone', $o['header_phone'], 'تلفن هدر' );
				liferuss_admin_field( 'header_show_phone', $o['header_show_phone'], 'نمایش تلفن در هدر', 'checkbox' );
				liferuss_admin_table_end();
				?>
				<p class="description">منوی ناوبری از نمایش ← فهرست‌ها مدیریت می‌شود.</p>
			</div>

			<div id="tab-hero" class="liferuss-tab">
				<h2>هیرو و نوار اعتماد</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'hero_eyebrow', $o['hero_eyebrow'], 'خط بالای عنوان' );
				liferuss_admin_field( 'hero_headline', $o['hero_headline'], 'عنوان اصلی (H1)', 'textarea' );
				liferuss_admin_field( 'hero_subheadline', $o['hero_subheadline'], 'توضیح هیرو', 'textarea' );
				liferuss_admin_field( 'hero_cta1_text', $o['hero_cta1_text'], 'متن دکمه اول' );
				liferuss_admin_field( 'hero_cta1_link', $o['hero_cta1_link'], 'لینک دکمه اول' );
				liferuss_admin_field( 'hero_cta2_text', $o['hero_cta2_text'], 'متن دکمه دوم' );
				liferuss_admin_field( 'hero_cta2_link', $o['hero_cta2_link'], 'لینک دکمه دوم' );
				liferuss_admin_field( 'hero_quote', $o['hero_quote'], 'نقل‌قول روی تصویر' );
				liferuss_admin_field( 'hero_quote_cite', $o['hero_quote_cite'], 'منبع نقل‌قول' );
				liferuss_admin_media( 'hero_bg_id', $o['hero_bg_id'], 'تصویر پس‌زمینه هیرو (اختیاری)' );
				liferuss_admin_media( 'hero_image_id', $o['hero_image_id'], 'تصویر اصلی (سنت باسیل)' );
				liferuss_admin_media( 'hero_student_id', $o['hero_student_id'], 'تصویر دانشجو' );
				liferuss_admin_table_end();
				echo '<h3>چهار آمار اعتماد</h3><div class="liferuss-grid">';
				foreach ( $o['trust'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>آیتم ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "trust][$i][title", $item['title'], 'عنوان' );
					liferuss_admin_subfield( "trust][$i][text", $item['text'], 'توضیح' );
					liferuss_admin_subfield( "trust][$i][icon", $item['icon'], 'آیکون (users, shield, chat, bolt, …)' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-services" class="liferuss-tab">
				<h2>خدمات</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'services_enabled', $o['services_enabled'], 'نمایش بخش خدمات', 'checkbox' );
				liferuss_admin_field( 'services_eyebrow', $o['services_eyebrow'], 'خط بالای عنوان' );
				liferuss_admin_field( 'services_title', $o['services_title'], 'عنوان بخش' );
				liferuss_admin_field( 'services_subtitle', $o['services_subtitle'], 'توضیح بخش', 'textarea' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['services'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>کارت ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "services][$i][enabled", $item['enabled'] ?? '1', 'فعال بودن کارت', 'checkbox' );
					liferuss_admin_subfield( "services][$i][title", $item['title'], 'عنوان' );
					liferuss_admin_subfield( "services][$i][text", $item['text'], 'توضیح', 'textarea' );
					liferuss_admin_subfield( "services][$i][icon", $item['icon'], 'آیکون' );
					liferuss_admin_media_inline( "services][$i][image_id", $item['image_id'] ?? 0, 'تصویر اختیاری' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-universities" class="liferuss-tab">
				<h2>دانشگاه‌ها</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'universities_eyebrow', $o['universities_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'universities_title', $o['universities_title'], 'عنوان' );
				liferuss_admin_field( 'universities_subtitle', $o['universities_subtitle'], 'توضیح', 'textarea' );
				liferuss_admin_field( 'universities_link_text', $o['universities_link_text'], 'متن لینک مشاهده همه' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['universities'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>دانشگاه ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "universities][$i][name", $item['name'], 'نام فارسی' );
					liferuss_admin_subfield( "universities][$i][latin", $item['latin'], 'نام انگلیسی / مخفف' );
					liferuss_admin_subfield( "universities][$i][rank", $item['rank'], 'رتبه' );
					liferuss_admin_subfield( "universities][$i][city", $item['city'], 'شهر' );
					liferuss_admin_subfield( "universities][$i][focus", $item['focus'], 'رشته‌ها' );
					liferuss_admin_subfield( "universities][$i][link", $item['link'] ?? '', 'لینک' );
					liferuss_admin_subfield( "universities][$i][image", $item['image'] ?? '', 'تصویر پیش‌فرض قالب (مسیر)' );
					liferuss_admin_media_inline( "universities][$i][image_id", $item['image_id'] ?? 0, 'آپلود تصویر' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-majors" class="liferuss-tab">
				<h2>رشته‌های محبوب</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'majors_eyebrow', $o['majors_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'majors_title', $o['majors_title'], 'عنوان' );
				liferuss_admin_field( 'majors_subtitle', $o['majors_subtitle'], 'توضیح', 'textarea' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['majors'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>رشته ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "majors][$i][title", $item['title'], 'عنوان' );
					liferuss_admin_subfield( "majors][$i][icon", $item['icon'], 'آیکون' );
					liferuss_admin_subfield( "majors][$i][link", $item['link'] ?? '', 'لینک' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-costs" class="liferuss-tab">
				<h2>هزینه و شرایط</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'costs_eyebrow', $o['costs_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'costs_title', $o['costs_title'], 'عنوان' );
				liferuss_admin_field( 'costs_subtitle', $o['costs_subtitle'], 'توضیح', 'textarea' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['costs'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>بلوک ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "costs][$i][title", $item['title'], 'عنوان' );
					liferuss_admin_subfield( "costs][$i][value", $item['value'], 'مقدار' );
					liferuss_admin_subfield( "costs][$i][note", $item['note'], 'توضیح کوتاه' );
					liferuss_admin_subfield( "costs][$i][icon", $item['icon'], 'آیکون' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-roadmap" class="liferuss-tab">
				<h2>مسیر پذیرش</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'roadmap_eyebrow', $o['roadmap_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'roadmap_title', $o['roadmap_title'], 'عنوان' );
				liferuss_admin_field( 'roadmap_subtitle', $o['roadmap_subtitle'], 'توضیح', 'textarea' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['roadmap'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>گام ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "roadmap][$i][num", $item['num'], 'شماره' );
					liferuss_admin_subfield( "roadmap][$i][title", $item['title'], 'عنوان' );
					liferuss_admin_subfield( "roadmap][$i][text", $item['text'], 'توضیح', 'textarea' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-testimonials" class="liferuss-tab">
				<h2>نظرات و آمار موفقیت</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'testimonials_eyebrow', $o['testimonials_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'testimonials_title', $o['testimonials_title'], 'عنوان' );
				liferuss_admin_field( 'testimonials_subtitle', $o['testimonials_subtitle'], 'توضیح', 'textarea' );
				liferuss_admin_field( 'stat_value', $o['stat_value'], 'عدد آمار (مثلاً +۵۰۰)' );
				liferuss_admin_field( 'stat_text', $o['stat_text'], 'متن آمار', 'textarea' );
				liferuss_admin_table_end();
				echo '<div class="liferuss-grid">';
				foreach ( $o['testimonials'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>دانشجو ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "testimonials][$i][name", $item['name'], 'نام' );
					liferuss_admin_subfield( "testimonials][$i][meta", $item['meta'], 'رشته / دانشگاه' );
					liferuss_admin_subfield( "testimonials][$i][quote", $item['quote'], 'نقل‌قول', 'textarea' );
					liferuss_admin_subfield( "testimonials][$i][rating", $item['rating'] ?? '5', 'امتیاز (۱ تا ۵)' );
					liferuss_admin_subfield( "testimonials][$i][image", $item['image'] ?? '', 'تصویر پیش‌فرض قالب' );
					liferuss_admin_media_inline( "testimonials][$i][image_id", $item['image_id'] ?? 0, 'آپلود عکس' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-form" class="liferuss-tab">
				<h2>فرم مشاوره</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'form_eyebrow', $o['form_eyebrow'], 'خط بالا' );
				liferuss_admin_field( 'form_title', $o['form_title'], 'عنوان فرم' );
				liferuss_admin_field( 'form_intro', $o['form_intro'], 'متن معرفی', 'textarea' );
				liferuss_admin_field( 'form_name_label', $o['form_name_label'], 'برچسب نام' );
				liferuss_admin_field( 'form_name_ph', $o['form_name_ph'], 'placeholder نام' );
				liferuss_admin_field( 'form_phone_label', $o['form_phone_label'], 'برچسب تلفن' );
				liferuss_admin_field( 'form_phone_ph', $o['form_phone_ph'], 'placeholder تلفن' );
				liferuss_admin_field( 'form_level_label', $o['form_level_label'], 'برچسب مقطع' );
				liferuss_admin_field( 'form_submit', $o['form_submit'], 'متن دکمه ارسال' );
				liferuss_admin_field( 'form_note', $o['form_note'], 'توضیح زیر فرم' );
				liferuss_admin_field( 'form_success', $o['form_success'], 'پیام موفقیت', 'textarea' );
				liferuss_admin_field( 'form_email', $o['form_email'], 'ایمیل مقصد درخواست‌ها', 'email' );
				liferuss_admin_media( 'form_image_id', $o['form_image_id'], 'تصویر کنار فرم' );
				liferuss_admin_table_end();
				?>
			</div>

			<div id="tab-contact" class="liferuss-tab">
				<h2>تماس و شبکه‌های اجتماعی</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'phone', $o['phone'], 'تلفن' );
				liferuss_admin_field( 'phone_alt', $o['phone_alt'], 'تلفن دوم' );
				liferuss_admin_field( 'email', $o['email'], 'ایمیل', 'email' );
				liferuss_admin_field( 'address', $o['address'], 'آدرس', 'textarea' );
				liferuss_admin_field( 'whatsapp', $o['whatsapp'], 'واتساپ' );
				liferuss_admin_field( 'telegram', $o['telegram'], 'تلگرام', 'url' );
				liferuss_admin_field( 'instagram', $o['instagram'], 'اینستاگرام', 'url' );
				liferuss_admin_field( 'linkedin', $o['linkedin'], 'لینکدین', 'url' );
				liferuss_admin_field( 'youtube', $o['youtube'], 'یوتیوب', 'url' );
				liferuss_admin_table_end();
				?>
			</div>

			<div id="tab-footer" class="liferuss-tab">
				<h2>فوتر</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'footer_about', $o['footer_about'], 'متن معرفی فوتر', 'textarea' );
				liferuss_admin_field( 'footer_copyright', $o['footer_copyright'], 'کپی‌رایت ({year} با سال جایگزین می‌شود)', 'textarea' );
				liferuss_admin_field( 'footer_en', $o['footer_en'], 'خط انگلیسی پایین فوتر' );
				liferuss_admin_table_end();
				echo '<h3>لینک‌های سریع</h3><div class="liferuss-grid">';
				foreach ( $o['footer_links'] as $i => $item ) {
					echo '<div class="liferuss-card"><h3>لینک ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
					liferuss_admin_subfield( "footer_links][$i][label", $item['label'], 'عنوان' );
					liferuss_admin_subfield( "footer_links][$i][url", $item['url'], 'آدرس' );
					echo '</div>';
				}
				echo '</div>';
				?>
			</div>

			<div id="tab-seo" class="liferuss-tab">
				<h2>سئو و دادهٔ ساختاریافته</h2>
				<?php
				liferuss_admin_table_start();
				liferuss_admin_field( 'seo_title', $o['seo_title'], 'عنوان پیش‌فرض سئو (صفحهٔ خانه)' );
				liferuss_admin_field( 'seo_description', $o['seo_description'], 'توضیح متا / Open Graph', 'textarea' );
				liferuss_admin_media( 'og_image_id', $o['og_image_id'], 'تصویر Open Graph' );
				liferuss_admin_field( 'org_name', $o['org_name'], 'نام سازمان در schema' );
				liferuss_admin_field( 'org_legal', $o['org_legal'], 'نام قانونی / انگلیسی' );
				liferuss_admin_field( 'org_url', $o['org_url'], 'آدرس رسمی سازمان', 'url' );
				liferuss_admin_table_end();
				?>
				<p class="description">JSON-LD سازمان، وب‌سایت، خدمات حرفه‌ای و پرسش‌های متداول به‌صورت خودکار از همین فیلدها ساخته می‌شود. فایل <code>llms.txt</code> در ریشه سایت از طریق قالب سرو می‌شود. عنوان و توضیح سئوی فارسی اینجاست؛ EN / RU / AR را در زبانه زبان‌ها پر کنید.</p>
			</div>

			<?php
			if ( function_exists( 'liferuss_admin_landings_tabs' ) ) {
				liferuss_admin_landings_tabs( $o );
			}
			?>

			<div id="tab-languages" class="liferuss-tab">
				<?php liferuss_admin_i18n_tab(); ?>
			</div>

			<?php submit_button( 'ذخیره تنظیمات لایف روس' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Effective i18n value for admin fields (saved override or packaged default).
 *
 * @param string $lang Language.
 * @param string $key  Key.
 * @param mixed  $sub  Optional nested path [list, index, field].
 * @return string
 */
function liferuss_admin_i18n_value( $lang, $key, $sub = null ) {
	$overlay = liferuss_lang_overlay( $lang );
	if ( null === $sub ) {
		return isset( $overlay[ $key ] ) && ! is_array( $overlay[ $key ] ) ? (string) $overlay[ $key ] : '';
	}
	list( $index, $field ) = $sub;
	if ( isset( $overlay[ $key ][ $index ][ $field ] ) ) {
		return (string) $overlay[ $key ][ $index ][ $field ];
	}
	return '';
}

/**
 * Languages tab: EN / RU / AR editors. Persian stays on the other tabs.
 */
function liferuss_admin_i18n_tab() {
	$langs = array(
		'en' => 'English · LTR · /en/',
		'ru' => 'Русский · LTR · /ru/',
		'ar' => 'العربية · RTL · /ar/',
	);
	$sections = array(
		'header_cta_text'        => array( 'هدر / CTA', 'text' ),
		'tagline'                => array( 'شعار', 'textarea' ),
		'hero_eyebrow'           => array( 'هیرو: خط بالا', 'text' ),
		'hero_headline'          => array( 'هیرو: عنوان', 'textarea' ),
		'hero_subheadline'       => array( 'هیرو: توضیح', 'textarea' ),
		'hero_cta1_text'         => array( 'هیرو: دکمه ۱', 'text' ),
		'hero_cta2_text'         => array( 'هیرو: دکمه ۲', 'text' ),
		'hero_quote'             => array( 'هیرو: نقل‌قول', 'text' ),
		'services_eyebrow'       => array( 'خدمات: خط بالا', 'text' ),
		'services_title'         => array( 'خدمات: عنوان', 'text' ),
		'services_subtitle'      => array( 'خدمات: توضیح', 'textarea' ),
		'universities_eyebrow'   => array( 'دانشگاه: خط بالا', 'text' ),
		'universities_title'     => array( 'دانشگاه: عنوان', 'text' ),
		'universities_subtitle'  => array( 'دانشگاه: توضیح', 'textarea' ),
		'universities_link_text' => array( 'دانشگاه: لینک همه', 'text' ),
		'majors_eyebrow'         => array( 'رشته: خط بالا', 'text' ),
		'majors_title'           => array( 'رشته: عنوان', 'text' ),
		'majors_subtitle'        => array( 'رشته: توضیح', 'textarea' ),
		'costs_eyebrow'          => array( 'هزینه: خط بالا', 'text' ),
		'costs_title'            => array( 'هزینه: عنوان', 'text' ),
		'costs_subtitle'         => array( 'هزینه: توضیح', 'textarea' ),
		'roadmap_eyebrow'        => array( 'مسیر: خط بالا', 'text' ),
		'roadmap_title'          => array( 'مسیر: عنوان', 'text' ),
		'roadmap_subtitle'       => array( 'مسیر: توضیح', 'textarea' ),
		'testimonials_eyebrow'   => array( 'نظرات: خط بالا', 'text' ),
		'testimonials_title'     => array( 'نظرات: عنوان', 'text' ),
		'testimonials_subtitle'  => array( 'نظرات: توضیح', 'textarea' ),
		'stat_value'             => array( 'آمار: عدد', 'text' ),
		'stat_text'              => array( 'آمار: متن', 'textarea' ),
		'form_eyebrow'           => array( 'فرم: خط بالا', 'text' ),
		'form_title'             => array( 'فرم: عنوان', 'text' ),
		'form_intro'             => array( 'فرم: معرفی', 'textarea' ),
		'form_name_label'        => array( 'فرم: برچسب نام', 'text' ),
		'form_name_ph'           => array( 'فرم: placeholder نام', 'text' ),
		'form_phone_label'       => array( 'فرم: برچسب تلفن', 'text' ),
		'form_phone_ph'          => array( 'فرم: placeholder تلفن', 'text' ),
		'form_level_label'       => array( 'فرم: برچسب مقطع', 'text' ),
		'form_submit'            => array( 'فرم: دکمه', 'text' ),
		'form_note'              => array( 'فرم: توضیح', 'text' ),
		'form_success'           => array( 'فرم: پیام موفقیت', 'textarea' ),
		'address'                => array( 'آدرس', 'textarea' ),
		'footer_about'           => array( 'فوتر: معرفی', 'textarea' ),
		'footer_copyright'       => array( 'فوتر: کپی‌رایت', 'textarea' ),
		'seo_title'              => array( 'سئو: عنوان', 'text' ),
		'seo_description'        => array( 'سئو: توضیحات / OG', 'textarea' ),
		'seo_og_title'           => array( 'سئو: عنوان Open Graph', 'text' ),
		'seo_og_description'     => array( 'سئو: توضیح Open Graph', 'textarea' ),
	);

	$fa = liferuss_default_options();
	echo '<h2>ترجمه‌ها — English / русский / العربية</h2>';
	echo '<p>فارسی را از زبانه‌های دیگر ویرایش کنید. اینجا فقط EN، RU و AR است. فیلد خالی = متن پیش‌فرض بسته‌شده در قالب. برند همیشه <strong>لایف روس / LifeRuss</strong> است.</p>';
	echo '<p class="description">آدرس‌ها: <code>/</code> فارسی · <code>/ru/</code> روسی · <code>/ar/</code> عربی · <code>/en/</code> انگلیسی. WPML لازم نیست (اختیاری برای نوشته‌های وبلاگ).</p>';
	echo '<div class="liferuss-i18n-switch">';
	foreach ( $langs as $code => $label ) {
		echo '<button type="button" class="button' . ( 'en' === $code ? ' button-primary' : '' ) . '" data-i18n-lang="' . esc_attr( $code ) . '">' . esc_html( $label ) . '</button> ';
	}
	echo '</div>';

	foreach ( $langs as $code => $label ) {
		$dir = ( 'ar' === $code ) ? 'rtl' : 'ltr';
		echo '<div class="liferuss-i18n-panel" data-i18n-lang="' . esc_attr( $code ) . '"' . ( 'en' === $code ? '' : ' hidden' ) . ' dir="' . esc_attr( $dir ) . '">';
		echo '<h3>' . esc_html( $label ) . '</h3>';
		liferuss_admin_table_start();
		foreach ( $sections as $key => $meta ) {
			liferuss_admin_field( "i18n][$code][$key", liferuss_admin_i18n_value( $code, $key ), $meta[0], $meta[1] );
		}
		liferuss_admin_table_end();

		$lists = array(
			'trust'        => array( 'چهار آمار اعتماد', array( 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['trust'] ) ),
			'services'     => array( 'کارت‌های خدمات', array( 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['services'] ) ),
			'universities' => array( 'دانشگاه‌ها', array( 'name' => 'نام', 'city' => 'شهر', 'rank' => 'رتبه', 'focus' => 'تمرکز' ), count( $fa['universities'] ) ),
			'majors'       => array( 'رشته‌ها', array( 'title' => 'عنوان' ), count( $fa['majors'] ) ),
			'costs'        => array( 'هزینه‌ها', array( 'title' => 'عنوان', 'value' => 'مقدار', 'note' => 'یادداشت' ), count( $fa['costs'] ) ),
			'roadmap'      => array( 'مسیر پذیرش', array( 'num' => 'شماره', 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['roadmap'] ) ),
			'testimonials' => array( 'نظرات', array( 'name' => 'نام', 'meta' => 'متا', 'quote' => 'نقل‌قول' ), count( $fa['testimonials'] ) ),
			'footer_links' => array( 'لینک فوتر', array( 'label' => 'برچسب' ), count( $fa['footer_links'] ) ),
		);
		if ( function_exists( 'liferuss_admin_i18n_landings' ) ) {
			liferuss_admin_i18n_landings( $code, $fa );
		}
		foreach ( $lists as $key => $info ) {
			echo '<h3>' . esc_html( $info[0] ) . '</h3><div class="liferuss-grid">';
			for ( $i = 0; $i < $info[2]; $i++ ) {
				echo '<div class="liferuss-card"><h3>#' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
				foreach ( $info[1] as $field => $lab ) {
					$type = ( 'text' === $field || 'quote' === $field ) ? 'textarea' : 'text';
					liferuss_admin_subfield(
						"i18n][$code][$key][$i][$field",
						liferuss_admin_i18n_value( $code, $key, array( $i, $field ) ),
						$lab,
						$type
					);
				}
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
}
