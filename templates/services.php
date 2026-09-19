<?php
/**
 * Template Name: خدمات
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'services_page_eye' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'services_page_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'services_page_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<section class="section">
	<div class="container services-page-grid">
		<?php foreach ( liferuss_services() as $service ) : ?>
			<article class="service-card service-card-wide">
				<span class="icon-circle"><?php echo liferuss_icon( $service['icon'] ); ?></span>
				<h3><?php echo esc_html( $service['title'] ); ?></h3>
				<p><?php echo esc_html( $service['text'] ); ?></p>
			</article>
		<?php endforeach; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
