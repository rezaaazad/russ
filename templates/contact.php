<?php
/**
 * Template Name: تماس با ما
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'contact_page_eye' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'contact_page_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'contact_page_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<?php get_template_part( 'template-parts/contact-form' ); ?>

<?php
get_footer();
