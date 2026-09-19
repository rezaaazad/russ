<?php
/**
 * Template Name: درباره ما
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
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'about_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'about_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<section class="section">
	<div class="container prose about-intro">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		}
		?>
	</div>
	<div class="container values-grid">
		<article class="service-card">
			<span class="icon-circle"><?php echo liferuss_icon( 'shield' ); ?></span>
			<h3><?php echo esc_html( liferuss_t( 'about_v1_title' ) ); ?></h3>
			<p><?php echo esc_html( liferuss_t( 'about_v1_text' ) ); ?></p>
		</article>
		<article class="service-card">
			<span class="icon-circle"><?php echo liferuss_icon( 'chat' ); ?></span>
			<h3><?php echo esc_html( liferuss_t( 'about_v2_title' ) ); ?></h3>
			<p><?php echo esc_html( liferuss_t( 'about_v2_text' ) ); ?></p>
		</article>
		<article class="service-card">
			<span class="icon-circle"><?php echo liferuss_icon( 'users' ); ?></span>
			<h3><?php echo esc_html( liferuss_t( 'about_v3_title' ) ); ?></h3>
			<p><?php echo esc_html( liferuss_t( 'about_v3_text' ) ); ?></p>
		</article>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
