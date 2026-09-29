<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( liferuss_section_on( 'home_landings_enabled' ) ) :
	?>
<section class="section home-landings-section" id="landings">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'home_landings_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'landings', liferuss_opt( 'home_landings_title' ) ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'home_landings_subtitle' ) ); ?></p>
		</header>
		<div class="home-landings-grid">
			<?php foreach ( liferuss_enabled_items( 'home_landings' ) as $card ) : ?>
				<article class="home-landing-card">
					<span class="icon-circle"><?php echo liferuss_icon( $card['icon'] ?? 'globe' ); ?></span>
					<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
					<a class="btn btn-gold" href="<?php echo esc_url( liferuss_cta_url( $card['link'] ?? '' ) ); ?>">
						<?php echo esc_html( $card['cta'] ?? '' ); ?>
						<?php echo liferuss_icon( 'arrow' ); ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
