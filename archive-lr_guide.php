<?php
/**
 * Russia guide archive.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$terms = get_terms(
	array(
		'taxonomy'   => 'lr_guide_cat',
		'hide_empty' => true,
	)
);
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_guide' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'nav_guide' ) ); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
		<nav class="path-children" aria-label="<?php echo esc_attr( liferuss_t( 'nav_guide' ) ); ?>">
			<a class="path-child" href="<?php echo esc_url( liferuss_url( '/cities/' ) ); ?>"><strong><?php echo esc_html( liferuss_t( 'nav_cities' ) ); ?></strong></a>
			<?php if ( is_array( $terms ) ) : ?>
				<?php foreach ( $terms as $term ) : ?>
					<a class="path-child" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><strong><?php echo esc_html( $term->name ); ?></strong></a>
				<?php endforeach; ?>
			<?php endif; ?>
		</nav>
	</div>
</header>
<?php liferuss_the_guide_culture(); ?>
<section class="section">
	<div class="container">
		<div class="lr-cards">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article class="lr-card"><div class="lr-card-body">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( has_excerpt() ) : ?>
							<p class="lr-meta"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
					</div></article>
				<?php endwhile; ?>
			<?php else : ?>
				<?php liferuss_empty_catalog( 'راهنمایی منتشر نشده است' ); ?>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
