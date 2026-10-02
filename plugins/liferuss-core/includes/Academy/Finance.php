<?php
/**
 * Revenue line on service invoices, and the global report.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Lines: study, academy, exchange, cargo, trade. Figures are net of Academy refunds.
 */
class Finance {

	/**
	 * Public line keys in display order.
	 *
	 * @return array<string, string>
	 */
	public static function lines(): array {
		return array(
			'study'    => 'تحصیل و پذیرش',
			'academy'  => 'آکادمی',
			'exchange' => 'صرافی',
			'cargo'    => 'کارگو',
			'trade'    => 'تجارت',
		);
	}

	/**
	 * Set revenue_line on existing service payments from the lead's service group.
	 */
	public static function backfill(): void {
		if ( get_option( 'lr_revenue_line_backfill' ) ) {
			return;
		}
		global $wpdb;
		$payments = $wpdb->prefix . 'lr_payments';
		$leads    = $wpdb->prefix . 'lr_leads';
		$services = $wpdb->prefix . 'lr_services';
		$exists   = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$payments}` LIKE %s", 'revenue_line' ) );
		if ( ! $exists ) {
			return;
		}
		$wpdb->query(
			"UPDATE `{$payments}` p
			INNER JOIN `{$leads}` l ON l.id = p.lead_id
			INNER JOIN `{$services}` s ON s.id = l.service_id
			SET p.revenue_line = CASE s.service_group
				WHEN 'exchange' THEN 'exchange'
				WHEN 'cargo' THEN 'cargo'
				WHEN 'trade' THEN 'trade'
				ELSE 'study'
			END"
		);
		update_option( 'lr_revenue_line_backfill', '1', false );
	}

	/**
	 * Net Toman per line, and a daily series, for a UTC range.
	 *
	 * @param string $from UTC start.
	 * @param string $to   UTC end.
	 * @return array{totals: array<string, int>, days: array<string, array<string, int>>}
	 */
	public static function report( string $from, string $to ): array {
		$totals = array();
		foreach ( array_keys( self::lines() ) as $line ) {
			$totals[ $line ] = 0;
		}
		$days = array();
		foreach ( self::service_rows( $from, $to ) as $row ) {
			$line             = isset( $totals[ $row['revenue_line'] ] ) ? (string) $row['revenue_line'] : 'study';
			$totals[ $line ] += (int) $row['amount'];
			$day              = (string) $row['day'];
			if ( ! isset( $days[ $day ] ) ) {
				$days[ $day ] = $totals;
				foreach ( $days[ $day ] as $key => $ignore ) {
					$days[ $day ][ $key ] = 0;
					unset( $ignore );
				}
			}
			$days[ $day ][ $line ] += (int) $row['amount'];
		}
		foreach ( self::academy_rows( $from, $to ) as $row ) {
			$totals['academy'] += (int) $row['amount'];
			$day                = (string) $row['day'];
			if ( ! isset( $days[ $day ] ) ) {
				$days[ $day ] = array_fill_keys( array_keys( self::lines() ), 0 );
			}
			$days[ $day ]['academy'] += (int) $row['amount'];
		}
		ksort( $days );
		return array(
			'totals' => $totals,
			'days'   => $days,
		);
	}

	/**
	 * Paid service invoices. Amounts are positive; this table has no refund rows.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @return array<int, array<string, mixed>>
	 */
	private static function service_rows( string $from, string $to ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_payments';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT revenue_line, DATE(paid_at) AS day, SUM(amount_toman) AS amount
				FROM `{$table}` WHERE status = 'paid' AND paid_at >= %s AND paid_at <= %s
				GROUP BY revenue_line, DATE(paid_at)",
				$from,
				$to
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Academy charges and negative refunds.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @return array<int, array<string, mixed>>
	 */
	private static function academy_rows( string $from, string $to ): array {
		global $wpdb;
		$table = Db::table( 'payments' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(paid_at) AS day, SUM(amount_toman) AS amount
				FROM `{$table}` WHERE status IN ('paid','refund') AND paid_at >= %s AND paid_at <= %s
				GROUP BY DATE(paid_at)",
				$from,
				$to
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}
}
