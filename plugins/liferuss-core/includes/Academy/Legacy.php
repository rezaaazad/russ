<?php
/**
 * Move the Phase 2 Russian course into Academy without dropping progress.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Course\Store as CourseStore;
use LifeRuss\Core\Redirects\Store as Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * One free course, four modules, the old lessons and quizzes, then 301s.
 */
class Legacy {

	/**
	 * Run once after the category seed.
	 */
	public static function maybe(): void {
		if ( get_option( 'lr_academy_legacy' ) ) {
			return;
		}
		if ( ! Db::find_by( 'course_categories', 'slug', 'russian-from-zero' ) ) {
			return;
		}
		self::course();
		update_option( 'lr_academy_legacy', '1', false );
	}

	/**
	 * The in-store dialogue lesson keeps the slug shop and a clearer title.
	 */
	public static function relabel_shop(): void {
		if ( get_option( 'lr_academy_shop_label' ) ) {
			return;
		}
		$posts = get_posts(
			array(
				'name'           => 'shop',
				'post_type'      => 'lr_lesson',
				'post_status'    => 'any',
				'posts_per_page' => 20,
			)
		);
		foreach ( $posts as $post ) {
			if ( 'در فروشگاه' === $post->post_title ) {
				wp_update_post(
					array(
						'ID'         => $post->ID,
						'post_title' => 'گفتگو در فروشگاه',
					)
				);
			}
		}
		global $wpdb;
		$table  = Db::table( 'course_lessons' );
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET title = %s WHERE slug = %s AND title = %s",
				'گفتگو در فروشگاه',
				'shop',
				'در فروشگاه'
			)
		);
		update_option( 'lr_academy_shop_label', '1', false );
	}

	/**
	 * Build the course and the redirects.
	 */
	private static function course(): void {
		$category  = Db::find_by( 'course_categories', 'slug', 'russian-from-zero' );
		$existing  = Db::find_by( 'courses', 'slug', 'russian-language' );
		$course_id = $existing ? (int) $existing['id'] : Db::insert(
			'courses',
			array(
				'slug'                     => 'russian-language',
				'title'                    => 'زبان روسی از صفر تا پیشرفته',
				'description'              => 'دورهٔ رایگان زبان روسی از سطح A1 تا B2، با درس، تمرین و آزمون تعیین سطح. این همان دورهٔ قبلی لایف‌روس است و پیشرفت قبلی حفظ شده است.',
				'price'                    => 0,
				'currency'                 => 'IRT',
				'category_id'              => $category ? (int) $category['id'] : null,
				'level'                    => 'A1–B2',
				'duration'                 => 'چهار سطح',
				'status'                   => 'published',
				'is_free'                  => 1,
				'is_featured'              => 1,
				'tier'                     => 'premium',
				'included_in_subscription' => 0,
				'certificate_enabled'      => 1,
				'seo_title'                => 'آموزش زبان روسی از صفر',
				'seo_description'          => 'درس‌های رایگان روسی از A1 تا B2، آزمون تعیین سطح و گواهی.',
				'published_at'             => Db::now(),
				'sort_order'               => 1,
			)
		);
		if ( $course_id < 1 ) {
			return;
		}
		$map   = array();
		$posts = get_posts(
			array(
				'post_type'      => 'lr_course',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		$order = 1;
		foreach ( $posts as $post ) {
			$module = self::module( $course_id, $post, $order );
			++$order;
			$lessons      = get_posts(
				array(
					'post_type'      => 'lr_lesson',
					'post_parent'    => $post->ID,
					'post_status'    => 'any',
					'posts_per_page' => 50,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);
			$lesson_order = 1;
			foreach ( $lessons as $lesson ) {
				$made = self::lesson( $module, $lesson, $lesson_order );
				if ( $made > 0 ) {
					$map[ (int) $lesson->ID ] = $made;
				}
				++$lesson_order;
				self::redirect( '/russian-language/' . $post->post_name . '/' . $lesson->post_name . '/', '/academy/courses/russian-language/' . $lesson->post_name . '/' );
			}
			self::redirect( '/russian-language/' . $post->post_name . '/', '/academy/courses/russian-language/' );
		}
		self::redirect( '/russian-language/', '/academy/courses/russian-language/' );
		self::redirect( '/russian-language/placement/', '/academy/courses/russian-language/placement/' );
		self::redirect( '/russian-language/certificate/', '/account/academy/certificates/' );
		Redirects::upsert(
			array(
				'source_path' => '/russian-language/',
				'target_url'  => '/academy/courses/russian-language/',
				'status_code' => 301,
				'match_type'  => 'prefix',
				'origin'      => 'migration',
				'is_active'   => 1,
			)
		);
		self::progress( $map );
	}

	/**
	 * One module per old course.
	 *
	 * @param int      $course_id Course id.
	 * @param \WP_Post $post      Old course.
	 * @param int      $order     Sort.
	 */
	private static function module( int $course_id, \WP_Post $post, int $order ): int {
		$rows = Db::sorted( 'course_modules', 'course_id', $course_id );
		foreach ( $rows as $row ) {
			if ( (string) $row['title'] === $post->post_title ) {
				return (int) $row['id'];
			}
		}
		return Db::insert(
			'course_modules',
			array(
				'course_id'  => $course_id,
				'title'      => $post->post_title,
				'summary'    => wp_strip_all_tags( $post->post_content ),
				'sort_order' => $order,
				'status'     => 'publish' === $post->post_status ? 'published' : 'draft',
			)
		);
	}

	/**
	 * Copy one lesson, its video, and its quiz.
	 *
	 * @param int      $module_id Module id.
	 * @param \WP_Post $post      Old lesson.
	 * @param int      $order     Sort.
	 */
	private static function lesson( int $module_id, \WP_Post $post, int $order ): int {
		$found = Db::find_by( 'course_lessons', 'legacy_post_id', (string) $post->ID );
		if ( $found ) {
			return (int) $found['id'];
		}
		$placement = '1' === (string) get_post_meta( $post->ID, '_lr_placement', true );
		$title     = $post->post_title;
		if ( 'shop' === $post->post_name && 'در فروشگاه' === $title ) {
			$title = 'گفتگو در فروشگاه';
		}
		$id = Db::insert(
			'course_lessons',
			array(
				'module_id'      => $module_id,
				'slug'           => $post->post_name,
				'title'          => $title,
				'content'        => $post->post_content,
				'type'           => $placement ? 'quiz' : 'video',
				'sort_order'     => $order,
				'status'         => 'publish' === $post->post_status ? 'published' : 'draft',
				'legacy_post_id' => $post->ID,
			)
		);
		if ( $id < 1 ) {
			return 0;
		}
		$video = (string) get_post_meta( $post->ID, '_lr_video', true );
		if ( '' !== $video ) {
			Db::insert(
				'lesson_videos',
				array(
					'lesson_id'   => $id,
					'provider'    => 'youtube',
					'external_id' => self::youtube_id( $video ),
					'status'      => 'published',
				)
			);
		}
		$questions = CourseStore::questions( $post->ID );
		if ( $questions ) {
			self::quiz( $placement ? 'course' : 'lesson', $placement ? (int) Db::find( 'course_modules', $module_id )['course_id'] : $id, $post->post_title, $questions );
		}
		return $id;
	}

	/**
	 * Store questions on a quiz.
	 *
	 * @param string                           $scope     Scope.
	 * @param int                              $scope_id  Scope id.
	 * @param string                           $title     Title.
	 * @param array<int, array<string, mixed>> $questions Questions.
	 */
	private static function quiz( string $scope, int $scope_id, string $title, array $questions ): void {
		$quiz  = Db::insert(
			'quizzes',
			array(
				'scope_type'   => $scope,
				'scope_id'     => $scope_id,
				'title'        => $title,
				'pass_percent' => 70,
				'status'       => 'published',
			)
		);
		$order = 1;
		foreach ( $questions as $question ) {
			$type = (string) ( $question['type'] ?? 'choice' );
			Db::insert(
				'quiz_questions',
				array(
					'quiz_id'    => $quiz,
					'type'       => in_array( $type, array( 'choice', 'match', 'fill' ), true ) ? $type : 'choice',
					'prompt'     => (string) ( $question['prompt'] ?? '' ),
					'payload'    => wp_json_encode( $question ),
					'sort_order' => $order,
				)
			);
			++$order;
		}
	}

	/**
	 * Copy completion rows onto the new lessons.
	 *
	 * @param array<int, int> $map Old lesson id to new lesson id.
	 */
	private static function progress( array $map ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lesson_progress';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			return;
		}
		$rows = $wpdb->get_results( "SELECT user_id, lesson_id, completed FROM `{$table}` WHERE completed = 1", ARRAY_A );
		foreach ( (array) $rows as $row ) {
			$lesson_id = $map[ (int) $row['lesson_id'] ] ?? 0;
			if ( $lesson_id < 1 ) {
				continue;
			}
			$lesson  = Db::find( 'course_lessons', $lesson_id );
			$module  = $lesson ? Db::find( 'course_modules', (int) $lesson['module_id'] ) : null;
			$student = Students::ensure( (int) $row['user_id'] );
			if ( ! $module || ! $student ) {
				continue;
			}
			Access::enroll( (int) $student['id'], (int) $module['course_id'], 0, 'migration', 'premium' );
			Progress::complete( (int) $student['id'], (int) $module['course_id'], $lesson_id );
		}
	}

	/**
	 * Exact redirect into the redirect manager.
	 *
	 * @param string $from Source path.
	 * @param string $to   Target path.
	 */
	private static function redirect( string $from, string $to ): void {
		Redirects::upsert(
			array(
				'source_path' => $from,
				'target_url'  => $to,
				'status_code' => 301,
				'match_type'  => 'exact',
				'origin'      => 'migration',
				'is_active'   => 1,
			)
		);
	}

	/**
	 * YouTube id from a pasted URL.
	 *
	 * @param string $url URL.
	 */
	private static function youtube_id( string $url ): string {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( str_contains( $host, 'youtu.be' ) ) {
			return sanitize_text_field( trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) );
		}
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$id = isset( $query['v'] ) ? (string) $query['v'] : '';
		return preg_match( '/^[A-Za-z0-9_-]{6,}$/', $id ) ? $id : 'video';
	}
}
