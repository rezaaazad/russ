<?php
/**
 * Academy dashboard numbers and course-page visitors.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Conversion is paid course orders divided by unique course-page visitors.
 */
class Metrics {

	/**
	 * Count a course page view. One unique per browser per Tehran day.
	 *
	 * @param int $course_id Course id.
	 */
	public static function hit( int $course_id ): void {
		if ( $course_id < 1 || is_admin() ) {
			return;
		}
		$day    = gmdate( 'Y-m-d', time() + (int) ( 3.5 * HOUR_IN_SECONDS ) );
		$cookie = 'lr_ac_view_' . $course_id;
		$unique = ! isset( $_COOKIE[ $cookie ] ) || (string) $_COOKIE[ $cookie ] !== $day;
		if ( $unique && ! headers_sent() ) {
			setcookie( $cookie, $day, time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
		global $wpdb;
		$table = Db::table( 'course_views_daily' );
		$now   = Db::now();
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (course_id, stat_date, views, uniques, created_at, updated_at)
				VALUES (%d, %s, 1, %d, %s, %s)
				ON DUPLICATE KEY UPDATE views = views + 1, uniques = uniques + %d, updated_at = %s",
				$course_id,
				$day,
				$unique ? 1 : 0,
				$now,
				$now,
				$unique ? 1 : 0,
				$now
			)
		);
	}

	/**
	 * Figures for a UTC range, plus the same length just before it.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id, or 0 for everyone.
	 * @return array<string, mixed>
	 */
	public static function summary( string $from, string $to, int $instructor = 0 ): array {
		$current   = self::slice( $from, $to, $instructor );
		$seconds   = max( 1, strtotime( $to . ' UTC' ) - strtotime( $from . ' UTC' ) );
		$prev_to   = gmdate( 'Y-m-d H:i:s', strtotime( $from . ' UTC' ) - 1 );
		$prev_from = gmdate( 'Y-m-d H:i:s', strtotime( $prev_to . ' UTC' ) - $seconds );
		return array(
			'current'  => $current,
			'previous' => self::slice( $prev_from, $prev_to, $instructor ),
			'from'     => $from,
			'to'       => $to,
		);
	}

	/**
	 * One period.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor filter.
	 * @return array<string, mixed>
	 */
	private static function slice( string $from, string $to, int $instructor ): array {
		$items   = self::item_totals( $from, $to, $instructor );
		$refunds = self::refunds( $from, $to, $instructor );
		$revenue = self::net( $from, $to, $instructor );
		$orders  = (int) ( $items['course_orders'] ?? 0 );
		$uniques = self::uniques( $from, $to, $instructor );
		return array(
			'revenue'       => $revenue,
			'courses'       => (int) ( $items['course'] ?? 0 ),
			'subscriptions' => (int) ( $items['plan'] ?? 0 ),
			'private'       => (int) ( $items['private_class'] ?? 0 ),
			'group'         => (int) ( $items['group_class'] ?? 0 ),
			'refunds'       => $refunds,
			'active'        => self::active_students(),
			'new_students'  => self::new_students( $from, $to ),
			'bestsellers'   => self::bestsellers( $from, $to, $instructor ),
			'conversion'    => $uniques > 0 ? round( ( $orders * 100 ) / $uniques, 1 ) : 0,
			'uniques'       => $uniques,
			'course_orders' => $orders,
			'days'          => self::days( $from, $to, $instructor ),
		);
	}

