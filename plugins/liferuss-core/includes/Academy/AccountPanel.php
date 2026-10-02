<?php
/**
 * Student panel data for /account/academy/.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Courses, resume, certificates, subscription, bookings, and orders.
 */
class AccountPanel {

	/**
	 * Everything the panel renders.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>
	 */
	public static function data( int $user_id ): array {
		$student = Students::ensure( $user_id );
		if ( ! $student ) {
			return array( 'courses' => array() );
		}
		$sid = (int) $student['id'];
		return array(
			'student'      => $student,
			'courses'      => self::courses( $sid ),
			'certificates' => Certificates::for_student( $sid ),
			'subscription' => Billing::covering( $sid ),
			'bookings'     => self::bookings( $sid ),
			'orders'       => self::orders( $sid ),
		);
	}

	/**
	 * Enrolled courses with percent and the next lesson.
	 *
	 * @param int $student_id Student id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function courses( int $student_id ): array {
		global $wpdb;
		$table = Db::table( 'enrollments' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE student_id = %d AND status = 'active' ORDER BY id DESC", $student_id ), ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$course = Db::find( 'courses', (int) $row['course_id'] );
			if ( ! $course || 'published' !== $course['status'] ) {
				continue;
			}
			$next  = Progress::next_lesson( $student_id, (int) $course['id'] );
			$out[] = array(
				'course'  => $course,
				'percent' => Progress::percent( $student_id, (int) $course['id'] ),
				'next'    => $next,
			);
		}
		return $out;
	}

	/**
	 * Class bookings.
	 *
	 * @param int $student_id Student id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function bookings( int $student_id ): array {
		global $wpdb;
		$bookings = Db::table( 'class_bookings' );
		$sessions = Db::table( 'class_sessions' );
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, s.title, s.starts_at, s.meeting_url, s.kind FROM `{$bookings}` b INNER JOIN `{$sessions}` s ON s.id = b.session_id WHERE b.student_id = %d ORDER BY s.starts_at DESC",
				$student_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Orders and their items.
	 *
	 * @param int $student_id Student id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function orders( int $student_id ): array {
		global $wpdb;
		$table = Db::table( 'orders' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE student_id = %d ORDER BY id DESC LIMIT 30", $student_id ), ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$row['items'] = Db::where_id( 'order_items', 'order_id', (int) $row['id'] );
			$row['net']   = Orders::net( (int) $row['id'] );
			$out[]        = $row;
		}
		return $out;
	}
}
