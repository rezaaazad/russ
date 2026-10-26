<?php
/**
 * TOTP enrollment and the login second step.
 *
 * Application Passwords authenticate through REST and never reach this gate.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Security;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mandatory authenticator codes for the five staff roles.
 */
class TwoFactor {

	/**
	 * Roles that must enroll.
	 *
	 * @var string[]
	 */
	public const ROLES = array(
		'administrator',
		'lr_admin',
		'lr_content_manager',
		'lr_seo_manager',
		'lr_consultant',
	);

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_login', array( self::class, 'after_password' ), 10, 2 );
		add_action( 'login_form_lr_2fa', array( self::class, 'form' ) );
		add_action( 'admin_init', array( self::class, 'force_enroll' ) );
		add_action( 'admin_notices', array( self::class, 'grace_notice' ) );
		add_action( 'admin_post_lr_dismiss_notice', array( self::class, 'dismiss_notice' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_lr_2fa_enroll', array( self::class, 'enroll' ) );
		add_action( 'admin_post_lr_2fa_reset', array( self::class, 'reset_user' ) );
		add_filter( 'user_row_actions', array( self::class, 'row_action' ), 10, 2 );
	}

	/**
	 * Whether this account must use an authenticator.
	 *
	 * @param \WP_User $user User.
	 */
	public static function required( \WP_User $user ): bool {
		return (bool) array_intersect( self::ROLES, $user->roles );
	}

	/**
	 * Whether a secret is stored.
	 *
	 * @param int $user_id User id.
	 */
	public static function enrolled( int $user_id ): bool {
		return '1' === (string) get_user_meta( $user_id, 'lr_totp_enrolled', true ) && '' !== self::secret( $user_id );
	}

	/**
	 * After a password login, either start the grace clock or ask for a code.
	 *
	 * @param string   $login Username.
	 * @param \WP_User $user  User.
	 */
	public static function after_password( string $login, \WP_User $user ): void {
		unset( $login );
		if ( did_action( 'application_password_did_authenticate' ) ) {
			return;
		}
		if ( ! self::required( $user ) ) {
			return;
		}
		if ( ! self::enrolled( $user->ID ) ) {
			self::touch_grace( $user->ID );
			return;
		}
		wp_clear_auth_cookie();
		$token = wp_generate_password( 32, false, false );
		set_transient( 'lr_2fa_' . $token, (int) $user->ID, 5 * MINUTE_IN_SECONDS );
		$secure = is_ssl();
		setcookie( 'lr_2fa', $token, time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
		wp_safe_redirect( site_url( 'wp-login.php?action=lr_2fa', 'login_post' ) );
		exit;
	}

	/**
	 * Second-step form. A valid code or backup code sets the auth cookie.
	 */
	public static function form(): void {
		$pending = self::pending_user();
		$error   = '';
		$method  = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' === $method ) {
			check_admin_referer( 'lr_2fa_login', 'lr_2fa_nonce' );
			$code = isset( $_POST['lr_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_code'] ) ) : '';
			if ( $pending && self::accept( $pending, $code ) ) {
				wp_set_auth_cookie( $pending, true );
				self::clear_pending();
				wp_safe_redirect( admin_url() );
				exit;
			}
			$error = __( 'کد درست نیست.', 'liferuss-core' );
		}
		login_header( __( 'ورود دومرحله‌ای', 'liferuss-core' ) );
		if ( $error ) {
			echo '<div id="login_error">' . esc_html( $error ) . '</div>';
		}
		echo '<form method="post">';
		wp_nonce_field( 'lr_2fa_login', 'lr_2fa_nonce' );
		echo '<p><label for="lr_code">' . esc_html__( 'کد شش‌رقمی یا کد پشتیبان', 'liferuss-core' ) . '</label>';
		echo '<input type="text" name="lr_code" id="lr_code" class="input" autocomplete="one-time-code" inputmode="numeric" required></p>';
		echo '<p><button class="button button-primary">' . esc_html__( 'ورود', 'liferuss-core' ) . '</button></p></form>';
		login_footer();
		exit;
	}

	/**
	 * Send an unenrolled staff user to the enrollment screen after the grace window.
	 */
	public static function force_enroll(): void {
		if ( ! is_user_logged_in() || wp_doing_ajax() ) {
			return;
		}
		$screen = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		if ( 'admin-post.php' === $screen ) {
			return;
		}
		$user = wp_get_current_user();
		if ( ! self::required( $user ) || self::enrolled( $user->ID ) || self::grace_open( $user->ID ) ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'lr-2fa' === $page ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=lr-2fa' ) );
		exit;
	}

	/**
	 * Reminder while the grace window is still open.
	 */
	public static function grace_notice(): void {
		if ( ! self::notice_screen() ) {
			return;
		}
		$user = wp_get_current_user();
		if ( ! self::required( $user ) || self::enrolled( $user->ID ) || ! self::grace_open( $user->ID ) ) {
			return;
		}
		if ( (int) get_user_meta( $user->ID, 'lr_notice_2fa', true ) > time() ) {
			return;
		}
		$url = admin_url( 'admin.php?page=lr-2fa' );
		$bye = wp_nonce_url( admin_url( 'admin-post.php?action=lr_dismiss_notice&key=2fa' ), 'lr_dismiss_notice' );
		echo '<div class="notice notice-warning lr-compact-notice"><p>' . esc_html__( 'ورود دومرحله‌ای برای این نقش لازم است. تا پایان مهلت آن را فعال کنید.', 'liferuss-core' );
		echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'فعال‌سازی', 'liferuss-core' ) . '</a>';
		echo ' <a href="' . esc_url( $bye ) . '">' . esc_html__( 'بستن برای ۷ روز', 'liferuss-core' ) . '</a></p></div>';
	}

