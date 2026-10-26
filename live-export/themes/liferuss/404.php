<?php
/**
 * 404 template.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="empty-state empty-state-page">
	<div class="container">
		<p class="eyebrow">۴۰۴</p>
		<h1><?php echo esc_html( liferuss_t( 'page_404_title' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'page_404_text' ) ); ?></p>
		<a class="btn btn-gold" href="<?php echo esc_url( liferuss_home() ); ?>"><?php echo esc_html( liferuss_t( 'page_404_back' ) ); ?></a>
	</div>
</section>

<?php
get_footer();
