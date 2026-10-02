<?php
/**
 * LifeRuss admin menu, grouped by job.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Academy\Admin as AcademyAdmin;
use LifeRuss\Core\Catalog\ImportScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top-level menus: dashboard, CRM, universities, finance, and settings.
 * Academy registers its own menu.
 */
class Menu {

	/**
	 * Register the menu.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register' ) );
	}

	/**
	 * Add the grouped menu tree.
	 */
	public static function register(): void {
		add_menu_page( 'پیشخوان لایف‌روس', 'پیشخوان لایف‌روس', 'lr_view_dashboard', 'liferuss', array( Screens::class, 'dashboard' ), 'dashicons-dashboard', 25 );
		add_submenu_page( 'liferuss', 'پیشخوان', 'پیشخوان', 'lr_view_dashboard', 'liferuss', array( Screens::class, 'dashboard' ) );
		self::link( 'liferuss', 'برگه‌ها', 'edit_pages', 'edit.php?post_type=page' );
		self::link( 'liferuss', 'مجله — نوشته‌ها', 'edit_posts', 'edit.php' );
		self::link( 'liferuss', 'مجله — دسته‌ها', 'manage_categories', 'edit-tags.php?taxonomy=category' );
		self::link( 'liferuss', 'دانستنی‌ها', 'edit_lr_guides', 'edit.php?post_type=lr_guide' );
		self::link( 'liferuss', 'سؤالات متداول', 'edit_lr_faqs', 'edit.php?post_type=lr_faq' );
		self::link( 'liferuss', 'نظرات مشتریان', 'edit_lr_testimonials', 'edit.php?post_type=lr_testimonial' );
		self::link( 'liferuss', 'رسانه', 'upload_files', 'upload.php' );

		add_menu_page( 'CRM', 'CRM', 'lr_access_crm', 'lr-crm', array( LeadAdmin::class, 'queue' ), 'dashicons-groups', 26 );
		$crm = array(
			array( 'lr-crm', 'صف مشترک', 'lr_assign_leads', array( LeadAdmin::class, 'queue' ) ),
			array( 'lr-leads', 'همه لیدها', 'lr_manage_leads', array( LeadAdmin::class, 'all' ) ),
			array( 'lr-my-leads', 'لیدهای من', 'lr_view_own_leads', array( LeadAdmin::class, 'mine' ) ),
			array( 'lr-kanban', 'کانبان', 'lr_access_crm', array( Kanban::class, 'render' ) ),
			array( 'lr-tasks', 'وظایف و پیگیری', 'lr_access_crm', array( LeadAdmin::class, 'tasks' ) ),
			array( 'lr-funnel', 'گزارش قیف', 'lr_manage_leads', array( LeadAdmin::class, 'funnel' ) ),
			array( 'lr-export', 'خروجی', 'lr_export_leads', array( LeadAdmin::class, 'export_screen' ) ),
			array( 'lr-req-admission', 'پذیرش', 'lr_access_admission', array( LeadAdmin::class, 'admission' ) ),
			array( 'lr-req-exchange', 'صرافی', 'lr_manage_exchange_requests', array( LeadAdmin::class, 'exchange' ) ),
			array( 'lr-req-cargo', 'کارگو', 'lr_manage_cargo_requests', array( LeadAdmin::class, 'cargo' ) ),
			array( 'lr-req-trade', 'تجارت', 'lr_manage_trade_requests', array( LeadAdmin::class, 'trade' ) ),
			array( 'lr-req-immigration', 'مهاجرت', 'lr_manage_immigration_requests', array( LeadAdmin::class, 'immigration' ) ),
		);
		foreach ( $crm as $item ) {
			add_submenu_page( 'lr-crm', $item[1], $item[1], $item[2], $item[0], $item[3] );
		}

		add_menu_page( 'دانشگاه‌ها', 'دانشگاه‌ها', 'lr_view_university_data', 'lr-uni', array( Screens::class, 'universities' ), 'dashicons-bank', 27 );
		add_submenu_page( null, 'دانشگاه‌ها', 'دانشگاه‌ها', 'lr_view_university_data', 'lr-universities', array( Screens::class, 'universities' ) );
		$uni = array(
			array( 'lr-uni', 'داده‌ها', 'lr_view_university_data', array( Screens::class, 'universities' ) ),
			array( 'lr-tuition', 'شهریه‌ها', 'lr_view_university_data', array( Screens::class, 'tuition' ) ),
			array( 'lr-programs', 'رشته‌ها', 'lr_manage_university_data', array( \LifeRuss\Core\Catalog\ProgramsScreen::class, 'render' ) ),
			array( 'lr-uni-csv', 'ورود و خروج CSV', 'lr_manage_university_data', array( ImportScreen::class, 'render' ) ),
			array( 'lr-stale', 'نیازمند بررسی', 'lr_manage_university_data', array( Screens::class, 'stale' ) ),
			array( 'lr-approvals', 'تأییدیه‌ها', 'lr_manage_university_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-rankings', 'رتبه‌بندی', 'lr_manage_university_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-prep', 'پادفک و کورس', 'lr_manage_academic_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-intakes', 'ورودی‌ها', 'lr_manage_academic_data', array( Screens::class, 'placeholder' ) ),
		);
		foreach ( $uni as $item ) {
			add_submenu_page( 'lr-uni', $item[1], $item[1], $item[2], $item[0], $item[3] );
		}
		self::link( 'lr-uni', 'فهرست دانشگاه‌ها', 'edit_lr_universities', 'edit.php?post_type=lr_university' );
		self::link( 'lr-uni', 'افزودن دانشگاه', 'create_lr_universities', 'post-new.php?post_type=lr_university' );
		self::link( 'lr-uni', 'رشته‌ها', 'edit_lr_fields', 'edit.php?post_type=lr_field' );
		self::link( 'lr-uni', 'شهرها', 'edit_lr_cities', 'edit.php?post_type=lr_city' );
		self::link( 'lr-uni', 'بورسیه‌ها', 'edit_lr_scholarships', 'edit.php?post_type=lr_scholarship' );
		add_submenu_page( 'lr-uni', 'انتقال بورسیه‌ها', 'انتقال بورسیه‌ها', 'edit_lr_scholarships', 'lr-scholarship-migrate', array( \LifeRuss\Core\Scholarships\Admin::class, 'screen' ) );

		add_menu_page( 'مالی', 'مالی', 'lr_view_finance', 'lr-finance', array( AcademyAdmin::class, 'finance' ), 'dashicons-chart-area', 29 );
		add_submenu_page( 'lr-finance', 'گزارش', 'گزارش', 'lr_view_finance', 'lr-finance', array( AcademyAdmin::class, 'finance' ) );
		add_submenu_page( 'lr-finance', 'گزارش خط‌ها', 'گزارش خط‌ها', 'lr_view_finance', 'lr-finance-report', array( AcademyAdmin::class, 'finance' ) );
		add_submenu_page( 'lr-finance', 'پرداخت‌های خدمات', 'پرداخت‌های خدمات', 'lr_manage_leads', 'lr-payments', array( \LifeRuss\Core\Payments\Admin::class, 'screen' ) );

		add_menu_page( 'تنظیمات', 'تنظیمات', 'lr_access_settings', 'lr-settings', array( \LifeRuss\Core\Settings\SettingsPage::class, 'render' ), 'dashicons-admin-generic', 30 );
		add_submenu_page( 'lr-settings', 'لایف‌روس', 'لایف‌روس', 'lr_access_settings', 'lr-settings', array( \LifeRuss\Core\Settings\SettingsPage::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'ریدایرکت‌ها', 'ریدایرکت‌ها', 'lr_view_redirects', 'lr-redirects', array( RedirectScreen::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'پایش ۴۰۴', 'پایش ۴۰۴', 'lr_manage_redirects', 'lr-404', array( NotFoundScreen::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'مقایسه دانشگاه', 'مقایسه دانشگاه', 'lr_edit_seo', 'lr-compare', array( CompareScreen::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'آمار جستجو', 'آمار جستجو', 'lr_edit_seo', 'lr-search-stats', array( SearchScreen::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'نسخه‌های تکراری', 'نسخه‌های تکراری', 'edit_posts', 'lr-duplicates', array( Duplicates::class, 'render' ) );
		add_submenu_page( 'lr-settings', 'نقش‌ها', 'نقش‌ها', 'lr_manage_roles', 'lr-roles', array( Screens::class, 'placeholder' ) );
		add_submenu_page( 'lr-settings', 'واژگان', 'واژگان', 'lr_edit_vocab', 'lr-vocab', array( Screens::class, 'placeholder' ) );
		add_submenu_page( 'lr-settings', 'گزارش فعالیت', 'گزارش فعالیت', 'lr_view_activity_log', 'lr-activity', array( Screens::class, 'placeholder' ) );
		self::link( 'lr-settings', 'کاربران', 'list_users', 'users.php' );
		self::link( 'lr-settings', 'زبان — دوره‌ها', 'edit_lr_courses', 'edit.php?post_type=lr_course' );
		self::link( 'lr-settings', 'زبان — درس‌ها', 'edit_lr_lessons', 'edit.php?post_type=lr_lesson' );
	}

	/**
	 * Submenu entry that points at a core admin URL.
	 *
	 * @param string $menu  Parent slug.
	 * @param string $title Menu title.
	 * @param string $cap   Capability.
	 * @param string $url   Admin path.
	 */
	private static function link( string $menu, string $title, string $cap, string $url ): void {
		if ( ! current_user_can( $cap ) ) {
			return;
		}
		global $submenu;
		if ( ! isset( $submenu[ $menu ] ) || ! is_array( $submenu[ $menu ] ) ) {
			return;
		}
		$submenu[ $menu ][] = array( $title, $cap, $url ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
