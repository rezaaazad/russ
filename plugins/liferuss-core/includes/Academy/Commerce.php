<?php
/**
 * Plans, reports, and Academy settings.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Admin\Chrome;
use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\Settings\Settings as CoreSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Subscription, report, and settings screens.
 */
class Commerce {

	/**
	 * Plans and subscribers.
	 */
	public static function plans(): void {
		self::guard();
		Chrome::open( 'اشتراک‌ها و طرح‌ها', 'آکادمی' );
		global $wpdb;
		$plans = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'subscription_plans' ) . '` ORDER BY id DESC', ARRAY_A );
		echo '<div class="lr-split"><section>';
		if ( ! $plans ) {
			Chrome::empty( 'طرحی نیست. فرم کنار صفحه اولین طرح را می‌سازد.' );
		}
		foreach ( (array) $plans as $plan ) {
			$ids = Db::where_id( 'plan_courses', 'plan_id', (int) $plan['id'] );
			echo '<article class="lr-card"><strong>' . esc_html( (string) $plan['title'] ) . '</strong>';
			echo '<span>' . esc_html( Chrome::toman( (int) $plan['price'] ) . ' / ' . ( 'year' === $plan['billing_interval'] ? 'سال' : 'ماه' ) ) . '</span>';
			echo '<span>' . esc_html( Chrome::num( count( $ids ) ) . ' دوره' ) . '</span></article>';
		}
		echo '<h2>مشترک‌ها</h2>';
		$subs = $wpdb->get_results( 'SELECT s.*, st.display_name FROM `' . Db::table( 'subscriptions' ) . '` s JOIN `' . Db::table( 'students' ) . '` st ON st.id = s.student_id ORDER BY s.ends_at ASC LIMIT 40', ARRAY_A );
		if ( ! $subs ) {
			Chrome::empty( 'مشترک فعالی نیست.' );
		} else {
			echo '<div class="lr-scroll"><table class="widefat"><thead><tr><th>دانشجو</th><th>وضعیت</th><th>پایان</th><th>یادآوری</th></tr></thead><tbody>';
			$soon = time() + ( 3 * DAY_IN_SECONDS );
			foreach ( (array) $subs as $sub ) {
				$state = (string) $sub['status'];
				if ( 'active' === $state && strtotime( (string) $sub['ends_at'] . ' UTC' ) <= $soon ) {
					$state = 'pending';
					$label = 'رو به پایان';
				} else {
					$label = Chrome::status( $state );
				}
				echo '<tr><td>' . esc_html( (string) $sub['display_name'] ) . '</td><td>' . Chrome::pill( $state, $label ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td>' . esc_html( Chrome::date( (string) $sub['ends_at'] ) ) . '</td>';
				echo '<td>' . esc_html( $sub['reminded_at'] ? 'ارسال شده' : 'ارسال نشده' ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '</section>';
		if ( current_user_can( 'lr_academy_manage' ) ) {
			echo '<section class="lr-panel"><h2>طرح</h2><form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="plan_save">';
			echo '<label>عنوان<input name="title" required></label><label>نامک<input name="slug"></label>';
			echo '<label>قیمت (تومان)<input type="number" name="price" min="0" required></label>';
			echo '<label>مدت <select name="interval"><option value="month">ماهانه</option><option value="year">سالانه</option></select></label>';
			echo '<label>توضیح<textarea name="description" rows="3"></textarea></label><p><strong>دوره‌های شامل</strong></p>';
			$courses = $wpdb->get_results( 'SELECT id, title FROM `' . Db::table( 'courses' ) . '` ORDER BY title ASC LIMIT 60', ARRAY_A );
			foreach ( (array) $courses as $course ) {
				echo '<label><input type="checkbox" name="course_ids[]" value="' . esc_attr( (string) $course['id'] ) . '"> ' . esc_html( (string) $course['title'] ) . '</label>';
			}
			submit_button( 'ذخیره طرح' );
			echo '</form></section>';
		}
		echo '</div>';
		Chrome::close();
	}

	/**
	 * Sales, funnel, completion, and drop-off.
	 */
	public static function reports(): void {
		self::guard();
		Chrome::open( 'گزارش‌ها', 'آکادمی' );
		$preset = Jalali::presets()['30'];
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : $preset['from']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) ) : $preset['to']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="lr-tabs">';
		foreach ( Jalali::presets() as $row ) {
			$url = Chrome::url(
				'lr-academy-reports',
				array(
					'from' => $row['from'],
					'to'   => $row['to'],
				)
			);
			echo '<a href="' . esc_url( $url ) . '"><bdi dir="rtl">' . esc_html( $row['label'] ) . '</bdi></a>';
		}
		echo '</div>';
		$start = Jalali::filter_utc( $from, false );
		$end   = Jalali::filter_utc( $to, true );
		if ( '' === $start ) {
			$start = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		}
		if ( '' === $end ) {
			$end = Db::now();
		}
		$scope = Flow::scope();
		$data  = Metrics::summary( $start, $end, $scope > 0 ? $scope : 0 );
		$now   = $data['current'];
		echo '<div class="lr-kpis">';
		$revenue = (int) $now['revenue'];
		if ( $scope > 0 ) {
			$share   = (int) ( Db::find( 'instructors', $scope )['share_percent'] ?? 0 );
			$revenue = (int) floor( ( $revenue * $share ) / 100 );
		}
		echo '<article class="lr-kpi"><span>' . esc_html( $scope > 0 ? 'سهم شما' : 'فروش' ) . '</span><strong>' . esc_html( Chrome::toman( $revenue ) ) . '</strong></article>';
		echo '<article class="lr-kpi"><span>دانشجوی جدید</span><strong>' . esc_html( Chrome::num( (int) $now['new_students'] ) ) . '</strong></article>';
		echo '<article class="lr-kpi"><span>نرخ تبدیل</span><strong>' . esc_html( Chrome::num( (float) $now['conversion'] ) ) . '٪</strong></article>';
		echo '</div>';
		self::funnel( $start, $end, $scope );
		self::completion( $scope );
		self::dropoff( $scope );
		Chrome::close();
	}

	/**
	 * Payment, video, reminders, certificate.
	 */
	public static function settings(): void {
		if ( ! current_user_can( 'lr_academy_manage' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		Chrome::open( 'تنظیمات', 'آکادمی' );
		$academy = Settings::get();
		$pay     = CoreSettings::get( 'payments' );
		echo '<form class="lr-form lr-panel" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="settings">';
		echo '<h2>پرداخت</h2>';
		echo '<label>مرچنت زرین‌پال<input name="merchant_id" value="' . esc_attr( (string) $pay['merchant_id'] ) . '"></label>';
		echo '<p class="description">برای آزمون محلی، مرچنت را <code>mock</code> بگذارید و سندباکس را روشن نگه دارید. پرداخت بدون تماس شبکه‌ای تأیید می‌شود.</p>';
		echo '<label><input type="checkbox" name="sandbox" value="1" ' . checked( '1', (string) $pay['sandbox'], false ) . '> سندباکس زرین‌پال</label>';
		echo '<h2>ویدیو</h2><label>ارائه‌دهنده <select name="provider">';
		foreach ( array(
			'arvan_vod' => 'آروان‌کلاد',
			'upload'    => 'بارگذاری',
			'aparat'    => 'آپارات',
		) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) $academy['default_provider'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>کلید API آروان<input name="arvan_api_key" value="' . esc_attr( (string) $academy['arvan_api_key'] ) . '"></label>';
		echo '<h2>یادآوری اشتراک</h2><label>متن یادآوری سه روز مانده<textarea name="reminder_expiring" rows="3">' . esc_textarea( (string) ( $academy['reminder_expiring'] ?? 'اشتراک شما تا سه روز دیگر تمام می‌شود.' ) ) . '</textarea></label>';
		echo '<h2>گواهی</h2><label>عنوان قالب گواهی<input name="certificate_title" value="' . esc_attr( (string) ( $academy['certificate_title'] ?? 'گواهی پایان دوره' ) ) . '"></label>';
		submit_button( 'ذخیره تنظیمات' );
		echo '</form>';
		Chrome::close();
	}

	/**
	 * Visit, enroll, complete.
	 *
	 * @param string $start Start UTC.
	 * @param string $end   End UTC.
	 * @param int    $scope Scope.
	 */
	private static function funnel( string $start, string $end, int $scope ): void {
		global $wpdb;
		$course_sql = $scope > 0 ? $wpdb->prepare( ' AND course_id IN (SELECT id FROM `' . Db::table( 'courses' ) . '` WHERE instructor_id = %d)', $scope ) : '';
		$views      = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(views),0) FROM `' . Db::table( 'course_views_daily' ) . '` WHERE stat_date BETWEEN %s AND %s' . $course_sql, substr( $start, 0, 10 ), substr( $end, 0, 10 ) ) );
		$enrolls    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'enrollments' ) . '` WHERE granted_at BETWEEN %s AND %s' . $course_sql, $start, $end ) );
		$done       = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT CONCAT(student_id, "-", course_id)) FROM `' . Db::table( 'course_progress' ) . '` WHERE completed = 1 AND updated_at BETWEEN %s AND %s' . $course_sql, $start, $end ) );
		echo '<section class="lr-panel"><h2>قیف</h2><div class="lr-kpis">';
		echo '<article class="lr-kpi"><span>بازدید</span><strong>' . esc_html( Chrome::num( $views ) ) . '</strong></article>';
		echo '<article class="lr-kpi"><span>ثبت‌نام</span><strong>' . esc_html( Chrome::num( $enrolls ) ) . '</strong></article>';
		echo '<article class="lr-kpi"><span>تکمیل درس</span><strong>' . esc_html( Chrome::num( $done ) ) . '</strong></article>';
		echo '</div></section>';
	}

	/**
	 * Completion rate per course.
	 *
	 * @param int $scope Scope.
	 */
	private static function completion( int $scope ): void {
		global $wpdb;
		$sql = 'SELECT id, title FROM `' . Db::table( 'courses' ) . "` WHERE status = 'published'";
		if ( $scope > 0 ) {
			$sql = $wpdb->prepare( $sql . ' AND instructor_id = %d', $scope );
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		echo '<section class="lr-panel"><h2>نرخ تکمیل</h2>';
		if ( ! $rows ) {
			Chrome::empty( 'دورهٔ منتشرشده‌ای برای این گزارش نیست.' );
			echo '</section>';
			return;
		}
		echo '<div class="lr-scroll"><table class="widefat"><thead><tr><th>دوره</th><th>دانشجو</th><th>تکمیل</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			$students = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'enrollments' ) . "` WHERE course_id = %d AND status = 'active'", (int) $row['id'] ) );
			$finished = 0;
			$ids      = $wpdb->get_col( $wpdb->prepare( 'SELECT student_id FROM `' . Db::table( 'enrollments' ) . "` WHERE course_id = %d AND status = 'active'", (int) $row['id'] ) );
			foreach ( (array) $ids as $student_id ) {
				if ( Progress::percent( (int) $student_id, (int) $row['id'] ) >= 100 ) {
					++$finished;
				}
			}
			$rate = $students > 0 ? (int) floor( ( $finished * 100 ) / $students ) : 0;
			echo '<tr><td>' . esc_html( (string) $row['title'] ) . '</td><td>' . esc_html( Chrome::num( $students ) ) . '</td><td>' . esc_html( Chrome::num( $rate ) ) . '٪</td></tr>';
		}
		echo '</tbody></table></div></section>';
	}

	/**
	 * Lessons where students stop.
	 *
	 * @param int $scope Scope.
	 */
	private static function dropoff( int $scope ): void {
		global $wpdb;
		$sql  = 'SELECT l.title, COUNT(p.id) AS stopped FROM `' . Db::table( 'course_lessons' ) . '` l JOIN `' . Db::table( 'course_modules' ) . '` m ON m.id = l.module_id JOIN `' . Db::table( 'courses' ) . '` c ON c.id = m.course_id LEFT JOIN `' . Db::table( 'course_progress' ) . '` p ON p.lesson_id = l.id AND p.completed = 0';
		$sql .= " WHERE c.status = 'published'";
		if ( $scope > 0 ) {
			$sql = $wpdb->prepare( $sql . ' AND c.instructor_id = %d', $scope );
		}
		$sql .= ' GROUP BY l.id ORDER BY stopped DESC LIMIT 8';
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		echo '<section class="lr-panel"><h2>درس‌هایی که دانشجو رها می‌کند</h2>';
		if ( ! $rows ) {
			Chrome::empty( 'هنوز داده‌ای از رها کردن درس نیست.' );
			echo '</section>';
			return;
		}
		echo '<ul class="lr-work">';
		foreach ( (array) $rows as $row ) {
			echo '<li><span>' . esc_html( (string) $row['title'] ) . '</span><span>' . esc_html( Chrome::num( (int) $row['stopped'] ) ) . '</span></li>';
		}
		echo '</ul></section>';
	}

	/**
	 * Gate.
	 */
	private static function guard(): void {
		if ( ! current_user_can( 'lr_academy_access' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
	}
}
