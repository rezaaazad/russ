<?php
/**
 * Landing footer CTA band.
 *
 * @package LifeRuss
 *
 * @var array $args { prefix: freight|trade }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prefix = isset( $args['prefix'] ) ? sanitize_key( $args['prefix'] ) : 'freight';
if ( ! in_array( $prefix, array( 'freight', 'trade' ), true ) ) {
	$prefix = 'freight';
}
?>
<section class="landing-cta" id="<?php echo esc_attr( $prefix ); ?>-cta">
	<div class="container landing-cta-inner">
		<div>
			<h2><?php echo esc_html( liferuss_opt( $prefix . '_cta_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( $prefix . '_cta_text' ) ); ?></p>
		</div>
		<div class="hero-actions">
			<a class="btn btn-gold js-scroll-consult" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( $prefix . '_cta1_link' ) ) ); ?>">
				<?php echo esc_html( liferuss_opt( $prefix . '_cta1_text' ) ); ?>
				<?php echo liferuss_icon( 'arrow' ); ?>
			</a>
			<a class="btn btn-ghost btn-ghost-light" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( $prefix . '_cta2_link' ) ) ); ?>">
				<?php echo esc_html( liferuss_opt( $prefix . '_cta2_text' ) ); ?>
			</a>
		</div>
	</div>
</section>
