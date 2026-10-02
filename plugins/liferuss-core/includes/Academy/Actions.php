<?php
/**
 * Academy admin writes: course editor, students, orders, plans, and settings.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Settings\Settings as CoreSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is checked before maybe() runs.

/**
 * POST handlers for the Academy admin.
 */
class Actions {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_ajax_lr_academy_sort', array( self::class, 'sort' ) );
		add_action( 'admin_init', array( Flow::class, 'publish_due' ) );
	}

	/**
	 * Handle a known lr_do value. Redirects on success.
	 *
	 * @param string $task Action slug.
	 */
	public static function maybe( string $task ): bool {
		$map = array(
			'new_course'   => 'new_course',
			'course_info'  => 'course_info',
			'module'       => 'module',
			'lesson'       => 'lesson',
			'quiz'         => 'quiz',
			'course_price' => 'course_price',
			'course_seo'   => 'course_seo',
			'course_pub'   => 'course_pub',
			'student'      => 'student',
			'refund'       => 'refund_order',
			'plan_save'    => 'plan_save',
			'question'     => 'question',
			'instructor'   => 'instructor',
			'session'      => 'session',
			'settings'     => 'settings',
			'dismiss'      => 'dismiss',
		);
		if ( ! isset( $map[ $task ] ) ) {
			return false;
		}
		if ( ! current_user_can( 'lr_academy_access' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		call_user_func( array( self::class, $map[ $task ] ) );
		return true;
	}

	/**
	 * Drag-and-drop order.
	 */
	public static function sort(): void {
		check_ajax_referer( 'lr_academy_ui', 'nonce' );
		if ( ! current_user_can( 'lr_academy_access' ) ) {
			wp_send_json_error( array( 'message' => 'مجوز ندارید.' ), 403 );
		}
		$kind  = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( (string) $_POST['kind'] ) ) : '';
		$ids   = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$table = 'modules' === $kind ? 'course_modules' : 'course_lessons';
		if ( 'modules' !== $kind && 'lessons' !== $kind ) {
			wp_send_json_error( array( 'message' => 'نامعتبر' ), 400 );
		}
		$order = 0;
		foreach ( $ids as $id ) {
			if ( $id < 1 ) {
				continue;
			}
			$row = Db::find( $table, $id );
			if ( ! $row || ! self::owns_row( $kind, $row ) ) {
				continue;
			}
			Db::update( $table, $id, array( 'sort_order' => $order ) );
			++$order;
		}
		wp_send_json_success();
	}

	/**
	 * Create a draft and open the editor.
	 */
	private static function new_course(): void {
		$scope = Flow::scope();
		if ( $scope < 0 ) {
			Flow::back( 'پروفایل مدرس به حساب شما وصل نیست.', 'err' );
		}
		$slug = 'course-' . strtolower( wp_generate_password( 6, false, false ) );
		$id   = Db::insert(
			'courses',
			array(
				'title'               => 'دورهٔ تازه',
				'slug'                => $slug,
				'status'              => 'draft',
				'currency'            => 'IRT',
				'instructor_id'       => $scope > 0 ? $scope : null,
				'certificate_enabled' => 1,
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=lr-academy-course&id=' . $id . '&step=info&lr_note=' . rawurlencode( 'پیش‌نویس ساخته شد.' ) ) );
		exit;
	}

	/**
	 * Save the information step.
	 */
	private static function course_info(): void {
		$course = self::posted_course();
		$title  = self::text( 'title' );
		$slug   = sanitize_title( self::text( 'slug' ) );
		if ( '' === $title ) {
			Flow::back( 'عنوان را بنویسید.', 'err', array( 'lr_err' => 'title' ) );
		}
		if ( '' === $slug ) {
			$slug = sanitize_title( $title );
		}
		$slug = self::unique_slug( $slug, (int) $course['id'] );
		$inst = absint( $_POST['instructor_id'] ?? 0 );
		if ( Flow::scope() > 0 ) {
			$inst = Flow::scope();
		}
		Db::update(
			'courses',
			(int) $course['id'],
			array(
				'title'         => $title,
				'slug'          => $slug,
				'category_id'   => absint( $_POST['category_id'] ?? 0 ) > 0 ? absint( $_POST['category_id'] ?? 0 ) : null,
				'level'         => self::text( 'level' ),
				'instructor_id' => $inst > 0 ? $inst : null,
				'thumbnail'     => esc_url_raw( self::text( 'thumbnail' ) ),
				'excerpt'       => self::text( 'excerpt' ),
				'description'   => wp_kses_post( wp_unslash( (string) ( $_POST['description'] ?? '' ) ) ),
				'outcomes'      => self::text( 'outcomes' ),
				'prerequisites' => self::text( 'prerequisites' ),
			)
		);
		Flow::back( 'اطلاعات ذخیره شد.' );
	}

	/**
	 * Add a section.
	 */
	private static function module(): void {
		$course = self::posted_course();
		$title  = self::text( 'title' );
		if ( '' === $title ) {
			Flow::back( 'عنوان بخش را بنویسید.', 'err' );
		}
		Db::insert(
			'course_modules',
			array(
				'course_id' => (int) $course['id'],
				'title'     => $title,
				'status'    => 'published',
			)
		);
		Flow::back( 'بخش اضافه شد.' );
	}

	/**
	 * Add or update a lesson.
	 */
	private static function lesson(): void {
		$course = self::posted_course();
		$title  = self::text( 'title' );
		if ( '' === $title ) {
			Flow::back( 'عنوان درس را بنویسید.', 'err' );
		}
		$type = sanitize_key( self::text( 'type' ) );
		if ( ! in_array( $type, array( 'video', 'text', 'quiz', 'file' ), true ) ) {
			$type = 'video';
		}
		$module = absint( $_POST['module_id'] ?? 0 );
		$mod    = Db::find( 'course_modules', $module );
		if ( ! $mod || (int) $mod['course_id'] !== (int) $course['id'] ) {
			Flow::back( 'بخش درس پیدا نشد.', 'err' );
		}
		$id   = absint( $_POST['lesson_id'] ?? 0 );
		$data = array(
			'module_id'        => $module,
			'title'            => $title,
			'slug'             => sanitize_title( $title . '-' . wp_generate_password( 4, false, false ) ),
			'type'             => $type,
			'content'          => wp_kses_post( wp_unslash( (string) ( $_POST['content'] ?? '' ) ) ),
			'duration_seconds' => absint( $_POST['duration'] ?? 0 ) * 60,
			'is_preview'       => empty( $_POST['is_preview'] ) ? 0 : 1,
			'status'           => 'published',
		);
		if ( $id > 0 ) {
			unset( $data['slug'] );
			Db::update( 'course_lessons', $id, $data );
		} else {
			$id = Db::insert( 'course_lessons', $data );
		}
		if ( 'video' === $type && $id > 0 ) {
			self::video( $id );
		}
		if ( 'file' === $type && $id > 0 && '' !== self::text( 'file_url' ) ) {
			Db::insert(
				'lesson_attachments',
				array(
					'lesson_id'    => $id,
					'title'        => $title,
					'storage_path' => esc_url_raw( self::text( 'file_url' ) ),
					'status'       => 'published',
				)
			);
		}
		Flow::back( 'درس ذخیره شد.' );
	}

	/**
	 * Quiz question.
	 */
	private static function quiz(): void {
		$course = self::posted_course();
		$prompt = self::text( 'prompt' );
		if ( '' === $prompt ) {
			Flow::back( 'متن سؤال را بنویسید.', 'err' );
		}
		$quiz    = self::course_quiz( (int) $course['id'] );
		$choices = array(
			self::text( 'a' ),
			self::text( 'b' ),
			self::text( 'c' ),
		);
		Db::insert(
			'quiz_questions',
			array(
				'quiz_id' => (int) $quiz['id'],
				'type'    => 'choice',
				'prompt'  => $prompt,
				'payload' => wp_json_encode(
					array(
						'choices' => $choices,
						'answer'  => absint( $_POST['answer'] ?? 0 ),
					)
				),
			)
		);
		Db::update( 'courses', (int) $course['id'], array( 'certificate_enabled' => empty( $_POST['certificate'] ) ? 0 : 1 ) );
		Flow::back( 'سؤال آزمون ذخیره شد.' );
	}

	/**
	 * Price and access.
	 */
	private static function course_price(): void {
		$course = self::posted_course();
		$free   = empty( $_POST['is_free'] ) ? 0 : 1;
		$price  = absint( $_POST['price'] ?? 0 );
		if ( 0 === $free && $price < 1 ) {
			Flow::back( 'قیمت را به تومان بنویسید یا دوره را رایگان کنید.', 'err', array( 'lr_err' => 'price' ) );
		}
		$discount = absint( $_POST['discount_price'] ?? 0 );
		$plans    = isset( $_POST['plans'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['plans'] ) ) : array();
		Db::update(
			'courses',
			(int) $course['id'],
			array(
				'is_free'                  => $free,
				'price'                    => $free ? 0 : $price,
				'discount_price'           => $discount > 0 ? $discount : null,
				'discount_starts'          => self::when( 'discount_starts' ),
				'discount_ends'            => self::when( 'discount_ends' ),
				'access_days'              => absint( $_POST['access_days'] ?? 0 ),
				'included_in_subscription' => $plans ? 1 : 0,
			)
		);
		self::sync_plans( (int) $course['id'], $plans );
		self::sync_bundle( (int) $course['id'], isset( $_POST['bundles'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_POST['bundles'] ) ) : array() );
		Flow::back( 'قیمت و دسترسی ذخیره شد.' );
	}

	/**
	 * SEO fields.
	 */
	private static function course_seo(): void {
		$course = self::posted_course();
		Db::update(
			'courses',
			(int) $course['id'],
			array(
				'seo_title'       => self::text( 'seo_title' ),
				'seo_description' => self::text( 'seo_description' ),
			)
		);
		Flow::back( 'سئو ذخیره شد.' );
	}

	/**
	 * Draft, review, schedule, or publish.
	 */
	private static function course_pub(): void {
		$course = self::posted_course();
		$intent = sanitize_key( self::text( 'intent' ) );
		$fresh  = Db::find( 'courses', (int) $course['id'] );
		if ( ! $fresh ) {
			Flow::back( 'دوره پیدا نشد.', 'err' );
		}
		if ( 'archive' === $intent && current_user_can( 'lr_academy_manage' ) ) {
			Db::update( 'courses', (int) $fresh['id'], array( 'status' => 'archived' ) );
			Flow::back( 'دوره بایگانی شد.' );
		}
		if ( 'draft' === $intent ) {
			Db::update(
				'courses',
				(int) $fresh['id'],
				array(
					'status'   => 'draft',
					'workflow' => '',
				)
			);
			Flow::back( 'پیش‌نویس ذخیره شد.' );
		}
		if ( ! Flow::ready( $fresh ) ) {
			Flow::back( 'موارد ضروری انتشار هنوز کامل نیست.', 'err' );
		}
		if ( 'review' === $intent || ! current_user_can( 'lr_academy_manage' ) ) {
			Db::update(
				'courses',
				(int) $fresh['id'],
				array(
					'status'   => 'draft',
					'workflow' => 'review',
				)
			);
			Flow::back( 'برای بررسی ارسال شد.' );
		}
		$when = self::when( 'scheduled_at' );
		if ( 'schedule' === $intent && null !== $when ) {
			Db::update(
				'courses',
				(int) $fresh['id'],
				array(
					'status'       => 'draft',
					'workflow'     => 'ready',
					'scheduled_at' => $when,
				)
			);
			Flow::back( 'انتشار زمان‌بندی شد.' );
		}
		Db::update(
			'courses',
			(int) $fresh['id'],
			array(
				'status'       => 'published',
				'workflow'     => '',
				'published_at' => Db::now(),
			)
		);
		Flow::back( 'دوره منتشر شد.' );
	}

	/**
	 * Grant, revoke, extend, or reset.
	 */
	private static function student(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			Flow::back( 'این کار فقط برای مدیر آکادمی است.', 'err' );
		}
		$student = absint( $_POST['student_id'] ?? 0 );
		$course  = absint( $_POST['course_id'] ?? 0 );
		$do      = sanitize_key( self::text( 'student_do' ) );
		$row     = Db::find( 'students', $student );
		if ( ! $row ) {
			Flow::back( 'دانشجو پیدا نشد.', 'err' );
		}
		if ( 'grant' === $do && $course > 0 ) {
			Access::enroll( $student, $course, 0, 'purchase', 'premium' );
			Flow::audit( 'student', $student, 'grant', 'دسترسی دوره ' . $course );
			Flow::back( 'دسترسی داده شد.' );
		}
		if ( 'revoke' === $do && $course > 0 ) {
			global $wpdb;
			$table = Db::table( 'enrollments' );
			$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET status = 'revoked', revoked_at = %s, updated_at = %s WHERE student_id = %d AND course_id = %d", Db::now(), Db::now(), $student, $course ) );
			Flow::audit( 'student', $student, 'revoke', 'لغو دوره ' . $course );
			Flow::back( 'دسترسی لغو شد.' );
		}
		if ( 'extend' === $do ) {
			$sub = absint( $_POST['subscription_id'] ?? 0 );
			$one = Db::find( 'subscriptions', $sub );
			if ( $one ) {
				$ends = gmdate( 'Y-m-d H:i:s', strtotime( (string) $one['ends_at'] . ' UTC' ) + ( 30 * DAY_IN_SECONDS ) );
				Db::update(
					'subscriptions',
					$sub,
					array(
						'ends_at'     => $ends,
						'status'      => 'active',
						'grace_until' => $ends,
					)
				);
				Flow::audit( 'student', $student, 'extend', 'تمدید اشتراک ' . $sub );
			}
			Flow::back( 'اشتراک ۳۰ روز تمدید شد.' );
		}
		if ( 'reset' === $do && $course > 0 ) {
			global $wpdb;
			$table = Db::table( 'course_progress' );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE student_id = %d AND course_id = %d", $student, $course ) );
			Flow::audit( 'student', $student, 'reset', 'بازنشانی پیشرفت ' . $course );
			Flow::back( 'پیشرفت بازنشانی شد.' );
		}
		Flow::back( 'کاری انجام نشد.', 'err' );
	}

	/**
	 * Refund through the existing order service.
	 */
	private static function refund_order(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			Flow::back( 'مجوز بازپرداخت ندارید.', 'err' );
		}
		$id     = absint( $_POST['order_id'] ?? 0 );
		$amount = absint( $_POST['amount'] ?? 0 );
		$reason = self::text( 'reason' );
		if ( '' === $reason ) {
			Flow::back( 'دلیل بازپرداخت را بنویسید.', 'err' );
		}
		$ok = Orders::refund( $id, $amount, $reason );
		if ( $ok ) {
			Flow::audit( 'order', $id, 'refund', $reason );
			Flow::back( 'بازپرداخت ثبت شد.' );
		}
		Flow::back( 'بازپرداخت انجام نشد.', 'err' );
	}

	/**
	 * Plan editor.
	 */
	private static function plan_save(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			Flow::back( 'مجوز ندارید.', 'err' );
		}
		$title = self::text( 'title' );
		if ( '' === $title ) {
			Flow::back( 'عنوان طرح را بنویسید.', 'err' );
		}
		$id   = absint( $_POST['plan_id'] ?? 0 );
		$data = array(
			'title'            => $title,
			'slug'             => sanitize_title( self::text( 'slug' ) ? self::text( 'slug' ) : $title ),
			'price'            => absint( $_POST['price'] ?? 0 ),
			'billing_interval' => 'year' === sanitize_key( self::text( 'interval' ) ) ? 'year' : 'month',
			'description'      => self::text( 'description' ),
			'status'           => 'published',
		);
		if ( $id > 0 ) {
			Db::update( 'subscription_plans', $id, $data );
		} else {
			$id = Db::insert( 'subscription_plans', $data );
		}
		$courses = isset( $_POST['course_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['course_ids'] ) ) : array();
		global $wpdb;
		$table = Db::table( 'plan_courses' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE plan_id = %d", $id ) );
		foreach ( $courses as $course_id ) {
			if ( $course_id > 0 ) {
				Db::insert(
					'plan_courses',
					array(
						'plan_id'   => $id,
						'course_id' => $course_id,
					)
				);
			}
		}
		Flow::back( 'طرح ذخیره شد.' );
	}

	/**
	 * Moderate a question or review.
	 */
	private static function question(): void {
		$id  = absint( $_POST['question_id'] ?? 0 );
		$row = Db::find( 'questions', $id );
		if ( ! $row ) {
			Flow::back( 'مورد پیدا نشد.', 'err' );
		}
		$status = sanitize_key( self::text( 'status' ) );
		if ( ! in_array( $status, array( 'pending', 'published', 'hidden' ), true ) ) {
			$status = 'pending';
		}
		Db::update(
			'questions',
			$id,
			array(
				'status' => $status,
				'answer' => self::text( 'answer' ),
			)
		);
		Flow::back( 'صف بررسی به‌روز شد.' );
	}

	/**
	 * Instructor profile and share.
	 */
	private static function instructor(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			Flow::back( 'مجوز ندارید.', 'err' );
		}
		$name = self::text( 'name' );
		if ( '' === $name ) {
			Flow::back( 'نام مدرس را بنویسید.', 'err' );
		}
		$id   = absint( $_POST['instructor_id'] ?? 0 );
		$data = array(
			'name'          => $name,
			'slug'          => sanitize_title( self::text( 'slug' ) ? self::text( 'slug' ) : $name ),
			'bio'           => self::text( 'bio' ),
			'user_id'       => absint( $_POST['user_id'] ?? 0 ) > 0 ? absint( $_POST['user_id'] ?? 0 ) : null,
			'share_percent' => min( 100, absint( $_POST['share_percent'] ?? 0 ) ),
			'status'        => 'published',
		);
		if ( $id > 0 ) {
			Db::update( 'instructors', $id, $data );
		} else {
			Db::insert( 'instructors', $data );
		}
		Flow::back( 'مدرس ذخیره شد.' );
	}

	/**
	 * Class session.
	 */
	private static function session(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) && Flow::scope() < 1 ) {
			Flow::back( 'مجوز ندارید.', 'err' );
		}
		$title = self::text( 'title' );
		if ( '' === $title ) {
			Flow::back( 'عنوان کلاس را بنویسید.', 'err' );
		}
		$scope = Flow::scope();
		Db::insert(
			'class_sessions',
			array(
				'title'         => $title,
				'kind'          => 'private' === sanitize_key( self::text( 'kind' ) ) ? 'private' : 'group',
				'starts_at'     => self::when( 'starts_at' ) ? self::when( 'starts_at' ) : Db::now(),
				'capacity'      => max( 1, absint( $_POST['capacity'] ?? 1 ) ),
				'price'         => absint( $_POST['price'] ?? 0 ),
				'meeting_url'   => esc_url_raw( self::text( 'meeting' ) ),
				'instructor_id' => $scope > 0 ? $scope : ( absint( $_POST['instructor_id'] ?? 0 ) > 0 ? absint( $_POST['instructor_id'] ?? 0 ) : null ),
				'status'        => 'open',
			)
		);
		Flow::back( 'کلاس ثبت شد.' );
	}

	/**
	 * Academy settings, including the payment mock switch.
	 */
	private static function settings(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			Flow::back( 'مجوز ندارید.', 'err' );
		}
		$settings                     = Settings::get();
		$settings['default_provider'] = sanitize_key( self::text( 'provider' ) );
		if ( ! in_array( $settings['default_provider'], array( 'arvan_vod', 'upload', 'aparat' ), true ) ) {
			$settings['default_provider'] = 'arvan_vod';
		}
		$settings['arvan_api_key']     = self::text( 'arvan_api_key' );
		$settings['reminder_expiring'] = self::text( 'reminder_expiring' );
		$settings['certificate_title'] = self::text( 'certificate_title' );
		$settings['provider_ready']    = '1';
		Settings::save( $settings );
		$pay                = CoreSettings::get( 'payments' );
		$pay['merchant_id'] = self::text( 'merchant_id' );
		$pay['sandbox']     = empty( $_POST['sandbox'] ) ? '0' : '1';
		CoreSettings::update( 'payments', $pay );
		Flow::back( 'تنظیمات ذخیره شد.' );
	}

	/**
	 * Hide the hub checklist after it is complete.
	 */
	private static function dismiss(): void {
		update_user_meta( get_current_user_id(), 'lr_academy_onboarding_done', '1' );
		Flow::back( 'راهنمای شروع بسته شد.' );
	}

	/**
	 * Course from the hidden id field.
	 *
	 * @return array<string, mixed>
	 */
	private static function posted_course(): array {
		$id     = absint( $_POST['course_id'] ?? 0 );
		$course = Db::find( 'courses', $id );
		if ( ! $course || ! Flow::can_course( $course ) ) {
			Flow::back( 'به این دوره دسترسی ندارید.', 'err' );
		}
		return $course;
	}

	/**
	 * Posted text field.
	 *
	 * @param string $key Field.
	 */
	private static function text( string $key ): string {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) : '';
	}

	/**
	 * Datetime-local value as UTC, or null.
	 *
	 * @param string $key Field.
	 */
	private static function when( string $key ): ?string {
		$raw = self::text( $key );
		if ( '' === $raw ) {
			return null;
		}
		$stamp = strtotime( $raw );
		if ( ! $stamp ) {
			return null;
		}
		return gmdate( 'Y-m-d H:i:s', $stamp );
	}

	/**
	 * Keep slugs unique.
	 *
	 * @param string $slug    Slug.
	 * @param int    $current Course id.
	 */
	private static function unique_slug( string $slug, int $current ): string {
		global $wpdb;
		$table = Db::table( 'courses' );
		$taken = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s AND id <> %d LIMIT 1", $slug, $current ) );
		if ( $taken > 0 ) {
			return $slug . '-' . $current;
		}
		return $slug;
	}

	/**
	 * Upsert the lesson video row.
	 *
	 * @param int $lesson_id Lesson id.
	 */
	private static function video( int $lesson_id ): void {
		$external = self::text( 'external_id' );
		$provider = sanitize_key( self::text( 'provider' ) );
		if ( ! in_array( $provider, array( 'arvan_vod', 'upload', 'aparat' ), true ) ) {
			$provider = 'arvan_vod';
		}
		$rows = Db::where_id( 'lesson_videos', 'lesson_id', $lesson_id );
		$data = array(
			'provider'    => $provider,
			'external_id' => $external,
			'status'      => '' !== $external ? 'published' : 'draft',
		);
		if ( $rows ) {
			Db::update( 'lesson_videos', (int) $rows[0]['id'], $data );
			return;
		}
		$data['lesson_id'] = $lesson_id;
		Db::insert( 'lesson_videos', $data );
	}

	/**
	 * One course-scoped quiz.
	 *
	 * @param int $course_id Course id.
	 * @return array<string, mixed>
	 */
	private static function course_quiz( int $course_id ): array {
		global $wpdb;
		$table = Db::table( 'quizzes' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE scope_type = 'course' AND scope_id = %d LIMIT 1", $course_id ), ARRAY_A );
		if ( is_array( $row ) ) {
			return $row;
		}
		$id   = Db::insert(
			'quizzes',
			array(
				'scope_type'   => 'course',
				'scope_id'     => $course_id,
				'title'        => 'آزمون دوره',
				'pass_percent' => 70,
				'status'       => 'published',
			)
		);
		$made = Db::find( 'quizzes', $id );
		return $made ? $made : array( 'id' => 0 );
	}

	/**
	 * Replace plan membership for one course.
	 *
	 * @param int   $course_id Course id.
	 * @param int[] $plans     Plan ids.
	 */
	private static function sync_plans( int $course_id, array $plans ): void {
		global $wpdb;
		$table = Db::table( 'plan_courses' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE course_id = %d", $course_id ) );
		foreach ( $plans as $plan_id ) {
			if ( $plan_id > 0 ) {
				Db::insert(
					'plan_courses',
					array(
						'plan_id'   => $plan_id,
						'course_id' => $course_id,
					)
				);
			}
		}
	}

	/**
	 * Add or remove the course from bundle definitions.
	 *
	 * @param int      $course_id Course id.
	 * @param string[] $slugs     Selected bundle slugs.
	 */
	private static function sync_bundle( int $course_id, array $slugs ): void {
		$settings = Settings::get();
		$bundles  = is_array( $settings['bundles'] ) ? $settings['bundles'] : array();
		foreach ( $bundles as $index => $bundle ) {
			if ( ! is_array( $bundle ) ) {
				continue;
			}
			$ids = array_map( 'intval', (array) ( $bundle['course_ids'] ?? array() ) );
			$ids = array_values( array_diff( $ids, array( $course_id ) ) );
			if ( in_array( (string) ( $bundle['slug'] ?? '' ), $slugs, true ) ) {
				$ids[] = $course_id;
			}
			$bundles[ $index ]['course_ids'] = array_values( array_unique( $ids ) );
		}
		$settings['bundles'] = $bundles;
		Settings::save( $settings );
	}

	/**
	 * Whether the sorted row belongs to a course this user can edit.
	 *
	 * @param string               $kind modules or lessons.
	 * @param array<string, mixed> $row  Row.
	 */
	private static function owns_row( string $kind, array $row ): bool {
		if ( 'modules' === $kind ) {
			$course = Db::find( 'courses', (int) $row['course_id'] );
			return $course && Flow::can_course( $course );
		}
		$module = Db::find( 'course_modules', (int) $row['module_id'] );
		if ( ! $module ) {
			return false;
		}
		$course = Db::find( 'courses', (int) $module['course_id'] );
		return $course && Flow::can_course( $course );
	}
}
