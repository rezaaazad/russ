<?php
/**
 * Front-end redirect runner.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies lr_redirects before the template loads.
 */
class Runner {

	/**
	 * Hook early on the front end.
	 */
	public static function hooks(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_redirect' ), 0 );
	}

	/**
	 * Send 301, 302, or 410. A cycle is not followed.
	 */
	public static function maybe_redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$path = function_exists( 'liferuss_current_path' ) ? (string) liferuss_current_path() : '';
		if ( '' === $path && isset( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}
		$decision = Store::decide( $path );
		if ( ! $decision ) {
			return;
		}
		Store::hit( $decision['id'] );
		if ( 410 === $decision['code'] ) {
			status_header( 410 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Gone';
			exit;
		}
		$target = $decision['target'];
		if ( preg_match( '#^https?://#i', $target ) ) {
			$url = $target;
		} elseif ( function_exists( 'liferuss_url' ) ) {
			$url = liferuss_url( $target );
		} else {
			$url = home_url( $target );
		}
		$query = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		if ( '' !== $query && ! str_contains( $url, '?' ) ) {
			$url .= '?' . $query;
		}
		wp_redirect( $url, $decision['code'], 'LifeRuss' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}
}
