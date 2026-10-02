<?php
/**
 * Payment list, Jalali filters, and admin-only CSV.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Payments;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

/**
 * Staff screens for service invoices.
 */
class Admin {

	/**
	 * Menu actions.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_payment_create', array( self::class, 'create' ) );
		add_action( 'admin_post_lr_payment_cancel', array( self::class, 'cancel' ) );
		add_action( 'admin_post_lr_export_payments', array( self::class, 'export' ) );
	}

	/**
	 * List with Jalali date filters.
	 */
	public static function screen(): void {
		if ( ! current_user_can( 'lr_manage_leads' ) ) {
			wp_die( esc_html__( 'به این بخش دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$after  = isset( $_GET['lr_after'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['lr_after'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$before = isset( $_GET['lr_before'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['lr_before'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['lr_status'] ) ? sanitize_key( wp_unslash( (string) $_GET['lr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rows   = self::query( $after, $before, $status );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'پرداخت‌های خدمات', 'liferuss-core' ) . '</h1>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="lr-payments">';
		$hint = Jalali::year_hint();
		echo '<input type="text" name="lr_after" placeholder="' . esc_attr( 'از ' . $hint[0] ) . '" value="' . esc_attr( $after ) . '"> ';
		echo '<input type="text" name="lr_before" placeholder="' . esc_attr( 'تا ' . $hint[1] ) . '" value="' . esc_attr( $before ) . '"> ';
		echo '<select name="lr_status"><option value="">همه</option>';
		foreach ( Checkout::statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> ';
		submit_button( __( 'صافی', 'liferuss-core' ), 'secondary', 'submit', false );
		echo '</form>';
		if ( current_user_can( 'lr_export_payments' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_export_payments' );
			echo '<input type="hidden" name="action" value="lr_export_payments">';
			echo '<input type="hidden" name="lr_after" value="' . esc_attr( $after ) . '">';
			echo '<input type="hidden" name="lr_before" value="' . esc_attr( $before ) . '">';
			echo '<input type="hidden" name="lr_status" value="' . esc_attr( $status ) . '">';
			submit_button( __( 'دانلود CSV', 'liferuss-core' ) );
			echo '</form>';
		}
		echo '<table class="widefat striped"><thead><tr><th>شناسه</th><th>لید</th><th>مبلغ</th><th>وضعیت</th><th>مرجع</th><th>تاریخ</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="6">' . esc_html__( 'موردی نیست.', 'liferuss-core' ) . '</td></tr>';
		}
		foreach ( $rows as $row ) {
			$link  = admin_url( 'admin.php?page=lr-lead&id=' . (int) $row['lead_id'] );
			$label = Checkout::statuses()[ (string) $row['status'] ] ?? (string) $row['status'];
			echo '<tr><td>' . esc_html( (string) $row['id'] ) . '</td>';
			echo '<td><a href="' . esc_url( $link ) . '">#' . esc_html( (string) $row['lead_id'] ) . '</a></td>';
			echo '<td>' . esc_html( number_format_i18n( (int) $row['amount_toman'] ) ) . '</td>';
			echo '<td>' . esc_html( $label ) . '</td>';
			echo '<td>' . esc_html( (string) $row['ref_id'] ) . '</td>';
			echo '<td>' . Jalali::html( (string) $row['created_at'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Create from the lead screen.
	 */
	public static function create(): void {
		check_admin_referer( 'lr_payment_create' );
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$amount  = isset( $_POST['amount_toman'] ) ? absint( $_POST['amount_toman'] ) : 0;
		$desc    = isset( $_POST['pay_description'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['pay_description'] ) ) : '';
		$expires = isset( $_POST['pay_expires'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['pay_expires'] ) ) : '';
		$result  = Checkout::create( $lead_id, $amount, $desc, $expires );
		$args    = array(
			'page' => 'lr-lead',
			'id'   => $lead_id,
		);
		if ( is_wp_error( $result ) ) {
			$args['lr_pay_err'] = rawurlencode( $result->get_error_message() );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Cancel a pending invoice.
	 */
	public static function cancel(): void {
		check_admin_referer( 'lr_payment_cancel' );
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$id      = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		Checkout::cancel( $id, $lead_id );
		wp_safe_redirect( admin_url( 'admin.php?page=lr-lead&id=' . $lead_id ) );
		exit;
	}

	/**
	 * CSV for administrators only.
	 */
	public static function export(): void {
		check_admin_referer( 'lr_export_payments' );
		if ( ! current_user_can( 'lr_export_payments' ) ) {
			wp_die( esc_html__( 'به خروجی پرداخت‌ها دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$after  = isset( $_POST['lr_after'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['lr_after'] ) ) : '';
		$before = isset( $_POST['lr_before'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['lr_before'] ) ) : '';
		$status = isset( $_POST['lr_status'] ) ? sanitize_key( wp_unslash( (string) $_POST['lr_status'] ) ) : '';
		$rows   = self::query( $after, $before, $status );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=liferuss-payments.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $out ) {
			exit;
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fputcsv( $out, array( 'id', 'lead_id', 'amount_toman', 'status', 'ref_id', 'created_at', 'paid_at' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		foreach ( $rows as $row ) {
			fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				$out,
				array(
					$row['id'],
					$row['lead_id'],
					$row['amount_toman'],
					$row['status'],
					$row['ref_id'],
					$row['created_at'],
					$row['paid_at'],
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Filtered rows.
	 *
	 * @param string $after  Jalali or Gregorian start.
	 * @param string $before Jalali or Gregorian end.
	 * @param string $status Status slug.
	 * @return array<int, array<string, mixed>>
	 */
	private static function query( string $after, string $before, string $status ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$where = array( '1=1' );
		$args  = array();
		$start = Jalali::filter_utc( $after, false );
		$end   = Jalali::filter_utc( $before, true );
		if ( '' !== $start ) {
			$where[] = 'created_at >= %s';
			$args[]  = $start;
		}
		if ( '' !== $end ) {
			$where[] = 'created_at <= %s';
			$args[]  = $end;
		}
		if ( isset( Checkout::statuses()[ $status ] ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		$sql = "SELECT * FROM `{$table}` WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT 200';
		if ( $args ) {
			$sql = $wpdb->prepare( $sql, $args );
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}
}
