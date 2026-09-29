<?php
/**
 * Lesson progress, quiz scoring, and certificate eligibility.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names and integer id lists are internal.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Data for the Russian course.
 */
class Store {

	/**
	 * Saved quiz questions.
	 *
	 * @param int $post_id Lesson id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function questions( int $post_id ): array {
		$raw = json_decode( (string) get_post_meta( $post_id, '_lr_quiz', true ), true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $row ) {
			if ( is_array( $row ) && isset( $row['type'], $row['prompt'] ) ) {
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Replace the quiz on a lesson.
	 *
	 * @param int                              $post_id Lesson id.
	 * @param array<int, array<string, mixed>> $rows Questions.
	 */
	public static function save_questions( int $post_id, array $rows ): void {
		update_post_meta( $post_id, '_lr_quiz', wp_json_encode( array_values( $rows ), JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Turn builder text into one question.
	 *
	 * @param string $type    choice, match, or fill.
	 * @param string $prompt  Prompt.
	 * @param string $payload Builder text.
	 * @return array<string, mixed>|null
	 */
	public static function parse( string $type, string $prompt, string $payload ): ?array {
		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return null;
		}
		if ( 'match' === $type ) {
			$pairs = array();
			foreach ( preg_split( '/\r\n|\r|\n/', $payload ) as $line ) {
				$line = trim( (string) $line );
				if ( ! str_contains( $line, '|' ) ) {
					continue;
				}
				list( $left, $right ) = array_map( 'trim', explode( '|', $line, 2 ) );
				if ( '' !== $left && '' !== $right ) {
					$pairs[] = array(
						'left'  => $left,
						'right' => $right,
					);
				}
			}
			return $pairs ? array(
				'type'   => 'match',
				'prompt' => $prompt,
				'pairs'  => $pairs,
			) : null;
		}
		if ( 'fill' === $type ) {
			$accept = trim( $payload );
			return '' === $accept ? null : array(
				'type'   => 'fill',
				'prompt' => $prompt,
				'accept' => $accept,
			);
		}
		$choices = array();
		$answer  = 0;
		foreach ( preg_split( '/\r\n|\r|\n/', $payload ) as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}
			if ( str_starts_with( $line, '*' ) ) {
				$answer = count( $choices );
				$line   = trim( substr( $line, 1 ) );
			}
			if ( '' !== $line ) {
				$choices[] = $line;
			}
		}
		if ( count( $choices ) < 2 ) {
			return null;
		}
		return array(
			'type'    => 'choice',
			'prompt'  => $prompt,
			'choices' => $choices,
			'answer'  => $answer,
		);
	}

	/**
	 * Score a submitted quiz. Match and fill values are already sanitized.
	 *
	 * @param array<int, array<string, mixed>> $questions Questions.
	 * @param array<int, int>                  $choice    Selected indexes.
	 * @param array<int, array<int, string>>   $matched   Selected right-hand values.
	 * @param array<int, string>               $fill      Typed answers.
	 * @return array{score: int, max: int}
	 */
	public static function grade( array $questions, array $choice, array $matched, array $fill ): array {
		$score = 0;
		$max   = 0;
		foreach ( $questions as $index => $question ) {
			$type = (string) ( $question['type'] ?? '' );
			if ( 'match' === $type && ! empty( $question['pairs'] ) && is_array( $question['pairs'] ) ) {
				foreach ( $question['pairs'] as $pair_index => $pair ) {
					++$max;
					$given = $matched[ $index ][ $pair_index ] ?? '';
					if ( self::same( (string) $given, (string) ( $pair['right'] ?? '' ) ) ) {
						++$score;
					}
				}
				continue;
			}
			++$max;
			if ( 'fill' === $type && self::same( (string) ( $fill[ $index ] ?? '' ), (string) ( $question['accept'] ?? '' ) ) ) {
				++$score;
			} elseif ( 'choice' === $type && (int) ( $choice[ $index ] ?? -1 ) === (int) ( $question['answer'] ?? 0 ) ) {
				++$score;
			}
		}
		return array(
			'score' => $score,
			'max'   => $max,
		);
	}

	/**
	 * Published lessons of a course, without placement-only rows.
	 *
	 * @param int $course_id Course id.
	 * @return \WP_Post[]
	 */
	public static function lessons( int $course_id ): array {
		$posts = get_posts(
			array(
				'post_type'      => 'lr_lesson',
				'post_parent'    => $course_id,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		$out   = array();
		foreach ( $posts as $post ) {
			if ( '1' !== (string) get_post_meta( $post->ID, '_lr_placement', true ) ) {
				$out[] = $post;
			}
		}
		return $out;
	}

	/**
	 * Published courses in level order.
	 *
	 * @return \WP_Post[]
	 */
	public static function courses(): array {
		$posts = get_posts(
			array(
				'post_type'      => 'lr_course',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Questions gathered from placement lessons.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function placement_questions(): array {
		$ids = get_posts(
			array(
				'post_type'      => 'lr_lesson',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_key'       => '_lr_placement', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		$out = array();
		foreach ( (array) $ids as $id ) {
			$out = array_merge( $out, self::questions( (int) $id ) );
		}
		return $out;
	}

	/**
	 * Mark a lesson finished.
	 *
	 * @param int $user_id   User id.
	 * @param int $lesson_id Lesson id.
	 */
	public static function complete( int $user_id, int $lesson_id ): void {
		if ( $user_id < 1 || $lesson_id < 1 ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lesson_progress';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (user_id, lesson_id, completed, updated_at) VALUES (%d, %d, 1, %s)
				ON DUPLICATE KEY UPDATE completed = 1, updated_at = VALUES(updated_at)",
				$user_id,
				$lesson_id,
				$now
			)
		);
	}

	/**
	 * Completed lesson ids for a user.
	 *
	 * @param int $user_id User id.
	 * @return int[]
	 */
	public static function completed_ids( int $user_id ): array {
		if ( $user_id < 1 ) {
			return array();
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lesson_progress';
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT lesson_id FROM `{$table}` WHERE user_id = %d AND completed = 1", $user_id ) );
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Store a score.
	 *
	 * @param int    $user_id   User id.
	 * @param int    $lesson_id Lesson id, or 0 for placement.
	 * @param string $kind      lesson or placement.
	 * @param int    $score     Points.
	 * @param int    $max       Possible points.
	 */
	public static function attempt( int $user_id, int $lesson_id, string $kind, int $score, int $max ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'lr_quiz_attempts',
			array(
				'user_id'    => $user_id,
				'lesson_id'  => $lesson_id,
				'kind'       => 'placement' === $kind ? 'placement' : 'lesson',
				'score'      => $score,
				'max_score'  => $max,
				'detail'     => '',
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s' )
		);
		if ( 'lesson' === $kind && $max > 0 && ( $score / $max ) >= 0.7 ) {
			self::complete( $user_id, $lesson_id );
		}
	}

	/**
	 * Latest attempt for a lesson or the placement test.
	 *
	 * @param int    $user_id   User id.
	 * @param int    $lesson_id Lesson id.
	 * @param string $kind      lesson or placement.
	 * @return array<string, mixed>|null
	 */
	public static function latest( int $user_id, int $lesson_id, string $kind ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_quiz_attempts';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT score, max_score, created_at FROM `{$table}` WHERE user_id = %d AND lesson_id = %d AND kind = %s ORDER BY id DESC LIMIT 1",
				$user_id,
				$lesson_id,
				$kind
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Courses whose regular lessons are all complete.
	 *
	 * @param int $user_id User id.
	 * @return array<int, array{course: \WP_Post, level: string, at: string}>
	 */
	public static function certificates( int $user_id ): array {
		$done = self::completed_ids( $user_id );
		$out  = array();
		foreach ( self::courses() as $course ) {
			$lessons = self::lessons( (int) $course->ID );
			if ( ! $lessons ) {
				continue;
			}
			$ready = true;
			foreach ( $lessons as $lesson ) {
				if ( ! in_array( (int) $lesson->ID, $done, true ) ) {
					$ready = false;
					break;
				}
			}
			if ( $ready ) {
				$ids = array();
				foreach ( $lessons as $lesson ) {
					$ids[] = (int) $lesson->ID;
				}
				global $wpdb;
				$table = $wpdb->prefix . 'lr_lesson_progress';
				$at    = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(updated_at) FROM `' . $table . '` WHERE user_id = %d AND lesson_id IN (' . implode( ',', $ids ) . ')', $user_id ) );
				$out[] = array(
					'course' => $course,
					'level'  => self::level_name( (int) $course->ID ),
					'at'     => $at,
				);
			}
		}
		return $out;
	}

	/**
	 * Level term name, or the course title.
	 *
	 * @param int $post_id Course or lesson id.
	 */
	public static function level_name( int $post_id ): string {
		$terms = get_the_terms( $post_id, 'lr_level' );
		if ( is_array( $terms ) && isset( $terms[0]->name ) ) {
			return (string) $terms[0]->name;
		}
		return '';
	}

	/**
	 * Suggested level from a placement percentage.
	 *
	 * @param int $score Points.
	 * @param int $max   Possible points.
	 */
	public static function recommend( int $score, int $max ): string {
		$ratio = $max > 0 ? $score / $max : 0;
		if ( $ratio < 0.4 ) {
			return 'A1';
		}
		if ( $ratio < 0.6 ) {
			return 'A2';
		}
		if ( $ratio < 0.8 ) {
			return 'B1';
		}
		return 'B2';
	}

	/**
	 * Safe embed for YouTube and Aparat. Anything else is omitted.
	 *
	 * @param string $url Pasted URL.
	 */
	public static function video_embed( string $url ): string {
		$url  = esc_url_raw( $url );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		$id   = '';
		$src  = '';
		if ( 'youtu.be' === $host ) {
			$id = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		} elseif ( 'youtube.com' === $host || 'm.youtube.com' === $host ) {
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$id = isset( $query['v'] ) ? (string) $query['v'] : '';
			if ( '' === $id && str_contains( (string) wp_parse_url( $url, PHP_URL_PATH ), '/embed/' ) ) {
				$id = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			}
		} elseif ( 'aparat.com' === $host ) {
			$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
			$bits = explode( '/', $path );
			$id   = (string) end( $bits );
			if ( preg_match( '/^[A-Za-z0-9_-]+$/', $id ) ) {
				$src = 'https://www.aparat.com/video/video/embed/videohash/' . rawurlencode( $id ) . '/vt/frame';
			}
			$id = '';
		}
		if ( '' === $src && preg_match( '/^[A-Za-z0-9_-]{6,}$/', $id ) ) {
			$src = 'https://www.youtube.com/embed/' . rawurlencode( $id );
		}
		if ( '' === $src ) {
			return '';
		}
		return '<iframe class="lr-embed" src="' . esc_url( $src ) . '" title="ویدیو" loading="lazy" allowfullscreen></iframe>';
	}

	/**
	 * Case-folded comparison for short answers.
	 *
	 * @param string $given    Submitted text.
	 * @param string $expected Expected text.
	 */
	private static function same( string $given, string $expected ): bool {
		$given    = trim( mb_strtolower( $given ) );
		$expected = trim( mb_strtolower( $expected ) );
		return '' !== $expected && $given === $expected;
	}
}
