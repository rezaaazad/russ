<?php
/**
 * Theme Options tabs for homepage landing cards and freight / trade pages.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra admin tabs for landings.
 *
 * @return array<string, string>
 */
function liferuss_landing_admin_tabs() {
	return array(
		'home_landings' => 'لندینگ‌های خانه',
		'freight'       => 'باربری و ارسال',
		'trade'         => 'تجارت و تأمین',
	);
}

/**
 * Checkbox flags that must be '0' when missing from POST.
 *
 * @return string[]
 */
function liferuss_landing_flag_keys() {
	return array(
		'home_landings_enabled',
		'freight_hero_enabled',
		'freight_services_enabled',
		'freight_process_enabled',
		'freight_form_enabled',
		'freight_items_enabled',
		'freight_cta_enabled',
		'trade_hero_enabled',
		'trade_services_enabled',
		'trade_process_enabled',
		'trade_form_enabled',
		'trade_cats_enabled',
		'trade_cta_enabled',
	);
}

/**
 * Scalar text keys added by landings.
 *
 * @return string[]
 */
function liferuss_landing_text_keys() {
	return array(
		'home_landings_eyebrow', 'home_landings_title', 'home_landings_subtitle',
		'freight_hero_eyebrow', 'freight_hero_headline', 'freight_hero_accent', 'freight_hero_lead',
		'freight_hero_cta_text', 'freight_hero_cta_link', 'freight_hero_image',
		'freight_process_title', 'freight_process_subtitle',
		'freight_form_title', 'freight_form_intro', 'freight_form_banner_title', 'freight_form_banner_text',
		'freight_form_banner_image', 'freight_form_name_label', 'freight_form_name_ph',
		'freight_form_phone_label', 'freight_form_phone_ph', 'freight_form_origin_label', 'freight_form_origin_ph',
		'freight_form_dest_label', 'freight_form_dest_ph', 'freight_form_type_label',
		'freight_form_weight_label', 'freight_form_weight_ph', 'freight_form_dims_label', 'freight_form_dims_ph',
		'freight_form_packages_label', 'freight_form_packages_ph', 'freight_form_value_label', 'freight_form_value_ph',
		'freight_form_notes_label', 'freight_form_notes_ph', 'freight_form_file_label', 'freight_form_file_note',
		'freight_form_submit', 'freight_form_note', 'freight_form_success',
		'freight_items_title', 'freight_items_subtitle', 'freight_items_notice',
		'freight_cta_title', 'freight_cta_text', 'freight_cta1_text', 'freight_cta1_link', 'freight_cta2_text', 'freight_cta2_link',
		'freight_seo_title', 'freight_seo_description',
		'trade_hero_eyebrow', 'trade_hero_headline', 'trade_hero_accent', 'trade_hero_lead',
		'trade_hero_cta_text', 'trade_hero_cta_link', 'trade_hero_image',
		'trade_process_title', 'trade_process_subtitle',
		'trade_form_title', 'trade_form_intro', 'trade_form_banner_title', 'trade_form_banner_text',
		'trade_form_banner_image', 'trade_form_name_label', 'trade_form_name_ph',
		'trade_form_phone_label', 'trade_form_phone_ph', 'trade_form_product_label', 'trade_form_product_ph',
		'trade_form_category_label', 'trade_form_origin_label', 'trade_form_origin_ph',
		'trade_form_dest_label', 'trade_form_dest_ph', 'trade_form_qty_label', 'trade_form_qty_ph',
		'trade_form_specs_label', 'trade_form_specs_ph', 'trade_form_notes_label', 'trade_form_notes_ph',
		'trade_form_file_label', 'trade_form_file_note', 'trade_form_submit', 'trade_form_note', 'trade_form_success',
		'trade_cats_title', 'trade_cats_subtitle', 'trade_cats_notice',
		'trade_cta_title', 'trade_cta_text', 'trade_cta1_text', 'trade_cta1_link', 'trade_cta2_text', 'trade_cta2_link',
		'trade_seo_title', 'trade_seo_description',
	);
}

/**
 * Repeater schemas for landing options.
 *
 * @return array<string, array<string, string>>
 */
