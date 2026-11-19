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
echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode escapes its own markup.
