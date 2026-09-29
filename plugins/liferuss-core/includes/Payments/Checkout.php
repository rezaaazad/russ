<?php
/**
 * Service invoices: create, pay link, and idempotent verify.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Payments;

use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\CRM\LeadWriter;
use LifeRuss\Core\CRM\Notifier;
use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Payment rows and the public /pay/{token}/ flow.
 */
class Checkout {

	/**
	 * Cron and the public callback.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'lr_pay_expire', array( self::class, 'expire_due' ) );
		add_action( 'template_redirect', array( self::class, 'handle' ), 1 );
	}

	/**
	 * Hourly expiry sweep.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'lr_pay_expire' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'lr_pay_expire' );
		}
	}

	/**
	 * Status labels.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			'pending'   => 'در انتظار پرداخت',
			'paid'      => 'پرداخت شد',
			'failed'    => 'ناموفق',
			'expired'   => 'منقضی',
			'cancelled' => 'لغو',
		);
	}

	/**
	 * Create an invoice for a lead the current user can see.
	 *
	 * @param int    $lead_id     Lead id.
	 * @param int    $amount      Toman.
	 * @param string $description Description.
	 * @param string $expires     Jalali or Gregorian date, optional.
	 * @return int|\WP_Error
	 */
	public static function create( int $lead_id, int $amount, string $description, string $expires ) {
		if ( ! LeadWriter::can_view( $lead_id ) ) {
			return new \WP_Error( 'lr_pay', 'به این لید دسترسی ندارید.' );
		}
		if ( $amount < 1000 ) {
			return new \WP_Error( 'lr_pay', 'مبلغ باید حداقل ۱۰۰۰ تومان باشد.' );
		}
		$description = trim( $description );
		if ( '' === $description ) {
			return new \WP_Error( 'lr_pay', 'شرح صورتحساب لازم است.' );
		}
		$when = Jalali::filter_utc( $expires, true );
		if ( '' === $when ) {
			$when = gmdate( 'Y-m-d H:i:s', time() + ( 7 * DAY_IN_SECONDS ) );
		}
		$token = bin2hex( random_bytes( 16 ) );
		$now   = gmdate( 'Y-m-d H:i:s' );
		$id    = Repository::for( 'payments' )->insert(
			array(
				'lead_id'      => $lead_id,
				'token'        => $token,
				'amount_toman' => $amount,
				'description'  => substr( $description, 0, 255 ),
				'status'       => 'pending',
				'expires_at'   => $when,
				'gateway'      => 'zarinpal',
				'created_by'   => get_current_user_id(),
				'updated_at'   => $now,
			)
		);
		return $id ? $id : new \WP_Error( 'lr_pay', 'ثبت صورتحساب ممکن نشد.' );
	}