function liferuss_landing_repeater_schemas() {
	return array(
		'home_landings'       => array( 'enabled' => 'text', 'title' => 'text', 'text' => 'text', 'cta' => 'text', 'link' => 'text', 'icon' => 'text' ),
		'freight_trust'       => array( 'title' => 'text', 'text' => 'text', 'icon' => 'text' ),
		'freight_services'    => array( 'enabled' => 'text', 'title' => 'text', 'text' => 'text', 'icon' => 'text', 'image' => 'text', 'image_id' => 'int' ),
		'freight_process'     => array( 'num' => 'text', 'title' => 'text', 'text' => 'text' ),
		'freight_form_types'  => array( 'label' => 'text', 'value' => 'text' ),
		'freight_items'       => array( 'title' => 'text', 'image' => 'text', 'image_id' => 'int' ),
		'trade_trust'         => array( 'title' => 'text', 'text' => 'text', 'icon' => 'text' ),
		'trade_services'      => array( 'enabled' => 'text', 'title' => 'text', 'text' => 'text', 'icon' => 'text', 'image' => 'text', 'image_id' => 'int' ),
		'trade_process'       => array( 'num' => 'text', 'title' => 'text', 'text' => 'text' ),
		'trade_form_categories'=> array( 'label' => 'text', 'value' => 'text' ),
		'trade_cats'          => array( 'title' => 'text', 'image' => 'text', 'image_id' => 'int' ),
	);
}

/**
 * i18n text keys for landing overlays.
 *
 * @return string[]
 */
function liferuss_landing_i18n_text_keys() {
	return liferuss_landing_text_keys();
}

/**
 * i18n repeater schemas (labels only).
 *
 * @return array<string, array<string, string>>
 */
function liferuss_landing_i18n_repeaters() {
	return array(
		'home_landings'      => array( 'title' => 'text', 'text' => 'text', 'cta' => 'text' ),
		'freight_trust'      => array( 'title' => 'text', 'text' => 'text' ),
		'freight_services'   => array( 'title' => 'text', 'text' => 'text' ),
		'freight_process'    => array( 'num' => 'text', 'title' => 'text', 'text' => 'text' ),
		'freight_form_types' => array( 'label' => 'text' ),
		'freight_items'      => array( 'title' => 'text' ),
		'trade_trust'        => array( 'title' => 'text', 'text' => 'text' ),
		'trade_services'     => array( 'title' => 'text', 'text' => 'text' ),
		'trade_process'      => array( 'num' => 'text', 'title' => 'text', 'text' => 'text' ),
		'trade_form_categories' => array( 'label' => 'text' ),
		'trade_cats'         => array( 'title' => 'text' ),
	);
}

/**
 * Render a repeater grid.
 *
 * @param string $key    Option key.
 * @param array  $items  Items.
 * @param array  $fields field => [label, type].
 * @param string $title  Heading.
 */
