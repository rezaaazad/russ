<?php
/**
 * 404 list and one-click redirect creation.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Monitor\NotFound;
use LifeRuss\Core\Redirects\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO 404 screen.
 */
class NotFoundScreen {

	/**
	 * Admin post handler.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_404_redirect', array( self::class, 'redirect' ) );
	}

	/**
	 * List.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_manage_redirects' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		Chrome::open( 'پایش ۴۰۴', 'تنظیمات' );
		if ( isset( $_GET['made'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>ریدایرکت ساخته شد.</p></div>';
		}
		echo '<table class="widefat striped"><thead><tr><th>مسیر</th><th>تعداد</th><th>آخرین</th><th>ارجاع</th><th>ریدایرکت</th></tr></thead><tbody>';
		foreach ( NotFound::all() as $row ) {
			echo '<tr><td><code>' . esc_html( (string) $row['path'] ) . '</code></td>';
			echo '<td>' . (int) $row['hits'] . '</td>';
			echo '<td>' . esc_html( (string) $row['last_hit_at'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['referrer'] ) . '</td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_404_redirect' );
			echo '<input type="hidden" name="action" value="lr_404_redirect">';
			echo '<input type="hidden" name="id" value="' . (int) $row['id'] . '">';
			echo '<input class="regular-text" name="target_url" placeholder="/cargo/" required> ';
			echo '<button class="button">ساخت ریدایرکت</button></form></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Insert an exact 301 for the logged path.
	 */
	public static function redirect(): void {
		if ( ! current_user_can( 'lr_manage_redirects' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_404_redirect' );
		$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$row = NotFound::find( $id );
		if ( ! $row ) {
			wp_die( esc_html__( 'ردیف پیدا نشد.', 'liferuss-core' ) );
		}
		$target = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';
		Store::upsert(
			array(
				'source_path' => (string) $row['path'],
				'target_url'  => $target,
				'status_code' => 301,
				'match_type'  => 'exact',
				'origin'      => 'manual',
				'is_active'   => 1,
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=lr-404&made=1' ) );
		exit;
	}
}
