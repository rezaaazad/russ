<?php
/**
 * Ordered homepage sections. Defaults reproduce the previous fixed homepage.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section catalog. Disabled extras stay out of the default paint.
 *
 * @return array<string, array{label: string, enabled: string}>
 */
function liferuss_home_section_defs() {
	return array(
		'hero'         => array( 'label' => 'هیرو', 'enabled' => '1' ),
		'services'     => array( 'label' => 'شبکه خدمات', 'enabled' => '1' ),
		'paths'        => array( 'label' => 'مسیرهای تحصیل و خدمات', 'enabled' => '1' ),
		'landings'     => array( 'label' => 'لندینگ‌های خانه', 'enabled' => '1' ),
		'universities' => array( 'label' => 'دانشگاه‌های ویژه', 'enabled' => '1' ),
		'majors'       => array( 'label' => 'رشته‌ها', 'enabled' => '1' ),
		'costs'        => array( 'label' => 'هزینه‌ها', 'enabled' => '1' ),
		'roadmap'      => array( 'label' => 'نقشه راه', 'enabled' => '1' ),
		'testimonials' => array( 'label' => 'تجربه‌ها', 'enabled' => '1' ),
		'cta'          => array( 'label' => 'فرم درخواست', 'enabled' => '1' ),
		'stats'        => array( 'label' => 'آمار (جدا)', 'enabled' => '0' ),
		'scholarships' => array( 'label' => 'بورسیه‌ها', 'enabled' => '0' ),
		'blog'         => array( 'label' => 'تازه‌های مجله', 'enabled' => '0' ),
		'faq'          => array( 'label' => 'سؤالات متداول', 'enabled' => '0' ),
	);
}

/**
 * Saved order merged onto the catalog. Unknown ids are dropped.
 *
 * @return array<int, array{id: string, enabled: string, title: string, label: string}>
 */
function liferuss_home_sections() {
	$defs   = liferuss_home_section_defs();
	$stored = get_option( 'liferuss_home_sections', array() );
	$rows   = array();
	$seen   = array();
	if ( is_array( $stored ) ) {
		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) ) {
				continue;
			}
			$id = sanitize_key( (string) $row['id'] );
			if ( ! isset( $defs[ $id ] ) || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$rows[]      = array(
				'id'      => $id,
				'enabled' => empty( $row['enabled'] ) ? '0' : '1',
				'title'   => isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '',
				'label'   => $defs[ $id ]['label'],
			);
		}
	}
	foreach ( $defs as $id => $def ) {
		if ( isset( $seen[ $id ] ) ) {
			continue;
		}
		$rows[] = array(
			'id'      => $id,
			'enabled' => $def['enabled'],
			'title'   => '',
			'label'   => $def['label'],
		);
	}
	return $rows;
}

/**
 * Whether a section is turned on.
 *
 * @param string $id Section id.
 */
function liferuss_home_section_enabled( $id ) {
	foreach ( liferuss_home_sections() as $row ) {
		if ( $row['id'] === $id ) {
			return '1' === $row['enabled'];
		}
	}
	return false;
}

/**
 * Optional heading. An empty override keeps the existing copy.
 *
 * @param string $id       Section id.
 * @param string $fallback Current heading.
 */
function liferuss_home_heading( $id, $fallback ) {
	foreach ( liferuss_home_sections() as $row ) {
		if ( $row['id'] === $id && '' !== $row['title'] ) {
			return $row['title'];
		}
	}
	return (string) $fallback;
}

/**
 * Appearance screen.
 */
function liferuss_home_sections_menu() {
	add_theme_page(
		'بخش‌های صفحهٔ اصلی',
		'بخش‌های صفحهٔ اصلی',
		'edit_theme_options',
		'liferuss-home',
		'liferuss_home_sections_screen'
	);
}
add_action( 'admin_menu', 'liferuss_home_sections_menu' );

/**
 * Sortable list.
 */
function liferuss_home_sections_screen() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$rows = liferuss_home_sections();
	echo '<div class="wrap"><h1>بخش‌های صفحهٔ اصلی</h1>';
	echo '<p>ترتیب را بکشید. عنوان خالی همان متن فعلی بخش را نگه می‌دارد. آمار جدا، بورسیه، مجله و سؤالات در حالت پیش‌فرض خاموش‌اند تا ظاهر خانه عوض نشود.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'liferuss_home_sections', 'liferuss_home_nonce' );
	echo '<input type="hidden" name="action" value="liferuss_home_sections">';
	echo '<ul id="lr-home-sections">';
	foreach ( $rows as $row ) {
		echo '<li style="background:#fff;border:1px solid #c3c4c7;padding:8px 12px;margin:0 0 8px;display:flex;gap:12px;align-items:center;max-width:720px">';
		echo '<span class="dashicons dashicons-menu" aria-hidden="true"></span>';
		echo '<input type="hidden" name="section_id[]" value="' . esc_attr( $row['id'] ) . '">';
		echo '<label><input type="checkbox" name="section_on[]" value="' . esc_attr( $row['id'] ) . '" ' . checked( '1', $row['enabled'], false ) . '> ' . esc_html( $row['label'] ) . '</label>';
		echo '<input class="regular-text" type="text" name="section_title[' . esc_attr( $row['id'] ) . ']" value="' . esc_attr( $row['title'] ) . '" placeholder="عنوان اختیاری">';
		echo '</li>';
	}
	echo '</ul>';
	submit_button( 'ذخیره ترتیب' );
	echo '</form></div>';
	echo '<script>jQuery(function($){ $("#lr-home-sections").sortable(); });</script>';
}

/**
 * Sortable library on this screen only.
 *
 * @param string $hook Current admin page.
 */
function liferuss_home_sections_assets( $hook ) {
	if ( 'appearance_page_liferuss-home' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'jquery-ui-sortable' );
}
add_action( 'admin_enqueue_scripts', 'liferuss_home_sections_assets' );

/**
 * Persist order, toggles, and optional titles.
 */
function liferuss_home_sections_save() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'اجازه ندارید.', 'liferuss' ) );
	}
	check_admin_referer( 'liferuss_home_sections', 'liferuss_home_nonce' );
	$defs  = liferuss_home_section_defs();
	$ids   = isset( $_POST['section_id'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['section_id'] ) ) : array();
	$on    = isset( $_POST['section_on'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['section_on'] ) ) : array();
	$titles = isset( $_POST['section_title'] ) && is_array( $_POST['section_title'] ) ? wp_unslash( $_POST['section_title'] ) : array();
	$rows  = array();
	foreach ( $ids as $id ) {
		if ( ! isset( $defs[ $id ] ) ) {
			continue;
		}
		$title  = isset( $titles[ $id ] ) ? sanitize_text_field( (string) $titles[ $id ] ) : '';
		$rows[] = array(
			'id'      => $id,
			'enabled' => in_array( $id, $on, true ) ? '1' : '0',
			'title'   => $title,
		);
	}
	update_option( 'liferuss_home_sections', $rows, false );
	wp_safe_redirect( admin_url( 'themes.php?page=liferuss-home&updated=1' ) );
	exit;
}
add_action( 'admin_post_liferuss_home_sections', 'liferuss_home_sections_save' );
