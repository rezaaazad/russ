<?php
/**
 * Blog / fallback index.
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
		<h1><?php echo is_home() ? esc_html( liferuss_t( 'blog_title' ) ) : wp_kses_post( get_the_archive_title() ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'blog_intro' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<div class="container archive-layout">
	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article <?php post_class( 'post-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="post-thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'liferuss-card' ); ?></a>
					<?php endif; ?>
					<div class="post-card-body">
						<p class="post-date"><?php echo esc_html( get_the_date() ); ?></p>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						<a class="text-link" href="<?php the_permalink(); ?>"><?php echo esc_html( liferuss_t( 'read_more' ) ); ?> <?php echo liferuss_icon( 'arrow' ); ?></a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="pagination">
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => liferuss_t( 'slider_prev' ), 'next_text' => liferuss_t( 'slider_next' ) ) ); ?>
		</div>
	<?php else : ?>
		<div class="empty-state">
			<h2><?php echo esc_html( liferuss_t( 'blog_empty_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_t( 'blog_empty_text' ) ); ?></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
