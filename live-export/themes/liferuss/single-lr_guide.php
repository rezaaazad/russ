<?php
/**
 * One Russia guide.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
while ( have_posts() ) {
	the_post();
}
$post_id = (int) get_the_ID();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><a href="<?php echo esc_url( get_post_type_archive_link( 'lr_guide' ) ); ?>"><?php echo esc_html( liferuss_t( 'nav_guide' ) ); ?></a></p>
		<h1><?php the_title(); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<section class="section">
	<div class="container path-prose">
		<?php the_content(); ?>
		<p><a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/cities/' ) ); ?>"><?php echo esc_html( liferuss_t( 'nav_cities' ) ); ?></a></p>
	</div>
</section>
<?php
liferuss_path_faqs( $post_id );
get_footer();
