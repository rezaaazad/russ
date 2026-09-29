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
$html_dir   = liferuss_lang_meta( 'dir' );
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
<body <?php body_class(); ?> dir="<?php echo esc_attr( $html_dir ? $html_dir : 'rtl' ); ?>">
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php echo esc_html( liferuss_t( 'skip_link' ) ); ?></a>
<header class="site-header" id="top">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( liferuss_home() ); ?>">
			<?php
			if ( $logo_id ) {
				echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'custom-logo', 'alt' => '' ) );
			} elseif ( has_custom_logo() ) {
				echo wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'custom-logo', 'alt' => '' ) );
			} else {
				?>
				<span class="brand-mark" aria-hidden="true">
					<svg viewBox="0 0 48 48" class="brand-cap">
						<circle cx="24" cy="24" r="24" fill="#0B2341"/>
						<path d="M10 22.2 24 15.4 38 22.2 24 29 10 22.2z" fill="#E8B923"/>
						<path d="M16 24.4v6.2c0 .7 3.4 2.6 8 2.6s8-1.9 8-2.6v-6.2" fill="none" stroke="#E8B923" stroke-width="1.8"/>
						<circle cx="38" cy="22.2" r="1.5" fill="#F6D768"/>
					</svg>
				</span>
				<?php
			}
			?>
			<span class="brand-text">
				<strong><?php echo esc_html( $brand ); ?></strong>
				<?php if ( $brand_en && 0 !== strcasecmp( $brand, $brand_en ) ) : ?>
					<small><?php echo esc_html( $brand_en ); ?></small>
				<?php endif; ?>
			</span>
		</a>

		<nav class="site-nav" id="site-nav" aria-label="<?php echo esc_attr( liferuss_t( 'nav_aria' ) ); ?>">
			<?php
			// Designed mega menu. A saved flat menu of 13 items overlaps the header at 1024–1440.
			liferuss_fallback_menu();
			?>
		</nav>

		<div class="header-actions">
			<button class="header-search-toggle" type="button" aria-expanded="false" aria-controls="header-search" aria-label="<?php echo esc_attr( liferuss_t( 'search_open' ) ); ?>">
				<?php echo liferuss_icon( 'search' ); ?>
			</button>
			<div class="header-search" id="header-search">
				<?php liferuss_header_search(); ?>
				<button class="header-search-close" type="button" aria-label="<?php echo esc_attr( liferuss_t( 'search_close' ) ); ?>">
					<?php echo liferuss_icon( 'close' ); ?>
				</button>
			</div>
			<a class="header-account" href="<?php echo esc_url( liferuss_url( '/account/' ) ); ?>"><?php echo is_user_logged_in() ? 'حساب من' : 'ورود'; ?></a>
			<?php liferuss_language_switcher( 'header' ); ?>
			<?php if ( $show_phone && $phone ) : ?>
				<a class="header-phone" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
					<?php echo liferuss_icon( 'phone' ); ?>
					<span dir="ltr"><?php echo esc_html( $phone ); ?></span>
				</a>
			<?php endif; ?>
			<a class="btn btn-gold header-cta js-scroll-consult" href="<?php echo esc_url( $cta_link ); ?>">
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
