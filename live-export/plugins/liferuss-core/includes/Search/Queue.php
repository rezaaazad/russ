<?php
/**
 * Deferred index updates for imports and Meilisearch retries.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * One row per object. A newer action replaces the pending one.
 */
class Queue {

	/**
	 * Store or replace a pending action.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post id.
	 * @param string $action    upsert or delete.
	 */
	public static function enqueue( string $post_type, int $post_id, string $action ): void {
		if ( $post_id < 1 || ! in_array( $action, array( 'upsert', 'delete' ), true ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_queue';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (object_type, object_id, action, attempts, created_at) VALUES (%s, %d, %s, 0, %s)
				ON DUPLICATE KEY UPDATE action = VALUES(action), attempts = 0, created_at = VALUES(created_at)",
				$post_type,
				$post_id,
				$action,
				$now
			)
		);
	}

	/**
	 * Pending rows, oldest first.
	 *
	 * @param int $limit Batch size.
	 * @return array<int, array<string, mixed>>
	 */
	public static function pending( int $limit = 40 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_queue';
		$limit = max( 1, min( 100, $limit ) );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id ASC LIMIT {$limit}", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Drop a row after it is applied.
	 *
	 * @param int $id Queue id.
	 */
	public static function done( int $id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'lr_search_queue', array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Count a failed Meilisearch attempt. Five failures drop the row.
	 *
	 * @param int $id Queue id.
	 */
	public static function failed( int $id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_queue';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT attempts FROM `{$table}` WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return;
		}
		$attempts = (int) $row['attempts'] + 1;
		if ( $attempts >= 5 ) {
			self::done( $id );
			return;
		}
		$wpdb->update(
			$table,
			array( 'attempts' => $attempts ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}
}
