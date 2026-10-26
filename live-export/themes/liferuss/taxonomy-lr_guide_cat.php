<?php
/**
 * One guide category.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$term = get_queried_object();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><a href="<?php echo esc_url( get_post_type_archive_link( 'lr_guide' ) ); ?>"><?php echo esc_html( liferuss_t( 'nav_guide' ) ); ?></a></p>
		<h1><?php echo esc_html( $term instanceof WP_Term ? $term->name : '' ); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
		<p><a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/cities/' ) ); ?>"><?php echo esc_html( liferuss_t( 'nav_cities' ) ); ?></a></p>
	</div>
</header>
<?php liferuss_the_guide_culture(); ?>
<section class="section">
	<div class="container lr-cards">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article class="lr-card"><div class="lr-card-body">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				</div></article>
			<?php endwhile; ?>
		<?php else : ?>
			<p class="lr-empty"><?php echo esc_html( liferuss_t( 'path_empty' ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
