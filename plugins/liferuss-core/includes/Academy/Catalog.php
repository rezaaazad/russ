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

	/**
	 * One-line Persian copy for each seeded category.
	 *
	 * @return array<string, string>
	 */
	public static function category_lines(): array {
		return array(
			'russian-from-zero' => 'از الفبا تا مکالمه، با درس‌های کوتاه.',
			'medical-russian'   => 'واژگان درمانگاه، بیمارستان و دندانپزشکی.',
			'newcomer-russian'  => 'زبان روزمره برای هفته‌های اول ورود.',
			'padfak-prep'       => 'آمادگی آزمون و کلاس‌های پادفک.',
			'life-in-russia'    => 'مسکن، حمل‌ونقل و زندگی دانشجویی.',
			'university-entry'  => 'پذیرش، ثبت‌نام و شروع ترم.',
			'banking-russia'    => 'حساب، کارت و انتقال پول در روسیه.',
			'migration-docs'    => 'ویزا، ثبت اقامت و مدارک ضروری.',
			'medical-specialty' => 'مسیر تخصص برای پزشکی و دندانپزشکی.',
		);
	}

	/**
	 * Fill empty category descriptions once. The column already exists.
	 */
	public static function fill_descriptions(): void {
		if ( get_option( 'lr_academy_category_lines' ) ) {
			return;
		}
		foreach ( self::category_lines() as $slug => $line ) {
			$row = Db::find_by( 'course_categories', 'slug', $slug );
			if ( $row && '' === trim( (string) $row['description'] ) ) {
				Db::update( 'course_categories', (int) $row['id'], array( 'description' => $line ) );
			}
		}
		update_option( 'lr_academy_category_lines', '1', false );
	}

	/**
	 * Landing questions. The same list is the FAQ schema.
	 *
	 * @return array<int, array{q: string, a: string}>
	 */
	public static function faq(): array {
		return array(
			array(
				'q' => 'آکادمی لایف‌روس فروشگاه است؟',
				'a' => 'نه. لایف‌روس کالا نمی‌فروشد. آکادمی فقط دوره، اشتراک و کلاس آنلاین است.',
			),
			array(
				'q' => 'پرداخت چطور انجام می‌شود؟',
				'a' => 'قیمت‌ها به تومان است و از زرین‌پال پرداخت می‌شود. اگر بعد از تخفیف مبلغ صفر شود، دسترسی همان لحظه باز می‌شود.',
			),
			array(
				'q' => 'اشتراک خودکار تمدید می‌شود؟',
				'a' => 'نه. نزدیک پایان یک یادآوری می‌آید و تا سه روز بعد از پایان، دسترسی می‌ماند.',
			),
			array(
				'q' => 'دورهٔ رایگان هم دارید؟',
				'a' => 'بله. آموزش زبان روسی از صفر رایگان است. دسته‌هایی که هنوز دوره ندارند با نشان به‌زود دیده می‌شوند.',
			),
		);
	}

	/**
	 * Published courses per category.
	 *
	 * @return array<int, int>
	 */
	public static function course_counts(): array {
		global $wpdb;
		$table = Db::table( 'courses' );
		$rows  = $wpdb->get_results( "SELECT category_id, COUNT(*) AS n FROM `{$table}` WHERE status = 'published' GROUP BY category_id", ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['category_id'] ] = (int) $row['n'];
		}
		return $out;
	}

	/**
	 * Published lesson count and seconds per course.
	 *
	 * @return array<int, array{n: int, seconds: int}>
	 */
	public static function lesson_meta(): array {
		global $wpdb;
		$lessons = Db::table( 'course_lessons' );
		$modules = Db::table( 'course_modules' );
		$courses = Db::table( 'courses' );
		$rows    = $wpdb->get_results(
			"SELECT m.course_id, COUNT(*) AS n, COALESCE(SUM(l.duration_seconds), 0) AS seconds
			FROM `{$lessons}` l
			INNER JOIN `{$modules}` m ON m.id = l.module_id
			INNER JOIN `{$courses}` c ON c.id = m.course_id
			WHERE l.status = 'published' AND m.status = 'published' AND c.status = 'published'
			GROUP BY m.course_id",
			ARRAY_A
		);
		$out     = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['course_id'] ] = array(
				'n'       => (int) $row['n'],
				'seconds' => (int) $row['seconds'],
			);
		}
		return $out;
	}

	/**
	 * Hero figures: courses, lessons, free courses, categories.
	 *
	 * @return array{courses: int, lessons: int, free: int, categories: int}
	 */
	public static function stats(): array {
		$courses = self::courses();
		$meta    = self::lesson_meta();
		$free    = 0;
		$lessons = 0;
		foreach ( $courses as $course ) {
			if ( ! empty( $course['is_free'] ) ) {
				++$free;
			}
		}
		foreach ( $meta as $row ) {
			$lessons += $row['n'];
		}
		return array(
			'courses'    => count( $courses ),
			'lessons'    => $lessons,
			'free'       => $free,
			'categories' => count( Db::published( 'course_categories' ) ),
		);
	}

	/**
	 * Other published courses, same category first.
	 *
	 * @param array<string, mixed> $course Course.
	 * @param int                  $limit  How many.
	 * @return array<int, array<string, mixed>>
	 */
	public static function related( array $course, int $limit = 3 ): array {
		$out   = array();
		$seen  = array( (int) $course['id'] => true );
		$pools = array( self::courses( (int) $course['category_id'] ), self::courses() );
		$kept  = 0;
		foreach ( $pools as $pool ) {
			foreach ( $pool as $row ) {
				$id = (int) $row['id'];
				if ( isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$out[]       = $row;
				++$kept;
				if ( $kept >= $limit ) {
					return $out;
				}
			}
		}
		return $out;
	}

	/**
	 * Readable length. The course field wins; otherwise the lesson seconds.
	 *
	 * @param array<string, mixed>          $course Course.
	 * @param array{n?: int, seconds?: int} $meta   Lesson totals.
	 */
	public static function duration_label( array $course, array $meta = array() ): string {
		$text = trim( (string) ( $course['duration'] ?? '' ) );
		if ( '' !== $text ) {
			return $text;
		}
		$seconds = (int) ( $meta['seconds'] ?? 0 );
		if ( $seconds < 60 ) {
			return '';
		}
		return (string) (int) round( $seconds / 60 ) . ' دقیقه';
	}
}
