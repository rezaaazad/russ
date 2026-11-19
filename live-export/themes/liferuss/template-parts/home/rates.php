<?php
/**
 * Homepage reference-rate strip.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! shortcode_exists( 'lr_rates_ticker' ) ) {
	return;
}
$html = do_shortcode( '[lr_rates_ticker]' );
if ( '' === trim( $html ) ) {
	return;
}
echo '<div class="lr-home-rates">';
echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode escapes its own markup.
echo '<p class="lr-home-rates__cta"><a class="btn btn-gold" href="' . esc_url( liferuss_url( '/exchange/tuition-payment/' ) ) . '">پرداخت شهریه دانشگاه‌های روسیه</a></p>';
echo '</div>';
