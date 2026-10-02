<?php
/**
 * Search analytics.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Search\Stats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top queries and zero-result queries.
 */
class SearchScreen {

	/**
	 * Screen.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap lr-wrap"><h1>آمار جستجو</h1>';
		echo '<h2>پرسش‌های پرتکرار</h2>';
		self::table( Stats::top() );
		echo '<h2>بدون نتیجه</h2>';
		self::table( Stats::zeros() );
		echo '</div>';
	}

	/**
	 * One stats table.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 */
	private static function table( array $rows ): void {
		echo '<table class="widefat striped"><thead><tr><th>عبارت</th><th>تعداد</th><th>بدون نتیجه</th><th>آخرین</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="4">موردی نیست.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['query_text'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['total'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['zeros'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['last_at'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
}
