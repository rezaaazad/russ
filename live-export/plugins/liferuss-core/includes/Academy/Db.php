<?php
/**
 * Small query helper for lr_academy_* tables.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are internal and cannot be placeholders.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Reads and writes Academy rows.
 */
class Db {

	/**
	 * Physical table name.
	 *
	 * @param string $suffix Suffix after lr_academy_.
	 */
	public static function table( string $suffix ): string {
		global $wpdb;
		return $wpdb->prefix . 'lr_academy_' . $suffix;
	}

	/**
	 * UTC timestamp.
	 */
	public static function now(): string {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Insert a row and return its id.
	 *
	 * @param string               $suffix Table suffix.
	 * @param array<string, mixed> $data   Columns.
	 */
	public static function insert( string $suffix, array $data ): int {
		global $wpdb;
		$now = self::now();
		if ( ! isset( $data['created_at'] ) ) {
			$data['created_at'] = $now;
		}
		if ( ! isset( $data['updated_at'] ) ) {
			$data['updated_at'] = $now;
		}
		$ok = $wpdb->insert( self::table( $suffix ), $data );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update a row by id.
	 *
	 * @param string               $suffix Table suffix.
	 * @param int                  $id     Row id.
	 * @param array<string, mixed> $data   Columns.
	 */
	public static function update( string $suffix, int $id, array $data ): bool {
		global $wpdb;
		if ( ! isset( $data['updated_at'] ) ) {
			$data['updated_at'] = self::now();
		}
		$result = $wpdb->update( self::table( $suffix ), $data, array( 'id' => $id ) );
		return false !== $result;
	}

	/**
	 * One row by id.
	 *
	 * @param string $suffix Table suffix.
	 * @param int    $id     Row id.
	 * @return array<string, mixed>|null
	 */
	public static function find( string $suffix, int $id ): ?array {
		global $wpdb;
		$table = self::table( $suffix );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * One row matched on a single column.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $column Column name from a fixed list.
	 * @param string $value  Value.
	 * @return array<string, mixed>|null
	 */
	public static function find_by( string $suffix, string $column, string $value ): ?array {
		if ( ! self::column( $column ) ) {
			return null;
		}
		global $wpdb;
		$table = self::table( $suffix );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$column}` = %s LIMIT 1", $value ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Rows for a simple equality, ordered by sort_order then id.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $column Column.
	 * @param int    $value  Value.
	 * @return array<int, array<string, mixed>>
	 */
	public static function where_id( string $suffix, string $column, int $value ): array {
		if ( ! self::column( $column ) ) {
			return array();
		}
		global $wpdb;
		$table = self::table( $suffix );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$column}` = %d ORDER BY id ASC", $value ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Child rows in display order.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $column Parent column.
	 * @param int    $value  Parent id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sorted( string $suffix, string $column, int $value ): array {
		if ( ! self::column( $column ) ) {
			return array();
		}
		global $wpdb;
		$table = self::table( $suffix );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$column}` = %d ORDER BY sort_order ASC, id ASC", $value ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Published rows ordered for public lists.
	 *
	 * @param string $suffix Table suffix.
	 * @return array<int, array<string, mixed>>
	 */
	public static function published( string $suffix ): array {
		global $wpdb;
		$table = self::table( $suffix );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE status = 'published' ORDER BY sort_order ASC, id ASC", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Allow only known column identifiers.
	 *
	 * @param string $column Column.
	 */
	private static function column( string $column ): bool {
		return in_array(
			$column,
			array(
				'id',
				'slug',
				'code',
				'token',
				'user_id',
				'course_id',
				'module_id',
				'lesson_id',
				'student_id',
				'order_id',
				'quiz_id',
				'session_id',
				'instructor_id',
				'category_id',
				'plan_id',
				'legacy_post_id',
				'scope_type',
			),
			true
		);
	}
}
