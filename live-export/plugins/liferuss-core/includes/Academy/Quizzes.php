<?php
/**
 * Polymorphic quizzes: a course, a module, or a lesson.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Course\Store as CourseStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Scoring reuses the Phase 2 grader so choice, match, and fill stay the same.
 */
class Quizzes {

	/**
	 * Published quizzes for a scope.
	 *
	 * @param string $scope course, module, or lesson.
	 * @param int    $id    Scope id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_scope( string $scope, int $id ): array {
		global $wpdb;
		$table = Db::table( 'quizzes' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE scope_type = %s AND scope_id = %d AND status = 'published' ORDER BY sort_order ASC, id ASC",
				$scope,
				$id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Questions for a quiz.
	 *
	 * @param int $quiz_id Quiz id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function questions( int $quiz_id ): array {
		$rows = Db::sorted( 'quiz_questions', 'quiz_id', $quiz_id );
		$out  = array();
		foreach ( $rows as $row ) {
			$payload = json_decode( (string) $row['payload'], true );
			$payload = is_array( $payload ) ? $payload : array();
			$out[]   = array_merge(
				array(
					'type'   => (string) $row['type'],
					'prompt' => (string) $row['prompt'],
				),
				$payload
			);
		}
		return $out;
	}

	/**
	 * Grade a submission and store the result.
	 *
	 * @param int                            $quiz_id   Quiz id.
	 * @param int                            $student_id Student id.
	 * @param array<int, int>                $choice    Choice indexes.
	 * @param array<int, array<int, string>> $matched   Match answers.
	 * @param array<int, string>             $fill      Fill answers.
	 * @return array{score: int, max: int, passed: bool}
	 */
	public static function submit( int $quiz_id, int $student_id, array $choice, array $matched, array $fill ): array {
		$quiz      = Db::find( 'quizzes', $quiz_id );
		$questions = self::questions( $quiz_id );
		$graded    = CourseStore::grade( $questions, $choice, $matched, $fill );
		$max       = (int) $graded['max'];
		$score     = (int) $graded['score'];
		$need      = $quiz ? (int) $quiz['pass_percent'] : 70;
		$passed    = $max > 0 && ( ( $score * 100 ) / $max ) >= $need;
		Db::insert(
			'quiz_results',
			array(
				'quiz_id'    => $quiz_id,
				'student_id' => $student_id,
				'score'      => $score,
				'max_score'  => $max,
				'passed'     => $passed ? 1 : 0,
			)
		);
		if ( $passed && $quiz && 'lesson' === $quiz['scope_type'] ) {
			$lesson = Db::find( 'course_lessons', (int) $quiz['scope_id'] );
			$module = $lesson ? Db::find( 'course_modules', (int) $lesson['module_id'] ) : null;
			if ( $module ) {
				Progress::complete( $student_id, (int) $module['course_id'], (int) $lesson['id'] );
			}
		}
		return array(
			'score'  => $score,
			'max'    => $max,
			'passed' => $passed,
		);
	}

	/**
	 * Latest result for a student.
	 *
	 * @param int $quiz_id    Quiz id.
	 * @param int $student_id Student id.
	 * @return array<string, mixed>|null
	 */
	public static function latest( int $quiz_id, int $student_id ): ?array {
		global $wpdb;
		$table = Db::table( 'quiz_results' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE quiz_id = %d AND student_id = %d ORDER BY id DESC LIMIT 1",
				$quiz_id,
				$student_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}
}
