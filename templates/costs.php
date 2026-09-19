<?php
/**
 * Template Name: هزینه‌ها
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'costs_page_eye' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'costs_page_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'costs_page_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<section class="section">
	<div class="container costs-grid">
		<?php foreach ( liferuss_costs() as $cost ) : ?>
			<article class="cost-card">
				<span class="icon-circle"><?php echo liferuss_icon( $cost['icon'] ); ?></span>
				<h3><?php echo esc_html( $cost['title'] ); ?></h3>
				<p class="cost-value"><?php echo esc_html( $cost['value'] ); ?></p>
				<p class="cost-note"><?php echo esc_html( $cost['note'] ); ?></p>
			</article>
		<?php endforeach; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
