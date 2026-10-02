<?php
/**
 * Category, tag, and date archives.
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'blog_eyebrow' ) ); ?></p>
		<h1><?php echo wp_kses_post( get_the_archive_title() ); ?></h1>
		<?php the_archive_description( '<p>', '</p>' ); ?>
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
					<a class="post-thumb" href="<?php the_permalink(); ?>"><?php liferuss_the_entry_image(); ?></a>
					<div class="post-card-body">
						<p class="post-date"><?php echo esc_html( get_the_date() ); ?></p>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="pagination"><?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?></div>
	<?php else : ?>
		<div class="empty-state"><p><?php echo esc_html( liferuss_t( 'blog_empty_text' ) ); ?></p></div>
	<?php endif; ?>
</div>
<?php
get_footer();
