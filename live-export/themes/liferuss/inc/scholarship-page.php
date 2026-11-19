<?php
/**
 * «بورسیه روسیه» on the existing /scholarships/ URL.
 *
 * If a page with that slug already exists, its template is set on admin_init.
 * No second page is inserted and no HTTP request runs. When the address is
 * still the scholarship archive, the same template file is used.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hero photo. Replace this one file to swap the picture.
 */
function liferuss_scholarship_hero_file() {
	return 'hero-scholarship.webp';
}

/**
 * Consultation card photo. Replace this one file to swap the picture.
 */
function liferuss_scholarship_consult_file() {
	return 'card-consultation.webp';
}

/**
 * Suggested hero paragraph from the spec.
 */
function liferuss_scholarship_hero_lead() {
	return 'لایف روس شما را برای دریافت بورسیه در دانشگاه‌های روسیه همراهی می‌کند: از پزشکی و دندانپزشکی تا مهندسی، کارشناسی ارشد و دکتری.';
}

/**
 * Consultation button. Edited in the core form settings. No phone link.
 */
function liferuss_scholarship_consult_url() {
	$path = '/contact/';
	if ( class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
		$saved = isset( $forms['scholarship_consult_url'] ) ? trim( (string) $forms['scholarship_consult_url'] ) : '';
		if ( '' !== $saved && ! str_starts_with( strtolower( $saved ), 'tel:' ) ) {
			$path = $saved;
		}
	}
	return liferuss_cta_url( $path );
}

/**
 * Degree choices. The last one stores no degree on the request row.
 *
 * @return array<string, string>
 */
function liferuss_scholarship_degrees() {
	return array(
		'bachelor' => 'کارشناسی',
		'master'   => 'کارشناسی ارشد',
		'phd'      => 'دکتری',
		'unsure'   => 'هنوز مطمئن نیستم',
	);
}

/**
 * Field choices.
 *
 * @return array<string, string>
 */
function liferuss_scholarship_fields() {
	return array(
		'medicine'    => 'پزشکی',
		'dentistry'   => 'دندانپزشکی',
		'engineering' => 'مهندسی',
		'other'       => 'سایر',
	);
}

/**
 * Language-certificate choices. No scores.
 *
 * @return array<string, string>
 */
function liferuss_scholarship_languages() {
	return array(
		'none'  => 'ندارم',
		'en'    => 'مدرک انگلیسی',
		'ru'    => 'مدرک روسی',
		'other' => 'سایر',
	);
}

/**
 * Stats row. Empty until the option is on and all four figures are saved.
 *
 * @return array<int, array{icon: string, value: string, label: string}>|null
 */
function liferuss_scholarship_stats() {
	if ( ! class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		return null;
	}
	$forms = \LifeRuss\Core\Settings\Settings::get( 'forms' );
	if ( '1' !== (string) ( $forms['scholarship_stats_on'] ?? '0' ) ) {
		return null;
	}
	$rows = array(
		array( 'icon' => 'shield', 'key' => 'scholarship_stat_satisfaction', 'label' => 'رضایت متقاضیان' ),
		array( 'icon' => 'steps', 'key' => 'scholarship_stat_years', 'label' => 'سال تجربه و تخصص' ),
		array( 'icon' => 'building', 'key' => 'scholarship_stat_universities', 'label' => 'دانشگاه همکار در روسیه' ),
		array( 'icon' => 'cap', 'key' => 'scholarship_stat_students', 'label' => 'دانشجوی اعزام‌شده' ),
	);
	$out = array();
	foreach ( $rows as $row ) {
		$value = trim( (string) ( $forms[ $row['key'] ] ?? '' ) );
		if ( '' === $value ) {
			return null;
		}
		$out[] = array(
			'icon'  => $row['icon'],
			'value' => $value,
			'label' => $row['label'],
		);
	}
	return $out;
}

/**
 * Public URL of one scholarship image.
 *
 * @param string $file File name inside assets/images/scholarship.
 */
function liferuss_scholarship_img( $file ) {
	return trailingslashit( LIFERUSS_URI ) . 'assets/images/scholarship/' . ltrim( (string) $file, '/' );
}

/**
 * Assign the template to an existing /scholarships/ page. Never inserts one.
 */
function liferuss_scholarship_ensure() {
	if ( ! is_admin() ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_scholarship_page_ready', '' ) ) {
		return;
	}
	if ( '1' === (string) get_option( 'lr_scholarship_page_lock', '' ) ) {
		return;
	}
	update_option( 'lr_scholarship_page_lock', '1', false );
	$page = get_page_by_path( 'scholarships' );
	if ( $page instanceof WP_Post ) {
		update_post_meta( (int) $page->ID, '_wp_page_template', 'templates/scholarship.php' );
	}
	update_option( 'lr_scholarship_page_ready', '1', false );
	delete_option( 'lr_scholarship_page_lock' );
}
add_action( 'admin_init', 'liferuss_scholarship_ensure', 62 );

/**
 * Keep a published scholarships page in front of the empty catalog archive.
 *
 * @param array<string, mixed> $vars Query vars.
 * @return array<string, mixed>
 */
function liferuss_scholarship_prefer_page( $vars ) {
	if ( is_admin() || ! is_array( $vars ) ) {
		return $vars;
	}
	if ( ( $vars['post_type'] ?? '' ) !== 'lr_scholarship' ) {
		return $vars;
	}
	if ( ! empty( $vars['lr_scholarship'] ) || ! empty( $vars['name'] ) || ! empty( $vars['p'] ) ) {
		return $vars;
	}
	$page = get_page_by_path( 'scholarships' );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return $vars;
	}
	if ( 'templates/scholarship.php' !== (string) get_post_meta( $page->ID, '_wp_page_template', true ) ) {
		return $vars;
	}
	return array( 'page_id' => (int) $page->ID );
}
add_filter( 'request', 'liferuss_scholarship_prefer_page', 20 );

/**
 * The archive index uses the same template until a page owns the slug.
 *
 * @param string $template Current template path.
 */
function liferuss_scholarship_template( $template ) {
	if ( ! is_post_type_archive( 'lr_scholarship' ) ) {
		return $template;
	}
	$file = LIFERUSS_DIR . '/templates/scholarship.php';
	return is_readable( $file ) ? $file : $template;
}
add_filter( 'template_include', 'liferuss_scholarship_template' );

/**
 * Page styles.
 */
function liferuss_scholarship_assets() {
	$archive = is_post_type_archive( 'lr_scholarship' );
	if ( ! $archive && ! is_page_template( 'templates/scholarship.php' ) ) {
		return;
	}
	wp_enqueue_style(
		'liferuss-scholarship',
		LIFERUSS_URI . '/assets/css/scholarship.css',
		array( 'liferuss-theme' ),
		LIFERUSS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_scholarship_assets' );
