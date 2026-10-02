<?php
/**
 * Course actions: progress, lesson quizzes, and the placement test.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POST handling for the language screens.
 */
class Front {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'template_redirect', array( self::class, 'guard' ), 0 );
		add_action( 'template_redirect', array( self::class, 'handle' ), 1 );
	}

	/**
	 * A lesson URL must name its parent course.
	 */
	public static function guard(): void {
		if ( ! is_singular( 'lr_lesson' ) ) {
			return;
		}
		$slug = (string) get_query_var( 'lr_course_slug' );
		if ( '' === $slug ) {
			return;
		}
		$parent = get_post( (int) get_queried_object()->post_parent );
		$name   = ( $parent instanceof \WP_Post && 'lr_course' === $parent->post_type ) ? $parent->post_name : '';
		if ( $name === $slug ) {
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Save progress and scores for a logged-in user.
	 */
	public static function handle(): void {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'POST' !== $method ) {
			return;
		}
		if ( ! isset( $_POST['lr_course_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_course_nonce'] ) ), 'lr_course' ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		$action = isset( $_POST['lr_course_action'] ) ? sanitize_key( wp_unslash( $_POST['lr_course_action'] ) ) : '';
		if ( 'complete' === $action ) {
			self::complete();
		} elseif ( 'quiz' === $action ) {
			self::quiz();
		} elseif ( 'placement' === $action ) {
			self::placement();
		}
	}

	/**
	 * Mark the current lesson done.
	 */
	private static function complete(): void {
		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $lesson_id && 'lr_lesson' === get_post_type( $lesson_id ) ) {
			Store::complete( get_current_user_id(), $lesson_id );
		}
		self::back( $lesson_id );
	}

	/**
	 * Grade a lesson quiz.
	 */
	private static function quiz(): void {
		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $lesson_id || 'lr_lesson' !== get_post_type( $lesson_id ) ) {
			self::back( 0 );
		}
		$result = Store::grade( Store::questions( $lesson_id ), self::choices(), self::matches(), self::fills() );
		Store::attempt( get_current_user_id(), $lesson_id, 'lesson', $result['score'], $result['max'] );
		self::back( $lesson_id, $result['score'], $result['max'] );
	}

	/**
	 * Grade the placement test and send the learner to the result.
	 */
	private static function placement(): void {
		$result = Store::grade( Store::placement_questions(), self::choices(), self::matches(), self::fills() );
		Store::attempt( get_current_user_id(), 0, 'placement', $result['score'], $result['max'] );
		$url = function_exists( 'liferuss_url' ) ? liferuss_url( '/russian-language/placement/' ) : home_url( '/russian-language/placement/' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'score' => $result['score'],
					'max'   => $result['max'],
				),
				$url
			)
		);
		exit;
	}

	/**
	 * Selected choice indexes.
	 *
	 * @return array<int, int>
	 */
	private static function choices(): array {
		$raw = isset( $_POST['choice'] ) ? wp_unslash( $_POST['choice'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $index => $value ) {
				$out[ absint( $index ) ] = absint( $value );
			}
		}
		return $out;
	}

	/**
	 * Selected match values.
	 *
	 * @return array<int, array<int, string>>
	 */
	private static function matches(): array {
		$raw = isset( $_POST['match'] ) ? wp_unslash( $_POST['match'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array();
		if ( ! is_array( $raw ) ) {
			return $out;
		}
		foreach ( $raw as $index => $pairs ) {
			if ( ! is_array( $pairs ) ) {
				continue;
			}
			foreach ( $pairs as $pair => $value ) {
				$out[ absint( $index ) ][ absint( $pair ) ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}

	/**
	 * Fill-in answers.
	 *
	 * @return array<int, string>
	 */
	private static function fills(): array {
		$raw = isset( $_POST['fill'] ) ? wp_unslash( $_POST['fill'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $index => $value ) {
				$out[ absint( $index ) ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}

	/**
	 * Return to the lesson.
	 *
	 * @param int $lesson_id Lesson to return to.
	 * @param int $score     Score, or -1 when there is no score.
	 * @param int $max       Max.
	 */
	private static function back( int $lesson_id, int $score = -1, int $max = 0 ): void {
		$url = $lesson_id ? get_permalink( $lesson_id ) : '';
		if ( ! $url ) {
			$url = function_exists( 'liferuss_url' ) ? liferuss_url( '/russian-language/' ) : home_url( '/russian-language/' );
		}
		if ( $score >= 0 ) {
			$url = add_query_arg(
				array(
					'score' => $score,
					'max'   => $max,
				),
				$url
			);
		}
		wp_safe_redirect( $url );
		exit;
	}
}