function liferuss_admin_repeater_cards( $key, $items, $fields, $title ) {
	echo '<h3>' . esc_html( $title ) . '</h3><div class="liferuss-grid">';
	foreach ( (array) $items as $i => $item ) {
		echo '<div class="liferuss-card"><h3>#' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
		foreach ( $fields as $field => $meta ) {
			$label = $meta[0];
			$type  = $meta[1];
			$value = $item[ $field ] ?? ( 'int' === $type || 'media' === $type ? 0 : '' );
			if ( 'media' === $type ) {
				liferuss_admin_media_inline( $key . '][' . $i . '][' . $field, $value, $label );
			} else {
				liferuss_admin_subfield( $key . '][' . $i . '][' . $field, $value, $label, $type );
			}
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Homepage landings tab.
 *
 * @param array $o Options.
 */
function liferuss_admin_tab_home_landings( $o ) {
	echo '<h2>کارت‌های لندینگ در صفحهٔ خانه</h2>';
	echo '<p>این بخش دو مسیر باربری و تجارت را به‌صورت واضح از صفحهٔ اصلی لینک می‌کند.</p>';
	liferuss_admin_table_start();
	liferuss_admin_field( 'home_landings_enabled', $o['home_landings_enabled'], 'نمایش بخش در صفحهٔ خانه', 'checkbox' );
	liferuss_admin_field( 'home_landings_eyebrow', $o['home_landings_eyebrow'], 'خط بالای عنوان' );
	liferuss_admin_field( 'home_landings_title', $o['home_landings_title'], 'عنوان بخش' );
	liferuss_admin_field( 'home_landings_subtitle', $o['home_landings_subtitle'], 'توضیح بخش', 'textarea' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		'home_landings',
		$o['home_landings'],
		array(
			'enabled' => array( 'فعال بودن کارت', 'checkbox' ),
			'title'   => array( 'عنوان', 'text' ),
			'text'    => array( 'توضیح', 'textarea' ),
			'cta'     => array( 'متن دکمه', 'text' ),
			'link'    => array( 'لینک (مثلاً /freight/)', 'text' ),
			'icon'    => array( 'آیکون', 'text' ),
		),
		'دو کارت لینک'
	);
}

/**
 * Shared hero + process + form + gallery admin for one landing.
 *
 * @param array  $o      Options.
 * @param string $prefix freight|trade.
 */
function liferuss_admin_tab_landing( $o, $prefix ) {
	$is_freight = 'freight' === $prefix;
	echo $is_freight ? '<h2>لندینگ باربری و ارسال</h2>' : '<h2>لندینگ تجارت و تأمین کالا</h2>';

	echo '<h3>هیرو</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $prefix . '_hero_enabled', $o[ $prefix . '_hero_enabled' ], 'نمایش هیرو', 'checkbox' );
	liferuss_admin_field( $prefix . '_hero_eyebrow', $o[ $prefix . '_hero_eyebrow' ], 'خط بالای عنوان' );
	liferuss_admin_field( $prefix . '_hero_headline', $o[ $prefix . '_hero_headline' ], 'عنوان اصلی (H1)', 'textarea' );
	liferuss_admin_field( $prefix . '_hero_accent', $o[ $prefix . '_hero_accent' ], 'عبارت طلایی داخل عنوان' );
	liferuss_admin_field( $prefix . '_hero_lead', $o[ $prefix . '_hero_lead' ], 'توضیح هیرو', 'textarea' );
	liferuss_admin_field( $prefix . '_hero_cta_text', $o[ $prefix . '_hero_cta_text' ], 'متن دکمه هیرو' );
	liferuss_admin_field( $prefix . '_hero_cta_link', $o[ $prefix . '_hero_cta_link' ], 'لینک دکمه هیرو' );
	liferuss_admin_field( $prefix . '_hero_image', $o[ $prefix . '_hero_image' ], 'تصویر پیش‌فرض قالب (مسیر)' );
	liferuss_admin_media( $prefix . '_hero_image_id', $o[ $prefix . '_hero_image_id' ], 'تصویر هیرو' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		$prefix . '_trust',
		$o[ $prefix . '_trust' ],
		array(
			'title' => array( 'عنوان', 'text' ),
			'text'  => array( 'توضیح', 'text' ),
			'icon'  => array( 'آیکون', 'text' ),
		),
		'نوار اعتماد'
	);

	echo '<h3>کارت‌های خدمات</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $prefix . '_services_enabled', $o[ $prefix . '_services_enabled' ], 'نمایش کارت‌های خدمات', 'checkbox' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		$prefix . '_services',
		$o[ $prefix . '_services' ],
		array(
			'enabled'  => array( 'فعال بودن کارت', 'checkbox' ),
			'title'    => array( 'عنوان', 'text' ),
			'text'     => array( 'توضیح', 'textarea' ),
			'icon'     => array( 'آیکون', 'text' ),
			'image'    => array( 'تصویر پیش‌فرض قالب', 'text' ),
			'image_id' => array( 'آپلود تصویر', 'media' ),
		),
		'چهار کارت'
	);

	echo '<h3>مراحل همکاری</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $prefix . '_process_enabled', $o[ $prefix . '_process_enabled' ], 'نمایش مراحل', 'checkbox' );
	liferuss_admin_field( $prefix . '_process_title', $o[ $prefix . '_process_title' ], 'عنوان' );
	liferuss_admin_field( $prefix . '_process_subtitle', $o[ $prefix . '_process_subtitle' ], 'توضیح', 'textarea' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		$prefix . '_process',
		$o[ $prefix . '_process' ],
		array(
			'num'   => array( 'شماره', 'text' ),
			'title' => array( 'عنوان', 'text' ),
			'text'  => array( 'توضیح', 'textarea' ),
		),
		'پنج گام'
	);

	echo '<h3>فرم درخواست</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $prefix . '_form_enabled', $o[ $prefix . '_form_enabled' ], 'نمایش فرم', 'checkbox' );
	liferuss_admin_field( $prefix . '_form_title', $o[ $prefix . '_form_title' ], 'عنوان فرم' );
	liferuss_admin_field( $prefix . '_form_intro', $o[ $prefix . '_form_intro' ], 'معرفی فرم', 'textarea' );
	liferuss_admin_field( $prefix . '_form_banner_title', $o[ $prefix . '_form_banner_title' ], 'عنوان بنر کناری' );
	liferuss_admin_field( $prefix . '_form_banner_text', $o[ $prefix . '_form_banner_text' ], 'متن بنر کناری', 'textarea' );
	liferuss_admin_field( $prefix . '_form_banner_image', $o[ $prefix . '_form_banner_image' ], 'تصویر پیش‌فرض بنر' );
	liferuss_admin_media( $prefix . '_form_banner_image_id', $o[ $prefix . '_form_banner_image_id' ], 'تصویر بنر کناری' );
	liferuss_admin_field( $prefix . '_form_name_label', $o[ $prefix . '_form_name_label' ], 'برچسب نام' );
	liferuss_admin_field( $prefix . '_form_name_ph', $o[ $prefix . '_form_name_ph' ], 'placeholder نام' );
	liferuss_admin_field( $prefix . '_form_phone_label', $o[ $prefix . '_form_phone_label' ], 'برچسب تلفن' );
	liferuss_admin_field( $prefix . '_form_phone_ph', $o[ $prefix . '_form_phone_ph' ], 'placeholder تلفن' );
	if ( $is_freight ) {
		liferuss_admin_field( 'freight_form_origin_label', $o['freight_form_origin_label'], 'برچسب مبدأ' );
		liferuss_admin_field( 'freight_form_origin_ph', $o['freight_form_origin_ph'], 'placeholder مبدأ' );
		liferuss_admin_field( 'freight_form_dest_label', $o['freight_form_dest_label'], 'برچسب مقصد' );
		liferuss_admin_field( 'freight_form_dest_ph', $o['freight_form_dest_ph'], 'placeholder مقصد' );
		liferuss_admin_field( 'freight_form_type_label', $o['freight_form_type_label'], 'برچسب نوع بار' );
		liferuss_admin_field( 'freight_form_weight_label', $o['freight_form_weight_label'], 'برچسب وزن' );
		liferuss_admin_field( 'freight_form_weight_ph', $o['freight_form_weight_ph'], 'placeholder وزن' );
		liferuss_admin_field( 'freight_form_dims_label', $o['freight_form_dims_label'], 'برچسب ابعاد' );
		liferuss_admin_field( 'freight_form_dims_ph', $o['freight_form_dims_ph'], 'placeholder ابعاد' );
		liferuss_admin_field( 'freight_form_packages_label', $o['freight_form_packages_label'], 'برچسب تعداد بسته' );
		liferuss_admin_field( 'freight_form_packages_ph', $o['freight_form_packages_ph'], 'placeholder تعداد بسته' );
		liferuss_admin_field( 'freight_form_value_label', $o['freight_form_value_label'], 'برچسب ارزش' );
		liferuss_admin_field( 'freight_form_value_ph', $o['freight_form_value_ph'], 'placeholder ارزش' );
	} else {
		liferuss_admin_field( 'trade_form_product_label', $o['trade_form_product_label'], 'برچسب نام کالا' );
		liferuss_admin_field( 'trade_form_product_ph', $o['trade_form_product_ph'], 'placeholder نام کالا' );
		liferuss_admin_field( 'trade_form_category_label', $o['trade_form_category_label'], 'برچسب دسته‌بندی' );
		liferuss_admin_field( 'trade_form_origin_label', $o['trade_form_origin_label'], 'برچسب کشور مبدأ' );
		liferuss_admin_field( 'trade_form_origin_ph', $o['trade_form_origin_ph'], 'placeholder مبدأ' );
		liferuss_admin_field( 'trade_form_dest_label', $o['trade_form_dest_label'], 'برچسب کشور مقصد' );
		liferuss_admin_field( 'trade_form_dest_ph', $o['trade_form_dest_ph'], 'placeholder مقصد' );
		liferuss_admin_field( 'trade_form_qty_label', $o['trade_form_qty_label'], 'برچسب حجم' );
		liferuss_admin_field( 'trade_form_qty_ph', $o['trade_form_qty_ph'], 'placeholder حجم' );
		liferuss_admin_field( 'trade_form_specs_label', $o['trade_form_specs_label'], 'برچسب مشخصات' );
		liferuss_admin_field( 'trade_form_specs_ph', $o['trade_form_specs_ph'], 'placeholder مشخصات' );
	}
	liferuss_admin_field( $prefix . '_form_notes_label', $o[ $prefix . '_form_notes_label' ], 'برچسب توضیحات' );
	liferuss_admin_field( $prefix . '_form_notes_ph', $o[ $prefix . '_form_notes_ph' ], 'placeholder توضیحات' );
	liferuss_admin_field( $prefix . '_form_file_label', $o[ $prefix . '_form_file_label' ], 'برچسب فایل' );
	liferuss_admin_field( $prefix . '_form_file_note', $o[ $prefix . '_form_file_note' ], 'توضیح فایل' );
	liferuss_admin_field( $prefix . '_form_submit', $o[ $prefix . '_form_submit' ], 'متن دکمه ارسال' );
	liferuss_admin_field( $prefix . '_form_note', $o[ $prefix . '_form_note' ], 'توضیح زیر فرم' );
	liferuss_admin_field( $prefix . '_form_success', $o[ $prefix . '_form_success' ], 'پیام موفقیت', 'textarea' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		$is_freight ? 'freight_form_types' : 'trade_form_categories',
		$is_freight ? $o['freight_form_types'] : $o['trade_form_categories'],
		array(
			'label' => array( 'برچسب گزینه', 'text' ),
			'value' => array( 'مقدار', 'text' ),
		),
		$is_freight ? 'گزینه‌های نوع بار' : 'گزینه‌های دسته‌بندی کالا'
	);

	$gallery_flag = $is_freight ? 'freight_items_enabled' : 'trade_cats_enabled';
	$gallery_key  = $is_freight ? 'freight_items' : 'trade_cats';
	echo $is_freight ? '<h3>موارد معمول ارسال</h3>' : '<h3>دسته‌بندی محصولات</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $gallery_flag, $o[ $gallery_flag ], 'نمایش گالری دایره‌ای', 'checkbox' );
	liferuss_admin_field( $is_freight ? 'freight_items_title' : 'trade_cats_title', $o[ $is_freight ? 'freight_items_title' : 'trade_cats_title' ], 'عنوان' );
	liferuss_admin_field( $is_freight ? 'freight_items_subtitle' : 'trade_cats_subtitle', $o[ $is_freight ? 'freight_items_subtitle' : 'trade_cats_subtitle' ], 'توضیح', 'textarea' );
	liferuss_admin_field( $is_freight ? 'freight_items_notice' : 'trade_cats_notice', $o[ $is_freight ? 'freight_items_notice' : 'trade_cats_notice' ], 'متن اعلان / محدودیت', 'textarea' );
	liferuss_admin_table_end();
	liferuss_admin_repeater_cards(
		$gallery_key,
		$o[ $gallery_key ],
		array(
			'title'    => array( 'عنوان', 'text' ),
			'image'    => array( 'تصویر پیش‌فرض قالب', 'text' ),
			'image_id' => array( 'آپلود تصویر', 'media' ),
		),
		'آیتم‌های گالری'
	);

	echo '<h3>نوار CTA پایین صفحه</h3>';
	liferuss_admin_table_start();
	liferuss_admin_field( $prefix . '_cta_enabled', $o[ $prefix . '_cta_enabled' ], 'نمایش نوار CTA', 'checkbox' );
	liferuss_admin_field( $prefix . '_cta_title', $o[ $prefix . '_cta_title' ], 'عنوان' );
	liferuss_admin_field( $prefix . '_cta_text', $o[ $prefix . '_cta_text' ], 'توضیح', 'textarea' );
	liferuss_admin_field( $prefix . '_cta1_text', $o[ $prefix . '_cta1_text' ], 'متن دکمه اول' );
	liferuss_admin_field( $prefix . '_cta1_link', $o[ $prefix . '_cta1_link' ], 'لینک دکمه اول' );
	liferuss_admin_field( $prefix . '_cta2_text', $o[ $prefix . '_cta2_text' ], 'متن دکمه دوم' );
	liferuss_admin_field( $prefix . '_cta2_link', $o[ $prefix . '_cta2_link' ], 'لینک دکمه دوم' );
	liferuss_admin_field( $prefix . '_seo_title', $o[ $prefix . '_seo_title' ], 'عنوان سئو' );
	liferuss_admin_field( $prefix . '_seo_description', $o[ $prefix . '_seo_description' ], 'توضیح سئو', 'textarea' );
	liferuss_admin_table_end();
}

/**
 * Render the three landing tabs.
 *
 * @param array $o Options.
 */
function liferuss_admin_landings_tabs( $o ) {
	echo '<div id="tab-home_landings" class="liferuss-tab">';
	liferuss_admin_tab_home_landings( $o );
	echo '</div>';
	echo '<div id="tab-freight" class="liferuss-tab">';
	liferuss_admin_tab_landing( $o, 'freight' );
	echo '</div>';
	echo '<div id="tab-trade" class="liferuss-tab">';
	liferuss_admin_tab_landing( $o, 'trade' );
	echo '</div>';
}

/**
 * Extra i18n editor blocks for landings.
 *
 * @param string $code Language code.
 * @param array  $fa   Persian defaults.
 */
function liferuss_admin_i18n_landings( $code, $fa ) {
	$fields = array(
		'home_landings_eyebrow'  => 'خانه: خط بالا',
		'home_landings_title'    => 'خانه: عنوان لندینگ‌ها',
		'home_landings_subtitle' => 'خانه: توضیح لندینگ‌ها',
		'freight_hero_headline'  => 'باربری: عنوان هیرو',
		'freight_hero_lead'      => 'باربری: توضیح هیرو',
		'freight_hero_cta_text'  => 'باربری: دکمه هیرو',
		'freight_process_title'  => 'باربری: عنوان مراحل',
		'freight_form_title'     => 'باربری: عنوان فرم',
		'freight_form_submit'    => 'باربری: دکمه فرم',
		'freight_items_title'    => 'باربری: عنوان گالری',
		'freight_cta_title'      => 'باربری: عنوان CTA',
		'trade_hero_headline'    => 'تجارت: عنوان هیرو',
		'trade_hero_lead'        => 'تجارت: توضیح هیرو',
		'trade_hero_cta_text'    => 'تجارت: دکمه هیرو',
		'trade_process_title'    => 'تجارت: عنوان مراحل',
		'trade_form_title'       => 'تجارت: عنوان فرم',
		'trade_form_submit'      => 'تجارت: دکمه فرم',
		'trade_cats_title'       => 'تجارت: عنوان دسته‌ها',
		'trade_cta_title'        => 'تجارت: عنوان CTA',
	);
	echo '<h3>لندینگ باربری و تجارت</h3>';
	liferuss_admin_table_start();
	foreach ( $fields as $key => $label ) {
		$type = ( false !== strpos( $key, 'lead' ) || false !== strpos( $key, 'subtitle' ) || false !== strpos( $key, 'headline' ) ) ? 'textarea' : 'text';
		liferuss_admin_field( 'i18n][' . $code . '][' . $key, liferuss_admin_i18n_value( $code, $key ), $label, $type );
	}
	liferuss_admin_table_end();

	$lists = array(
		'home_landings'    => array( 'کارت‌های خانه', array( 'title' => 'عنوان', 'text' => 'توضیح', 'cta' => 'دکمه' ), count( $fa['home_landings'] ) ),
		'freight_services' => array( 'خدمات باربری', array( 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['freight_services'] ) ),
		'freight_process'  => array( 'مراحل باربری', array( 'num' => 'شماره', 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['freight_process'] ) ),
		'trade_services'   => array( 'خدمات تجارت', array( 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['trade_services'] ) ),
		'trade_process'    => array( 'مراحل تجارت', array( 'num' => 'شماره', 'title' => 'عنوان', 'text' => 'توضیح' ), count( $fa['trade_process'] ) ),
	);
	foreach ( $lists as $key => $info ) {
		echo '<h3>' . esc_html( $info[0] ) . '</h3><div class="liferuss-grid">';
		for ( $i = 0; $i < $info[2]; $i++ ) {
			echo '<div class="liferuss-card"><h3>#' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
			foreach ( $info[1] as $field => $lab ) {
				$type = ( 'text' === $field ) ? 'textarea' : 'text';
				liferuss_admin_subfield(
					'i18n][' . $code . '][' . $key . '][' . $i . '][' . $field,
					liferuss_admin_i18n_value( $code, $key, array( $i, $field ) ),
					$lab,
					$type
				);
			}
			echo '</div>';
		}
		echo '</div>';
	}
}
