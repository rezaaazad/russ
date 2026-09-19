<?php
/**
 * Default page template.
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
		<header class="page-hero">
			<div class="container">
				<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
				<h1><?php the_title(); ?></h1>
				<?php liferuss_breadcrumbs(); ?>
			</div>
		</header>
		<article class="page-body container prose">
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