	/**
	 * Paid item totals. Refunds are reported separately and also reduce net revenue.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 * @return array<string, int>
	 */
	private static function item_totals( string $from, string $to, int $instructor ): array {
		global $wpdb;
		$items  = Db::table( 'order_items' );
		$orders = Db::table( 'orders' );
		$sql    = "SELECT i.item_type, SUM(i.amount_toman) AS amount, SUM(CASE WHEN i.item_type = 'course' THEN 1 ELSE 0 END) AS course_orders
			FROM `{$items}` i INNER JOIN `{$orders}` o ON o.id = i.order_id
			WHERE o.status = 'paid' AND o.paid_at >= %s AND o.paid_at <= %s";
		$args   = array( $from, $to );
		$sql   .= self::instructor_sql( $instructor, $args );
		$sql   .= ' GROUP BY i.item_type';
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out    = array( 'course_orders' => 0 );
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['item_type'] ] = (int) $row['amount'];
			$out['course_orders']             += (int) $row['course_orders'];
		}
		return $out;
	}

	/**
	 * Absolute value of refund rows.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 */
	private static function refunds( string $from, string $to, int $instructor ): int {
		global $wpdb;
		$table = Db::table( 'payments' );
		$sql   = "SELECT COALESCE(SUM(ABS(amount_toman)), 0) FROM `{$table}` p WHERE p.status = 'refund' AND p.paid_at >= %s AND p.paid_at <= %s";
		$args  = array( $from, $to );
		if ( $instructor > 0 ) {
			$sql   .= ' AND p.order_id IN (' . self::instructor_orders_sql() . ')';
			$args[] = $instructor;
			$args[] = $instructor;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Net academy payments.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 */
	private static function net( string $from, string $to, int $instructor ): int {
		global $wpdb;
		$table = Db::table( 'payments' );
		$sql   = "SELECT COALESCE(SUM(amount_toman), 0) FROM `{$table}` p WHERE p.status IN ('paid','refund') AND p.paid_at >= %s AND p.paid_at <= %s";
		$args  = array( $from, $to );
		if ( $instructor > 0 ) {
			$sql   .= ' AND p.order_id IN (' . self::instructor_orders_sql() . ')';
			$args[] = $instructor;
			$args[] = $instructor;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Unique course visitors in the range.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 */
	private static function uniques( string $from, string $to, int $instructor ): int {
		global $wpdb;
		$views   = Db::table( 'course_views_daily' );
		$courses = Db::table( 'courses' );
		$sql     = "SELECT COALESCE(SUM(v.uniques), 0) FROM `{$views}` v INNER JOIN `{$courses}` c ON c.id = v.course_id WHERE v.stat_date >= %s AND v.stat_date <= %s";
		$args    = array( substr( $from, 0, 10 ), substr( $to, 0, 10 ) );
		if ( $instructor > 0 ) {
			$sql   .= ' AND c.instructor_id = %d';
			$args[] = $instructor;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Students with progress in the last 30 days. This window does not follow the date filter.
	 */
	private static function active_students(): int {
		global $wpdb;
		$table = Db::table( 'course_progress' );
		$since = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT student_id) FROM `{$table}` WHERE updated_at >= %s", $since ) );
	}

	/**
	 * Profiles created in the range.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 */
	private static function new_students( string $from, string $to ): int {
		global $wpdb;
		$table = Db::table( 'students' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s AND created_at <= %s", $from, $to ) );
	}

	/**
	 * Top courses by paid order count.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function bestsellers( string $from, string $to, int $instructor ): array {
		global $wpdb;
		$items   = Db::table( 'order_items' );
		$orders  = Db::table( 'orders' );
		$courses = Db::table( 'courses' );
		$sql     = "SELECT c.title, COUNT(*) AS sales, SUM(i.amount_toman) AS amount
			FROM `{$items}` i
			INNER JOIN `{$orders}` o ON o.id = i.order_id
			INNER JOIN `{$courses}` c ON c.id = i.item_id
			WHERE i.item_type = 'course' AND o.status = 'paid' AND o.paid_at >= %s AND o.paid_at <= %s";
		$args    = array( $from, $to );
		if ( $instructor > 0 ) {
			$sql   .= ' AND c.instructor_id = %d';
			$args[] = $instructor;
		}
		$sql .= ' GROUP BY c.id ORDER BY sales DESC LIMIT 5';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Daily net revenue for the chart.
	 *
	 * @param string $from Start.
	 * @param string $to   End.
	 * @param int    $instructor Instructor id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function days( string $from, string $to, int $instructor ): array {
		global $wpdb;
		$table = Db::table( 'payments' );
		$sql   = "SELECT DATE(paid_at) AS day, SUM(amount_toman) AS amount FROM `{$table}` p WHERE status IN ('paid','refund') AND paid_at >= %s AND paid_at <= %s";
		$args  = array( $from, $to );
		if ( $instructor > 0 ) {
			$sql   .= ' AND order_id IN (' . self::instructor_orders_sql() . ')';
			$args[] = $instructor;
			$args[] = $instructor;
		}
		$sql .= ' GROUP BY DATE(paid_at) ORDER BY day ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Extra AND for item queries owned by one instructor.
	 *
	 * @param int      $instructor Instructor id.
	 * @param string[] $args       Bound args, by reference.
	 */
	private static function instructor_sql( int $instructor, array &$args ): string {
		if ( $instructor < 1 ) {
			return '';
		}
		$args[] = $instructor;
		$args[] = $instructor;
		return ' AND i.order_id IN (' . self::instructor_orders_sql() . ')';
	}

	/**
	 * Subquery of order ids that include this instructor's courses or classes.
	 */
	private static function instructor_orders_sql(): string {
		$items    = Db::table( 'order_items' );
		$courses  = Db::table( 'courses' );
		$sessions = Db::table( 'class_sessions' );
		return "SELECT i2.order_id FROM `{$items}` i2
			LEFT JOIN `{$courses}` c2 ON i2.item_type = 'course' AND c2.id = i2.item_id
			LEFT JOIN `{$sessions}` s2 ON i2.item_type IN ('private_class','group_class') AND s2.id = i2.item_id
			WHERE c2.instructor_id = %d OR s2.instructor_id = %d";
	}
}
