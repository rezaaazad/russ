<?php
/**
 * Academy admin menu, dashboard, and editors.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Separate from the CRM. Instructors see only their own rows and cannot export.
 */
class Admin {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_lr_academy_save', array( self::class, 'save' ) );
		add_action( 'admin_post_lr_academy_export', array( self::class, 'export' ) );
		add_action( 'admin_post_lr_finance_export', array( self::class, 'export_finance' ) );
	}

	/**
	 * Top-level menu.
	 */
	public static function menu(): void {
		add_menu_page( 'آکادمی', 'آکادمی', 'lr_academy_access', 'lr-academy', array( self::class, 'dashboard' ), 'dashicons-welcome-learn-more', 26 );
		add_submenu_page( 'lr-academy', 'داشبورد آکادمی', 'داشبورد', 'lr_academy_access', 'lr-academy', array( self::class, 'dashboard' ) );
		add_submenu_page( 'lr-academy', 'دوره‌ها', 'دوره‌ها', 'lr_academy_access', 'lr-academy-courses', array( self::class, 'courses' ) );
		add_submenu_page( 'lr-academy', 'سفارش‌ها', 'سفارش‌ها', 'lr_academy_access', 'lr-academy-orders', array( self::class, 'orders' ) );
		add_submenu_page( 'lr-academy', 'طرح و کلاس', 'طرح و کلاس', 'lr_academy_manage', 'lr-academy-catalog', array( self::class, 'catalog' ) );
		add_submenu_page( 'lr-academy', 'گزارش مالی', 'گزارش مالی', 'lr_view_finance', 'lr-academy-finance', array( self::class, 'finance' ) );
	}

	/**
	 * Dashboard with a Jalali range and the previous period.
	 */
	public static function dashboard(): void {
		self::guard( 'lr_academy_access' );
		$range = self::range();
		$data  = Metrics::summary( $range[0], $range[1], self::instructor_scope() );
		$now   = $data['current'];
		$prev  = $data['previous'];
		echo '<div class="wrap"><h1>داشبورد آکادمی</h1>';
		self::range_form( 'lr-academy' );
		if ( current_user_can( 'lr_export_academy' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_export"><input type="hidden" name="from" value="' . esc_attr( $range[2] ) . '"><input type="hidden" name="to" value="' . esc_attr( $range[3] ) . '">';
			submit_button( 'خروجی CSV', 'secondary', 'submit', false );
			echo '</form>';
		}
		echo '<table class="widefat striped" style="max-width:920px"><thead><tr><th>شاخص</th><th>این بازه</th><th>بازهٔ قبل</th></tr></thead><tbody>';
		self::metric_row( 'درآمد آکادمی', $now['revenue'], $prev['revenue'] );
		self::metric_row( 'فروش دوره', $now['courses'], $prev['courses'] );
		self::metric_row( 'درآمد اشتراک', $now['subscriptions'], $prev['subscriptions'] );
		self::metric_row( 'کلاس خصوصی', $now['private'], $prev['private'] );
		self::metric_row( 'کلاس گروهی', $now['group'], $prev['group'] );
		self::metric_row( 'بازپرداخت', $now['refunds'], $prev['refunds'] );
		echo '<tr><td>دانشجوی فعال (۳۰ روز اخیر)</td><td>' . esc_html( (string) $now['active'] ) . '</td><td>' . esc_html( (string) $prev['active'] ) . '</td></tr>';
		echo '<tr><td>دانشجوی جدید</td><td>' . esc_html( (string) $now['new_students'] ) . '</td><td>' . esc_html( (string) $prev['new_students'] ) . '</td></tr>';
		echo '<tr><td>نرخ تبدیل</td><td>' . esc_html( (string) $now['conversion'] ) . '٪</td><td>' . esc_html( (string) $prev['conversion'] ) . '٪</td></tr>';
		echo '</tbody></table>';
		echo '<h2>پرفروش‌ها</h2><ul>';
		foreach ( $now['bestsellers'] as $row ) {
			echo '<li>' . esc_html( (string) $row['title'] ) . ' — ' . esc_html( (string) $row['sales'] ) . '</li>';
		}
		echo '</ul>';
		self::chart( $now['days'] );
		echo '</div>';
	}

	/**
	 * Course list, save form, and the draft demo generator.
	 */
	public static function courses(): void {
		self::guard( 'lr_academy_access' );
		$scope = self::instructor_scope();
		global $wpdb;
		$table = Db::table( 'courses' );
		if ( $scope > 0 ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE instructor_id = %d ORDER BY id DESC LIMIT 50", $scope ), ARRAY_A );
		} elseif ( -1 === $scope ) {
			$rows = array();
		} else {
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 50", ARRAY_A );
		}
		echo '<div class="wrap"><h1>دوره‌ها</h1>';
		if ( current_user_can( 'lr_academy_manage' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="demo">';
			submit_button( 'ساخت دورهٔ آزمایشی (پیش‌نویس)', 'secondary', 'submit', false );
			echo '</form>';
		}
		echo '<table class="widefat striped"><thead><tr><th>عنوان</th><th>وضعیت</th><th>قیمت</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['title'] ) . '</td><td>' . esc_html( (string) $row['status'] ) . '</td><td>' . esc_html( (string) $row['price'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
		self::course_form();
		echo '</div>';
	}

	/**
	 * Orders and refunds. Instructors see orders of their own courses and cannot refund.
	 */
	public static function orders(): void {
		self::guard( 'lr_academy_access' );
		global $wpdb;
		$table = Db::table( 'orders' );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 40", ARRAY_A );
		echo '<div class="wrap"><h1>سفارش‌های آکادمی</h1><table class="widefat striped"><thead><tr><th>کد</th><th>وضعیت</th><th>مبلغ</th><th>خالص</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			if ( ! self::can_see_order( (int) $row['id'] ) ) {
				continue;
			}
			echo '<tr><td>' . esc_html( (string) $row['code'] ) . '</td><td>' . esc_html( (string) $row['status'] ) . '</td><td>' . esc_html( (string) $row['total'] ) . '</td><td>' . esc_html( (string) Orders::net( (int) $row['id'] ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( current_user_can( 'lr_academy_manage' ) ) {
			echo '<h2>بازپرداخت</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="refund">';
			echo '<input name="order_id" type="number" min="1" placeholder="شناسه سفارش" required> ';
			echo '<input name="amount" type="number" min="1" placeholder="مبلغ تومان" required> ';
			echo '<input name="reason" type="text" class="regular-text" placeholder="دلیل" required> ';
			submit_button( 'ثبت بازپرداخت', 'secondary', 'submit', false );
			echo '</form>';
		}
		echo '</div>';
	}

	/**
	 * Plans, coupons, classes, instructors, and bundles.
	 */
	public static function catalog(): void {
		self::guard( 'lr_academy_manage' );
		echo '<div class="wrap"><h1>طرح، کوپن، کلاس</h1>';
		self::simple_form(
			'plan',
			'طرح اشتراک',
			array(
				'title'    => 'عنوان',
				'slug'     => 'نامک',
				'price'    => 'قیمت',
				'interval' => 'month یا year',
				'tier'     => 'standard یا premium',
			)
		);
		self::simple_form(
			'coupon',
			'کد تخفیف',
			array(
				'code'      => 'کد',
				'type'      => 'percent یا fixed',
				'amount'    => 'مقدار',
				'scope'     => 'all یا courses یا plans',
				'scope_ids' => 'شناسه‌ها با ویرگول',
			)
		);
		self::simple_form(
			'session',
			'کلاس',
			array(
				'title'    => 'عنوان',
				'kind'     => 'private یا group',
				'starts'   => 'شروع میلادی Y-m-d H:i',
				'capacity' => 'ظرفیت',
				'price'    => 'قیمت',
				'meeting'  => 'لینک جلسه',
			)
		);
		self::simple_form(
			'instructor',
			'مدرس',
			array(
				'name'    => 'نام',
				'slug'    => 'نامک',
				'user_id' => 'شناسه کاربر وردپرس',
			)
		);
		self::simple_form(
			'bundle',
			'بسته',
			array(
				'title'      => 'عنوان',
				'slug'       => 'نامک',
				'price'      => 'قیمت',
				'course_ids' => 'شناسه دوره‌ها',
				'note'       => 'یادداشت مشاوره',
			)
		);
		echo '</div>';
	}

	/**
	 * Global LifeRuss revenue by line.
	 */
	public static function finance(): void {
		self::guard( 'lr_view_finance' );
		$range  = self::range();
		$report = Finance::report( $range[0], $range[1] );
		echo '<div class="wrap"><h1>گزارش مالی لایف‌روس</h1>';
		self::range_form( 'lr-academy-finance' );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_finance_export"><input type="hidden" name="from" value="' . esc_attr( $range[2] ) . '"><input type="hidden" name="to" value="' . esc_attr( $range[3] ) . '">';
		submit_button( 'خروجی CSV', 'secondary', 'submit', false );
		echo '</form>';
		echo '<table class="widefat striped" style="max-width:640px"><thead><tr><th>خط</th><th>خالص (تومان)</th></tr></thead><tbody>';
		foreach ( Finance::lines() as $key => $label ) {
			echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( (string) ( $report['totals'][ $key ] ?? 0 ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		self::chart( self::finance_days( $report['days'] ) );
		echo '</div>';
	}

	/**
	 * Save handlers.
	 */
	public static function save(): void {
		if ( ! check_admin_referer( 'lr_academy_save' ) ) {
			wp_die( esc_html__( 'نشست منقضی شده است.', 'liferuss-core' ) );
		}
		$do = isset( $_POST['lr_do'] ) ? sanitize_key( wp_unslash( (string) $_POST['lr_do'] ) ) : '';
		if ( 'demo' === $do && current_user_can( 'lr_academy_manage' ) ) {
			Catalog::demo( get_current_user_id() );
		}
		if ( 'refund' === $do && current_user_can( 'lr_academy_manage' ) ) {
			Orders::refund( absint( $_POST['order_id'] ?? 0 ), absint( $_POST['amount'] ?? 0 ), sanitize_text_field( wp_unslash( (string) ( $_POST['reason'] ?? '' ) ) ) );
		}
		if ( 'course' === $do && current_user_can( 'lr_academy_access' ) ) {
			self::save_course();
		}
		if ( 'plan' === $do && current_user_can( 'lr_academy_manage' ) ) {
			$interval = isset( $_POST['interval'] ) ? sanitize_key( wp_unslash( (string) $_POST['interval'] ) ) : '';
			$tier     = isset( $_POST['tier'] ) ? sanitize_key( wp_unslash( (string) $_POST['tier'] ) ) : '';
			Db::insert(
				'subscription_plans',
				array(
					'title'            => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
					'slug'             => sanitize_title( wp_unslash( (string) ( $_POST['slug'] ?? '' ) ) ),
					'price'            => absint( $_POST['price'] ?? 0 ),
					'billing_interval' => 'year' === $interval ? 'year' : 'month',
					'tier'             => 'premium' === $tier ? 'premium' : 'standard',
					'status'           => 'published',
				)
			);
		}
		if ( 'coupon' === $do && current_user_can( 'lr_academy_manage' ) ) {
			$coupon_type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( (string) $_POST['type'] ) ) : '';
			$scope       = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( (string) $_POST['scope'] ) ) : 'all';
			Db::insert(
				'coupons',
				array(
					'code'      => strtoupper( sanitize_text_field( wp_unslash( (string) ( $_POST['code'] ?? '' ) ) ) ),
					'type'      => 'fixed' === $coupon_type ? 'fixed' : 'percent',
					'amount'    => absint( $_POST['amount'] ?? 0 ),
					'scope'     => in_array( $scope, array( 'all', 'courses', 'plans' ), true ) ? $scope : 'all',
					'scope_ids' => sanitize_text_field( wp_unslash( (string) ( $_POST['scope_ids'] ?? '' ) ) ),
					'status'    => 'published',
				)
			);
		}
		if ( 'session' === $do && current_user_can( 'lr_academy_manage' ) ) {
			$start = sanitize_text_field( wp_unslash( (string) ( $_POST['starts'] ?? '' ) ) );
			$kind  = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( (string) $_POST['kind'] ) ) : '';
			Db::insert(
				'class_sessions',
				array(
					'title'       => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
					'kind'        => 'private' === $kind ? 'private' : 'group',
					'starts_at'   => $start ? get_gmt_from_date( $start ) : Db::now(),
					'capacity'    => max( 1, absint( $_POST['capacity'] ?? 1 ) ),
					'price'       => absint( $_POST['price'] ?? 0 ),
					'meeting_url' => esc_url_raw( wp_unslash( (string) ( $_POST['meeting'] ?? '' ) ) ),
					'status'      => 'open',
				)
			);
		}
		if ( 'instructor' === $do && current_user_can( 'lr_academy_manage' ) ) {
			$linked = absint( $_POST['user_id'] ?? 0 );
			Db::insert(
				'instructors',
				array(
					'name'    => sanitize_text_field( wp_unslash( (string) ( $_POST['name'] ?? '' ) ) ),
					'slug'    => sanitize_title( wp_unslash( (string) ( $_POST['slug'] ?? '' ) ) ),
					'user_id' => $linked > 0 ? $linked : null,
					'status'  => 'published',
				)
			);
		}
		if ( 'bundle' === $do && current_user_can( 'lr_academy_manage' ) ) {
			$settings            = Settings::get();
			$bundles             = is_array( $settings['bundles'] ) ? $settings['bundles'] : array();
			$course_raw          = isset( $_POST['course_ids'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['course_ids'] ) ) : '';
			$course_parts        = preg_split( '/[^0-9]+/', $course_raw );
			$bundles[]           = array(
				'title'      => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
				'slug'       => sanitize_title( wp_unslash( (string) ( $_POST['slug'] ?? '' ) ) ),
				'price'      => absint( $_POST['price'] ?? 0 ),
				'course_ids' => array_filter( array_map( 'absint', is_array( $course_parts ) ? $course_parts : array() ) ),
				'note'       => sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) ),
				'status'     => 'published',
			);
			$settings['bundles'] = $bundles;
			Settings::save( $settings );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=lr-academy' ) );
		exit;
	}

	/**
	 * Academy CSV. Admin and finance only.
	 */
	public static function export(): void {
		if ( ! check_admin_referer( 'lr_academy_save' ) || ! current_user_can( 'lr_export_academy' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$range = self::posted_range();
		$data  = Metrics::summary( $range[0], $range[1], 0 );
		self::csv(
			'academy.csv',
			array( array( 'metric', 'current', 'previous' ) ),
			self::csv_metrics( $data )
		);
	}

	/**
	 * Global finance CSV.
	 */
	public static function export_finance(): void {
		if ( ! check_admin_referer( 'lr_academy_save' ) || ! current_user_can( 'lr_view_finance' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$range  = self::posted_range();
		$report = Finance::report( $range[0], $range[1] );
		$rows   = array( array( 'line', 'amount' ) );
		foreach ( Finance::lines() as $key => $label ) {
			$rows[] = array( $label, (string) ( $report['totals'][ $key ] ?? 0 ) );
		}
		self::csv( 'finance.csv', $rows, array() );
	}

	/**
	 * Course form.
	 */
	private static function course_form(): void {
		echo '<h2>دورهٔ تازه</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="course">';
		echo '<p><input name="title" class="regular-text" placeholder="عنوان" required></p>';
		echo '<p><input name="slug" placeholder="نامک" required></p>';
		echo '<p><input name="price" type="number" min="0" placeholder="قیمت تومان"></p>';
		echo '<p><select name="tier"><option value="standard">استاندارد</option><option value="premium">ویژه</option></select></p>';
		echo '<p><label><input type="checkbox" name="is_free" value="1"> رایگان</label> <label><input type="checkbox" name="included" value="1"> داخل اشتراک</label></p>';
		echo '<p><select name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشر</option></select></p>';
		submit_button( 'ذخیره' );
		echo '</form>';
	}

	/**
	 * Insert a course from the form. Instructors are attached to themselves.
	 */
	private static function save_course(): void {
		$scope  = self::instructor_scope();
		$tier   = isset( $_POST['tier'] ) ? sanitize_key( wp_unslash( (string) $_POST['tier'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( (string) $_POST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
		Db::insert(
			'courses',
			array(
				'title'                    => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['title'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
				'slug'                     => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( (string) $_POST['slug'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
				'price'                    => isset( $_POST['price'] ) ? absint( $_POST['price'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
				'currency'                 => 'IRT',
				'tier'                     => 'premium' === $tier ? 'premium' : 'standard',
				'is_free'                  => empty( $_POST['is_free'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
				'included_in_subscription' => empty( $_POST['included'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in save().
				'status'                   => 'published' === $status && current_user_can( 'lr_academy_manage' ) ? 'published' : 'draft',
				'instructor_id'            => $scope > 0 ? $scope : null,
				'certificate_enabled'      => 1,
			)
		);
	}

	/**
	 * 0 for every row, a positive id for one instructor, -1 when the user has no instructor row.
	 */
	private static function instructor_scope(): int {
		if ( current_user_can( 'lr_academy_manage' ) ) {
			return 0;
		}
		$row = Db::find_by( 'instructors', 'user_id', (string) get_current_user_id() );
		return $row ? (int) $row['id'] : -1;
	}

	/**
	 * Whether this user may see an order.
	 *
	 * @param int $order_id Order id.
	 */
	private static function can_see_order( int $order_id ): bool {
		$scope = self::instructor_scope();
		if ( 0 === $scope ) {
			return true;
		}
		if ( $scope < 0 ) {
			return false;
		}
		foreach ( Db::where_id( 'order_items', 'order_id', $order_id ) as $item ) {
			if ( 'course' === $item['item_type'] ) {
				$course = Db::find( 'courses', (int) $item['item_id'] );
				if ( $course && (int) $course['instructor_id'] === $scope ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Jalali range from the query, defaulting to the last 30 days.
	 *
	 * @return array{0: string, 1: string, 2: string, 3: string}
	 */
	private static function range(): array {
		$from  = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to    = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$start = $from ? Jalali::filter_utc( $from, false ) : gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$end   = $to ? Jalali::filter_utc( $to, true ) : Db::now();
		if ( '' === $start ) {
			$start = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		}
		if ( '' === $end ) {
			$end = Db::now();
		}
		return array( $start, $end, $from, $to );
	}

	/**
	 * Range posted with an export.
	 *
	 * @return array{0: string, 1: string}
	 */
	private static function posted_range(): array {
		$from  = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the export handler.
		$to    = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the export handler.
		$start = $from ? Jalali::filter_utc( $from, false ) : gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$end   = $to ? Jalali::filter_utc( $to, true ) : Db::now();
		return array( $start ? $start : Db::now(), $end ? $end : Db::now() );
	}

	/**
	 * Date filter.
	 *
	 * @param string $page Admin page slug.
	 */
	private static function range_form( string $page ): void {
		echo '<form method="get"><input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
		echo '<input name="from" placeholder="از ۱۴۰۴/۰۱/۰۱"> <input name="to" placeholder="تا ۱۴۰۴/۱۲/۲۹"> ';
		submit_button( 'اعمال', 'secondary', 'submit', false );
		echo '</form>';
	}

	/**
	 * One comparison row.
	 *
	 * @param string $label Label.
	 * @param int    $now   Current.
	 * @param int    $prev  Previous.
	 */
	private static function metric_row( string $label, int $now, int $prev ): void {
		echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( number_format_i18n( $now ) ) . '</td><td>' . esc_html( number_format_i18n( $prev ) ) . '</td></tr>';
	}

	/**
	 * SVG bars.
	 *
	 * @param array<int, array<string, mixed>> $days Days.
	 */
	private static function chart( array $days ): void {
		echo '<h2>روند</h2><svg viewBox="0 0 640 160" width="100%" height="160" role="img">';
		$max = 1;
		foreach ( $days as $day ) {
			$max = max( $max, (int) $day['amount'] );
		}
		$i = 0;
		$n = max( 1, count( $days ) );
		foreach ( $days as $day ) {
			$h = (int) round( ( (int) $day['amount'] * 120 ) / $max );
			$x = (int) round( ( $i * 620 ) / $n );
			echo '<rect x="' . esc_attr( (string) $x ) . '" y="' . esc_attr( (string) ( 140 - $h ) ) . '" width="12" height="' . esc_attr( (string) $h ) . '" fill="#0b2341"/>';
			++$i;
		}
		echo '</svg>';
	}

	/**
	 * Flatten finance days to amount bars.
	 *
	 * @param array<string, array<string, int>> $days Days.
	 * @return array<int, array<string, mixed>>
	 */
	private static function finance_days( array $days ): array {
		$out = array();
		foreach ( $days as $day => $lines ) {
			$out[] = array(
				'day'    => $day,
				'amount' => array_sum( $lines ),
			);
		}
		return $out;
	}

	/**
	 * A short create form.
	 *
	 * @param string                $action Action.
	 * @param string                $title  Heading.
	 * @param array<string, string> $fields Fields.
	 */
	private static function simple_form( string $action, string $title, array $fields ): void {
		echo '<h2>' . esc_html( $title ) . '</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_academy_save"><input type="hidden" name="lr_do" value="' . esc_attr( $action ) . '">';
		foreach ( $fields as $name => $label ) {
			echo '<p><input class="regular-text" name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $label ) . '"></p>';
		}
		submit_button( 'افزودن', 'secondary' );
		echo '</form>';
	}

	/**
	 * Capability or die.
	 *
	 * @param string $cap Capability.
	 */
	private static function guard( string $cap ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ) );
		}
	}

	/**
	 * Metric rows for CSV.
	 *
	 * @param array<string, mixed> $data Summary.
	 * @return array<int, array<int, string>>
	 */
	private static function csv_metrics( array $data ): array {
		$keys = array( 'revenue', 'courses', 'subscriptions', 'private', 'group', 'refunds', 'active', 'new_students', 'conversion' );
		$rows = array();
		foreach ( $keys as $key ) {
			$rows[] = array( $key, (string) $data['current'][ $key ], (string) $data['previous'][ $key ] );
		}
		return $rows;
	}

	/**
	 * Send CSV and stop.
	 *
	 * @param string                         $name Name.
	 * @param array<int, array<int, string>> $head Rows.
	 * @param array<int, array<int, string>> $more More rows.
	 */
	private static function csv( string $name, array $head, array $more ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $name );
		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			exit;
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		foreach ( array_merge( $head, $more ) as $row ) {
			fputcsv( $out, $row );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