	/**
	 * Invoices for one lead, newest first.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_lead( int $lead_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE lead_id = %d ORDER BY id DESC LIMIT 20", $lead_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One invoice by public token.
	 *
	 * @param string $token Token.
	 * @return array<string, mixed>|null
	 */
	public static function by_token( string $token ): ?array {
		$token = strtolower( preg_replace( '/[^a-f0-9]/', '', $token ) ?? '' );
		if ( 32 !== strlen( $token ) ) {
			return null;
		}
		$row = Repository::for( 'payments' )->find_by( 'token', $token );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Public URL.
	 *
	 * @param array<string, mixed> $payment Row.
	 */
	public static function url( array $payment ): string {
		return home_url( '/pay/' . $payment['token'] . '/' );
	}

	/**
	 * Mark overdue pending rows.
	 */
	public static function expire_due(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET status = 'expired', updated_at = %s WHERE status = 'pending' AND expires_at IS NOT NULL AND expires_at < %s", $now, $now ) );
	}

	/**
	 * Cancel a pending invoice.
	 *
	 * @param int $id      Payment id.
	 * @param int $lead_id Lead id, for the capability check.
	 */
	public static function cancel( int $id, int $lead_id ): bool {
		if ( ! LeadWriter::can_view( $lead_id ) ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$done  = $wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET status = 'cancelled', updated_at = %s WHERE id = %d AND lead_id = %d AND status = 'pending'", $now, $id, $lead_id ) );
		return (int) $done > 0;
	}

	/**
	 * Start or verify the public page.
	 */
	public static function handle(): void {
		$token = (string) get_query_var( 'lr_pay' );
		if ( '' === $token ) {
			return;
		}
		nocache_headers();
		$row = self::by_token( $token );
		if ( ! $row ) {
			return;
		}
		self::touch_expiry( $row );
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( 'POST' === $method ) {
			self::start( $row );
		}
		if ( isset( $_GET['Authority'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$authority = sanitize_text_field( wp_unslash( (string) $_GET['Authority'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status    = isset( $_GET['Status'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['Status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::verify( $row, $authority, $status );
			wp_safe_redirect( self::url( $row ) );
			exit;
		}
	}

	/**
	 * Lead-detail form and existing invoices.
	 *
	 * @param int $lead_id Lead id.
	 */
	public static function box( int $lead_id ): void {
		echo '<h2>' . esc_html__( 'صورتحساب خدمات', 'liferuss-core' ) . '</h2>';
		echo '<ul>';
		foreach ( self::for_lead( $lead_id ) as $row ) {
			$label = self::statuses()[ (string) $row['status'] ] ?? (string) $row['status'];
			echo '<li>' . esc_html( number_format_i18n( (int) $row['amount_toman'] ) . ' تومان — ' . $label );
			echo ' — <a href="' . esc_url( self::url( $row ) ) . '">' . esc_html__( 'لینک پرداخت', 'liferuss-core' ) . '</a>';
			if ( 'pending' === $row['status'] ) {
				echo ' <form style="display:inline" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'lr_payment_cancel' );
				echo '<input type="hidden" name="action" value="lr_payment_cancel">';
				echo '<input type="hidden" name="lead_id" value="' . esc_attr( (string) $lead_id ) . '">';
				echo '<input type="hidden" name="payment_id" value="' . esc_attr( (string) $row['id'] ) . '">';
				submit_button( __( 'لغو', 'liferuss-core' ), 'secondary small', 'submit', false );
				echo '</form>';
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_payment_create' );
		echo '<input type="hidden" name="action" value="lr_payment_create">';
		echo '<input type="hidden" name="lead_id" value="' . esc_attr( (string) $lead_id ) . '">';
		echo '<input type="number" min="1000" name="amount_toman" placeholder="' . esc_attr__( 'مبلغ به تومان', 'liferuss-core' ) . '" required> ';
		echo '<input type="text" name="pay_description" class="regular-text" placeholder="' . esc_attr__( 'شرح', 'liferuss-core' ) . '" required> ';
		echo '<input type="text" name="pay_expires" placeholder="' . esc_attr__( 'انقضا ۱۴۰۵/۰۷/۲۰', 'liferuss-core' ) . '"> ';
		submit_button( __( 'ساخت لینک پرداخت', 'liferuss-core' ), 'secondary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Driver selected by the liferuss_payment_gateway filter.
	 *
	 * @param array<string, mixed> $payment Row.
	 */
	public static function gateway( array $payment ): Gateway {
		$driver = apply_filters( 'liferuss_payment_gateway', new Zarinpal(), $payment );
		return $driver instanceof Gateway ? $driver : new Zarinpal();
	}

	/**
	 * POST the pay button.
	 *
	 * @param array<string, mixed> $row Payment row.
	 */
	private static function start( array $row ): void {
		$nonce = isset( $_POST['lr_pay_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['lr_pay_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'lr_pay_' . $row['token'] ) ) {
			wp_die( esc_html__( 'نشست منقضی شده است.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		if ( 'pending' !== $row['status'] ) {
			wp_safe_redirect( self::url( $row ) );
			exit;
		}
		$result = self::gateway( $row )->request( $row );
		if ( $result['ok'] && '' !== $result['authority'] ) {
			Repository::for( 'payments' )->update(
				(int) $row['id'],
				array(
					'authority' => $result['authority'],
				)
			);
			wp_redirect( $result['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Gateway URL is built by the driver.
			exit;
		}
		wp_die( esc_html( $result['message'] ), '', array( 'response' => 502 ) );
	}

	/**
	 * Idempotent verify. A second callback does not notify again.
	 *
	 * @param array<string, mixed> $row       Payment row.
	 * @param string               $authority Authority.
	 * @param string               $status    Gateway status.
	 */
	private static function verify( array $row, string $authority, string $status ): void {
		if ( 'paid' === $row['status'] ) {
			return;
		}
		if ( 'pending' !== $row['status'] ) {
			return;
		}
		$result = self::gateway( $row )->verify( $row, $authority, $status );
		if ( ! $result['ok'] ) {
			if ( 'OK' === strtoupper( $status ) ) {
				self::mark( (int) $row['id'], 'failed' );
			}
			return;
		}
		self::mark_paid( $row, $result['ref_id'] );
	}

	/**
	 * Flip pending to paid once, then write history and notify.
	 *
	 * @param array<string, mixed> $row    Payment row.
	 * @param string               $ref_id Gateway reference.
	 */
	public static function mark_paid( array $row, string $ref_id ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$done  = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET status = 'paid', ref_id = %s, paid_at = %s, updated_at = %s WHERE id = %d AND status = 'pending'",
				substr( $ref_id, 0, 64 ),
				$now,
				$now,
				(int) $row['id']
			)
		);
		if ( 1 !== (int) $done ) {
			return false;
		}
		$lead = Repository::for( 'leads' )->find( (int) $row['lead_id'] );
		if ( $lead ) {
			Repository::for( 'lead_status_history' )->insert(
				array(
					'lead_id'     => (int) $lead['id'],
					'from_status' => (string) $lead['status'],
					'to_status'   => (string) $lead['status'],
					'reason'      => substr( 'پرداخت ' . $ref_id, 0, 255 ),
				)
			);
			$body       = 'پرداخت ' . number_format_i18n( (int) $row['amount_toman'] ) . ' تومان برای ' . $lead['lead_code'] . ' ثبت شد. مرجع: ' . $ref_id;
			$consultant = (int) ( $lead['consultant_id'] ?? 0 );
			if ( $consultant ) {
				$user = get_userdata( $consultant );
				if ( $user && is_email( $user->user_email ) ) {
					Notifier::mail_to( $user->user_email, 'پرداخت ' . $lead['lead_code'], $body );
				}
			}
			Notifier::notify_admins( 'پرداخت ' . $lead['lead_code'], $body );
		}
		return true;
	}

	/**
	 * Set a non-paid status only while the row is still pending.
	 *
	 * @param int    $id     Payment id.
	 * @param string $status failed, expired, or cancelled.
	 */
	private static function mark( int $id, string $status ): void {
		if ( ! isset( self::statuses()[ $status ] ) || 'paid' === $status || 'pending' === $status ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET status = %s, updated_at = %s WHERE id = %d AND status = 'pending'", $status, gmdate( 'Y-m-d H:i:s' ), $id ) );
	}

	/**
	 * Expire one row when its deadline has passed.
	 *
	 * @param array<string, mixed> $row Payment row.
	 */
	private static function touch_expiry( array $row ): void {
		if ( 'pending' !== $row['status'] || empty( $row['expires_at'] ) ) {
			return;
		}
		if ( (string) $row['expires_at'] < gmdate( 'Y-m-d H:i:s' ) ) {
			self::mark( (int) $row['id'], 'expired' );
		}
	}
}
