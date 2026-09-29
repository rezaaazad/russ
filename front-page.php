<?php
/**
 * Homepage — لایف روس.
 *
 * Sections come from Appearance → بخش‌های صفحهٔ اصلی. The stored default
 * matches the previous fixed order, so an untouched site looks the same.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

foreach ( liferuss_home_sections() as $section ) {
	if ( empty( $section['enabled'] ) ) {
		continue;
	}
	get_template_part( 'template-parts/home/' . $section['id'] );
}

get_footer();
