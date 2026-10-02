<?php
/**
 * Template Name: تماس با ما
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'contact_page_eye' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'contact_page_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'contact_page_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<section class="section contact-facts">
	<div class="container">
		<ul class="lr-facts">
			<?php if ( liferuss_opt( 'phone' ) ) : ?>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', (string) liferuss_opt( 'phone' ) ) ); ?>"><bdi dir="ltr"><?php echo esc_html( liferuss_opt( 'phone' ) ); ?></bdi></a></li>
			<?php endif; ?>
			<?php if ( liferuss_opt( 'whatsapp' ) ) : ?>
				<li><a href="<?php echo esc_url( liferuss_whatsapp_url( liferuss_opt( 'whatsapp' ) ) ); ?>">WhatsApp</a></li>
			<?php endif; ?>
			<?php if ( liferuss_opt( 'email' ) ) : ?>
				<li><a href="mailto:<?php echo esc_attr( liferuss_opt( 'email' ) ); ?>"><?php echo esc_html( liferuss_opt( 'email' ) ); ?></a></li>
			<?php endif; ?>
			<?php if ( liferuss_opt( 'address' ) ) : ?>
				<li><?php echo esc_html( liferuss_opt( 'address' ) ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</section>
<?php get_template_part( 'template-parts/contact-form' ); ?>

<?php
get_footer();
