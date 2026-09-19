<?php
/**
 * Search results.
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'search_eyebrow' ) ); ?></p>
		<h1><?php echo esc_html( get_search_query() ? liferuss_t( 'search_results' ) : liferuss_t( 'search_title' ) ); ?></h1>
		<?php get_search_form(); ?>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<div class="container archive-layout">
	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article <?php post_class( 'post-card' ); ?>>
					<div class="post-card-body">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="empty-state">
			<h2><?php echo esc_html( liferuss_t( 'search_empty_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_t( 'search_empty_text' ) ); ?></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
