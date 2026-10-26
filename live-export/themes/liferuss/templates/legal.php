<?php
/**
 * Template Name: صفحه حقوقی
 * Privacy policy and terms. Body copy is the page content.
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
				<?php if ( function_exists( 'liferuss_current_lang' ) && 'fa' !== liferuss_current_lang() ) : ?>
					<p><?php echo esc_html( 'متن رسمی این صفحه فارسی است.' ); ?></p>
				<?php endif; ?>
				<?php liferuss_breadcrumbs(); ?>
			</div>
		</header>
		<article class="page-body container prose legal-prose">
			<?php the_content(); ?>
			<?php liferuss_legal_contact_line(); ?>
		</article>
	<?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
