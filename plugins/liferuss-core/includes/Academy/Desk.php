<?php
/**
 * Academy hub, course list, and the course editor.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Admin\Chrome;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * The first three Academy screens.
 */
class Desk {


	/**
	 * Hub: KPIs, today's work, and onboarding.
	 */
	public static function hub(): void {
		self::guard();
		$scope = Flow::scope();
		$new   = current_user_can( 'lr_academy_access' ) && $scope >= 0 ? self::button( 'دورهٔ جدید', 'new_course' ) : '';
		Chrome::open( 'پیشخوان', 'آکادمی', $new );
		self::onboarding();
		self::kpis( $scope );
		echo '<div class="lr-hub-grid">';
		echo '<div>';
		self::today_cards( $scope );
		echo '</div>';
		self::sales_chart( $scope );
		echo '</div>';
		Chrome::close();
	}

	/**
	 * Course list.
	 */
	public static function courses(): void {
		self::guard();
		$scope = Flow::scope();
		Chrome::open( 'دوره‌ها', 'آکادمی', $scope >= 0 ? self::button( 'دورهٔ جدید', 'new_course' ) : '' );
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cat    = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$inst   = isset( $_GET['inst'] ) ? absint( $_GET['inst'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<form class="lr-filters" method="get"><input type="hidden" name="page" value="lr-academy-courses">';
		echo '<label>وضعیت <select name="status"><option value="">همه</option>';
		foreach ( array(
			'draft'     => 'پیش‌نویس',
			'review'    => 'در انتظار بررسی',
			'ready'     => 'آماده انتشار',
			'published' => 'منتشرشده',
			'archived'  => 'بایگانی',
		) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>دسته <select name="cat"><option value="0">همه</option>';
		foreach ( Db::published( 'course_categories' ) as $row ) {
			echo '<option value="' . esc_attr( (string) $row['id'] ) . '" ' . selected( $cat, (int) $row['id'], false ) . '>' . esc_html( (string) $row['title'] ) . '</option>';
		}
		echo '</select></label>';
		if ( 0 === $scope ) {
			echo '<label>مدرس <select name="inst"><option value="0">همه</option>';
			foreach ( self::instructors() as $row ) {
				echo '<option value="' . esc_attr( (string) $row['id'] ) . '" ' . selected( $inst, (int) $row['id'], false ) . '>' . esc_html( (string) $row['name'] ) . '</option>';
			}
			echo '</select></label>';
		}
		echo '<button class="button">اعمال فیلتر</button></form>';
		$rows = self::course_rows( $scope, $status, $cat, $inst );
		if ( ! $rows ) {
			Chrome::empty( 'دوره‌ای با این فیلتر نیست.', $scope >= 0 ? self::button( 'اولین دوره را بسازید', 'new_course' ) : '' );
			Chrome::close();
			return;
		}
		echo '<div class="lr-scroll"><table class="widefat lr-table"><thead><tr><th>دوره</th><th>وضعیت</th><th>کامل بودن</th><th>دانشجو</th><th>درآمد</th><th>مدرس</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$pill = Flow::pill( $row );
			$pct  = Flow::percent( $row );
			$url  = Chrome::url(
				'lr-academy-course',
				array(
					'id'   => (int) $row['id'],
					'step' => 'info',
				)
			);
			echo '<tr><td><a href="' . esc_url( $url ) . '"><strong>' . esc_html( (string) $row['title'] ) . '</strong></a></td>';
			echo '<td>' . Chrome::pill( $pill[0], $pill[1] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<td><div class="lr-bar"><span style="width:' . esc_attr( (string) $pct ) . '%"></span></div><small>' . esc_html( Chrome::num( $pct ) ) . '٪</small></td>';
			echo '<td>' . esc_html( Chrome::num( (int) $row['students'] ) ) . '</td>';
			echo '<td>' . esc_html( Chrome::toman( (int) $row['revenue_show'] ) ) . '</td>';
			echo '<td>' . esc_html( (string) $row['instructor_name'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		Chrome::close();
	}

	/**
	 * Single-page editor with a step rail.
	 */
	public static function editor(): void {
		self::guard();
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$course = Db::find( 'courses', $id );
		if ( ! $course || ! Flow::can_course( $course ) ) {
			Chrome::open( 'دوره', 'آکادمی' );
			Chrome::empty( 'این دوره در دسترس شما نیست.', '<a class="button" href="' . esc_url( Chrome::url( 'lr-academy-courses' ) ) . '">بازگشت به دوره‌ها</a>' );
			Chrome::close();
			return;
		}
		$step  = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( (string) $_GET['step'] ) ) : 'info'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$steps = array(
			'info'    => 'اطلاعات',
			'outline' => 'سرفصل‌ها',
			'quiz'    => 'آزمون و گواهی',
			'price'   => 'قیمت و دسترسی',
			'seo'     => 'سئو و پیش‌نمایش',
			'publish' => 'انتشار',
		);
		if ( ! isset( $steps[ $step ] ) ) {
			$step = 'info';
		}
		Chrome::open( (string) $course['title'], 'آکادمی / دوره‌ها' );
		echo '<div class="lr-editor-layout"><aside class="lr-rail"><ol>';
		$checks = self::step_marks( $course );
		foreach ( $steps as $key => $label ) {
			$mark = ! empty( $checks[ $key ] ) ? 'is-ok' : '';
			$icon = ! empty( $checks[ $key ] ) ? '✓' : '✗';
			$on   = $key === $step ? 'is-on' : '';
			echo '<li class="' . esc_attr( $on ) . '"><a href="' . esc_url(
				Chrome::url(
					'lr-academy-course',
					array(
						'id'   => $id,
						'step' => $key,
					)
				)
			) . '"><span class="lr-mark ' . esc_attr( $mark ) . '">' . esc_html( $icon ) . '</span>' . esc_html( $label ) . '</a></li>';
		}
		echo '</ol></aside><div>';
		echo '<nav class="lr-tabs">';
		foreach ( $steps as $key => $label ) {
			echo '<a class="' . esc_attr( $key === $step ? 'is-on' : '' ) . '" href="' . esc_url(
				Chrome::url(
					'lr-academy-course',
					array(
						'id'   => $id,
						'step' => $key,
					)
				)
			) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav><section class="lr-panel">';
		if ( 'outline' === $step ) {
			self::outline( $course );
		} elseif ( 'quiz' === $step ) {
			self::quiz( $course );
		} elseif ( 'price' === $step ) {
			self::price( $course );
		} elseif ( 'seo' === $step ) {
			self::seo( $course );
		} elseif ( 'publish' === $step ) {
			self::publish( $course );
		} else {
			self::info( $course );
		}
		echo '</section></div></div>';
		Chrome::close();
	}

	/**
	 * Information step.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function info( array $course ): void {
		$err = isset( $_GET['lr_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['lr_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::form_open( 'course_info', (int) $course['id'] );
		echo '<h2>هویت دوره</h2>';
		self::field( 'title', 'عنوان', (string) $course['title'], 'title' === $err );
		self::field( 'slug', 'نامک', (string) $course['slug'] );
		echo '<label>دسته <select name="category_id"><option value="0">بدون دسته</option>';
		foreach ( Db::published( 'course_categories' ) as $row ) {
			echo '<option value="' . esc_attr( (string) $row['id'] ) . '" ' . selected( (int) $course['category_id'], (int) $row['id'], false ) . '>' . esc_html( (string) $row['title'] ) . '</option>';
		}
		echo '</select></label>';
		self::field( 'level', 'سطح', (string) $course['level'] );
		if ( 0 === Flow::scope() ) {
			echo '<label>مدرس <select name="instructor_id"><option value="0">انتخاب کنید</option>';
			foreach ( self::instructors() as $row ) {
				echo '<option value="' . esc_attr( (string) $row['id'] ) . '" ' . selected( (int) $course['instructor_id'], (int) $row['id'], false ) . '>' . esc_html( (string) $row['name'] ) . '</option>';
			}
			echo '</select></label>';
		}
		self::field( 'thumbnail', 'نشانی تصویر جلد', (string) $course['thumbnail'] );
		echo '<h2>توضیح</h2>';
		self::area( 'excerpt', 'توضیح کوتاه', (string) ( $course['excerpt'] ?? '' ) );
		self::area( 'description', 'توضیح کامل', (string) $course['description'] );
		self::area( 'outcomes', 'دستاوردها', (string) ( $course['outcomes'] ?? '' ) );
		self::area( 'prerequisites', 'پیش‌نیازها', (string) ( $course['prerequisites'] ?? '' ) );
		submit_button( 'ذخیره اطلاعات', 'primary' );
		echo '</form>';
	}

	/**
	 * Sections and lessons.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function outline( array $course ): void {
		$modules = Db::sorted( 'course_modules', 'course_id', (int) $course['id'] );
		echo '<div class="lr-outline" data-course="' . esc_attr( (string) $course['id'] ) . '">';
		if ( ! $modules ) {
			Chrome::empty( 'هنوز فصلی نیست. عنوان را بنویسید و فصل اول را اضافه کنید.' );
		}
		echo '<ul class="lr-sort" data-kind="modules">';
		$first = true;
		foreach ( $modules as $module ) {
			$lessons = Db::sorted( 'course_lessons', 'module_id', (int) $module['id'] );
			$seconds = 0;
			foreach ( $lessons as $lesson ) {
				$seconds += (int) $lesson['duration_seconds'];
			}
			echo '<li class="lr-section" data-id="' . esc_attr( (string) $module['id'] ) . '" draggable="true">';
			echo '<header class="lr-section-head"><button type="button" class="lr-handle" aria-label="جابه‌جایی">⋮⋮</button>';
			echo '<button type="button" class="lr-fold" aria-expanded="' . esc_attr( $first ? 'true' : 'false' ) . '">' . esc_html( (string) $module['title'] ) . '</button>';
			echo '<span class="lr-lesson-count lr-count">' . esc_html( Chrome::num( count( $lessons ) ) ) . '</span>';
			echo '<span class="lr-dur">' . esc_html( Chrome::num( (int) floor( $seconds / 60 ) ) . ' دقیقه' ) . '</span></header>';
			echo '<ul class="lr-lessons lr-sort" data-kind="lessons" data-module="' . esc_attr( (string) $module['id'] ) . '"' . ( $first ? '' : ' hidden' ) . '>';
			foreach ( $lessons as $lesson ) {
				self::lesson_row( $module, $lesson );
			}
			echo '</ul>';
			echo '<button type="button" class="button lr-add" data-act="add-lesson" data-module="' . esc_attr( (string) $module['id'] ) . '">+ درس</button>';
			echo '</li>';
			$first = false;
		}
		echo '</ul>';
		self::form_open( 'module', (int) $course['id'] );
		echo '<div class="lr-inline-add" data-course="' . esc_attr( (string) $course['id'] ) . '">';
		echo '<input name="title" placeholder="عنوان فصل تازه" required>';
		echo '<button class="button button-primary" type="submit">+ فصل</button></div></form>';
		echo '</div>';
		self::drawer( (int) $course['id'] );
	}

	/**
	 * Lesson fields the drawer and the row both use.
	 *
	 * @param  array<string, mixed> $module Module.
	 * @param  array<string, mixed> $lesson Lesson.
	 * @return array<string, mixed>
	 */
	public static function lesson_payload( array $module, array $lesson ): array {
		$state   = self::video_state( $lesson );
		$minutes = (int) floor( (int) $lesson['duration_seconds'] / 60 );
		$videos  = Db::where_id( 'lesson_videos', 'lesson_id', (int) $lesson['id'] );
		$video   = $videos ? $videos[0] : array(
			'provider'    => 'arvan_vod',
			'external_id' => '',
		);
		$files   = Db::where_id( 'lesson_attachments', 'lesson_id', (int) $lesson['id'] );
		return array(
			'id'             => (int) $lesson['id'],
			'module'         => (int) $module['id'],
			'title'          => (string) $lesson['title'],
			'type'           => (string) $lesson['type'],
			'type_label'     => self::type_label( (string) $lesson['type'] ),
			'type_icon'      => self::type_icon( (string) $lesson['type'] ),
			'content'        => (string) $lesson['content'],
			'minutes'        => $minutes,
			'duration_label' => Chrome::num( $minutes ) . ' دقیقه',
			'preview'        => (int) $lesson['is_preview'],
			'provider'       => (string) $video['provider'],
			'external_id'    => (string) $video['external_id'],
			'file_url'       => $files ? (string) $files[0]['storage_path'] : '',
			'badge'          => $state['label'],
			'badge_key'      => $state['key'],
		);
	}

	/**
	 * Visible name of a lesson type.
	 *
	 * @param string $type Type slug.
	 */
	private static function type_label( string $type ): string {
		$map = array(
			'video' => 'ویدیو',
			'text'  => 'متن',
			'quiz'  => 'آزمون',
			'file'  => 'فایل',
		);
		return $map[ $type ] ?? 'درس';
	}

	/**
	 * Compact mark for a lesson type.
	 *
	 * @param string $type Type slug.
	 */
	private static function type_icon( string $type ): string {
		$map = array(
			'video' => '▶',
			'text'  => '¶',
			'quiz'  => '؟',
			'file'  => '▤',
		);
		return $map[ $type ] ?? '•';
	}

	/**
	 * Video badge: ready, processing, or missing.
	 *
	 * @param  array<string, mixed> $lesson Lesson.
	 * @return array{key: string, label: string}
	 */
	private static function video_state( array $lesson ): array {
		if ( 'video' !== (string) $lesson['type'] ) {
			return array(
				'key'   => 'ready',
				'label' => self::type_label( (string) $lesson['type'] ),
			);
		}
		$videos = Db::where_id( 'lesson_videos', 'lesson_id', (int) $lesson['id'] );
		$video  = $videos ? $videos[0] : null;
		if ( ! $video || '' === (string) $video['external_id'] ) {
			return array(
				'key'   => 'pending',
				'label' => 'بدون ویدیو',
			);
		}
		if ( 'draft' === (string) ( $video['status'] ?? '' ) ) {
			return array(
				'key'   => 'review',
				'label' => 'در حال پردازش',
			);
		}
		return array(
			'key'   => 'ok',
			'label' => 'آماده',
		);
	}

	/**
	 * One compact lesson row.
	 *
	 * @param array<string, mixed> $module Module.
	 * @param array<string, mixed> $lesson Lesson.
	 */
	private static function lesson_row( array $module, array $lesson ): void {
		$payload = self::lesson_payload( $module, $lesson );
		echo '<li class="lr-row" draggable="true" data-id="' . esc_attr( (string) $lesson['id'] ) . '" data-lesson="' . esc_attr( (string) wp_json_encode( $payload ) ) . '">';
		echo '<button type="button" class="lr-handle" aria-label="جابه‌جایی">⋮⋮</button>';
		echo '<span class="lr-type" title="' . esc_attr( (string) $payload['type_label'] ) . '">' . esc_html( (string) $payload['type_icon'] ) . '</span>';
		echo '<span class="lr-row-title">' . esc_html( (string) $lesson['title'] ) . '</span>';
		echo '<span class="lr-row-dur">' . esc_html( $payload['duration_label'] ) . '</span>';
		echo Chrome::pill( (string) $payload['badge_key'], (string) $payload['badge'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $lesson['is_preview'] ) ) {
			echo Chrome::pill( 'ready', 'پیش‌نمایش' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<button type="button" class="lr-icon" data-act="edit" aria-label="ویرایش">✎</button>';
		echo '<button type="button" class="lr-icon" data-act="copy" aria-label="کپی">⧉</button>';
		echo '<button type="button" class="lr-icon" data-act="delete" aria-label="حذف">✕</button>';
		echo '</li>';
	}

	/**
	 * Lesson editor drawer. One form for every row.
	 *
	 * @param int $course_id Course id.
	 */
	private static function drawer( int $course_id ): void {
		echo '<div class="lr-drawer-back" data-act="close-drawer" hidden></div>';
		echo '<aside class="lr-drawer" hidden><h2>درس</h2>';
		self::form_open( 'lesson', $course_id );
		echo '<input type="hidden" name="module_id" value="0"><input type="hidden" name="lesson_id" value="0">';
		echo '<div id="lr-lesson-form">';
		self::field( 'title', 'عنوان درس', '' );
		echo '<label>نوع <select name="type"><option value="video">ویدیو</option><option value="text">متن</option><option value="quiz">آزمون</option><option value="file">فایل</option></select></label>';
		echo '<label>ارائه‌دهنده <select name="provider"><option value="arvan_vod">آروان‌کلاد</option><option value="upload">بارگذاری</option><option value="aparat">آپارات</option></select></label>';
		self::field( 'external_id', 'شناسه آروان یا نشانی ویدیو', '' );
		self::field( 'file_url', 'نشانی فایل', '' );
		self::field( 'duration', 'مدت (دقیقه)', '0' );
		self::area( 'content', 'متن درس', '' );
		echo '<label><input type="checkbox" name="is_preview" value="1"> پیش‌نمایش رایگان</label>';
		echo '<div class="lr-actions"><button class="button button-primary" type="submit">ذخیره درس</button>';
		echo '<button class="button" type="button" data-act="close-drawer">بستن</button></div></div></form></aside>';
	}

	/**
	 * Quiz builder and certificate preview.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function quiz( array $course ): void {
		global $wpdb;
		$table = Db::table( 'quizzes' );
		$quiz  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE scope_type = 'course' AND scope_id = %d LIMIT 1", (int) $course['id'] ), ARRAY_A );
		if ( $quiz ) {
			echo '<h2>سؤال‌ها</h2><ul class="lr-work">';
			$index = 1;
			foreach ( Db::sorted( 'quiz_questions', 'quiz_id', (int) $quiz['id'] ) as $question ) {
				echo '<li><span><strong>' . esc_html( Chrome::num( $index ) . '. ' . (string) $question['prompt'] ) . '</strong></span></li>';
				++$index;
			}
			echo '</ul>';
		} else {
			Chrome::empty( 'هنوز سؤالی نیست. اولین سؤال را پایین اضافه کنید.' );
		}
		echo '<h2>سؤال تازه</h2>';
		self::form_open( 'quiz', (int) $course['id'] );
		self::field( 'prompt', 'سؤال', '' );
		self::field( 'a', 'گزینه ۱', '' );
		self::field( 'b', 'گزینه ۲', '' );
		self::field( 'c', 'گزینه ۳', '' );
		self::field( 'answer', 'شماره پاسخ درست (۰ تا ۲)', '0' );
		echo '<label><input type="checkbox" name="certificate" value="1" ' . checked( 1, (int) $course['certificate_enabled'], false ) . '> گواهی پایان دوره فعال باشد</label>';
		submit_button( 'افزودن سؤال', 'secondary' );
		echo '</form>';
		$settings = Settings::get();
		$title    = (string) ( $settings['certificate_title'] ?? 'گواهی پایان دوره' );
		echo '<div class="lr-cert"><p>لایف‌روس</p><h3>' . esc_html( '' !== $title ? $title : 'گواهی پایان دوره' ) . '</h3><p>این گواهی تأیید می‌کند که دانشجو دورهٔ <strong>' . esc_html( (string) $course['title'] ) . '</strong> را گذرانده است.</p><p>تاریخ: ' . esc_html( Chrome::date( Db::now() ) ) . '</p></div>';
	}

	/**
	 * Price, discount window, plans, bundles, access length.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function price( array $course ): void {
		$err = isset( $_GET['lr_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['lr_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$in  = array();
		foreach ( Db::where_id( 'plan_courses', 'course_id', (int) $course['id'] ) as $row ) {
			$in[] = (int) $row['plan_id'];
		}
		self::form_open( 'course_price', (int) $course['id'] );
		echo '<h2>قیمت</h2>';
		echo '<label><input type="checkbox" name="is_free" value="1" ' . checked( 1, (int) $course['is_free'], false ) . '> رایگان</label>';
		self::field( 'price', 'قیمت (تومان)', (string) $course['price'], 'price' === $err );
		self::field( 'discount_price', 'قیمت با تخفیف', (string) ( $course['discount_price'] ?? '' ) );
		self::field( 'discount_starts', 'شروع تخفیف', self::local( (string) ( $course['discount_starts'] ?? '' ) ) );
		self::field( 'discount_ends', 'پایان تخفیف', self::local( (string) ( $course['discount_ends'] ?? '' ) ) );
		echo '<h2>دسترسی</h2>';
		self::field( 'access_days', 'مدت دسترسی (روز، صفر یعنی نامحدود)', (string) ( $course['access_days'] ?? '0' ) );
		echo '<h2>طرح‌ها و بسته‌ها</h2>';
		$plans = Db::published( 'subscription_plans' );
		if ( ! $plans ) {
			echo '<p>هنوز طرح فعالی نیست. از «اشتراک‌ها و طرح‌ها» یکی بسازید.</p>';
		}
		foreach ( $plans as $plan ) {
			echo '<label><input type="checkbox" name="plans[]" value="' . esc_attr( (string) $plan['id'] ) . '" ' . checked( in_array( (int) $plan['id'], $in, true ), true, false ) . '> ' . esc_html( (string) $plan['title'] ) . '</label>';
		}
		echo '<h3>بسته‌ها</h3>';
		foreach ( Settings::bundles() as $bundle ) {
			$ids = array_map( 'intval', (array) $bundle['course_ids'] );
			echo '<label><input type="checkbox" name="bundles[]" value="' . esc_attr( (string) $bundle['slug'] ) . '" ' . checked( in_array( (int) $course['id'], $ids, true ), true, false ) . '> ' . esc_html( (string) $bundle['title'] ) . '</label>';
		}
		submit_button( 'ذخیره قیمت', 'primary' );
		echo '</form>';
	}

	/**
	 * SEO fields and a public preview link.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function seo( array $course ): void {
		echo '<h2>سئو</h2>';
		self::form_open( 'course_seo', (int) $course['id'] );
		self::field( 'seo_title', 'عنوان سئو (Rank Math)', (string) $course['seo_title'] );
		self::area( 'seo_description', 'توضیح متا (Rank Math)', (string) $course['seo_description'] );
		submit_button( 'ذخیره سئو', 'primary' );
		echo '</form>';
		$url = home_url( '/academy/courses/' . $course['slug'] . '/' );
		echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( $url ) . '">پیش‌نمایش زنده</a></p>';
		echo '<p class="description">اگر Rank Math روی نوشته‌ها فعال باشد، همین عنوان و توضیح برای صفحهٔ دوره استفاده می‌شود.</p>';
	}

	/**
	 * Publish checklist.
	 *
	 * @param array<string, mixed> $course Course.
	 */
	private static function publish( array $course ): void {
		$checks = Flow::checklist( $course );
		$labels = array(
			'title'      => 'عنوان',
			'cover'      => 'جلد',
			'outline'    => 'حداقل یک بخش با یک درس',
			'price'      => 'قیمت یا رایگان',
			'instructor' => 'مدرس',
		);
		echo '<h2>پیش از انتشار</h2><ul class="lr-check">';
		foreach ( $labels as $key => $label ) {
			$ok = ! empty( $checks[ $key ] );
			echo '<li><span class="lr-mark ' . esc_attr( $ok ? 'is-ok' : '' ) . '">' . esc_html( $ok ? '✓' : '✗' ) . '</span> ' . esc_html( $label ) . '</li>';
		}
		echo '</ul>';
		$ready = Flow::ready( $course );
		self::form_open( 'course_pub', (int) $course['id'] );
		echo '<div class="lr-actions">';
		echo '<button class="button" name="intent" value="draft">ذخیره پیش‌نویس</button>';
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			echo '<button class="button button-primary" name="intent" value="review" ' . disabled( $ready, false, false ) . '>ارسال برای بررسی</button>';
		} else {
			echo '<button class="button button-primary" name="intent" value="publish" ' . disabled( $ready, false, false ) . '>انتشار</button>';
			echo '<button class="button" name="intent" value="archive">بایگانی</button>';
		}
		echo '</div>';
		self::field( 'scheduled_at', 'زمان انتشار', self::local( (string) ( $course['scheduled_at'] ?? '' ) ) );
		echo '<button class="button" name="intent" value="schedule" ' . disabled( $ready, false, false ) . '>زمان‌بندی انتشار</button>';
		echo '</form>';
		if ( ! $ready ) {
			echo '<p class="lr-error">تا وقتی موارد ضروری انجام نشده، انتشار بسته است.</p>';
		}
	}

	/**
	 * Step rail ticks.
	 *
	 * @param  array<string, mixed> $course Course.
	 * @return array<string, bool>
	 */
	private static function step_marks( array $course ): array {
		$checks = Flow::checklist( $course );
		global $wpdb;
		$quiz = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM `' . Db::table( 'quizzes' ) . '` WHERE scope_type = %s AND scope_id = %d LIMIT 1', 'course', (int) $course['id'] ) );
		return array(
			'info'    => ! empty( $checks['title'] ) && '' !== trim( (string) ( $course['excerpt'] ?? '' ) ),
			'outline' => ! empty( $checks['outline'] ),
			'quiz'    => $quiz > 0 || ! empty( $course['certificate_enabled'] ),
			'price'   => ! empty( $checks['price'] ),
			'seo'     => '' !== trim( (string) $course['seo_title'] ),
			'publish' => 'published' === $course['status'],
		);
	}

	/**
	 * KPI cards. Instructors see their share, not the company total.
	 *
	 * @param int $scope Instructor scope.
	 */
	private static function kpis( int $scope ): void {
		global $wpdb;
		$courses = Db::table( 'courses' );
		$where   = $scope > 0 ? $wpdb->prepare( ' AND instructor_id = %d', $scope ) : '';
		$from    = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$prev    = gmdate( 'Y-m-d H:i:s', time() - ( 60 * DAY_IN_SECONDS ) );
		$count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$courses}` WHERE status = 'published' {$where}" );
		$now_c   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$courses}` WHERE status = 'published' AND published_at >= %s {$where}", $from ) );
		$prev_c  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$courses}` WHERE status = 'published' AND published_at >= %s AND published_at < %s {$where}", $prev, $from ) );
		if ( $scope > 0 ) {
			$students = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT e.student_id) FROM `' . Db::table( 'enrollments' ) . "` e JOIN `{$courses}` c ON c.id = e.course_id WHERE c.instructor_id = %d", $scope ) );
		} else {
			$students = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . Db::table( 'students' ) . '`' );
		}
		$now_s     = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'students' ) . '` WHERE created_at >= %s', $from ) );
		$prev_s    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'students' ) . '` WHERE created_at >= %s AND created_at < %s', $prev, $from ) );
		$questions = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . Db::table( 'questions' ) . "` WHERE status = 'pending'" );
		$now_q     = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'questions' ) . '` WHERE created_at >= %s', $from ) );
		$prev_q    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'questions' ) . '` WHERE created_at >= %s AND created_at < %s', $prev, $from ) );
		$summary   = Metrics::summary( $from, Db::now(), $scope > 0 ? $scope : 0 );
		$revenue   = (int) $summary['current']['revenue'];
		$before    = (int) $summary['previous']['revenue'];
		if ( $scope > 0 ) {
			$share   = (int) ( Db::find( 'instructors', $scope )['share_percent'] ?? 0 );
			$revenue = (int) floor( ( $revenue * $share ) / 100 );
			$before  = (int) floor( ( $before * $share ) / 100 );
		}
		$show_money = ( 0 === $scope && current_user_can( 'lr_view_finance' ) ) || $scope > 0;
		echo '<div class="lr-kpis">';
		self::kpi_card( 'دوره‌های منتشرشده', Chrome::num( $count ), self::trend( $now_c, $prev_c ), '▣' );
		self::kpi_card( 'دانشجوها', Chrome::num( $students ), self::trend( $now_s, $prev_s ), '☺' );
		self::kpi_card( 'پرسش باز', Chrome::num( $questions ), self::trend( $now_q, $prev_q ), '?' );
		if ( $show_money ) {
			self::kpi_card( $scope > 0 ? 'سهم شما' : 'درآمد پرداخت‌شده', Chrome::toman( $revenue ), self::trend( $revenue, $before ), '﷼' );
		}
		echo '</div>';
	}

	/**
	 * One KPI with an icon and a 30-day trend.
	 *
	 * @param string $label Label.
	 * @param string $value Display value.
	 * @param int    $trend Percent versus the previous period.
	 * @param string $icon  Short mark.
	 */
	private static function kpi_card( string $label, string $value, int $trend, string $icon ): void {
		$class = $trend > 0 ? 'is-up' : ( $trend < 0 ? 'is-down' : '' );
		$sign  = $trend > 0 ? '+' : '';
		echo '<article class="lr-kpi"><div class="lr-kpi-top"><span>' . esc_html( $label ) . '</span><span class="lr-ico" aria-hidden="true">' . esc_html( $icon ) . '</span></div>';
		echo '<strong>' . esc_html( $value ) . '</strong>';
		echo '<span class="lr-trend ' . esc_attr( $class ) . '">' . esc_html( $sign . Chrome::num( $trend ) . '٪ نسبت به ۳۰ روز قبل' ) . '</span></article>';
	}

	/**
	 * Percent change. Zero previous period with growth is +100.
	 *
	 * @param int $now  Current.
	 * @param int $prev Previous.
	 */
	private static function trend( int $now, int $prev ): int {
		if ( 0 === $prev ) {
			return $now > 0 ? 100 : 0;
		}
		return (int) round( ( ( $now - $prev ) * 100 ) / $prev );
	}

	/**
	 * Small sales chart for the last 30 days.
	 *
	 * @param int $scope Scope.
	 */
	private static function sales_chart( int $scope ): void {
		if ( 0 === $scope && ! current_user_can( 'lr_view_finance' ) ) {
			return;
		}
		$from    = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$summary = Metrics::summary( $from, Db::now(), $scope > 0 ? $scope : 0 );
		$days    = isset( $summary['current']['days'] ) && is_array( $summary['current']['days'] ) ? $summary['current']['days'] : array();
		echo '<section class="lr-panel"><h2>فروش ۳۰ روز</h2>';
		if ( ! $days ) {
			Chrome::empty( 'در این بازه فروشی ثبت نشده.', '<a class="button" href="' . esc_url( Chrome::url( 'lr-academy-reports' ) ) . '">گزارش‌ها</a>' );
			echo '</section>';
			return;
		}
		$values = array();
		foreach ( $days as $day ) {
			$values[] = (int) $day['amount'];
		}
		$max    = max( 1, max( $values ) );
		$width  = 280;
		$height = 72;
		$count  = count( $values );
		$points = array();
		foreach ( $values as $index => $value ) {
			$x        = 1 === $count ? 0 : ( $index / ( $count - 1 ) ) * $width;
			$y        = $height - ( ( $value / $max ) * ( $height - 8 ) ) - 4;
			$points[] = round( $x, 1 ) . ',' . round( $y, 1 );
		}
		echo '<svg class="lr-spark" viewBox="0 0 ' . esc_attr( (string) $width ) . ' ' . esc_attr( (string) $height ) . '" role="img" aria-label="نمودار فروش"><polyline points="' . esc_attr( implode( ' ', $points ) ) . '"></polyline></svg>';
		echo '</section>';
	}

	/**
	 * Work list.
	 *
	 * @param  int $scope Scope.
	 * @return array<int, array{label: string, url: string, meta: string}>
	 */
	private static function today_cards( int $scope ): void {
		$groups = self::today_groups( $scope );
		$any    = false;
		foreach ( $groups as $group ) {
			if ( $group['rows'] ) {
				$any = true;
			}
		}
		if ( ! $any ) {
			Chrome::empty( 'کار بازی برای امروز نمانده است.', '<a class="button button-primary" href="' . esc_url( Chrome::url( 'lr-academy-courses' ) ) . '">مشاهده دوره‌ها</a>' );
			return;
		}
		echo '<div class="lr-work-grid">';
		foreach ( $groups as $group ) {
			if ( ! $group['rows'] ) {
				continue;
			}
			echo '<section class="lr-panel"><header class="lr-card-head"><h2>' . esc_html( $group['title'] ) . '</h2><span class="lr-count">' . esc_html( Chrome::num( count( $group['rows'] ) ) ) . '</span></header>';
			$shown = array_slice( $group['rows'], 0, 3 );
			foreach ( $shown as $row ) {
				echo '<div class="lr-task"><div><strong>' . esc_html( $row['label'] ) . '</strong><small>' . esc_html( $row['meta'] ) . '</small></div>';
				if ( ! empty( $row['form'] ) ) {
					echo $row['form']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with escaped attributes below.
				} else {
					echo '<a class="button button-primary" href="' . esc_url( $row['url'] ) . '">' . esc_html( $group['action'] ) . '</a>';
				}
				echo '</div>';
			}
			echo '<a class="lr-all" href="' . esc_url( $group['all'] ) . '">همه</a></section>';
		}
		echo '</div>';
	}

	/**
	 * Grouped work, at most a short list per card.
	 *
	 * @param  int $scope Scope.
	 * @return array<string, array{title: string, action: string, all: string, rows: array<int, array<string, string>>}>
	 */
	private static function today_groups( int $scope ): array {
		global $wpdb;
		$where  = $scope > 0 ? $wpdb->prepare( ' AND instructor_id = %d', $scope ) : '';
		$table  = Db::table( 'courses' );
		$drafts = array();
		$rows   = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE status = 'draft' {$where} ORDER BY updated_at DESC LIMIT 12", ARRAY_A );
		foreach ( (array) $rows as $row ) {
			if ( Flow::percent( $row ) >= 100 ) {
				continue;
			}
			$drafts[] = array(
				'label' => (string) $row['title'],
				'meta'  => Chrome::date( (string) $row['updated_at'] ) . ' — ' . Chrome::num( Flow::percent( $row ) ) . '٪',
				'url'   => Chrome::url(
					'lr-academy-course',
					array(
						'id'   => (int) $row['id'],
						'step' => 'publish',
					)
				),
				'form'  => '',
			);
		}
		$questions = array();
		$pending   = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'questions' ) . "` WHERE status = 'pending' ORDER BY id DESC LIMIT 12", ARRAY_A );
		foreach ( (array) $pending as $row ) {
			$questions[] = array(
				'label' => wp_trim_words( (string) $row['body'], 8, '…' ),
				'meta'  => Chrome::date( (string) $row['created_at'] ),
				'url'   => Chrome::url( 'lr-academy-qa' ),
				'form'  => '',
			);
		}
		$pays = array();
		$subs = array();
		if ( 0 === $scope ) {
			$orders = $wpdb->get_results( 'SELECT id, code, created_at FROM `' . Db::table( 'orders' ) . "` WHERE status = 'pending' ORDER BY id DESC LIMIT 12", ARRAY_A );
			foreach ( (array) $orders as $row ) {
				$pays[] = array(
					'label' => (string) $row['code'],
					'meta'  => Chrome::date( (string) $row['created_at'] ),
					'url'   => Chrome::url( 'lr-academy-order', array( 'id' => (int) $row['id'] ) ),
					'form'  => '',
				);
			}
			$soon = gmdate( 'Y-m-d H:i:s', time() + ( 3 * DAY_IN_SECONDS ) );
			$list = $wpdb->get_results( $wpdb->prepare( 'SELECT id, ends_at FROM `' . Db::table( 'subscriptions' ) . "` WHERE status = 'active' AND ends_at <= %s AND reminded_at IS NULL ORDER BY ends_at ASC LIMIT 12", $soon ), ARRAY_A );
			foreach ( (array) $list as $row ) {
				$subs[] = array(
					'label' => 'اشتراک تا ' . Chrome::date( (string) $row['ends_at'] ),
					'meta'  => Chrome::date( (string) $row['ends_at'] ),
					'url'   => Chrome::url( 'lr-academy-plans' ),
					'form'  => self::remind_button( (int) $row['id'] ),
				);
			}
		}
		$videos  = array();
		$lessons = $wpdb->get_results( 'SELECT l.title, m.course_id, c.updated_at FROM `' . Db::table( 'course_lessons' ) . '` l JOIN `' . Db::table( 'course_modules' ) . '` m ON m.id = l.module_id JOIN `' . Db::table( 'courses' ) . '` c ON c.id = m.course_id LEFT JOIN `' . Db::table( 'lesson_videos' ) . "` v ON v.lesson_id = l.id WHERE l.type = 'video' AND (v.id IS NULL OR v.external_id = '') ORDER BY l.id DESC LIMIT 12", ARRAY_A );
		foreach ( (array) $lessons as $row ) {
			$course = Db::find( 'courses', (int) $row['course_id'] );
			if ( ! $course || ! Flow::can_course( $course ) ) {
				continue;
			}
			$videos[] = array(
				'label' => (string) $row['title'],
				'meta'  => Chrome::date( (string) $row['updated_at'] ),
				'url'   => Chrome::url(
					'lr-academy-course',
					array(
						'id'   => (int) $row['course_id'],
						'step' => 'outline',
					)
				),
				'form'  => '',
			);
		}
		return array(
			'drafts'    => array(
				'title'  => 'دوره‌های ناقص',
				'action' => 'تکمیل دوره',
				'all'    => Chrome::url( 'lr-academy-courses', array( 'status' => 'draft' ) ),
				'rows'   => $drafts,
			),
			'questions' => array(
				'title'  => 'پرسش بی‌پاسخ',
				'action' => 'پاسخ',
				'all'    => Chrome::url( 'lr-academy-qa' ),
				'rows'   => $questions,
			),
			'pays'      => array(
				'title'  => 'پرداخت در انتظار',
				'action' => 'بررسی پرداخت',
				'all'    => Chrome::url( 'lr-academy-orders', array( 'status' => 'pending' ) ),
				'rows'   => $pays,
			),
			'subs'      => array(
				'title'  => 'اشتراک رو به پایان',
				'action' => 'ارسال یادآوری',
				'all'    => Chrome::url( 'lr-academy-plans' ),
				'rows'   => $subs,
			),
			'videos'    => array(
				'title'  => 'درس بدون ویدیو',
				'action' => 'افزودن ویدیو',
				'all'    => Chrome::url( 'lr-academy-courses' ),
				'rows'   => $videos,
			),
		);
	}

	/**
	 * Reminder button for one subscription.
	 *
	 * @param int $id Subscription id.
	 */
	private static function remind_button( int $id ): string {
		$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$html .= wp_nonce_field( 'lr_academy_save', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="remind">';
		$html .= '<input type="hidden" name="subscription_id" value="' . esc_attr( (string) $id ) . '">';
		$html .= '<button class="button button-primary" type="submit">ارسال یادآوری</button></form>';
		return $html;
	}

	/**
	 * First-run checklist.
	 */
	private static function onboarding(): void {
		if ( '1' === (string) get_user_meta( get_current_user_id(), 'lr_academy_onboarding_done', true ) ) {
			return;
		}
		$settings = Settings::get();
		$provider = ! empty( $settings['provider_ready'] ) || '' !== (string) ( $settings['arvan_api_key'] ?? '' );
		global $wpdb;
		$plans = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . Db::table( 'subscription_plans' ) . '`' );
		$any   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . Db::table( 'courses' ) . '`' );
		$live  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . Db::table( 'courses' ) . "` WHERE status = 'published'" );
		$items = array(
			array( $provider, 'ارائه‌دهنده ویدیو را تنظیم کنید', Chrome::url( 'lr-academy-settings' ) ),
			array( $plans > 0, 'یک طرح اشتراک بسازید', Chrome::url( 'lr-academy-plans' ) ),
			array( $any > 0, 'اولین دوره را بسازید', Chrome::url( 'lr-academy-courses' ) ),
			array( $live > 0, 'یک دوره را منتشر کنید', Chrome::url( 'lr-academy-courses', array( 'status' => 'draft' ) ) ),
		);
		$done  = 0;
		echo '<section class="lr-panel lr-onboard"><h2>شروع آکادمی</h2><ol class="lr-steps">';
		$index = 1;
		foreach ( $items as $item ) {
			if ( $item[0] ) {
				++$done;
			}
			echo '<li class="' . esc_attr( $item[0] ? 'is-done' : '' ) . '"><a href="' . esc_url( $item[2] ) . '"><span>' . esc_html( Chrome::num( $index ) ) . '</span>' . esc_html( $item[1] ) . '</a></li>';
			++$index;
		}
		echo '</ol><div class="lr-step-bar"><span style="width:' . esc_attr( (string) ( $done * 25 ) ) . '%"></span></div>';
		if ( 4 === $done ) {
			self::form_open( 'dismiss', 0 );
			submit_button( 'بستن راهنما', 'secondary' );
			echo '</form>';
		}
		echo '</section>';
	}

	/**
	 * Filtered course rows.
	 *
	 * @param  int    $scope  Scope.
	 * @param  string $status Filter.
	 * @param  int    $cat    Category.
	 * @param  int    $inst   Instructor.
	 * @return array<int, array<string, mixed>>
	 */
	private static function course_rows( int $scope, string $status, int $cat, int $inst ): array {
		global $wpdb;
		$sql  = 'SELECT c.*, COALESCE(i.name, "") AS instructor_name FROM `' . Db::table( 'courses' ) . '` c LEFT JOIN `' . Db::table( 'instructors' ) . '` i ON i.id = c.instructor_id WHERE 1=1';
		$args = array();
		if ( $scope > 0 ) {
			$sql   .= ' AND c.instructor_id = %d';
			$args[] = $scope;
		} elseif ( $scope < 0 ) {
			return array();
		}
		if ( 'published' === $status || 'archived' === $status ) {
			$sql   .= ' AND c.status = %s';
			$args[] = $status;
		} elseif ( 'review' === $status || 'ready' === $status ) {
			$sql   .= " AND c.status = 'draft' AND c.workflow = %s";
			$args[] = $status;
		} elseif ( 'draft' === $status ) {
			$sql .= " AND c.status = 'draft' AND c.workflow = ''";
		}
		if ( $cat > 0 ) {
			$sql   .= ' AND c.category_id = %d';
			$args[] = $cat;
		}
		if ( $inst > 0 && 0 === $scope ) {
			$sql   .= ' AND c.instructor_id = %d';
			$args[] = $inst;
		}
		$sql .= ' ORDER BY c.id DESC LIMIT 80';
		$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $row ) {
			$gross = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(i.amount_toman),0) FROM `' . Db::table( 'order_items' ) . '` i JOIN `' . Db::table( 'orders' ) . "` o ON o.id = i.order_id WHERE i.item_type = 'course' AND i.item_id = %d AND o.status = 'paid'", (int) $row['id'] ) );
			$share = 100;
			if ( $scope > 0 ) {
				$share = (int) ( Db::find( 'instructors', $scope )['share_percent'] ?? 0 );
			}
			$row['students']     = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'enrollments' ) . "` WHERE course_id = %d AND status = 'active'", (int) $row['id'] ) );
			$row['revenue_show'] = (int) floor( ( $gross * $share ) / 100 );
			$out[]               = $row;
		}
		return $out;
	}

	/**
	 * Instructors.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function instructors(): array {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'instructors' ) . "` WHERE status = 'published' ORDER BY name ASC", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Open a save form.
	 *
	 * @param string $task   Action.
	 * @param int    $course Course id.
	 */
	private static function form_open( string $task, int $course ): void {
		echo '<form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="' . esc_attr( $task ) . '">';
		if ( $course > 0 ) {
			echo '<input type="hidden" name="course_id" value="' . esc_attr( (string) $course ) . '">';
		}
	}

	/**
	 * Text field.
	 *
	 * @param string $name    Name.
	 * @param string $label   Label.
	 * @param string $value   Value.
	 * @param bool   $invalid Invalid state.
	 */
	private static function field( string $name, string $label, string $value, bool $invalid = false ): void {
		$type = str_contains( $name, 'starts' ) || str_contains( $name, 'ends' ) || 'scheduled_at' === $name ? 'datetime-local' : 'text';
		if ( in_array( $name, array( 'price', 'discount_price', 'duration', 'access_days', 'answer', 'capacity' ), true ) ) {
			$type = 'number';
		}
		echo '<label>' . esc_html( $label );
		echo '<input class="' . esc_attr( $invalid ? 'is-invalid' : '' ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		if ( $invalid ) {
			echo '<span class="lr-error">این مورد لازم است.</span>';
		}
		echo '</label>';
	}

	/**
	 * Textarea.
	 *
	 * @param string $name  Name.
	 * @param string $label Label.
	 * @param string $value Value.
	 */
	private static function area( string $name, string $label, string $value ): void {
		echo '<label>' . esc_html( $label ) . '<textarea name="' . esc_attr( $name ) . '" rows="4">' . esc_textarea( $value ) . '</textarea></label>';
	}

	/**
	 * UTC mysql datetime as a datetime-local value.
	 *
	 * @param string $utc UTC.
	 */
	private static function local( string $utc ): string {
		if ( '' === $utc ) {
			return '';
		}
		$stamp = strtotime( $utc . ' UTC' );
		return $stamp ? gmdate( 'Y-m-d\TH:i', $stamp ) : '';
	}

	/**
	 * Primary action form that only creates a course.
	 *
	 * @param string $label Label.
	 * @param string $task  Action.
	 */
	private static function button( string $label, string $task ): string {
		$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$html .= wp_nonce_field( 'lr_academy_save', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="' . esc_attr( $task ) . '">';
		$html .= '<button class="button button-primary">' . esc_html( $label ) . '</button></form>';
		return $html;
	}

	/**
	 * Capability gate.
	 */
	private static function guard(): void {
		if ( ! current_user_can( 'lr_academy_access' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
	}
}
