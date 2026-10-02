<?php
/**
 * Private hourly slots and scheduled group classes.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * A pending checkout holds a seat for 30 minutes. Payment confirms it.
 */
class Classes {

	/**
	 * Whether a class line still has room.
	 *
	 * @param array<string, mixed> $line Checkout line.
	 */
	public static function hold( array $line ): bool {
		$type = (string) ( $line['item_type'] ?? '' );
		if ( 'private_class' !== $type && 'group_class' !== $type ) {
			return true;
		}
		$session = Db::find( 'class_sessions', (int) $line['item_id'] );
		if ( ! $session || 'open' !== $session['status'] ) {
			return false;
		}
		return self::taken( (int) $session['id'] ) < (int) $session['capacity'];
	}

	/**
	 * Confirm a seat after payment and close the session when it is full.
	 *
	 * @param int $session_id Session id.
	 * @param int $student_id Student id.
	 * @param int $order_id   Order id.
	 */
	public static function confirm( int $session_id, int $student_id, int $order_id ): void {
		$existing = self::booking_for_order( $order_id );
		if ( $existing ) {
			Db::update( 'class_bookings', (int) $existing['id'], array( 'status' => 'confirmed' ) );
		} else {
			Db::insert(
				'class_bookings',
				array(
					'session_id' => $session_id,
					'student_id' => $student_id,
					'order_id'   => $order_id,
					'status'     => 'confirmed',
				)
			);
		}
		$session = Db::find( 'class_sessions', $session_id );
		if ( $session && self::confirmed( $session_id ) >= (int) $session['capacity'] ) {
			Db::update( 'class_sessions', $session_id, array( 'status' => 'full' ) );
		}
	}

	/**
	 * Upcoming open sessions.
	 *
	 * @param string $kind private or group. Empty returns both.
	 * @return array<int, array<string, mixed>>
	 */
	public static function upcoming( string $kind = '' ): array {
		global $wpdb;
		$table = Db::table( 'class_sessions' );
		$sql   = "SELECT * FROM `{$table}` WHERE status = 'open' AND starts_at >= %s";
		$args  = array( Db::now() );
		if ( 'private' === $kind || 'group' === $kind ) {
			$sql   .= ' AND kind = %s';
			$args[] = $kind;
		}
		$sql .= ' ORDER BY starts_at ASC LIMIT 24';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Confirmed seats plus pending orders from the last 30 minutes.
	 *
	 * @param int $session_id Session id.
	 */
	private static function taken( int $session_id ): int {
		return self::confirmed( $session_id ) + self::recent_pending( $session_id );
	}

	/**
	 * Confirmed bookings.
	 *
	 * @param int $session_id Session id.
	 */
	private static function confirmed( int $session_id ): int {
		global $wpdb;
		$table = Db::table( 'class_bookings' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE session_id = %d AND status = 'confirmed'", $session_id ) );
	}

	/**
	 * Pending class orders started in the last half hour.
	 *
	 * @param int $session_id Session id.
	 */
	private static function recent_pending( int $session_id ): int {
		global $wpdb;
		$items  = Db::table( 'order_items' );
		$orders = Db::table( 'orders' );
		$since  = gmdate( 'Y-m-d H:i:s', time() - ( 30 * MINUTE_IN_SECONDS ) );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$items}` i INNER JOIN `{$orders}` o ON o.id = i.order_id
				WHERE i.item_id = %d AND i.item_type IN ('private_class','group_class') AND o.status = 'pending' AND o.created_at >= %s",
				$session_id,
				$since
			)
		);
	}

	/**
	 * Booking already stored for an order.
	 *
	 * @param int $order_id Order id.
	 * @return array<string, mixed>|null
	 */
	private static function booking_for_order( int $order_id ): ?array {
		global $wpdb;
		$table = Db::table( 'class_bookings' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE order_id = %d LIMIT 1", $order_id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}
}
