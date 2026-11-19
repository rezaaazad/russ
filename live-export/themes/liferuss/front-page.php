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

if ( function_exists( 'liferuss_the_announcement' ) ) {
	liferuss_the_announcement();
}

if ( function_exists( 'liferuss_home_v2_active' ) && liferuss_home_v2_active() ) {
	foreach ( liferuss_home_v2_sections() as $section ) {
		get_template_part( 'template-parts/home-v2/' . $section );
		if ( 'hero' === $section && function_exists( 'liferuss_ruble_only' ) && liferuss_ruble_only() && function_exists( 'liferuss_the_ruble_spot' ) ) {
			echo '<section class="section lr-home-rates"><div class="container">';
			liferuss_the_ruble_spot();
			echo '<p class="lr-home-rates__cta"><a class="btn btn-gold" href="' . esc_url( liferuss_url( '/exchange/tuition-payment/' ) ) . '">پرداخت شهریه دانشگاه‌های روسیه</a></p>';
			echo '</div></section>';
		}
	}
} else {
foreach ( liferuss_home_sections() as $section ) {
	if ( empty( $section['enabled'] ) ) {
		continue;
	}
	get_template_part( 'template-parts/home/' . $section['id'] );
}
}

get_footer();
