<?php
/**
 * Count 404 responses and prune them on a daily cron.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Monitor;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Not-found log rows.
 */
class NotFound {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'template_redirect', array( self::class, 'log' ), 1 );
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'lr_purge_not_found', array( self::class, 'purge' ) );
	}

	/**
	 * Record one public 404. Redirects have already run.
	 */
	public static function log(): void {
		if ( is_admin() || ! is_404() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$path = function_exists( 'liferuss_current_path' ) ? (string) liferuss_current_path() : '';
		if ( '' === $path || '/' === $path ) {
			$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
			$path = $uri;
		}
		$path = '/' . trim( (string) rawurldecode( $path ), '/' );
		if ( '/' === $path ) {
			return;
		}
		$path = trailingslashit( $path );
		if ( preg_match( '#^/(wp-json|wp-admin|wp-includes|favicon\.ico|robots\.txt|apple-touch)#', $path ) ) {
			return;
		}
		self::hit( $path );
	}

	/**
	 * Increment or insert one path.
	 *
	 * @param string $path Normalized path.
	 */
	public static function hit( string $path ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_not_found';
		$hash  = sha1( $path );
		$now   = gmdate( 'Y-m-d H:i:s' );
		$ref   = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['HTTP_REFERER'] ) ) : '';
		$ref   = substr( $ref, 0, 500 );
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE path_hash = %s", $hash ) );
		if ( $found ) {
			$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET hits = hits + 1, last_hit_at = %s, referrer = %s WHERE id = %d", $now, $ref, (int) $found ) );
			return;
		}
		$wpdb->insert(
			$table,
			array(
				'path'        => substr( $path, 0, 500 ),
				'path_hash'   => $hash,
				'hits'        => 1,
				'referrer'    => $ref,
				'last_hit_at' => $now,
				'created_at'  => $now,
			)
		);
	}

	/**
	 * Recent rows for the admin list.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_not_found';
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY hits DESC, last_hit_at DESC LIMIT 200", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One row.
	 *
	 * @param int $id Row id.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_not_found';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Schedule the daily prune.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'lr_purge_not_found' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lr_purge_not_found' );
		}
	}

	/**
	 * Delete rows that have not been seen within the retention window.
	 */
	public static function purge(): void {
		global $wpdb;
		$settings = Settings::get( 'security' );
		$days     = isset( $settings['not_found_days'] ) ? (int) $settings['not_found_days'] : 90;
		if ( $days < 1 ) {
			$days = 90;
		}
		$table = $wpdb->prefix . 'lr_not_found';
		$cut   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE last_hit_at < %s", $cut ) );
	}
}
