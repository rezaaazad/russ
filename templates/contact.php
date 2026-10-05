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
		<p><?php echo esc_html( liferuss_contact_page_lead() ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<?php
$contact_phone   = liferuss_public_contact( 'phone' );
$contact_alt     = liferuss_public_contact( 'phone_alt' );
$contact_mail    = liferuss_public_contact( 'email' );
$contact_address = liferuss_public_contact( 'address' );
$contact_social  = liferuss_contact_links();
if ( $contact_phone || $contact_alt || $contact_mail || $contact_address || $contact_social ) :
	?>
<section class="section contact-facts">
	<div class="container">
		<h2><?php echo esc_html( liferuss_t( 'channels_aria' ) ); ?></h2>
		<ul class="lr-facts">
			<?php if ( $contact_phone ) : ?>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $contact_phone ) ); ?>"><bdi dir="ltr"><?php echo esc_html( $contact_phone ); ?></bdi></a></li>
			<?php endif; ?>
			<?php if ( $contact_alt ) : ?>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $contact_alt ) ); ?>"><bdi dir="ltr"><?php echo esc_html( $contact_alt ); ?></bdi></a></li>
			<?php endif; ?>
			<?php foreach ( $contact_social as $channel ) : ?>
				<?php if ( in_array( $channel['icon'], array( 'phone', 'mail' ), true ) ) { continue; } ?>
				<li><a href="<?php echo esc_url( $channel['href'] ); ?>"<?php echo $channel['external'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $channel['label'] ); ?></a></li>
			<?php endforeach; ?>
			<?php if ( $contact_mail ) : ?>
				<li><a href="mailto:<?php echo esc_attr( $contact_mail ); ?>"><?php echo esc_html( $contact_mail ); ?></a></li>
			<?php endif; ?>
			<?php if ( $contact_address ) : ?>
				<li><?php echo esc_html( $contact_address ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</section>
<?php endif; ?>
<?php get_template_part( 'template-parts/contact-form' ); ?>

<?php
get_footer();
