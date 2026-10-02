<?php
/**
 * Local demo course for the Academy admin cycle.
 *
 * Not hooked to upgrades. Run once with wp eval on a development site.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Settings\Settings as CoreSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Creates «روسی برای سفر», buys it through the Zarinpal mock, then refunds it.
 */
class Cycle {

	/**
	 * Seed or reuse the demo course and return a short report.
	 */
	public static function seed(): string {
		self::merchant();
		self::academy_settings();
		$course = self::course();
		$lines  = array( 'course ' . (int) $course['id'] );
		$lines  = array_merge( $lines, self::purchase( $course ) );
		$lines  = array_merge( $lines, self::fixtures( $course ) );
		return implode( "\n", $lines );
	}

	/**
	 * Use the offline gateway only when no real merchant is stored.
	 */
	private static function merchant(): void {
		$payments = CoreSettings::get( 'payments' );
		$current  = trim( (string) ( $payments['merchant_id'] ?? '' ) );
		$standin  = array( '', 'mock', 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee' );
		if ( ! in_array( $current, $standin, true ) ) {
			return;
		}
		$payments['merchant_id'] = 'mock';
		$payments['sandbox']     = '1';
		CoreSettings::update( 'payments', $payments );
	}

	/**
	 * Mark the video provider ready so the hub checklist can complete.
	 */
	private static function academy_settings(): void {
		$settings                      = Settings::get();
		$settings['default_provider']  = 'arvan_vod';
		$settings['provider_ready']    = 1;
		$settings['certificate_title'] = 'گواهی پایان دوره';
		$settings['reminder_expiring'] = 'اشتراک شما تا {date} معتبر است.';
		Settings::save( $settings );
	}

	/**
	 * Published course with sections, a quiz, a price window, and a plan.
	 *
	 * @return array<string, mixed>
	 */
	private static function course(): array {
		$found = Db::find_by( 'courses', 'slug', 'rosi-safar' );
		if ( $found ) {
			return $found;
		}
		$category = self::row(
			'course_categories',
			'slug',
			'safar',
			array(
				'slug'   => 'safar',
				'title'  => 'سفر',
				'status' => 'published',
			)
		);
		$teacher  = self::row(
			'instructors',
			'slug',
			'maryam-rezaei',
			array(
				'slug'          => 'maryam-rezaei',
				'name'          => 'مریم رضایی',
				'bio'           => 'مدرس زبان روسی با تمرکز بر مکالمهٔ سفر.',
				'share_percent' => 30,
				'status'        => 'published',
			)
		);
		$cover    = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/images/moscow-night-1024.jpg' ) : '';
		$now      = Db::now();
		$id       = Db::insert(
			'courses',
			array(
				'slug'                     => 'rosi-safar',
				'title'                    => 'روسی برای سفر',
				'excerpt'                  => 'الفبا، احوال‌پرسی و واژه‌های ضروری فرودگاه و هتل.',
				'description'              => 'یک دورهٔ کوتاه برای کسی که هفتهٔ بعد به مسکو می‌رود.',
				'outcomes'                 => "سلام و خداحافظی\nسفارش در کافه\nپرسیدن مسیر",
				'prerequisites'            => 'آشنایی با حروف فارسی کافی است.',
				'price'                    => 890000,
				'discount_price'           => 690000,
				'discount_starts'          => gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ),
				'discount_ends'            => gmdate( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) ),
				'access_days'              => 180,
				'currency'                 => 'IRT',
				'instructor_id'            => (int) $teacher['id'],
				'category_id'              => (int) $category['id'],
				'level'                    => 'مبتدی',
				'duration'                 => '۳ ساعت',
				'thumbnail'                => $cover,
				'status'                   => 'published',
				'is_featured'              => 1,
				'included_in_subscription' => 1,
				'certificate_enabled'      => 1,
				'seo_title'                => 'دوره روسی برای سفر | آکادمی لایف‌روس',
				'seo_description'          => 'یادگیری روسی سفر: الفبا، احوال‌پرسی، آزمون و واژه‌نامه.',
				'published_at'             => $now,
			)
		);
		$module   = Db::insert(
			'course_modules',
			array(
				'course_id' => $id,
				'title'     => 'هفتهٔ اول',
				'summary'   => 'از الفبا تا یک گفتگوی کوتاه.',
				'status'    => 'published',
			)
		);
		self::lesson( $module, 'alefba', 'الفبا', 'video', 1, 480, true );
		self::lesson( $module, 'salam', 'سلام و احوال‌پرسی', 'text', 2, 600, false );
		self::lesson( $module, 'azmoon', 'آزمون کوتاه', 'quiz', 3, 300, false );
		self::lesson( $module, 'vazhe', 'واژه‌نامه', 'file', 4, 0, false );
		self::quiz( $id );
		$plan   = self::row(
			'subscription_plans',
			'slug',
			'mahane',
			array(
				'slug'             => 'mahane',
				'title'            => 'طرح ماهانه',
				'description'      => 'دسترسی به دوره‌های داخل طرح برای یک ماه.',
				'price'            => 490000,
				'billing_interval' => 'month',
				'tier'             => 'premium',
				'status'           => 'published',
			)
		);
		$linked = Db::where_id( 'plan_courses', 'plan_id', (int) $plan['id'] );
		$has    = false;
		foreach ( $linked as $row ) {
			if ( (int) $row['course_id'] === $id ) {
				$has = true;
			}
		}
		if ( ! $has ) {
			Db::insert(
				'plan_courses',
				array(
					'plan_id'   => (int) $plan['id'],
					'course_id' => $id,
				)
			);
		}
		Db::insert(
			'class_sessions',
			array(
				'instructor_id' => (int) $teacher['id'],
				'kind'          => 'group',
				'title'         => 'کلاس مکالمهٔ جمعه',
				'starts_at'     => gmdate( 'Y-m-d H:i:s', time() + ( 5 * DAY_IN_SECONDS ) ),
				'capacity'      => 8,
				'price'         => 350000,
				'status'        => 'open',
			)
		);
		$course = Db::find( 'courses', $id );
		return $course ? $course : array( 'id' => $id );
	}

	/**
	 * Buy, watch, certify, and refund through the same order methods as the site.
	 *
	 * @param array<string, mixed> $course Course.
	 * @return string[]
	 */
	private static function purchase( array $course ): array {
		$user = get_user_by( 'login', 'student1' );
		if ( ! $user ) {
			return array( 'student1 missing' );
		}
		$student = Students::ensure( (int) $user->ID );
		if ( ! $student ) {
			return array( 'student profile missing' );
		}
		global $wpdb;
		$bought = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . Db::table( 'order_items' ) . '` i JOIN `' . Db::table( 'orders' ) . "` o ON o.id = i.order_id WHERE i.item_type = 'course' AND i.item_id = %d AND o.student_id = %d AND o.status IN ('paid','refunded')",
				(int) $course['id'],
				(int) $student['id']
			)
		);
		if ( $bought > 0 ) {
			$order = self::latest_order( (int) $student['id'], (int) $course['id'] );
			if ( $order && 'paid' === $order['status'] && Orders::refund( (int) $order['id'], (int) $order['total'], 'بازپرداخت آزمایشی چرخه آکادمی' ) ) {
				Flow::audit( 'student', (int) $student['id'], 'cycle', 'خرید آزمایشی، پیشرفت، گواهی و بازپرداخت ثبت شد.' );
				return array( 'refunded ' . $order['code'] );
			}
			return array( 'purchase already recorded' );
		}
		wp_set_current_user( (int) $user->ID );
		$start = Orders::start( (int) $user->ID, 'course', (string) $course['id'], '' );
		if ( empty( $start['ok'] ) ) {
			return array( 'checkout failed: ' . $start['message'] );
		}
		$parts = wp_parse_url( (string) $start['redirect'] );
		$query = array();
		if ( isset( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query );
		}
		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		if ( ! preg_match( '#/academy/pay/([a-f0-9]{32})/?#', $path, $match ) ) {
			return array( 'mock callback missing' );
		}
		$verify = Orders::verify( $match[1], (string) ( $query['Authority'] ?? '' ), (string) ( $query['Status'] ?? '' ) );
		if ( empty( $verify['ok'] ) ) {
			return array( 'verify failed: ' . $verify['message'] );
		}
		$order = self::latest_order( (int) $student['id'], (int) $course['id'] );
		$lines = array( 'paid ' . ( $order ? (string) $order['total'] : '?' ) );
		foreach ( Catalog::lessons( (int) $course['id'], true ) as $lesson ) {
			Progress::complete( (int) $student['id'], (int) $course['id'], (int) $lesson['id'] );
		}
		$cert    = Certificates::issue( (int) $student['id'], (int) $course['id'] );
		$lines[] = $cert ? 'certificate ' . $cert['code'] : 'certificate missing';
		self::score( (int) $course['id'], (int) $student['id'] );
		if ( $order && Orders::refund( (int) $order['id'], (int) $order['total'], 'بازپرداخت آزمایشی چرخه آکادمی' ) ) {
			$lines[] = 'refunded ' . $order['code'];
		}
		Flow::audit( 'student', (int) $student['id'], 'cycle', 'خرید آزمایشی، پیشرفت، گواهی و بازپرداخت ثبت شد.' );
		return $lines;
	}

	/**
	 * Rows that keep «کارهای امروز» from looking empty.
	 *
	 * @param array<string, mixed> $course Published course.
	 * @return string[]
	 */
	private static function fixtures( array $course ): array {
		$draft = Db::find_by( 'courses', 'slug', 'rosi-draft' );
		if ( ! $draft ) {
			$draft_id = Db::insert(
				'courses',
				array(
					'slug'        => 'rosi-draft',
					'title'       => 'مکالمهٔ فرودگاه',
					'description' => 'پیش‌نویس بدون جلد و مدرس.',
					'price'       => 0,
					'status'      => 'draft',
				)
			);
			$module   = Db::insert(
				'course_modules',
				array(
					'course_id' => $draft_id,
					'title'     => 'سرفصل خالی',
					'status'    => 'draft',
				)
			);
			self::lesson( $module, 'film', 'فیلم معرفی', 'video', 1, 0, false );
		}
		$account = get_user_by( 'login', 'student1' );
		$student = $account ? Students::ensure( (int) $account->ID ) : null;
		if ( $student ) {
			$open = Db::where_id( 'questions', 'course_id', (int) $course['id'] );
			$has  = false;
			foreach ( $open as $row ) {
				if ( 'pending' === $row['status'] ) {
					$has = true;
				}
			}
			if ( ! $has ) {
				Db::insert(
					'questions',
					array(
						'course_id'  => (int) $course['id'],
						'student_id' => (int) $student['id'],
						'user_id'    => (int) $student['user_id'],
						'kind'       => 'question',
						'body'       => 'تلفظ «спасибо» را یک بار دیگر توضیح می‌دهید؟',
						'status'     => 'pending',
					)
				);
			}
			self::expiring_sub( (int) $student['id'] );
			self::pending_plan( (int) $student['user_id'] );
		}
		self::views( (int) $course['id'] );
		return array( 'fixtures ready' );
	}

	/**
	 * Insert a lesson and its video or file when needed.
	 *
	 * @param int    $module   Module id.
	 * @param string $slug     Lesson slug.
	 * @param string $title    Title.
	 * @param string $type     video, text, quiz, or file.
	 * @param int    $sort     Order.
	 * @param int    $seconds  Duration.
	 * @param bool   $preview  Free preview.
	 */
	private static function lesson( int $module, string $slug, string $title, string $type, int $sort, int $seconds, bool $preview ): int {
		$id = Db::insert(
			'course_lessons',
			array(
				'module_id'        => $module,
				'slug'             => $slug,
				'title'            => $title,
				'content'          => 'text' === $type ? 'Здравствуйте! Меня зовут…' : '',
				'type'             => $type,
				'sort_order'       => $sort,
				'status'           => 'published',
				'is_preview'       => $preview ? 1 : 0,
				'duration_seconds' => $seconds,
			)
		);
		if ( 'video' === $type && 'alefba' === $slug ) {
			Db::insert(
				'lesson_videos',
				array(
					'lesson_id'   => $id,
					'provider'    => 'arvan_vod',
					'external_id' => 'arvan-rosi-alefba',
					'duration'    => $seconds,
					'status'      => 'published',
				)
			);
		}
		if ( 'file' === $type ) {
			Db::insert(
				'lesson_attachments',
				array(
					'lesson_id'    => $id,
					'title'        => 'واژه‌نامهٔ سفر',
					'storage_path' => 'academy/rosi-safar-vocab.pdf',
					'mime'         => 'application/pdf',
					'status'       => 'published',
				)
			);
		}
		return $id;
	}

	/**
	 * One course quiz.
	 *
	 * @param int $course_id Course id.
	 */
	private static function quiz( int $course_id ): void {
		$quiz = Db::insert(
			'quizzes',
			array(
				'scope_type'   => 'course',
				'scope_id'     => $course_id,
				'title'        => 'آزمون سلام',
				'pass_percent' => 70,
				'status'       => 'published',
			)
		);
		Db::insert(
			'quiz_questions',
			array(
				'quiz_id' => $quiz,
				'type'    => 'choice',
				'prompt'  => 'معنی «спасибо» چیست؟',
				'payload' => wp_json_encode(
					array(
						'choices' => array( 'سلام', 'متشکرم', 'خداحافظ' ),
						'answer'  => 1,
					)
				),
			)
		);
	}

	/**
	 * Store a passing score for the profile.
	 *
	 * @param int $course_id  Course id.
	 * @param int $student_id Student id.
	 */
	private static function score( int $course_id, int $student_id ): void {
		global $wpdb;
		$quiz = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id FROM `' . Db::table( 'quizzes' ) . "` WHERE scope_type = 'course' AND scope_id = %d LIMIT 1",
				$course_id
			),
			ARRAY_A
		);
		if ( ! $quiz ) {
			return;
		}
		Db::insert(
			'quiz_results',
			array(
				'quiz_id'    => (int) $quiz['id'],
				'student_id' => $student_id,
				'score'      => 1,
				'max_score'  => 1,
				'passed'     => 1,
				'detail'     => 'پاسخ درست',
			)
		);
	}

	/**
	 * Active subscription ending inside three days.
	 *
	 * @param int $student_id Student id.
	 */
	private static function expiring_sub( int $student_id ): void {
		$plan = Db::find_by( 'subscription_plans', 'slug', 'mahane' );
		if ( ! $plan ) {
			return;
		}
		global $wpdb;
		$soon = gmdate( 'Y-m-d H:i:s', time() + ( 3 * DAY_IN_SECONDS ) );
		$has  = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . Db::table( 'subscriptions' ) . "` WHERE student_id = %d AND status = 'active' AND ends_at <= %s",
				$student_id,
				$soon
			)
		);
		if ( $has > 0 ) {
			return;
		}
		Db::insert(
			'subscriptions',
			array(
				'student_id' => $student_id,
				'plan_id'    => (int) $plan['id'],
				'status'     => 'active',
				'starts_at'  => Db::now(),
				'ends_at'    => gmdate( 'Y-m-d H:i:s', time() + ( 2 * DAY_IN_SECONDS ) ),
			)
		);
	}

	/**
	 * Leave one plan checkout unpaid.
	 *
	 * @param int $user_id WordPress user.
	 */
	private static function pending_plan( int $user_id ): void {
		$plan = Db::find_by( 'subscription_plans', 'slug', 'mahane' );
		if ( ! $plan ) {
			return;
		}
		global $wpdb;
		$has = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . Db::table( 'order_items' ) . '` i JOIN `' . Db::table( 'orders' ) . "` o ON o.id = i.order_id WHERE i.item_type = 'plan' AND i.item_id = %d AND o.user_id = %d AND o.status = 'pending'",
				(int) $plan['id'],
				$user_id
			)
		);
		if ( $has > 0 ) {
			return;
		}
		wp_set_current_user( $user_id );
		Orders::start( $user_id, 'plan', (string) $plan['id'], '' );
	}

	/**
	 * A few course views so the funnel is not zero.
	 *
	 * @param int $course_id Course id.
	 */
	private static function views( int $course_id ): void {
		global $wpdb;
		$date = gmdate( 'Y-m-d' );
		$has  = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . Db::table( 'course_views_daily' ) . '` WHERE course_id = %d AND stat_date = %s',
				$course_id,
				$date
			)
		);
		if ( $has > 0 ) {
			return;
		}
		Db::insert(
			'course_views_daily',
			array(
				'course_id' => $course_id,
				'stat_date' => $date,
				'views'     => 48,
				'uniques'   => 31,
			)
		);
	}

	/**
	 * Latest course order for a student.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	private static function latest_order( int $student_id, int $course_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT o.* FROM `' . Db::table( 'orders' ) . '` o JOIN `' . Db::table( 'order_items' ) . "` i ON i.order_id = o.id WHERE o.student_id = %d AND i.item_type = 'course' AND i.item_id = %d ORDER BY o.id DESC LIMIT 1",
				$student_id,
				$course_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find or insert a row keyed by slug.
	 *
	 * @param string               $suffix Table suffix.
	 * @param string               $column Key column.
	 * @param string               $value  Key value.
	 * @param array<string, mixed> $data   Insert payload.
	 * @return array<string, mixed>
	 */
	private static function row( string $suffix, string $column, string $value, array $data ): array {
		$found = Db::find_by( $suffix, $column, $value );
		if ( $found ) {
			return $found;
		}
		$id  = Db::insert( $suffix, $data );
		$row = Db::find( $suffix, $id );
		return $row ? $row : array( 'id' => $id );
	}
}
