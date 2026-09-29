<?php
/**
 * LifeRuss admin menu.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Catalog\ImportScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top-level menu and module links, gated by capability.
 */
class Menu {

	/**
	 * Register the menu.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register' ) );
	}

	/**
	 * Add the menu tree from the admin-panel spec.
	 */
	public static function register(): void {
		add_menu_page(
			'لایف‌روس',
			'لایف‌روس',
			'lr_view_dashboard',
			'liferuss',
			array( Screens::class, 'dashboard' ),
			'dashicons-welcome-learn-more',
			25
		);

		$items = array(
			array( 'liferuss', 'داشبورد', 'lr_view_dashboard', array( Screens::class, 'dashboard' ) ),
			array( 'lr-leads-queue', 'CRM — صف مشترک', 'lr_assign_leads', array( LeadAdmin::class, 'queue' ) ),
			array( 'lr-leads', 'CRM — همه لیدها', 'lr_manage_leads', array( LeadAdmin::class, 'all' ) ),
			array( 'lr-my-leads', 'CRM — لیدهای من', 'lr_view_own_leads', array( LeadAdmin::class, 'mine' ) ),
			array( 'lr-tasks', 'CRM — وظایف و پیگیری', 'lr_access_crm', array( LeadAdmin::class, 'tasks' ) ),
			array( 'lr-funnel', 'CRM — گزارش قیف', 'lr_manage_leads', array( LeadAdmin::class, 'funnel' ) ),
			array( 'lr-export', 'CRM — خروجی', 'lr_export_leads', array( LeadAdmin::class, 'export_screen' ) ),
			array( 'lr-req-admission', 'درخواست‌ها — پذیرش', 'lr_access_admission', array( LeadAdmin::class, 'admission' ) ),
			array( 'lr-req-exchange', 'درخواست‌ها — صرافی', 'lr_manage_exchange_requests', array( LeadAdmin::class, 'exchange' ) ),
			array( 'lr-req-cargo', 'درخواست‌ها — کارگو', 'lr_manage_cargo_requests', array( LeadAdmin::class, 'cargo' ) ),
			array( 'lr-req-trade', 'درخواست‌ها — تجارت', 'lr_manage_trade_requests', array( LeadAdmin::class, 'trade' ) ),
			array( 'lr-universities', 'دانشگاه‌ها — داده‌ها', 'lr_view_university_data', array( Screens::class, 'universities' ) ),
			array( 'lr-tuition', 'دانشگاه‌ها — شهریه‌ها', 'lr_view_university_data', array( Screens::class, 'tuition' ) ),
			array( 'lr-approvals', 'دانشگاه‌ها — تأییدیه‌ها', 'lr_manage_university_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-rankings', 'دانشگاه‌ها — رتبه‌بندی', 'lr_manage_university_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-prep', 'دانشگاه‌ها — پادفک و کورس', 'lr_manage_academic_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-intakes', 'دانشگاه‌ها — ورودی‌ها', 'lr_manage_academic_data', array( Screens::class, 'placeholder' ) ),
			array( 'lr-uni-csv', 'دانشگاه‌ها — ورود/خروج CSV', 'lr_manage_university_data', array( ImportScreen::class, 'render' ) ),
			array( 'lr-stale', 'دانشگاه‌ها — نیازمند بررسی', 'lr_manage_university_data', array( Screens::class, 'stale' ) ),
			array( 'lr-services', 'خدمات و فرم‌ها', 'lr_manage_services', array( Screens::class, 'placeholder' ) ),
			array( 'lr-redirects', 'سئو — ریدایرکت‌ها', 'lr_view_redirects', array( Screens::class, 'placeholder' ) ),
			array( 'lr-404', 'سئو — پایش ۴۰۴', 'lr_manage_redirects', array( Screens::class, 'placeholder' ) ),
			array( 'lr-activity', 'گزارش فعالیت', 'lr_view_activity_log', array( Screens::class, 'placeholder' ) ),
			array( 'lr-roles', 'نقش‌ها و مجوزها', 'lr_manage_roles', array( Screens::class, 'placeholder' ) ),
		);

		foreach ( $items as $item ) {
			add_submenu_page( 'liferuss', $item[1], $item[1], $item[2], $item[0], $item[3] );
		}

		self::link( 'دانشگاه‌ها — فهرست', 'edit_lr_universities', 'edit.php?post_type=lr_university' );
		self::link( 'دانشگاه‌ها — افزودن', 'create_lr_universities', 'post-new.php?post_type=lr_university' );
		self::link( 'رشته‌ها', 'edit_lr_fields', 'edit.php?post_type=lr_field' );
		self::link( 'گروه‌های رشته', 'manage_lr_field_groups', 'edit-tags.php?taxonomy=lr_field_group&post_type=lr_field' );
		self::link( 'شهرها', 'edit_lr_cities', 'edit.php?post_type=lr_city' );
		self::link( 'برگه‌ها', 'edit_pages', 'edit.php?post_type=page' );
		self::link( 'مجله — نوشته‌ها', 'edit_posts', 'edit.php' );
		self::link( 'مجله — دسته‌ها', 'manage_categories', 'edit-tags.php?taxonomy=category' );
		self::link( 'دانستنی‌ها', 'edit_lr_guides', 'edit.php?post_type=lr_guide' );
		self::link( 'دسته‌های دانستنی', 'manage_lr_guide_cats', 'edit-tags.php?taxonomy=lr_guide_cat&post_type=lr_guide' );
		self::link( 'زبان — دوره‌ها', 'edit_lr_courses', 'edit.php?post_type=lr_course' );
		self::link( 'زبان — درس‌ها', 'edit_lr_lessons', 'edit.php?post_type=lr_lesson' );
		self::link( 'زبان — سطوح', 'manage_lr_levels', 'edit-tags.php?taxonomy=lr_level&post_type=lr_lesson' );
		self::link( 'سؤالات متداول', 'edit_lr_faqs', 'edit.php?post_type=lr_faq' );
		self::link( 'نظرات مشتریان', 'edit_lr_testimonials', 'edit.php?post_type=lr_testimonial' );
		self::link( 'رسانه', 'upload_files', 'upload.php' );
		self::link( 'کاربران', 'list_users', 'users.php' );

		add_submenu_page(
			'liferuss',
			'تنظیمات لایف‌روس',
			'تنظیمات لایف‌روس',
			'lr_access_settings',
			'lr-settings',
			array( \LifeRuss\Core\Settings\SettingsPage::class, 'render' )
		);

		add_submenu_page(
			'liferuss',
			'رشته‌ها و شهرها',
			'رشته‌ها و شهرها — مشاهده',
			'lr_view_academic_data',
			'lr-fields-view',
			array( Screens::class, 'placeholder' )
		);
		add_submenu_page(
			'liferuss',
			'واژگان',
			'واژگان',
			'lr_edit_vocab',
			'lr-vocab',
			array( Screens::class, 'placeholder' )
		);
	}

	/**
	 * Submenu entry that points at a core admin URL.
	 *
	 * @param string $title Menu title.
	 * @param string $cap   Capability.
	 * @param string $url   admin.php-relative or absolute admin path.
	 */
	private static function link( string $title, string $cap, string $url ): void {
		if ( ! current_user_can( $cap ) ) {
			return;
		}
		global $submenu;
		if ( ! isset( $submenu['liferuss'] ) || ! is_array( $submenu['liferuss'] ) ) {
			return;
		}
		// Core has no API for a submenu item that points at edit.php.
		$submenu['liferuss'][] = array( $title, $cap, $url ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
