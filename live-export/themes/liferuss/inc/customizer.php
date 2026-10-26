<?php
/**
 * Customizer shortcut — full editing lives in Theme Options.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Point Customizer to the Theme Options screen.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function liferuss_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'liferuss_notice',
		array(
			'title'       => 'لایف روس',
			'description' => 'متن‌ها، تصاویر، رنگ‌ها و فرم از <a href="' . esc_url( admin_url( 'themes.php?page=liferuss-options' ) ) . '">نمایش ← تنظیمات لایف روس</a> ویرایش می‌شوند.',
			'priority'    => 30,
		)
	);
	$wp_customize->add_setting(
		'liferuss_options_link',
		array(
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'liferuss_options_link',
		array(
			'label'       => 'پنل تنظیمات قالب',
			'description' => 'Appearance → تنظیمات لایف روس',
			'section'     => 'liferuss_notice',
			'type'        => 'hidden',
		)
	);
}
add_action( 'customize_register', 'liferuss_customize_register' );
