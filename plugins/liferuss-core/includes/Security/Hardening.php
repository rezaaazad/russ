<?php
/**
 * Login limits, XML-RPC, headers, and anonymous REST user lists.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Security;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baseline hardening that does not replace a firewall.
 */
class Hardening {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_filter( 'authenticate', array( self::class, 'limit' ), 1, 3 );
		add_action( 'wp_login_failed', array( self::class, 'failed' ) );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'wp_headers', array( self::class, 'headers' ) );
		add_action( 'send_headers', array( self::class, 'send' ) );
		add_filter( 'rest_pre_dispatch', array( self::class, 'hide_users' ), 10, 3 );
		add_action( 'init', array( self::class, 'block_xmlrpc' ), 0 );
	}

	/**
	 * Refuse the login form after too many failures from one address.
	 *
	 * @param \WP_User|\WP_Error|null $user     User so far.
	 * @param string                  $username Username.
	 * @param string                  $password Password.
	 * @return \WP_User|\WP_Error|null
	 */
	public static function limit( $user, $username, $password ) {
		unset( $username, $password );
		if ( self::is_login_screen() && self::locked() ) {
			return new \WP_Error( 'lr_locked', __( 'تلاش‌های ورود بیش از حد است. چند دقیقه بعد دوباره تلاش کنید.', 'liferuss-core' ) );
		}
		return $user;
	}

	/**
	 * Count a failed password on the login screen only.
	 *
	 * @param string $username Username.
	 */
	public static function failed( string $username ): void {
		unset( $username );
		if ( ! self::is_login_screen() ) {
			return;
		}
		$key = self::key();
		$n   = (int) get_transient( $key );
		set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
	}

	/**
	 * Drop the pingback header.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	public static function headers( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Security and anonymous cache headers. Both sets are filterable.
	 */
	public static function send(): void {
		$security = apply_filters(
			'liferuss_security_headers',
			array(
				'X-Content-Type-Options' => 'nosniff',
				'X-Frame-Options'        => 'SAMEORIGIN',
				'Referrer-Policy'        => 'strict-origin-when-cross-origin',
				'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
			)
		);
		if ( is_array( $security ) ) {
			foreach ( $security as $name => $value ) {
				header( $name . ': ' . $value );
			}
		}
		if ( is_user_logged_in() || is_admin() || is_preview() || is_404() || is_search() ) {
			return;
		}
		if ( function_exists( 'liferuss_catalog_is_filtered' ) && liferuss_catalog_is_filtered() ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method ) {
			return;
		}
		$cache = apply_filters(
			'liferuss_cache_headers',
			array(
				'Cache-Control' => 'public, max-age=120',
			)
		);
		if ( is_array( $cache ) ) {
			foreach ( $cache as $name => $value ) {
				header( $name . ': ' . $value );
			}
		}
	}

	/**
	 * Anonymous visitors cannot list users over REST.
	 *
	 * @param mixed            $result  Result so far.
	 * @param \WP_REST_Server  $server  Server.
	 * @param \WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function hide_users( $result, $server, $request ) {
		unset( $server );
		if ( is_user_logged_in() ) {
			return $result;
		}
		$route = $request->get_route();
		if ( 'GET' === $request->get_method() && is_string( $route ) && str_starts_with( $route, '/wp/v2/users' ) ) {
			return new \WP_Error( 'lr_users_hidden', __( 'فهرست کاربران برای مهمان بسته است.', 'liferuss-core' ), array( 'status' => 401 ) );
		}
		return $result;
	}

	/**
	 * XML-RPC answers 403 even if the file is requested directly.
	 */
	public static function block_xmlrpc(): void {
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			status_header( 403 );
			exit;
		}
	}

	/**
	 * Whether this IP is over the login limit.
	 */
	public static function locked(): bool {
		$settings = Settings::get( 'security' );
		$limit    = isset( $settings['login_limit'] ) ? (int) $settings['login_limit'] : 10;
		if ( $limit < 1 ) {
			return false;
		}
		return (int) get_transient( self::key() ) >= $limit;
	}

	/**
	 * Transient key for the current address.
	 */
	private static function key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '0';
		return 'lr_login_' . md5( $ip );
	}

	/**
	 * The request is the login form, not REST or XML-RPC.
	 */
	private static function is_login_screen(): bool {
		$script = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		return 'wp-login.php' === $script;
	}
}
