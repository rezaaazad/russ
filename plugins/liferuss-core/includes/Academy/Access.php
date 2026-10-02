<?php
/**
 * Who may watch a lesson, download a handout, or open a quiz.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Free courses grant the premium tier to a logged-in student.
 * A paid standard purchase grants video. Premium adds handouts, quizzes, and support.
 * A subscription covers courses flagged included_in_subscription, at the plan's tier.
 */
class Access {

	/**
	 * Effective tier for a user and course. Empty means no access.
	 *
	 * @param int                  $user_id User id.
	 * @param array<string, mixed> $course  Course row.
	 */
	public static function tier( int $user_id, array $course ): string {
		if ( 'published' !== ( $course['status'] ?? '' ) ) {
			return '';
		}
		if ( $user_id < 1 ) {
			return '';
		}
		$student = Students::ensure( $user_id );
		if ( ! $student ) {
			return '';
		}
		if ( ! empty( $course['is_free'] ) ) {
			self::enroll( (int) $student['id'], (int) $course['id'], 0, 'free', 'premium' );
			return 'premium';
		}
		$tier       = '';
		$enrollment = self::enrollment( (int) $student['id'], (int) $course['id'] );
		if ( $enrollment && 'active' === $enrollment['status'] ) {
			$tier = (string) $enrollment['tier'];
		}
		if ( ! empty( $course['included_in_subscription'] ) ) {
			$plan = Billing::covering( (int) $student['id'] );
			if ( $plan ) {
				$tier = self::higher( $tier, (string) $plan['tier'] );
			}
		}
		return $tier;
	}

	/**
	 * Video and text lessons.
	 *
	 * @param int                  $user_id User id.
	 * @param array<string, mixed> $course  Course.
	 * @param array<string, mixed> $lesson  Lesson.
	 */
	public static function can_watch( int $user_id, array $course, array $lesson ): bool {
		if ( ! empty( $lesson['is_preview'] ) && 'published' === ( $lesson['status'] ?? '' ) ) {
			return true;
		}
		return '' !== self::tier( $user_id, $course );
	}

	/**
	 * Handouts, quizzes, and the support ticket.
	 *
	 * @param int                  $user_id User id.
	 * @param array<string, mixed> $course  Course.
	 */
	public static function can_premium( int $user_id, array $course ): bool {
		return 'premium' === self::tier( $user_id, $course );
	}

	/**
	 * Create or refresh an enrollment. An existing higher tier is kept.
	 *
	 * @param int    $student_id Student id.
	 * @param int    $course_id  Course id.
	 * @param int    $order_id   Order id, or 0.
	 * @param string $source     purchase, subscription, free, bundle, or migration.
	 * @param string $tier       standard or premium.
	 */
	public static function enroll( int $student_id, int $course_id, int $order_id, string $source, string $tier ): void {
		if ( $student_id < 1 || $course_id < 1 ) {
			return;
		}
		$tier     = 'premium' === $tier ? 'premium' : 'standard';
		$existing = self::any_enrollment( $student_id, $course_id );
		if ( $existing ) {
			$next = self::higher( (string) $existing['tier'], $tier );
			$data = array(
				'status'     => 'active',
				'tier'       => '' !== $next ? $next : $tier,
				'source'     => $source,
				'revoked_at' => null,
			);
			if ( $order_id > 0 ) {
				$data['order_id'] = $order_id;
			}
			Db::update( 'enrollments', (int) $existing['id'], $data );
			return;
		}
		Db::insert(
			'enrollments',
			array(
				'student_id' => $student_id,
				'course_id'  => $course_id,
				'order_id'   => $order_id > 0 ? $order_id : null,
				'source'     => $source,
				'tier'       => $tier,
				'status'     => 'active',
				'granted_at' => Db::now(),
			)
		);
	}

	/**
	 * Enrollment row in any status.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	private static function any_enrollment( int $student_id, int $course_id ): ?array {
		global $wpdb;
		$table = Db::table( 'enrollments' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE student_id = %d AND course_id = %d LIMIT 1", $student_id, $course_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Active enrollment, if any.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	public static function enrollment( int $student_id, int $course_id ): ?array {
		global $wpdb;
		$table = Db::table( 'enrollments' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE student_id = %d AND course_id = %d AND status = 'active' LIMIT 1",
				$student_id,
				$course_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Premium outranks standard.
	 *
	 * @param string $left  Current tier.
	 * @param string $right Other tier.
	 */
	private static function higher( string $left, string $right ): string {
		if ( 'premium' === $left || 'premium' === $right ) {
			return 'premium';
		}
		if ( 'standard' === $left || 'standard' === $right ) {
			return 'standard';
		}
		return '';
	}
}
