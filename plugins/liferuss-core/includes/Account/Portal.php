<?php
/**
 * Client account: OTP login, requests, files, messages, and saved universities.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Account;

use LifeRuss\Core\CRM\Files;
use LifeRuss\Core\CRM\LeadWriter;
use LifeRuss\Core\CRM\Notifier;
use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names and assembled WHERE clauses are internal.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Front account and the lr_client role boundary.
 */
class Portal {

	/**
	 * Public labels. Internal pipeline names stay in the admin.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			'new'       => 'ثبت شد',
			'contacted' => 'در حال بررسی',
			'qualified' => 'بررسی تخصصی',
			'follow_up' => 'در پیگیری',
			'documents' => 'در انتظار مدارک',
			'contract'  => 'قرارداد',
			'paid'      => 'پرداخت ثبت شد',
			'completed' => 'انجام شد',
			'lost'      => 'بسته شد',
		);
	}

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'template_redirect', array( self::class, 'handle' ), 1 );
		add_action( 'admin_init', array( self::class, 'block_admin' ) );
		add_filter( 'show_admin_bar', array( self::class, 'hide_bar' ) );
	}

	/**
	 * Clients have no wp-admin.
	 */
	public static function block_admin(): void {
		if ( ! self::is_client() ) {
			return;
		}
		global $pagenow;
		if ( wp_doing_ajax() || 'admin-post.php' === $pagenow ) {
			return;
		}
		$target = function_exists( 'liferuss_url' ) ? liferuss_url( '/account/' ) : home_url( '/account/' );
		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * Hide the toolbar for a pure client.
	 *
	 * @param bool $show Whether to show the bar.
	 */
	public static function hide_bar( $show ) {
		if ( self::is_client() ) {
			return false;
		}
		return $show;
	}

	/**
	 * Whether the current user is only a client.
	 */
	public static function is_client(): bool {
		$user = wp_get_current_user();
		return $user instanceof \WP_User && in_array( 'lr_client', (array) $user->roles, true );
	}

	/**
	 * POST actions on the account screens.
	 */
	public static function handle(): void {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! get_query_var( 'lr_account' ) || 'POST' !== $method ) {
			return;
		}
		if ( ! isset( $_POST['lr_account_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_account_nonce'] ) ), 'lr_account' ) ) {
			return;
		}
		$action = isset( $_POST['lr_account_action'] ) ? sanitize_key( wp_unslash( $_POST['lr_account_action'] ) ) : '';
		if ( 'otp' === $action ) {
			self::send_otp();
		} elseif ( 'verify' === $action ) {
			self::verify_otp();
		} elseif ( 'upload' === $action ) {
			self::upload();
		} elseif ( 'message' === $action ) {
			self::message();
		} elseif ( 'save_uni' === $action ) {
			self::save_university();
		} elseif ( 'profile' === $action ) {
			self::save_profile();
		}
	}

	/**
	 * Leads tied to the verified phone or email.
	 *
	 * @param int $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function leads( int $user_id ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}
		$phone = LeadWriter::digits( (string) get_user_meta( $user_id, 'lr_phone', true ) );
		$email = sanitize_email( $user->user_email );
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$where = array();
		$args  = array();
		if ( strlen( $phone ) >= 8 ) {
			$where[] = 'phone_normalized = %s';
			$args[]  = $phone;
		}
		if ( is_email( $email ) && ! str_ends_with( $email, '@clients.liferuss.invalid' ) ) {
			$where[] = 'email = %s';
			$args[]  = $email;
		}
		if ( ! $where ) {
			return array();
		}
		$sql  = 'SELECT * FROM `' . $table . '` WHERE deleted_at IS NULL AND (' . implode( ' OR ', $where ) . ') ORDER BY id DESC LIMIT 50';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Whether this user may see the lead.
	 *
	 * @param int $user_id User id.
	 * @param int $lead_id Lead id.
	 */
	public static function owns( int $user_id, int $lead_id ): bool {
		foreach ( self::leads( $user_id ) as $lead ) {
			if ( (int) $lead['id'] === $lead_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Status history with public labels.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<int, array<string, string>>
	 */
	public static function timeline( int $lead_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lead_status_history';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT to_status, created_at, reason FROM `{$table}` WHERE lead_id = %d ORDER BY id ASC", $lead_id ), ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'label' => self::statuses()[ (string) $row['to_status'] ] ?? 'به‌روزرسانی',
				'at'    => (string) $row['created_at'],
				'note'  => self::public_note( (string) $row['reason'] ),
			);
		}
		return $out;
	}

	/**
	 * Public timeline note. Internal reasons stay hidden.
	 *
	 * @param string $reason History reason.
	 */
	private static function public_note( string $reason ): string {
		if ( str_starts_with( $reason, 'ادغام' ) ) {
			return 'درخواست تکراری به همین پرونده اضافه شد.';
		}
		if ( str_starts_with( $reason, 'پرداخت' ) ) {
			return 'پرداخت ثبت شد.';
		}
		return '';
	}

	/**
	 * Thread for one lead.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function messages( int $lead_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lead_messages';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE lead_id = %d ORDER BY id ASC LIMIT 200", $lead_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Files the client may download.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function files( int $lead_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lead_files';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, original_name, created_at FROM `{$table}` WHERE lead_id = %d AND deleted_at IS NULL AND purged_at IS NULL ORDER BY id DESC LIMIT 40",
				$lead_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Store a thread message and notify the other side.
	 *
	 * @param int    $lead_id   Lead id.
	 * @param int    $author_id Author.
	 * @param string $body      Text.
	 */
	public static function add_message( int $lead_id, int $author_id, string $body ): bool {
		$body = trim( $body );
		if ( $lead_id < 1 || '' === $body ) {
			return false;
		}
		global $wpdb;
		$ok = $wpdb->insert(
			$wpdb->prefix . 'lr_lead_messages',
			array(
				'lead_id'    => $lead_id,
				'author_id'  => $author_id,
				'body'       => $body,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		if ( ! $ok ) {
			return false;
		}
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead ) {
			return true;
		}
		$consultant = (int) ( $lead['consultant_id'] ?? 0 );
		if ( self::owns( $author_id, $lead_id ) ) {
			if ( $consultant ) {
				$user = get_userdata( $consultant );
				if ( $user && is_email( $user->user_email ) ) {
					Notifier::mail_to( $user->user_email, 'پیام جدید ' . $lead['lead_code'], $body );
				}
			}
			return true;
		}
		$email = sanitize_email( (string) ( $lead['email'] ?? '' ) );
		if ( is_email( $email ) ) {
			Notifier::mail_to( $email, 'پاسخ مشاور ' . $lead['lead_code'], $body );
			return true;
		}
		$phone = LeadWriter::digits( (string) ( $lead['phone'] ?? '' ) );
		if ( strlen( $phone ) >= 8 ) {
			Sms::send( $phone, 'پاسخ لایف‌روس: ' . mb_substr( $body, 0, 140 ) );
		}
		return true;
	}

	/**
	 * Saved university slugs.
	 *
	 * @param int $user_id User id.
	 * @return string[]
	 */
	public static function saved( int $user_id ): array {
		$raw = get_user_meta( $user_id, 'lr_saved_universities', true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( $slug ) {
				$out[] = $slug;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Send a code.
	 */
	private static function send_otp(): void {
		$channel = isset( $_POST['channel'] ) ? sanitize_key( wp_unslash( $_POST['channel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$target  = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'email' === $channel ) {
			$target = sanitize_email( $target );
			if ( ! is_email( $target ) ) {
				self::redirect( 'login', 'bad' );
			}
		} else {
			$channel = 'phone';
			$target  = LeadWriter::digits( $target );
			if ( strlen( $target ) < 8 ) {
				self::redirect( 'login', 'bad' );
			}
		}
		Otp::issue( $channel, $target );
		set_transient( 'lr_otp_pending_' . self::cookie_key(), $channel . '|' . $target, 10 * MINUTE_IN_SECONDS );
		self::redirect( 'login', 'sent' );
	}

	/**
	 * Check the code, create the account, and attach matching leads.
	 */
	private static function verify_otp(): void {
		$pending = get_transient( 'lr_otp_pending_' . self::cookie_key() );
		$code    = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_string( $pending ) || ! str_contains( $pending, '|' ) ) {
			self::redirect( 'login', 'bad' );
		}
		list( $channel, $target ) = explode( '|', $pending, 2 );
		if ( ! Otp::check( $channel, $target, $code ) ) {
			self::redirect( 'login', 'bad' );
		}
		$user_id = self::ensure_user( $channel, $target );
		if ( $user_id < 1 ) {
			self::redirect( 'login', 'bad' );
		}
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		delete_transient( 'lr_otp_pending_' . self::cookie_key() );
		self::redirect( 'requests', 'in' );
	}

	/**
	 * Upload into the client's own lead.
	 */
	private static function upload(): void {
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_user_logged_in() || ! self::owns( get_current_user_id(), $lead_id ) ) {
			self::redirect( 'requests', 'bad' );
		}
		$result = Files::store_upload( $lead_id, 'client_file', 'other' );
		self::redirect( 'request', is_wp_error( $result ) ? 'bad' : 'saved', $lead_id );
	}

	/**
	 * Append a message and email the consultant.
	 */
	private static function message(): void {
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$body    = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_user_logged_in() || ! self::owns( get_current_user_id(), $lead_id ) || '' === trim( $body ) ) {
			self::redirect( 'requests', 'bad' );
		}
		self::add_message( $lead_id, get_current_user_id(), $body );
		self::redirect( 'request', 'saved', $lead_id );
	}

	/**
	 * Remember a university slug.
	 */
	private static function save_university(): void {
		if ( ! is_user_logged_in() ) {
			self::redirect( 'login', 'bad' );
		}
		$slugs = array();
		if ( isset( $_POST['slug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$slugs[] = sanitize_title( wp_unslash( $_POST['slug'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		if ( isset( $_POST['slugs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			foreach ( explode( ',', sanitize_text_field( wp_unslash( $_POST['slugs'] ) ) ) as $part ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$slugs[] = sanitize_title( $part );
			}
		}
		$slugs = array_values( array_filter( $slugs ) );
		if ( ! $slugs ) {
			self::redirect( 'saved', 'bad' );
		}
		$list = array_merge( self::saved( get_current_user_id() ), $slugs );
		update_user_meta( get_current_user_id(), 'lr_saved_universities', array_slice( array_values( array_unique( $list ) ), 0, 40 ) );
		self::redirect( 'saved', 'saved' );
	}

	/**
	 * Display name only. Phone and email stay verified.
	 */
	private static function save_profile(): void {
		if ( ! is_user_logged_in() ) {
			self::redirect( 'login', 'bad' );
		}
		$name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' !== $name ) {
			wp_update_user(
				array(
					'ID'           => get_current_user_id(),
					'display_name' => $name,
					'first_name'   => $name,
				)
			);
		}
		self::redirect( 'profile', 'saved' );
	}

	/**
	 * Find or create the client.
	 *
	 * @param string $channel email or phone.
	 * @param string $target  Target.
	 */
	private static function ensure_user( string $channel, string $target ): int {
		if ( 'email' === $channel ) {
			$user = get_user_by( 'email', $target );
			if ( $user ) {
				return in_array( 'lr_client', (array) $user->roles, true ) ? (int) $user->ID : 0;
			}
			$login = sanitize_user( strstr( $target, '@', true ) . wp_rand( 100, 999 ), true );
			$id    = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_email'   => $target,
					'user_pass'    => wp_generate_password( 24 ),
					'role'         => 'lr_client',
					'display_name' => $login,
				)
			);
			return is_wp_error( $id ) ? 0 : (int) $id;
		}
		$found = self::user_by_phone( $target );
		if ( $found ) {
			$user = get_userdata( $found );
			return ( $user && in_array( 'lr_client', (array) $user->roles, true ) ) ? $found : 0;
		}
		$login = 'c' . $target;
		$email = $login . '@clients.liferuss.invalid';
		$id    = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 24 ),
				'role'         => 'lr_client',
				'display_name' => $target,
			)
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_user_meta( (int) $id, 'lr_phone', $target );
		return (int) $id;
	}

	/**
	 * User id for a normalized phone.
	 *
	 * @param string $phone Digits.
	 */
	private static function user_by_phone( string $phone ): int {
		$users = get_users(
			array(
				'meta_key'   => 'lr_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		return $users ? (int) $users[0] : 0;
	}

	/**
	 * Stable key for the OTP pending transient.
	 */
	private static function cookie_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return md5( $ip . '|' . $ua );
	}

	/**
	 * Redirect back to an account screen.
	 *
	 * @param string $screen Screen slug.
	 * @param string $notice Notice code.
	 * @param int    $id     Lead id.
	 */
	private static function redirect( string $screen, string $notice, int $id = 0 ): void {
		$path = '/account/';
		if ( 'requests' === $screen ) {
			$path = '/account/requests/';
		} elseif ( 'saved' === $screen ) {
			$path = '/account/saved/';
		} elseif ( 'profile' === $screen ) {
			$path = '/account/profile/';
		} elseif ( 'request' === $screen && $id ) {
			$path = '/account/request/' . $id . '/';
		}
		$url = function_exists( 'liferuss_url' ) ? liferuss_url( $path ) : home_url( $path );
		wp_safe_redirect( add_query_arg( 'notice', $notice, $url ) );
		exit;
	}
}
