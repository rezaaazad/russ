<?php
/**
 * Students, orders, questions, and instructors.
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
 * People and money screens of the Academy admin.
 */
class People {


	/**
	 * Student list.
	 */
	public static function students(): void {
		self::guard();
		$course = isset( $_GET['course'] ) ? absint( $_GET['course'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		Chrome::open( 'دانشجوها', 'آکادمی' );
		$rows = self::student_rows( $course );
		if ( ! $rows ) {
			Chrome::empty( 'هنوز دانشجویی ثبت نشده. اولین خرید یا ثبت‌نام آزاد این فهرست را پر می‌کند.' );
			Chrome::close();
			return;
		}
		echo '<div class="lr-scroll"><table class="widefat"><thead><tr><th>نام</th><th>موبایل</th><th>دوره فعال</th><th>آخرین فعالیت</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$url = Chrome::url( 'lr-academy-student', array( 'id' => (int) $row['id'] ) );
			echo '<tr><td><a href="' . esc_url( $url ) . '"><strong>' . esc_html( (string) $row['display_name'] ) . '</strong></a></td>';
			echo '<td dir="ltr">' . esc_html( (string) $row['phone'] ) . '</td>';
			echo '<td>' . esc_html( Chrome::num( (int) $row['courses'] ) ) . '</td>';
			echo '<td>' . esc_html( Chrome::date( (string) $row['last_seen'] ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		Chrome::close();
	}

	/**
	 * One student.
	 */
	public static function student(): void {
		self::guard();
		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$row = Db::find( 'students', $id );
		if ( ! $row || ! self::sees_student( $row ) ) {
			Chrome::open( 'دانشجو', 'آکادمی / دانشجوها' );
			Chrome::empty( 'این دانشجو در محدودهٔ شما نیست.' );
			Chrome::close();
			return;
		}
		Chrome::open( (string) $row['display_name'], 'آکادمی / دانشجوها' );
		echo '<div class="lr-split"><section class="lr-panel"><h2>ثبت‌نام‌ها</h2>';
		$enrollments = Db::where_id( 'enrollments', 'student_id', $id );
		if ( ! $enrollments ) {
			Chrome::empty( 'ثبت‌نامی ندارد. از فرم کنار صفحه دسترسی بدهید.' );
		}
		foreach ( $enrollments as $enrollment ) {
			$course = Db::find( 'courses', (int) $enrollment['course_id'] );
			if ( ! $course || ! Flow::can_course( $course ) ) {
				continue;
			}
			$pct = Progress::percent( $id, (int) $course['id'] );
			echo '<div class="lr-task"><div><strong>' . esc_html( (string) $course['title'] ) . '</strong><small>پیشرفت ' . esc_html( Chrome::num( $pct ) ) . '٪</small>';
			echo '<div class="lr-bar"><span style="width:' . esc_attr( (string) $pct ) . '%"></span></div></div>';
			echo Chrome::pill( (string) $enrollment['status'], Chrome::status( (string) $enrollment['status'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}
		echo '<h2>آزمون‌ها</h2>';
		$scores = Db::where_id( 'quiz_results', 'student_id', $id );
		if ( ! $scores ) {
			echo '<p>نمره‌ای ثبت نشده است.</p>';
		}
		foreach ( $scores as $score ) {
			echo '<p>' . esc_html( Chrome::num( (int) $score['score'] ) . ' از ' . Chrome::num( (int) $score['max_score'] ) ) . '</p>';
		}
		echo '<h2>گواهی‌ها</h2>';
		$certs = Db::where_id( 'certificates', 'student_id', $id );
		if ( ! $certs ) {
			echo '<p>گواهی صادر نشده است.</p>';
		}
		foreach ( $certs as $cert ) {
			echo '<p><a href="' . esc_url( home_url( '/academy/certificate/' . $cert['code'] . '/' ) ) . '">' . esc_html( (string) $cert['code'] ) . '</a> — ' . esc_html( (string) $cert['jalali_date'] ) . '</p>';
		}
		echo '</section><section class="lr-panel"><h2>پرداخت و اشتراک</h2>';
		self::payments( (int) $row['user_id'] );
		self::subscription( $id );
		$lead = self::lead( (string) $row['phone'] );
		if ( $lead ) {
			echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lr-lead&id=' . (int) $lead['id'] ) ) . '">لید CRM</a></p>';
		} else {
			echo '<p>لید هم‌شماره در CRM نیست.</p>';
		}
		if ( current_user_can( 'lr_academy_manage' ) ) {
			self::manual( $id );
		}
		echo '<h2>گزارش کارها</h2>';
		self::audit( 'student', $id );
		echo '</section></div>';
		Chrome::close();
	}

	/**
	 * Orders.
	 */
	public static function orders(): void {
		self::guard();
		Chrome::open( 'سفارش‌ها و پرداخت‌ها', 'آکادمی' );
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<form class="lr-filters" method="get"><input type="hidden" name="page" value="lr-academy-orders"><label>وضعیت <select name="status"><option value="">همه</option>';
		foreach ( array( 'pending', 'paid', 'failed', 'refunded', 'cancelled' ) as $key ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( Chrome::status( $key ) ) . '</option>';
		}
		echo '</select></label><button class="button lr-btn">اعمال فیلتر</button></form>';
		global $wpdb;
		$sql  = 'SELECT * FROM `' . Db::table( 'orders' ) . '` WHERE 1=1';
		$args = array();
		if ( '' !== $status ) {
			$sql   .= ' AND status = %s';
			$args[] = $status;
		}
		$sql  .= ' ORDER BY id DESC LIMIT 80';
		$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		$shown = 0;
		echo '<div class="lr-scroll"><table class="widefat"><thead><tr><th>کد</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			if ( ! self::sees_order( (int) $row['id'] ) ) {
				continue;
			}
			++$shown;
			echo '<tr><td><a href="' . esc_url( Chrome::url( 'lr-academy-order', array( 'id' => (int) $row['id'] ) ) ) . '"><strong>' . esc_html( (string) $row['code'] ) . '</strong></a></td>';
			echo '<td>' . Chrome::pill( (string) $row['status'], Chrome::status( (string) $row['status'] ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<td>' . esc_html( Chrome::toman( (int) $row['total'] ) ) . '</td>';
			echo '<td>' . esc_html( Chrome::date( (string) $row['created_at'] ) ) . '</td></tr>';
		}
		if ( 0 === $shown ) {
			echo '<tr><td colspan="4">سفارشی با این فیلتر نیست.</td></tr>';
		}
		echo '</tbody></table></div>';
		Chrome::close();
	}

	/**
	 * Order detail, timeline, refund, invoice.
	 */
	public static function order(): void {
		self::guard();
		$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = Db::find( 'orders', $id );
		if ( ! $order || ! self::sees_order( $id ) ) {
			Chrome::open( 'سفارش', 'آکادمی / سفارش‌ها' );
			Chrome::empty( 'سفارش پیدا نشد.' );
			Chrome::close();
			return;
		}
		$print = isset( $_GET['invoice'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		Chrome::open(
			'سفارش ' . $order['code'],
			'آکادمی / سفارش‌ها',
			'<a class="button" href="' . esc_url(
				Chrome::url(
					'lr-academy-order',
					array(
						'id'      => $id,
						'invoice' => 1,
					)
				)
			) . '">مشاهده فاکتور</a>'
		);
		echo '<div class="lr-split"><section class="lr-panel' . ( $print ? ' lr-cert' : '' ) . '"><h2>' . esc_html( $print ? 'فاکتور' : 'اقلام' ) . '</h2>';
		echo '<p>وضعیت: ' . Chrome::pill( (string) $order['status'], Chrome::status( (string) $order['status'] ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<table class="widefat"><thead><tr><th>عنوان</th><th>مبلغ</th></tr></thead><tbody>';
		foreach ( Db::where_id( 'order_items', 'order_id', $id ) as $item ) {
			echo '<tr><td>' . esc_html( (string) $item['title'] ) . '</td><td>' . esc_html( Chrome::toman( (int) $item['amount_toman'] ) ) . '</td></tr>';
		}
		echo '</tbody></table><p><strong>خالص: ' . esc_html( Chrome::toman( Orders::net( $id ) ) ) . '</strong></p></section>';
		echo '<section class="lr-panel"><h2>روند</h2><ol class="lr-timeline">';
		echo '<li>ساخته شد — ' . esc_html( Chrome::date( (string) $order['created_at'] ) ) . '</li>';
		if ( ! empty( $order['paid_at'] ) ) {
			echo '<li>پرداخت شد — ' . esc_html( Chrome::date( (string) $order['paid_at'] ) ) . '</li>';
		}
		foreach ( Db::where_id( 'enrollments', 'order_id', $id ) as $enrollment ) {
			echo '<li>دسترسی داده شد — ' . esc_html( Chrome::date( (string) $enrollment['granted_at'] ) ) . '</li>';
		}
		foreach ( Db::where_id( 'payments', 'order_id', $id ) as $payment ) {
			if ( 'refund' === $payment['status'] ) {
				echo '<li>بازپرداخت — ' . esc_html( Chrome::toman( abs( (int) $payment['amount_toman'] ) ) ) . ' — ' . esc_html( (string) $payment['reason'] ) . '</li>';
			}
		}
		echo '</ol>';
		if ( current_user_can( 'lr_academy_manage' ) && Orders::net( $id ) > 0 ) {
			echo '<form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="refund">';
			echo '<input type="hidden" name="order_id" value="' . esc_attr( (string) $id ) . '">';
			echo '<label>مبلغ بازپرداخت (تومان)<input type="number" name="amount" min="1" value="' . esc_attr( (string) Orders::net( $id ) ) . '" required></label>';
			echo '<label>دلیل<input type="text" name="reason" required></label>';
			submit_button( 'ثبت بازپرداخت', 'secondary' );
			echo '</form>';
		}
		echo '</section></div>';
		Chrome::close();
	}

	/**
	 * Moderation queue.
	 */
	public static function questions(): void {
		self::guard();
		Chrome::open( 'پرسش و پاسخ و نظرات', 'آکادمی' );
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'questions' ) . '` ORDER BY id DESC LIMIT 40', ARRAY_A );
		if ( ! $rows ) {
			Chrome::empty( 'صفی برای بررسی نیست. پرسش دانشجویان این‌جا می‌آید.' );
			Chrome::close();
			return;
		}
		foreach ( (array) $rows as $row ) {
			echo '<section class="lr-panel"><p>' . Chrome::pill( (string) $row['kind'], 'review' === $row['kind'] ? 'نظر' : 'پرسش' ) . ' ' . Chrome::pill( (string) $row['status'], Chrome::status( (string) $row['status'] ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p>' . esc_html( (string) $row['body'] ) . '</p>';
			echo '<form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="question">';
			echo '<input type="hidden" name="question_id" value="' . esc_attr( (string) $row['id'] ) . '">';
			echo '<label>پاسخ<textarea name="answer" rows="3">' . esc_textarea( (string) $row['answer'] ) . '</textarea></label>';
			echo '<div class="lr-actions"><button class="button button-primary" name="status" value="published">انتشار</button>';
			echo '<button class="button" name="status" value="hidden">پنهان</button></div></form></section>';
		}
		Chrome::close();
	}

	/**
	 * Instructors and classes.
	 */
	public static function instructors(): void {
		self::guard();
		Chrome::open( 'کلاس‌ها و مدرسان', 'آکادمی' );
		echo '<div class="lr-split"><section>';
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'instructors' ) . '` ORDER BY id DESC', ARRAY_A );
		if ( ! $rows ) {
			Chrome::empty( 'مدرسی ثبت نشده. فرم کنار صفحه اولین مدرس را می‌سازد.' );
		}
		echo '<div class="lr-scroll"><table class="widefat"><thead><tr><th>نام</th><th>سهم</th><th>دوره</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			if ( Flow::scope() > 0 && Flow::scope() !== (int) $row['id'] ) {
				continue;
			}
			$courses = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . Db::table( 'courses' ) . '` WHERE instructor_id = %d', (int) $row['id'] ) );
			echo '<tr><td>' . esc_html( (string) $row['name'] ) . '</td><td>' . esc_html( Chrome::num( (int) $row['share_percent'] ) ) . '٪</td><td>' . esc_html( Chrome::num( $courses ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		echo '<h2>کلاس‌ها</h2>';
		$sessions = $wpdb->get_results( 'SELECT * FROM `' . Db::table( 'class_sessions' ) . '` ORDER BY starts_at DESC LIMIT 20', ARRAY_A );
		if ( ! $sessions ) {
			Chrome::empty( 'کلاسی ثبت نشده است.' );
		}
		foreach ( (array) $sessions as $session ) {
			if ( Flow::scope() > 0 && Flow::scope() !== (int) $session['instructor_id'] ) {
				continue;
			}
			echo '<p>' . esc_html( (string) $session['title'] ) . ' — ' . esc_html( Chrome::date( (string) $session['starts_at'] ) ) . ' — ' . esc_html( Chrome::toman( (int) $session['price'] ) ) . '</p>';
		}
		echo '</section><section class="lr-panel">';
		if ( current_user_can( 'lr_academy_manage' ) ) {
			echo '<h2>مدرس</h2><form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="instructor">';
			echo '<label>نام<input name="name" required></label><label>نامک<input name="slug"></label>';
			echo '<label>شناسه کاربر وردپرس<input name="user_id" type="number"></label>';
			echo '<label>سهم درآمد (درصد)<input name="share_percent" type="number" min="0" max="100" value="30"></label>';
			echo '<label>بیو<textarea name="bio" rows="3"></textarea></label>';
			submit_button( 'ذخیره مدرس' );
			echo '</form>';
		}
		echo '<h2>کلاس تازه</h2><form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="session">';
		echo '<label>عنوان<input name="title" required></label>';
		echo '<label>نوع <select name="kind"><option value="group">گروهی</option><option value="private">خصوصی</option></select></label>';
		echo '<label>شروع<input type="datetime-local" name="starts_at"></label>';
		echo '<label>ظرفیت<input type="number" name="capacity" value="8"></label>';
		echo '<label>قیمت<input type="number" name="price" value="0"></label>';
		echo '<label>لینک جلسه<input name="meeting" type="url"></label>';
		submit_button( 'ثبت کلاس', 'secondary' );
		echo '</form></section></div>';
		Chrome::close();
	}

	/**
	 * Students visible to this user.
	 *
	 * @param int $course Optional course filter.
	 * @return array<int, array<string, mixed>>
	 */
	private static function student_rows( int $course = 0 ): array {
		global $wpdb;
		$sql = 'SELECT s.*, (SELECT COUNT(*) FROM `' . Db::table( 'enrollments' ) . "` e WHERE e.student_id = s.id AND e.status = 'active') AS courses, (SELECT MAX(updated_at) FROM `" . Db::table( 'course_progress' ) . '` p WHERE p.student_id = s.id) AS last_seen FROM `' . Db::table( 'students' ) . '` s';
		if ( $course > 0 ) {
			$sql .= $wpdb->prepare( ' WHERE EXISTS (SELECT 1 FROM `' . Db::table( 'enrollments' ) . '` e2 WHERE e2.student_id = s.id AND e2.course_id = %d)', $course );
		}
		$sql .= ' ORDER BY s.id DESC LIMIT 80';
		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are prepared above when present.
		$out  = array();
		foreach ( (array) $rows as $row ) {
			if ( self::sees_student( $row ) ) {
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Instructor sees a student only when they share a course.
	 *
	 * @param array<string, mixed> $student Student.
	 */
	private static function sees_student( array $student ): bool {
		if ( 0 === Flow::scope() ) {
			return true;
		}
		if ( Flow::scope() < 0 ) {
			return false;
		}
		foreach ( Db::where_id( 'enrollments', 'student_id', (int) $student['id'] ) as $enrollment ) {
			$course = Db::find( 'courses', (int) $enrollment['course_id'] );
			if ( $course && Flow::can_course( $course ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Order visibility for an instructor.
	 *
	 * @param int $order_id Order id.
	 */
	private static function sees_order( int $order_id ): bool {
		if ( 0 === Flow::scope() ) {
			return current_user_can( 'lr_academy_manage' ) || current_user_can( 'lr_view_finance' ) || current_user_can( 'lr_academy_access' );
		}
		foreach ( Db::where_id( 'order_items', 'order_id', $order_id ) as $item ) {
			if ( 'course' !== $item['item_type'] ) {
				continue;
			}
			$course = Db::find( 'courses', (int) $item['item_id'] );
			if ( $course && Flow::can_course( $course ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Orders of one user.
	 *
	 * @param int $user_id User id.
	 */
	private static function payments( int $user_id ): void {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . Db::table( 'orders' ) . '` WHERE user_id = %d ORDER BY id DESC LIMIT 10', $user_id ), ARRAY_A );
		if ( ! $rows ) {
			echo '<p>پرداختی ندارد.</p>';
			return;
		}
		foreach ( (array) $rows as $row ) {
			echo '<p><a href="' . esc_url( Chrome::url( 'lr-academy-order', array( 'id' => (int) $row['id'] ) ) ) . '">' . esc_html( (string) $row['code'] ) . '</a> ' . Chrome::pill( (string) $row['status'], Chrome::status( (string) $row['status'] ) ) . ' ' . esc_html( Chrome::toman( (int) $row['total'] ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Subscriptions of a student.
	 *
	 * @param int $student_id Student id.
	 */
	private static function subscription( int $student_id ): void {
		$rows = Db::where_id( 'subscriptions', 'student_id', $student_id );
		if ( ! $rows ) {
			echo '<p>اشتراک فعالی ندارد.</p>';
			return;
		}
		foreach ( $rows as $row ) {
			echo '<p>' . Chrome::pill( (string) $row['status'], Chrome::status( (string) $row['status'] ) ) . ' تا ' . esc_html( Chrome::date( (string) $row['ends_at'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $row['reminded_at'] ? ' — یادآوری ارسال شده' : ' — بدون یادآوری';
			echo '</p>';
		}
	}

	/**
	 * CRM lead with the same phone.
	 *
	 * @param  string $phone Phone.
	 * @return array<string, mixed>|null
	 */
	private static function lead( string $phone ): ?array {
		if ( '' === $phone ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, lead_code FROM `{$table}` WHERE phone = %s AND deleted_at IS NULL LIMIT 1", $phone ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Manual actions.
	 *
	 * @param int $student_id Student id.
	 */
	private static function manual( int $student_id ): void {
		echo '<h2>کار دستی</h2><form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="student">';
		echo '<input type="hidden" name="student_id" value="' . esc_attr( (string) $student_id ) . '">';
		echo '<label>دوره <select name="course_id"><option value="0">انتخاب</option>';
		global $wpdb;
		$courses = $wpdb->get_results( 'SELECT id, title, slug FROM `' . Db::table( 'courses' ) . '` ORDER BY id DESC LIMIT 40', ARRAY_A );
		foreach ( (array) $courses as $course ) {
			$label = trim( (string) $course['title'] );
			if ( '' === $label ) {
				$label = (string) $course['slug'];
			}
			echo '<option value="' . esc_attr( (string) $course['id'] ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		$subs = Db::where_id( 'subscriptions', 'student_id', $student_id );
		echo '<label>اشتراک <select name="subscription_id">';
		foreach ( $subs as $sub ) {
			$plan  = Db::find( 'subscription_plans', (int) $sub['plan_id'] );
			$name  = $plan ? (string) $plan['title'] : 'طرح';
			$label = $name . ' تا ' . Chrome::date( (string) $sub['ends_at'] );
			echo '<option value="' . esc_attr( (string) $sub['id'] ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select></label><div class="lr-actions">';
		echo '<button class="button button-primary" name="student_do" value="grant">دادن دسترسی</button>';
		echo '<button class="button" name="student_do" value="revoke">لغو دسترسی</button>';
		echo '<button class="button" name="student_do" value="extend">تمدید ۳۰ روز</button>';
		echo '<button class="button" name="student_do" value="reset">بازنشانی پیشرفت</button>';
		echo '</div></form>';
	}

	/**
	 * Audit lines.
	 *
	 * @param string $type Subject.
	 * @param int    $id   Id.
	 */
	private static function audit( string $type, int $id ): void {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . Db::table( 'audit' ) . '` WHERE subject_type = %s AND subject_id = %d ORDER BY id DESC LIMIT 12', $type, $id ), ARRAY_A );
		if ( ! $rows ) {
			echo '<p>هنوز کاری ثبت نشده است.</p>';
			return;
		}
		echo '<ul class="lr-timeline">';
		foreach ( (array) $rows as $row ) {
			echo '<li>' . esc_html( Chrome::date( (string) $row['created_at'] ) . ' — ' . (string) $row['detail'] ) . '</li>';
		}
		echo '</ul>';
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
