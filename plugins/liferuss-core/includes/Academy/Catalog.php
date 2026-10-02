<?php
/**
 * Published catalogue reads and the category seed.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Courses stay unpublished until an editor publishes them. Categories are seeded.
 */
class Catalog {

	/**
	 * Insert the nine categories once.
	 */
	public static function seed_categories(): void {
		if ( get_option( 'lr_academy_categories' ) ) {
			return;
		}
		$order = 1;
		foreach ( self::category_seed() as $slug => $title ) {
			if ( ! Db::find_by( 'course_categories', 'slug', $slug ) ) {
				Db::insert(
					'course_categories',
					array(
						'slug'       => $slug,
						'title'      => $title,
						'sort_order' => $order,
						'status'     => 'published',
					)
				);
			}
			++$order;
		}
		update_option( 'lr_academy_categories', '1', false );
	}

	/**
	 * Category titles. Slugs are stable.
	 *
	 * @return array<string, string>
	 */
	public static function category_seed(): array {
		return array(
			'russian-from-zero' => 'زبان روسی از صفر تا پیشرفته',
			'medical-russian'   => 'روسی پزشکی و دندانپزشکی',
			'newcomer-russian'  => 'روسی مخصوص دانشجویان تازه‌وارد',
			'padfak-prep'       => 'آمادگی پادفک',
			'life-in-russia'    => 'زندگی در روسیه',
			'university-entry'  => 'ورود و ثبت‌نام دانشگاه',
			'banking-russia'    => 'امور بانکی و مالی در روسیه',
			'migration-docs'    => 'مهاجرت و مدارک',
			'medical-specialty' => 'دوره‌های تخصصی دانشجویان پزشکی و دندانپزشکی',
		);
	}

	/**
	 * Published course by slug.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public static function course( string $slug ): ?array {
		$row = Db::find_by( 'courses', 'slug', $slug );
		if ( ! $row || 'published' !== $row['status'] ) {
			return null;
		}
		return $row;
	}

	/**
	 * Published courses, optionally one category.
	 *
	 * @param int $category_id Category id, or 0.
	 * @return array<int, array<string, mixed>>
	 */
	public static function courses( int $category_id = 0 ): array {
		global $wpdb;
		$table = Db::table( 'courses' );
		if ( $category_id > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE status = 'published' AND category_id = %d ORDER BY sort_order ASC, id ASC",
					$category_id
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE status = 'published' ORDER BY sort_order ASC, id ASC", ARRAY_A );
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Featured published courses.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function featured(): array {
		global $wpdb;
		$table = Db::table( 'courses' );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE status = 'published' AND is_featured = 1 ORDER BY sort_order ASC, id ASC LIMIT 6", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Published modules of a course.
	 *
	 * @param int $course_id Course id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function modules( int $course_id ): array {
		$rows = Db::sorted( 'course_modules', 'course_id', $course_id );
		return array_values(
			array_filter(
				$rows,
				static function ( $row ) {
					return 'published' === $row['status'];
				}
			)
		);
	}

	/**
	 * Lessons of a course, through its modules.
	 *
	 * @param int  $course_id Course id.
	 * @param bool $published Published only.
	 * @return array<int, array<string, mixed>>
	 */
	public static function lessons( int $course_id, bool $published = false ): array {
		$out     = array();
		$modules = $published ? self::modules( $course_id ) : Db::sorted( 'course_modules', 'course_id', $course_id );
		foreach ( $modules as $module ) {
			foreach ( Db::sorted( 'course_lessons', 'module_id', (int) $module['id'] ) as $lesson ) {
				if ( $published && 'published' !== $lesson['status'] ) {
					continue;
				}
				$lesson['module_title'] = (string) $module['title'];
				$out[]                  = $lesson;
			}
		}
		return $out;
	}

	/**
	 * One lesson inside a course, matched by slug.
	 *
	 * @param int    $course_id Course id.
	 * @param string $slug      Lesson slug.
	 * @return array<string, mixed>|null
	 */
	public static function lesson( int $course_id, string $slug ): ?array {
		foreach ( self::lessons( $course_id, false ) as $lesson ) {
			if ( $slug === (string) $lesson['slug'] && 'published' === $lesson['status'] ) {
				return $lesson;
			}
		}
		return null;
	}

	/**
	 * Sale price in Toman.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	public static function price( array $course ): int {
		if ( ! empty( $course['is_free'] ) ) {
			return 0;
		}
		if ( null !== $course['discount_price'] && '' !== (string) $course['discount_price'] && (int) $course['discount_price'] > 0 ) {
			return (int) $course['discount_price'];
		}
		return (int) $course['price'];
	}

	/**
	 * Draft course with one module, one lesson, and one quiz. Never published.
	 *
	 * @param int $user_id Acting user.
	 */
	public static function demo( int $user_id ): int {
		unset( $user_id );
		$slug = 'demo-' . strtolower( wp_generate_password( 6, false, false ) );
		$id   = Db::insert(
			'courses',
			array(
				'slug'        => $slug,
				'title'       => 'دورهٔ آزمایشی آکادمی',
				'description' => 'این دوره فقط برای آزمون پیشخوان است و منتشر نشده است.',
				'price'       => 1000,
				'currency'    => 'IRT',
				'status'      => 'draft',
				'tier'        => 'premium',
				'level'       => 'آزمایشی',
			)
		);
		if ( $id < 1 ) {
			return 0;
		}
		$module = Db::insert(
			'course_modules',
			array(
				'course_id' => $id,
				'title'     => 'بخش آزمایشی',
				'status'    => 'draft',
			)
		);
		$lesson = Db::insert(
			'course_lessons',
			array(
				'module_id' => $module,
				'slug'      => 'demo-lesson',
				'title'     => 'درس آزمایشی',
				'content'   => 'متن آزمایشی درس.',
				'type'      => 'video',
				'status'    => 'draft',
			)
		);
		if ( $lesson > 0 ) {
			Db::insert(
				'lesson_videos',
				array(
					'lesson_id'   => $lesson,
					'provider'    => 'arvan_vod',
					'external_id' => 'demo',
					'status'      => 'draft',
				)
			);
		}
		$quiz = Db::insert(
			'quizzes',
			array(
				'scope_type' => 'lesson',
				'scope_id'   => $lesson,
				'title'      => 'آزمون آزمایشی',
				'status'     => 'draft',
			)
		);
		if ( $quiz > 0 ) {
			Db::insert(
				'quiz_questions',
				array(
					'quiz_id' => $quiz,
					'type'    => 'choice',
					'prompt'  => 'این سؤال آزمایشی است؟',
					'payload' => wp_json_encode(
						array(
							'choices' => array( 'بله', 'خیر' ),
							'answer'  => 0,
						)
					),
				)
			);
		}
		return $id;
	}
}
