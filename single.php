<?php
/**
 * Single post template.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article <?php post_class( 'single-article' ); ?>>
			<header class="page-hero">
				<div class="container">
					<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
					<h1><?php the_title(); ?></h1>
					<?php liferuss_breadcrumbs(); ?>
				</div>
			</header>
			<div class="container single-layout">
				<div class="prose">
					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="single-thumb"><?php the_post_thumbnail( 'liferuss-wide' ); ?></figure>
					<?php endif; ?>
					<?php the_content(); ?>
					<nav class="post-nav">
						<?php previous_post_link( '%link', liferuss_t( 'post_prev' ) ); ?>
						<?php next_post_link( '%link', liferuss_t( 'post_next' ) ); ?>
					</nav>
				</div>
				<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
					<aside class="sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
				<?php endif; ?>
			</div>
		</article>
	<?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