	/**
	 * Hide a compact notice for this user for seven days.
	 */
	public static function dismiss_notice(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_dismiss_notice' );
		$key = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( (string) $_GET['key'] ) ) : '';
		if ( ! in_array( $key, array( '2fa', 'contacts' ), true ) ) {
			wp_die( esc_html__( 'نامعتبر.', 'liferuss-core' ), '', array( 'response' => 400 ) );
		}
		update_user_meta( get_current_user_id(), 'lr_notice_' . $key, time() + ( 7 * DAY_IN_SECONDS ) );
		$back = wp_get_referer();
		wp_safe_redirect( $back ? $back : admin_url() );
		exit;
	}

	/**
	 * LifeRuss dashboard and the Academy hub only.
	 */
	private static function notice_screen(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $page, array( 'liferuss', 'lr-academy' ), true );
	}

	/**
	 * Hidden-from-menu page so the redirect target exists.
	 */
	public static function menu(): void {
		add_submenu_page(
			null,
			__( 'ورود دومرحله‌ای', 'liferuss-core' ),
			__( 'ورود دومرحله‌ای', 'liferuss-core' ),
			'read',
			'lr-2fa',
			array( self::class, 'render' )
		);
	}

	/**
	 * Enrollment screen. The backup codes are shown once.
	 */
	public static function render(): void {
		$user = wp_get_current_user();
		if ( ! self::required( $user ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		$secret = (string) get_user_meta( $user->ID, 'lr_totp_pending', true );
		if ( '' === $secret ) {
			$secret = Totp::secret();
			update_user_meta( $user->ID, 'lr_totp_pending', $secret );
		}
		$uri = Totp::uri( $secret, $user->user_email ? $user->user_email : $user->user_login );
		echo '<div class="wrap"><h1>' . esc_html__( 'فعال‌سازی ورود دومرحله‌ای', 'liferuss-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'این کلید را در برنامهٔ احراز هویت وارد کنید، سپس کد را بنویسید.', 'liferuss-core' ) . '</p>';
		echo '<p><code>' . esc_html( $secret ) . '</code></p>';
		echo '<p><a href="' . esc_url( $uri ) . '">' . esc_html( $uri ) . '</a></p>';
		$shown = get_transient( 'lr_2fa_codes_' . $user->ID );
		if ( is_array( $shown ) ) {
			echo '<h2>' . esc_html__( 'کدهای پشتیبان (فقط همین یک بار)', 'liferuss-core' ) . '</h2><ul>';
			foreach ( $shown as $code ) {
				echo '<li><code>' . esc_html( (string) $code ) . '</code></li>';
			}
			echo '</ul>';
			delete_transient( 'lr_2fa_codes_' . $user->ID );
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_2fa_enroll', 'lr_2fa_nonce' );
		echo '<input type="hidden" name="action" value="lr_2fa_enroll">';
		echo '<p><input name="lr_code" class="regular-text" inputmode="numeric" autocomplete="one-time-code" required></p>';
		echo '<p><button class="button button-primary">' . esc_html__( 'تأیید و ذخیره', 'liferuss-core' ) . '</button></p></form></div>';
	}

	/**
	 * Confirm the first code and store the encrypted secret plus backup hashes.
	 */
	public static function enroll(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_2fa_enroll', 'lr_2fa_nonce' );
		$user   = wp_get_current_user();
		$secret = (string) get_user_meta( $user->ID, 'lr_totp_pending', true );
		$code   = isset( $_POST['lr_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_code'] ) ) : '';
		if ( '' === $secret || ! Totp::verify( $secret, $code ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=lr-2fa&error=1' ) );
			exit;
		}
		$codes  = self::backup_codes();
		$hashes = array();
		foreach ( $codes as $backup ) {
			$hashes[] = wp_hash_password( $backup );
		}
		update_user_meta( $user->ID, 'lr_totp_secret', SecretBox::seal( $secret ) );
		update_user_meta( $user->ID, 'lr_totp_backup', $hashes );
		update_user_meta( $user->ID, 'lr_totp_enrolled', '1' );
		delete_user_meta( $user->ID, 'lr_totp_pending' );
		set_transient( 'lr_2fa_codes_' . $user->ID, $codes, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=lr-2fa&enrolled=1' ) );
		exit;
	}

	/**
	 * Super Admin clears another user's authenticator and restarts the grace window.
	 */
	public static function reset_user(): void {
		if ( ! self::is_super_admin() ) {
			wp_die( esc_html__( 'فقط مدیر ارشد می‌تواند ورود دومرحله‌ای را بازنشانی کند.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_2fa_reset' );
		$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $user_id ) {
			self::reset( $user_id );
		}
		wp_safe_redirect( admin_url( 'users.php?lr_2fa=reset' ) );
		exit;
	}

	/**
	 * Users-list link for the Super Admin.
	 *
	 * @param array<string, string> $actions Actions.
	 * @param \WP_User              $user    User.
	 * @return array<string, string>
	 */
	public static function row_action( array $actions, \WP_User $user ): array {
		if ( ! self::is_super_admin() || ! self::required( $user ) ) {
			return $actions;
		}
		$url               = wp_nonce_url( admin_url( 'admin-post.php?action=lr_2fa_reset&user_id=' . $user->ID ), 'lr_2fa_reset' );
		$actions['lr_2fa'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'بازنشانی ۲FA', 'liferuss-core' ) . '</a>';
		return $actions;
	}

	/**
	 * Drop the secret. The next login starts a new grace window.
	 *
	 * @param int $user_id User id.
	 */
	public static function reset( int $user_id ): void {
		delete_user_meta( $user_id, 'lr_totp_secret' );
		delete_user_meta( $user_id, 'lr_totp_backup' );
		delete_user_meta( $user_id, 'lr_totp_enrolled' );
		delete_user_meta( $user_id, 'lr_totp_pending' );
		update_user_meta( $user_id, 'lr_2fa_grace_start', gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Accept a TOTP code or a one-time backup code.
	 *
	 * @param int    $user_id User id.
	 * @param string $code    Submitted code.
	 */
	public static function accept( int $user_id, string $code ): bool {
		$secret = self::secret( $user_id );
		if ( '' !== $secret && Totp::verify( $secret, $code ) ) {
			return true;
		}
		$hashes = get_user_meta( $user_id, 'lr_totp_backup', true );
		if ( ! is_array( $hashes ) ) {
			return false;
		}
		$plain = strtoupper( preg_replace( '/\s+/', '', $code ) );
		foreach ( $hashes as $i => $hash ) {
			if ( is_string( $hash ) && wp_check_password( $plain, $hash ) ) {
				unset( $hashes[ $i ] );
				update_user_meta( $user_id, 'lr_totp_backup', array_values( $hashes ) );
				return true;
			}
		}
		return false;
	}

	/**
	 * Decrypted secret, or empty.
	 *
	 * @param int $user_id User id.
	 */
	private static function secret( int $user_id ): string {
		$stored = (string) get_user_meta( $user_id, 'lr_totp_secret', true );
		return $stored ? SecretBox::open( $stored ) : '';
	}

	/**
	 * User id waiting on the second step.
	 */
	private static function pending_user(): int {
		$token = isset( $_COOKIE['lr_2fa'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['lr_2fa'] ) ) : '';
		if ( '' === $token ) {
			return 0;
		}
		$id = (int) get_transient( 'lr_2fa_' . $token );
		return $id > 0 ? $id : 0;
	}

	/**
	 * Forget the pending cookie.
	 */
	private static function clear_pending(): void {
		$token = isset( $_COOKIE['lr_2fa'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['lr_2fa'] ) ) : '';
		if ( $token ) {
			delete_transient( 'lr_2fa_' . $token );
		}
		setcookie( 'lr_2fa', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Start the grace clock once.
	 *
	 * @param int $user_id User id.
	 */
	private static function touch_grace( int $user_id ): void {
		if ( '' === (string) get_user_meta( $user_id, 'lr_2fa_grace_start', true ) ) {
			update_user_meta( $user_id, 'lr_2fa_grace_start', gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Whether the grace window is still open.
	 *
	 * @param int $user_id User id.
	 */
	public static function grace_open( int $user_id ): bool {
		$settings = Settings::get( 'security' );
		$days     = isset( $settings['totp_grace_days'] ) ? (int) $settings['totp_grace_days'] : 7;
		if ( $days < 1 ) {
			return false;
		}
		$start = (string) get_user_meta( $user_id, 'lr_2fa_grace_start', true );
		if ( '' === $start ) {
			self::touch_grace( $user_id );
			return true;
		}
		$stamp = strtotime( $start . ' UTC' );
		return $stamp && ( time() - $stamp ) < ( $days * DAY_IN_SECONDS );
	}

	/**
	 * Eight single-use backup codes.
	 *
	 * @return string[]
	 */
	private static function backup_codes(): array {
		$codes = array();
		for ( $i = 0; $i < 8; $i++ ) {
			$codes[] = strtoupper( wp_generate_password( 4, false, false ) . '-' . wp_generate_password( 4, false, false ) );
		}
		return $codes;
	}

	/**
	 * WordPress administrator, the Super Admin in this matrix.
	 */
	private static function is_super_admin(): bool {
		$user = wp_get_current_user();
		return in_array( 'administrator', $user->roles, true );
	}
}
