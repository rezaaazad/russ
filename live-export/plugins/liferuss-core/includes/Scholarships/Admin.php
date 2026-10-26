<?php
/**
 * Dry-run and real scholarship migration.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Scholarships;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copies CPT meta into lr_scholarships.
 */
class Admin {

	/**
	 * Register the POST handler.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_scholarship_migrate', array( self::class, 'migrate' ) );
		add_action( 'pre_get_posts', array( self::class, 'filter_archive' ) );
	}

	/**
	 * Migration screen.
	 */
	public static function screen(): void {
		if ( ! current_user_can( 'edit_lr_scholarships' ) ) {
			wp_die( esc_html__( 'به بورسیه‌ها دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'انتقال بورسیه‌ها به جدول', 'liferuss-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'نوشتهٔ بورسیه برای محتوا و سئو می‌ماند. فیلدهای ساخت‌یافته از جدول خوانده می‌شوند. ردیف موجود دوباره نوشته نمی‌شود.', 'liferuss-core' ) . '</p>';
		$notice = get_transient( 'lr_scholarship_migrate_' . get_current_user_id() );
		if ( is_array( $notice ) ) {
			delete_transient( 'lr_scholarship_migrate_' . get_current_user_id() );
			$mode = ! empty( $notice['dry'] ) ? 'آزمایشی' : 'واقعی';
			echo '<div class="notice notice-success"><p>' . esc_html( $mode . ': ' . (int) $notice['posts'] . ' پست، ' . (int) $notice['existing'] . ' ردیف موجود، ' . (int) $notice['inserted'] . ' ردیف تازه.' ) . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_scholarship_migrate' );
		echo '<input type="hidden" name="action" value="lr_scholarship_migrate">';
		echo '<label><input type="checkbox" name="dry_run" value="1" checked> ' . esc_html__( 'فقط آزمایش (بدون نوشتن)', 'liferuss-core' ) . '</label> ';
		submit_button( __( 'اجرا', 'liferuss-core' ), 'primary', 'submit', false );
		echo '</form></div>';
	}

	/**
	 * Run the migration.
	 */
	public static function migrate(): void {
		check_admin_referer( 'lr_scholarship_migrate' );
		if ( ! current_user_can( 'edit_lr_scholarships' ) ) {
			wp_die( esc_html__( 'به بورسیه‌ها دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$dry           = ! empty( $_POST['dry_run'] );
		$report        = Store::migrate( $dry );
		$report['dry'] = $dry;
		set_transient( 'lr_scholarship_migrate_' . get_current_user_id(), $report, MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=lr-scholarship-migrate' ) );
		exit;
	}

	/**
	 * Limit the public archive to table filters.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function filter_archive( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'lr_scholarship' ) ) {
			return;
		}
		$ids = Store::matching_posts(
			array(
				'degree'     => isset( $_GET['degree'] ) ? sanitize_key( wp_unslash( (string) $_GET['degree'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'coverage'   => isset( $_GET['coverage'] ) ? sanitize_key( wp_unslash( (string) $_GET['coverage'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'language'   => isset( $_GET['language'] ) ? sanitize_key( wp_unslash( (string) $_GET['language'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'university' => isset( $_GET['university'] ) ? absint( $_GET['university'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);
		if ( null === $ids ) {
			return;
		}
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
}
