<?php
/**
 * Theme header.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand      = liferuss_brand();
$brand_en   = liferuss_opt( 'brand_name_en', 'LifeRuss' );
$cta_text   = liferuss_opt( 'header_cta_text', 'دریافت مشاوره رایگان' );
$cta_link   = liferuss_cta_url( liferuss_opt( 'header_cta_link', '#consultation' ) );
$show_phone = '1' === (string) liferuss_opt( 'header_show_phone', '1' );
$phone      = liferuss_opt( 'header_phone', liferuss_opt( 'phone' ) );
$logo_id    = absint( liferuss_opt( 'logo_id', 0 ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php echo esc_html( liferuss_t( 'skip_link' ) ); ?></a>
<header class="site-header" id="top">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( liferuss_home() ); ?>" aria-label="<?php echo esc_attr( $brand ); ?>">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'custom-logo', 'alt' => $brand ) ); ?>
			<?php elseif ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="brand-mark" aria-hidden="true">
					<svg viewBox="0 0 48 48" class="brand-cap">
						<circle cx="24" cy="24" r="24" fill="#0B2341"/>
						<path d="M10 22.2 24 15.4 38 22.2 24 29 10 22.2z" fill="#E8B923"/>
						<path d="M16 24.4v6.2c0 .7 3.4 2.6 8 2.6s8-1.9 8-2.6v-6.2" fill="none" stroke="#E8B923" stroke-width="1.8"/>
						<circle cx="38" cy="22.2" r="1.5" fill="#F6D768"/>
					</svg>
				</span>
				<span class="brand-text">
					<strong><?php echo esc_html( $brand ); ?></strong>
					<small><?php echo esc_html( $brand_en ); ?></small>
				</span>
			<?php endif; ?>
		</a>

		<nav class="site-nav" id="site-nav" aria-label="<?php echo esc_attr( liferuss_t( 'nav_aria' ) ); ?>">
			<?php
			// Assigned WP menu OR fallback — never both (avoids duplicate nav lists).
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nav-list',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
			} else {
				liferuss_fallback_menu();
			}
			?>
		</nav>

		<div class="header-actions">
			<?php liferuss_language_switcher( 'header' ); ?>
			<?php if ( $show_phone && $phone ) : ?>
				<a class="header-phone" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
					<?php echo liferuss_icon( 'phone' ); ?>
					<span><?php echo esc_html( $phone ); ?></span>
				</a>
			<?php endif; ?>
			<a class="btn btn-gold js-scroll-consult" href="<?php echo esc_url( $cta_link ); ?>">
				<?php echo esc_html( $cta_text ); ?>
				<?php echo liferuss_icon( 'arrow' ); ?>
			</a>
			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="<?php echo esc_attr( liferuss_t( 'open_menu' ) ); ?>">
				<span class="nav-toggle-bars" aria-hidden="true"></span>
			</button>
		</div>
	</div>
</header>
<main id="main" class="site-main">
