<?php
/**
 * Append-only audit rows.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes lr_activity_logs.
 */
class ActivityLog {

	/**
	 * Record one action.
	 *
	 * @param string               $action      Action slug.
	 * @param string               $object_type Object type.
	 * @param int                  $object_id   Object id.
	 * @param string               $summary     Human summary.
	 * @param array<string, mixed> $changes     Optional diff.
	 */
	public static function record( string $action, string $object_type, int $object_id, string $summary, array $changes = array() ): void {
		global $wpdb;

		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		$agent = '';
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$agent = sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) );
		}

		$table = $wpdb->prefix . 'lr_activity_logs';
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"INSERT INTO {$table} (user_id, action, object_type, object_id, summary, changes, ip, user_agent, created_at) VALUES (%d, %s, %s, %d, %s, %s, INET6_ATON(%s), %s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_user_id(),
				$action,
				$object_type,
				$object_id,
				$summary,
				$changes ? wp_json_encode( $changes ) : null,
				'' !== $ip ? $ip : null,
				$agent,
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}
}
