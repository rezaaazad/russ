<?php
/**
 * Review list of duplicate slugs. Nothing is deleted from here.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools screen for posts and pages whose slug ends in -2.
 */
class Duplicates {

	/**
	 * Render the review table.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'اجازهٔ این صفحه را ندارید.', 'liferuss-core' ) );
		}
		global $wpdb;
		$types = array( 'post', 'page', 'lr_guide', 'lr_lesson' );
		$rows  = array();
		foreach ( $types as $type ) {
			$found = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_name, post_type, post_status FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash','auto-draft') AND post_name LIKE %s ORDER BY post_name ASC LIMIT 200",
					$type,
					'%-2'
				)
			);
			if ( is_array( $found ) ) {
				$rows = array_merge( $rows, $found );
			}
		}
		echo '<div class="wrap" dir="rtl">';
		echo '<h1>' . esc_html__( 'نسخه‌های تکراری', 'liferuss-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'این فهرست نامک‌هایی است که به «-2» ختم می‌شوند. چیزی خودکار حذف نمی‌شود. هر ردیف را در ویرایشگر بررسی کنید، در صورت تکرار به زباله‌دان ببرید و در صورت نیاز ریدایرکت ۳۰۱ بسازید.', 'liferuss-core' ) . '</p>';
		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'موردی با این الگو پیدا نشد.', 'liferuss-core' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'عنوان', 'liferuss-core' ) . '</th>';
		echo '<th>' . esc_html__( 'نامک', 'liferuss-core' ) . '</th>';
		echo '<th>' . esc_html__( 'نوع', 'liferuss-core' ) . '</th>';
		echo '<th>' . esc_html__( 'وضعیت', 'liferuss-core' ) . '</th>';
		echo '<th>' . esc_html__( 'ویرایش', 'liferuss-core' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$edit = get_edit_post_link( (int) $row->ID, 'raw' );
			echo '<tr>';
			echo '<td>' . esc_html( (string) $row->post_title ) . '</td>';
			echo '<td><code>' . esc_html( (string) $row->post_name ) . '</code></td>';
			echo '<td>' . esc_html( (string) $row->post_type ) . '</td>';
			echo '<td>' . esc_html( (string) $row->post_status ) . '</td>';
			echo '<td>';
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'بازبینی', 'liferuss-core' ) . '</a>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
