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
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
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
		echo '<div class="wrap lr-academy-dash"><h1>داشبورد آکادمی</h1>';
		self::range_form( 'lr-academy', $range[2], $range[3] );
		if ( current_user_can( 'lr_export_academy' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_academy_save' );
			echo '<input type="hidden" name="action" value="lr_academy_export"><input type="hidden" name="from" value="' . esc_attr( $range[2] ) . '"><input type="hidden" name="to" value="' . esc_attr( $range[3] ) . '">';
			submit_button( 'خروجی CSV', 'secondary', 'submit', false );
			echo '</form>';
		}
		echo '<div class="lr-kpis">';
		self::kpi( 'درآمد آکادمی', number_format_i18n( (int) $now['revenue'] ), (int) $now['revenue'], (int) $prev['revenue'], false );
		self::kpi( 'فروش دوره', number_format_i18n( (int) $now['courses'] ), (int) $now['courses'], (int) $prev['courses'], false );
		self::kpi( 'اشتراک', number_format_i18n( (int) $now['subscriptions'] ), (int) $now['subscriptions'], (int) $prev['subscriptions'], false );
		self::kpi( 'کلاس خصوصی', number_format_i18n( (int) $now['private'] ), (int) $now['private'], (int) $prev['private'], false );
		self::kpi( 'کلاس گروهی', number_format_i18n( (int) $now['group'] ), (int) $now['group'], (int) $prev['group'], false );
		self::kpi( 'بازپرداخت', number_format_i18n( (int) $now['refunds'] ), (int) $now['refunds'], (int) $prev['refunds'], true );
		self::kpi( 'دانشجوی جدید', number_format_i18n( (int) $now['new_students'] ), (int) $now['new_students'], (int) $prev['new_students'], false );
		self::kpi( 'نرخ تبدیل', (string) $now['conversion'] . '٪', (float) $now['conversion'], (float) $prev['conversion'], false );
		echo '</div>';
		echo '<div class="lr-charts">';
		echo '<section class="lr-panel"><h2>درآمد در طول زمان</h2>';
		self::revenue_chart( (array) $now['days'] );
		echo '</section>';
		echo '<section class="lr-panel"><h2>درآمد به تفکیک مدل</h2>';
		self::model_chart( $now );
		echo '</section></div>';
		echo '<div class="lr-split">';
		echo '<section class="lr-panel"><h2>پرفروش‌ها</h2>';
		self::bestsellers_table( (array) $now['bestsellers'] );
		echo '</section>';
		echo '<section class="lr-panel"><h2>سفارش‌های اخیر</h2>';
		self::recent_orders();
		echo '</section></div>';
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
		$range     = self::range();
		$report    = Finance::report( $range[0], $range[1] );
		$seconds   = max( 1, strtotime( $range[1] . ' UTC' ) - strtotime( $range[0] . ' UTC' ) );
		$prev_to   = gmdate( 'Y-m-d H:i:s', strtotime( $range[0] . ' UTC' ) - 1 );
		$prev_from = gmdate( 'Y-m-d H:i:s', strtotime( $prev_to . ' UTC' ) - $seconds );
		$previous  = Finance::report( $prev_from, $prev_to );
		echo '<div class="wrap lr-academy-dash"><h1>گزارش مالی لایف‌روس</h1>';
		self::range_form( 'lr-academy-finance', $range[2], $range[3] );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_academy_save' );
		echo '<input type="hidden" name="action" value="lr_finance_export"><input type="hidden" name="from" value="' . esc_attr( $range[2] ) . '"><input type="hidden" name="to" value="' . esc_attr( $range[3] ) . '">';
		submit_button( 'خروجی CSV', 'secondary', 'submit', false );
		echo '</form>';
		echo '<div class="lr-kpis">';
		foreach ( Finance::lines() as $key => $label ) {
			$amount = (int) ( $report['totals'][ $key ] ?? 0 );
			$before = (int) ( $previous['totals'][ $key ] ?? 0 );
			self::kpi( $label, number_format_i18n( $amount ), $amount, $before, false );
		}
		echo '</div>';
		echo '<section class="lr-panel"><h2>درآمد خط‌ها در طول زمان</h2>';
		self::finance_chart( $report['days'] );
		echo '</section></div>';
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
	 * Chart.js and the dashboard styles, only on the two report screens.
	 *
	 * @param string $hook Admin hook suffix.
	 */
	public static function assets( string $hook ): void {
		$finance = str_ends_with( $hook, '_page_lr-academy-finance' );
		if ( 'toplevel_page_lr-academy' !== $hook && ! $finance ) {
			return;
		}
		wp_enqueue_style( 'lr-academy-admin', LIFERUSS_CORE_URL . 'assets/academy-admin.css', array(), LIFERUSS_CORE_VERSION );
		wp_enqueue_script( 'lr-chart', LIFERUSS_CORE_URL . 'assets/chart.umd.min.js', array(), '4.4.6', true );
		wp_enqueue_script( 'lr-academy-admin', LIFERUSS_CORE_URL . 'assets/academy-admin.js', array( 'lr-chart' ), LIFERUSS_CORE_VERSION, true );
	}

	/**
	 * Jalali range from the query. Empty fields mean the last 30 Tehran days.
	 *
	 * @return array{0: string, 1: string, 2: string, 3: string}
	 */
	private static function range(): array {
		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::bounds_from_text( $from, $to );
	}

	/**
	 * Range posted with an export.
	 *
	 * @return array{0: string, 1: string}
	 */
	private static function posted_range(): array {
		$from   = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the export handler.
		$to     = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the export handler.
		$bounds = self::bounds_from_text( $from, $to );
		return array( $bounds[0], $bounds[1] );
	}

	/**
	 * Resolve Jalali inputs to UTC bounds. Blank input uses the 30-day preset.
	 *
	 * @param string $from From text.
	 * @param string $to   To text.
	 * @return array{0: string, 1: string, 2: string, 3: string}
	 */
	private static function bounds_from_text( string $from, string $to ): array {
		$preset = Jalali::presets()['30'];
		if ( '' === $from ) {
			$from = $preset['from'];
		}
		if ( '' === $to ) {
			$to = $preset['to'];
		}
		$start = Jalali::filter_utc( $from, false );
		$end   = Jalali::filter_utc( $to, true );
		if ( '' === $start ) {
			$from  = $preset['from'];
			$start = Jalali::filter_utc( $from, false );
		}
		if ( '' === $end ) {
			$to  = $preset['to'];
			$end = Jalali::filter_utc( $to, true );
		}
		if ( '' === $start ) {
			$start = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		}
		if ( '' === $end ) {
			$end = Db::now();
		}
		return array( $start, $end, $from, $to );
	}

	/**
	 * Date filter with the current Jalali year and presets.
	 *
	 * @param string $page Admin page slug.
	 * @param string $from Resolved from.
	 * @param string $to   Resolved to.
	 */
	private static function range_form( string $page, string $from, string $to ): void {
		$hint = Jalali::year_hint();
		echo '<div class="lr-presets">';
		foreach ( Jalali::presets() as $preset ) {
			$url   = add_query_arg(
				array(
					'page' => $page,
					'from' => $preset['from'],
					'to'   => $preset['to'],
				),
				admin_url( 'admin.php' )
			);
			$class = ( $from === $preset['from'] && $to === $preset['to'] ) ? 'button button-primary' : 'button';
			echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $preset['label'] ) . '</a>';
		}
		echo '</div>';
		echo '<form class="lr-range" method="get"><input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
		echo '<input type="text" name="from" inputmode="numeric" value="' . esc_attr( $from ) . '" placeholder="' . esc_attr( 'از ' . $hint[0] ) . '"> ';
		echo '<input type="text" name="to" inputmode="numeric" value="' . esc_attr( $to ) . '" placeholder="' . esc_attr( 'تا ' . $hint[1] ) . '"> ';
		submit_button( 'اعمال', 'secondary', 'submit', false );
		echo '</form>';
	}

	/**
	 * One KPI card with a percent change against the previous window.
	 *
	 * @param string    $label   Label.
	 * @param string    $display Already formatted value.
	 * @param int|float $now     Current.
	 * @param int|float $prev    Previous.
	 * @param bool      $invert  True when an increase is worse, such as refunds.
	 */
	private static function kpi( string $label, string $display, $now, $prev, bool $invert ): void {
		$now  = (float) $now;
		$prev = (float) $prev;
		echo '<article class="lr-kpi"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $display ) . '</strong>';
		if ( 0.0 === $now && 0.0 === $prev ) {
			echo '<span class="lr-delta">—</span>';
		} else {
			$pct  = ( 0.0 === $prev ) ? 100.0 : ( ( $now - $prev ) * 100 ) / $prev;
			$up   = $now >= $prev;
			$good = $invert ? ! $up : $up;
			$mark = $up ? '▲' : '▼';
			echo '<span class="lr-delta ' . esc_attr( $good ? 'up' : 'down' ) . '">' . esc_html( $mark . ' ' . number_format_i18n( abs( $pct ), 1 ) . '٪' ) . '</span>';
		}
		echo '</article>';
	}

	/**
	 * Revenue bars for the selected window.
	 *
	 * @param array<int, array<string, mixed>> $days Days.
	 */
	private static function revenue_chart( array $days ): void {
		if ( ! $days ) {
			echo '<p class="lr-empty">در این بازه درآمدی ثبت نشده است.</p>';
			return;
		}
		$labels = array();
		$values = array();
		foreach ( $days as $day ) {
			$labels[] = self::axis_label( (string) $day['day'] );
			$values[] = (int) $day['amount'];
		}
		self::chart_canvas(
			'revenue',
			array(
				'type'    => 'bar',
				'data'    => array(
					'labels'   => $labels,
					'datasets' => array(
						array(
							'type'            => 'bar',
							'label'           => 'درآمد',
							'data'            => $values,
							'backgroundColor' => '#0b2341',
							'borderRadius'    => 4,
							'order'           => 2,
						),
						array(
							'type'            => 'line',
							'label'           => 'روند',
							'data'            => $values,
							'borderColor'     => '#e8b923',
							'backgroundColor' => '#e8b923',
							'tension'         => 0.3,
							'order'           => 1,
						),
					),
				),
				'options' => self::chart_options( false ),
			)
		);
	}

	/**
	 * Doughnut of course, plan, and class revenue.
	 *
	 * @param array<string, mixed> $now Current slice.
	 */
	private static function model_chart( array $now ): void {
		$values = array(
			(int) $now['courses'],
			(int) $now['subscriptions'],
			(int) $now['private'],
			(int) $now['group'],
		);
		$sum    = 0;
		foreach ( $values as $value ) {
			$sum += $value;
		}
		if ( $sum < 1 ) {
			echo '<p class="lr-empty">در این بازه فروشی ثبت نشده است.</p>';
			return;
		}
		self::chart_canvas(
			'models',
			array(
				'type'    => 'doughnut',
				'data'    => array(
					'labels'   => array( 'دوره', 'اشتراک', 'کلاس خصوصی', 'کلاس گروهی' ),
					'datasets' => array(
						array(
							'data'            => $values,
							'backgroundColor' => array( '#0b2341', '#e8b923', '#14325a', '#8aa0bd' ),
							'borderWidth'     => 0,
						),
					),
				),
				'options' => array(
					'responsive'          => true,
					'maintainAspectRatio' => false,
					'plugins'             => array(
						'legend' => array(
							'position' => 'bottom',
						),
					),
				),
			)
		);
	}

	/**
	 * Stacked bars, one series per LifeRuss revenue line.
	 *
	 * @param array<string, array<string, int>> $days Days.
	 */
	private static function finance_chart( array $days ): void {
		if ( ! $days ) {
			echo '<p class="lr-empty">در این بازه درآمدی ثبت نشده است.</p>';
			return;
		}
		$labels = array();
		foreach ( array_keys( $days ) as $day ) {
			$labels[] = self::axis_label( (string) $day );
		}
		$colors = array(
			'study'    => '#0b2341',
			'academy'  => '#e8b923',
			'exchange' => '#14325a',
			'cargo'    => '#8aa0bd',
			'trade'    => '#6b4b16',
		);
		$sets   = array();
		foreach ( Finance::lines() as $key => $label ) {
			$data = array();
			foreach ( $days as $lines ) {
				$data[] = (int) ( $lines[ $key ] ?? 0 );
			}
			$sets[] = array(
				'label'           => $label,
				'data'            => $data,
				'backgroundColor' => $colors[ $key ],
				'stack'           => 'revenue',
			);
		}
		$options                = self::chart_options( true );
		$options['scales']['x'] = array( 'stacked' => true );
		$scale_y                = $options['scales']['y'];
		$scale_y['stacked']     = true;
		$options['scales']['y'] = $scale_y;
		self::chart_canvas(
			'finance',
			array(
				'type'    => 'bar',
				'data'    => array(
					'labels'   => $labels,
					'datasets' => $sets,
				),
				'options' => $options,
			)
		);
	}

	/**
	 * Shared Chart.js options.
	 *
	 * @param bool $legend Show the legend.
	 * @return array<string, mixed>
	 */
	private static function chart_options( bool $legend ): array {
		return array(
			'responsive'          => true,
			'maintainAspectRatio' => false,
			'plugins'             => array(
				'legend' => array(
					'display'  => $legend,
					'position' => 'bottom',
				),
			),
			'scales'              => array(
				'y' => array(
					'beginAtZero' => true,
				),
			),
		);
	}

	/**
	 * Gregorian Y-m-d from MySQL as a Jalali axis label.
	 *
	 * @param string $day Date.
	 */
	private static function axis_label( string $day ): string {
		$parts = explode( '-', $day );
		if ( 3 !== count( $parts ) ) {
			return $day;
		}
		return Jalali::ymd( Jalali::to_jalali( (int) $parts[0], (int) $parts[1], (int) $parts[2] ) );
	}

	/**
	 * Emit a canvas whose config is read by academy-admin.js.
	 *
	 * @param string               $id     Canvas id.
	 * @param array<string, mixed> $config Chart.js config.
	 */
	private static function chart_canvas( string $id, array $config ): void {
		$json = wp_json_encode( $config );
		echo '<div class="lr-chart-box"><canvas id="' . esc_attr( 'lr-chart-' . $id ) . '" data-lr-chart="' . esc_attr( (string) $json ) . '"></canvas></div>';
	}

	/**
	 * Best-seller rows, or a sentence when the window has no course sales.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 */
	private static function bestsellers_table( array $rows ): void {
		if ( ! $rows ) {
			echo '<p class="lr-empty">در این بازه فروشی ثبت نشده است.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>دوره</th><th>فروش</th><th>مبلغ</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['title'] ) . '</td><td>' . esc_html( (string) $row['sales'] ) . '</td><td>' . esc_html( number_format_i18n( (int) $row['amount'] ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Latest orders this user is allowed to see.
	 */
	private static function recent_orders(): void {
		global $wpdb;
		$table = Db::table( 'orders' );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 20", ARRAY_A );
		$shown = 0;
		echo '<table class="widefat striped"><thead><tr><th>کد</th><th>وضعیت</th><th>مبلغ</th></tr></thead><tbody>';
		foreach ( (array) $rows as $row ) {
			if ( ! self::can_see_order( (int) $row['id'] ) ) {
				continue;
			}
			echo '<tr><td>' . esc_html( (string) $row['code'] ) . '</td><td>' . esc_html( (string) $row['status'] ) . '</td><td>' . esc_html( number_format_i18n( (int) $row['total'] ) ) . '</td></tr>';
			++$shown;
			if ( $shown >= 6 ) {
				break;
			}
		}
		if ( 0 === $shown ) {
			echo '<tr><td colspan="3">سفارش اخیری نیست.</td></tr>';
		}
		echo '</tbody></table>';
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
