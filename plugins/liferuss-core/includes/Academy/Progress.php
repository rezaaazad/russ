<?php
/**
 * Lesson completion and the resume point.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Progress is stored per lesson and summarized as a percent of published lessons.
 */
class Progress {

	/**
	 * Mark a lesson complete and refresh the course percent.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @param int $lesson_id  Lesson id.
	 */
	public static function complete( int $student_id, int $course_id, int $lesson_id ): void {
		self::touch( $student_id, $course_id, $lesson_id, 1, 0 );
	}

	/**
	 * Save a video position without completing the lesson.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @param int $lesson_id  Lesson id.
	 * @param int $seconds    Playback position.
	 */
	public static function position( int $student_id, int $course_id, int $lesson_id, int $seconds ): void {
		self::touch( $student_id, $course_id, $lesson_id, 0, $seconds );
	}

	/**
	 * Percent of published lessons completed.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 */
	public static function percent( int $student_id, int $course_id ): int {
		$lessons = Catalog::lessons( $course_id, true );
		if ( ! $lessons ) {
			return 0;
		}
		$done = self::completed_ids( $student_id, $course_id );
		$hit  = 0;
		foreach ( $lessons as $lesson ) {
			if ( in_array( (int) $lesson['id'], $done, true ) ) {
				++$hit;
			}
		}
		return (int) floor( ( $hit * 100 ) / count( $lessons ) );
	}

	/**
	 * First published lesson that is not complete.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	public static function next_lesson( int $student_id, int $course_id ): ?array {
		$done = self::completed_ids( $student_id, $course_id );
		foreach ( Catalog::lessons( $course_id, true ) as $lesson ) {
			if ( ! in_array( (int) $lesson['id'], $done, true ) ) {
				return $lesson;
			}
		}
		$all = Catalog::lessons( $course_id, true );
		return $all ? $all[0] : null;
	}

	/**
	 * Completed lesson ids.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return int[]
	 */
	public static function completed_ids( int $student_id, int $course_id ): array {
		global $wpdb;
		$table = Db::table( 'course_progress' );
		$ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT lesson_id FROM `{$table}` WHERE student_id = %d AND course_id = %d AND completed = 1",
				$student_id,
				$course_id
			)
		);
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Insert or update one lesson row, then store the course percent on it.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @param int $lesson_id  Lesson id.
	 * @param int $completed  1 to complete.
	 * @param int $seconds    Position.
	 */
	private static function touch( int $student_id, int $course_id, int $lesson_id, int $completed, int $seconds ): void {
		global $wpdb;
		$table = Db::table( 'course_progress' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE student_id = %d AND lesson_id = %d", $student_id, $lesson_id ),
			ARRAY_A
		);
		$flag  = $completed || ( $row && ! empty( $row['completed'] ) ) ? 1 : 0;
		if ( $row ) {
			Db::update(
				'course_progress',
				(int) $row['id'],
				array(
					'completed'     => $flag,
					'last_position' => $seconds > 0 ? $seconds : (int) $row['last_position'],
				)
			);
		} else {
			Db::insert(
				'course_progress',
				array(
					'student_id'    => $student_id,
					'course_id'     => $course_id,
					'lesson_id'     => $lesson_id,
					'completed'     => $flag,
					'last_position' => max( 0, $seconds ),
				)
			);
		}
		$percent = self::percent( $student_id, $course_id );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET progress_percent = %d WHERE student_id = %d AND course_id = %d",
				$percent,
				$student_id,
				$course_id
			)
		);
		$course = Db::find( 'courses', $course_id );
		if ( $course && ! empty( $course['certificate_enabled'] ) && $percent >= 100 ) {
			Certificates::issue( $student_id, $course_id );
		}
	}
}
