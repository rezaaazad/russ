<?php
/**
 * Public login stays on the branded OTP account. Staff keep wp-login.php.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends /login/, registration, and forgotten-password visits to /account/.
 *
 * WordPress redirects the /login/ slug to wp-login.php. Clients use the OTP
 * screen instead. Staff still reach the core form from wp-admin, from a
 * password-reset link, or from an unlisted staff URL.
 */
class PublicLogin {

	/**
	 * Cookie that keeps a staff session on the core form.
	 */
	private const COOKIE = 'lr_staff_gate';

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'login_init', array( self::class, 'gate' ), 0 );
		add_action( 'login_enqueue_scripts', array( self::class, 'brand' ) );
		add_filter( 'login_headerurl', array( self::class, 'logo_url' ) );
		add_filter( 'login_headertext', array( self::class, 'logo_text' ) );
		add_filter( 'login_url', array( self::class, 'login_url' ), 10, 3 );
		add_filter( 'register_url', array( self::class, 'register_url' ) );
		add_filter( 'lostpassword_url', array( self::class, 'lost_url' ), 10, 2 );
		add_action( 'login_form', array( self::class, 'staff_field' ) );
		add_action( 'template_redirect', array( self::class, 'login_slug' ), 0 );
	}

	/**
	 * /login/ is the public account, never the WordPress screen.
	 */
	public static function login_slug(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$path = untrailingslashit( $path );
		if ( false !== strpos( $path, 'wp-login' ) ) {
			return;
		}
		$home = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		$want = untrailingslashit( $home . '/login' );
		if ( $path !== $want ) {
			return;
		}
		self::to_account( '' );
	}

	/**
	 * Redirect public wp-login.php actions. Staff and in-flight logins stay.
	 */
	public static function gate(): void {
		if ( self::allow_core_login() ) {
			self::remember_staff();
			return;
		}
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$flag   = isset( $_GET['registration'] ) ? sanitize_key( wp_unslash( (string) $_GET['registration'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'register' === $action || 'disabled' === $flag ) {
			self::to_account( 'register' );
		}
		if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
			self::to_account( 'password' );
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' === $method && in_array( $action, array( 'login', '' ), true ) ) {
			self::to_account( '' );
		}
	}

	/**
	 * LifeRuss colours on the staff form, including lost password and 2FA.
	 */
	public static function brand(): void {
		echo '<style id="lr-login">';
		echo 'body.login{background:#0b2341;direction:rtl}';
		echo 'body.login h1 a{background-image:none!important;text-indent:0;width:auto;height:auto;font-size:1.7rem;font-weight:800;color:#e8b923;overflow:visible}';
		echo 'body.login form{border:0;border-radius:18px;box-shadow:none}';
		echo 'body.login form .input,body.login input[type=text],body.login input[type=password]{border-radius:12px;padding:8px 12px}';
		echo 'body.login .button-primary{background:#e8b923;border-color:#e8b923;color:#0b2341;border-radius:999px;font-weight:700}';
		echo 'body.login #nav a,body.login #backtoblog a{color:#d7e0ec}';
		echo 'body.login .message,body.login #login_error{border-radius:12px}';
		echo 'body.login .language-switcher{display:none}';
		echo '</style>';
	}

	/**
	 * Logo links home.
	 */
	public static function logo_url(): string {
		return home_url( '/' );
	}

	/**
	 * Visible brand in place of the WordPress logo.
	 */
	public static function logo_text(): string {
		return 'لایف روس';
	}

	/**
	 * Front-end login links go to the account. Admin reauth stays on wp-login.
	 *
	 * @param string $url      Default login URL.
	 * @param string $redirect Redirect target.
	 * @param bool   $force    Force reauth.
	 */
	public static function login_url( $url, $redirect = '', $force = false ): string {
		unset( $force );
		if ( self::redirect_is_admin( (string) $redirect ) ) {
			return (string) $url;
		}
		$account = self::account_url( '' );
		$next    = self::safe_front( (string) $redirect );
		if ( '' !== $next ) {
			$account = add_query_arg( 'redirect_to', $next, $account );
		}
		return $account;
	}

	/**
	 * Registration is the OTP account.
	 *
	 * @param string $url Default register URL.
	 */
	public static function register_url( $url ): string {
		unset( $url );
		return self::account_url( 'register' );
	}

	/**
	 * Public lost-password links explain OTP. Staff keep the core screen.
	 *
	 * @param string $url      Default URL.
	 * @param string $redirect Redirect target.
	 */
	public static function lost_url( $url, $redirect = '' ): string {
		unset( $redirect );
		if ( self::allow_core_login() ) {
			return (string) $url;
		}
		return self::account_url( 'password' );
	}

	/**
	 * Carry the staff gate through the password POST.
	 */
	public static function staff_field(): void {
		$key = self::posted_or_query_key();
		if ( '' === $key || ! hash_equals( self::key(), $key ) ) {
			return;
		}
		echo '<input type="hidden" name="lr_staff" value="' . esc_attr( $key ) . '">';
	}

	/**
	 * Unlisted staff login URL. Shown in LifeRuss settings.
	 */
	public static function staff_url(): string {
		return add_query_arg( 'lr_staff', rawurlencode( self::key() ), site_url( 'wp-login.php', 'login' ) );
	}

	/**
	 * Send the visitor to /account/ and stop.
	 *
	 * @param string $notice register, password, or empty.
	 */
	private static function to_account( string $notice ): void {
		$url  = self::account_url( $notice );
		$next = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$next = self::safe_front( (string) wp_unslash( $_REQUEST['redirect_to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		if ( '' !== $next ) {
			$url = add_query_arg( 'redirect_to', $next, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Account URL, localised when the theme helper exists.
	 *
	 * @param string $notice Notice code.
	 */
	private static function account_url( string $notice ): string {
		$base = function_exists( 'liferuss_url' ) ? liferuss_url( '/account/' ) : home_url( '/account/' );
		if ( '' === $notice ) {
			return $base;
		}
		return add_query_arg( 'notice', $notice, $base );
	}

	/**
	 * Core login may render or accept this request.
	 */
	private static function allow_core_login(): bool {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $action, array( 'logout', 'postpass', 'confirmaction', 'rp', 'resetpass', 'lr_2fa' ), true ) ) {
			return true;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'POST' === $method && in_array( $action, array( 'login', 'lostpassword', 'retrievepassword', '' ), true ) ) {
			return true;
		}
		if ( self::has_staff_gate() ) {
			return true;
		}
		$redirect = isset( $_REQUEST['redirect_to'] ) ? (string) wp_unslash( $_REQUEST['redirect_to'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( self::redirect_is_admin( $redirect ) ) {
			return true;
		}
		if ( is_user_logged_in() && ! self::client_only() ) {
			return true;
		}
		return false;
	}

	/**
	 * Staff token in the query, POST, or the short-lived cookie.
	 */
	private static function has_staff_gate(): bool {
		$key = self::posted_or_query_key();
		if ( '' !== $key && hash_equals( self::key(), $key ) ) {
			return true;
		}
		$cookie = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::COOKIE ] ) ) : '';
		return '' !== $cookie && hash_equals( self::cookie_value(), $cookie );
	}

	/**
	 * Remember a valid staff token so the next screen stays branded.
	 */
	private static function remember_staff(): void {
		$key = self::posted_or_query_key();
		if ( '' === $key || ! hash_equals( self::key(), $key ) ) {
			return;
		}
		if ( headers_sent() ) {
			return;
		}
		setcookie( self::COOKIE, self::cookie_value(), time() + 12 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Token from the query or the hidden field.
	 */
	private static function posted_or_query_key(): string {
		if ( isset( $_POST['lr_staff'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return sanitize_text_field( wp_unslash( (string) $_POST['lr_staff'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		if ( isset( $_GET['lr_staff'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( (string) $_GET['lr_staff'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return '';
	}

	/**
	 * Stable staff key, created once.
	 */
	private static function key(): string {
		$stored = (string) get_option( 'lr_staff_login_key', '' );
		if ( strlen( $stored ) >= 20 ) {
			return $stored;
		}
		$stored = wp_generate_password( 24, false, false );
		update_option( 'lr_staff_login_key', $stored, false );
		return $stored;
	}

	/**
	 * Cookie value. The raw key is not stored in the cookie.
	 */
	private static function cookie_value(): string {
		return hash_hmac( 'sha256', self::key(), wp_salt( 'auth' ) );
	}

	/**
	 * Whether a redirect target is the dashboard.
	 *
	 * @param string $url URL.
	 */
	private static function redirect_is_admin( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $path ) {
			$path = $url;
		}
		return str_contains( $path, 'wp-admin' ) || str_contains( $path, 'wp-login.php' );
	}

	/**
	 * A front-end redirect, or empty when it points at wp-admin.
	 *
	 * @param string $url Requested URL.
	 */
	private static function safe_front( string $url ): string {
		$url = trim( $url );
		if ( '' === $url || self::redirect_is_admin( $url ) ) {
			return '';
		}
		$safe = wp_validate_redirect( $url, '' );
		return is_string( $safe ) ? $safe : '';
	}

	/**
	 * A user whose only role is the client account.
	 */
	private static function client_only(): bool {
		$user = wp_get_current_user();
		$roles = $user instanceof \WP_User ? (array) $user->roles : array();
		return array( 'lr_client' ) === array_values( $roles );
	}
}
