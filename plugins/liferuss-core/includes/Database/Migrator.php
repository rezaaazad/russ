<?php
/**
 * Schema migrations with dbDelta and physical foreign keys.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Database;

use LifeRuss\Core\Academy\Schema as AcademySchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and upgrades {prefix}lr_* tables.
 */
class Migrator {

	/**
	 * Upgrade when the stored schema version differs.
	 */
	public static function maybe_upgrade(): void {
		$current = get_option( 'lr_db_version', '' );
		if ( LIFERUSS_CORE_DB_VERSION === $current ) {
			return;
		}
		self::migrate();
		Seeder::seed();
	}

	/**
	 * Run dbDelta and add foreign keys.
	 */
	public static function migrate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = self::charset_collate();
		$errors  = array();

		foreach ( self::catalogue() as $suffix => $def ) {
			$sql    = self::create_sql( $suffix, $def, $charset );
			$result = dbDelta( $sql );
			if ( ! empty( $wpdb->last_error ) ) {
				$errors[]         = $suffix . ': ' . $wpdb->last_error;
				$wpdb->last_error = '';
			}
			unset( $result );
		}

		self::add_foreign_keys( $errors );
		self::lesson_file_type();

		update_option( 'lr_db_version', LIFERUSS_CORE_DB_VERSION, false );
		update_option( 'lr_core_version', LIFERUSS_CORE_VERSION, false );
		if ( $errors ) {
			update_option( 'lr_last_migration_errors', $errors, false );
		} else {
			delete_option( 'lr_last_migration_errors' );
		}
	}

	/**
	 * Build a CREATE TABLE statement dbDelta can parse.
	 *
	 * @param string               $suffix   Table suffix.
	 * @param array<string, mixed> $def      Definition.
	 * @param string               $charset  Charset clause.
	 */
	public static function create_sql( string $suffix, array $def, string $charset ): string {
		global $wpdb;

		$table = $wpdb->prefix . 'lr_' . $suffix;
		$lines = array();
		foreach ( $def['columns'] as $column => $type ) {
			$lines[] = "\t{$column} {$type}";
		}

		if ( is_array( $def['primary'] ) ) {
			$lines[] = "\tPRIMARY KEY  (" . implode( ', ', $def['primary'] ) . ')';
		} else {
			$lines[] = "\tPRIMARY KEY  ({$def['primary']})";
		}

		foreach ( $def['indexes'] as $index ) {
			$lines[] = "\t{$index}";
		}

		$body = implode( ",\n", $lines );
		return "CREATE TABLE {$table} (\n{$body}\n) ENGINE=InnoDB {$charset};";
	}

	/**
	 * Prefer the ERD collation and fall back to the WordPress default.
	 */
	private static function charset_collate(): string {
		global $wpdb;

		$preferred = 'utf8mb4_unicode_520_ci';
		$found     = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COLLATION_NAME FROM information_schema.COLLATIONS WHERE COLLATION_NAME = %s',
				$preferred
			)
		);
		if ( $found ) {
			return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
		}
		return $wpdb->get_charset_collate();
	}

	/**
	 * Add ON DELETE RESTRICT foreign keys between lr_* tables.
	 *
	 * @param string[] $errors Collected errors, by reference.
	 */
	private static function add_foreign_keys( array &$errors ): void {
		global $wpdb;

		foreach ( self::catalogue() as $suffix => $def ) {
			if ( empty( $def['fks'] ) ) {
				continue;
			}
			$table = $wpdb->prefix . 'lr_' . $suffix;
			foreach ( $def['fks'] as $fk ) {
				$name   = self::constraint_name( (string) $fk['name'] );
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s',
						$table,
						$name
					)
				);
				if ( $exists ) {
					continue;
				}
				$ref = $wpdb->prefix . 'lr_' . $fk['ref_table'];
				$sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$ref}` (`{$fk['ref_column']}`) ON DELETE RESTRICT ON UPDATE RESTRICT";
				$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( ! empty( $wpdb->last_error ) ) {
					$errors[]         = 'fk ' . $name . ': ' . $wpdb->last_error;
					$wpdb->last_error = '';
				}
			}
		}
	}

	/**
	 * Core tables plus the Academy catalogue.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalogue(): array {
		return array_merge( Tables::all(), AcademySchema::tables() );
	}

	/**
	 * Lesson type gains a file value. dbDelta does not alter enums.
	 */
	private static function lesson_file_type(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_academy_course_lessons';
		$col   = $wpdb->get_row( "SHOW COLUMNS FROM `{$table}` LIKE 'type'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! is_array( $col ) || ! isset( $col['Type'] ) || str_contains( (string) $col['Type'], 'file' ) ) {
			return;
		}
		$wpdb->query( "ALTER TABLE `{$table}` MODIFY `type` enum('video','text','quiz','live','file') NOT NULL DEFAULT 'video'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->last_error = '';
	}

	/**
	 * Constraint identifiers are unique per database and at most 64 characters.
	 *
	 * @param string $short Short name from the catalogue.
	 */
	private static function constraint_name( string $short ): string {
		global $wpdb;

		$raw = $wpdb->prefix . 'lr_' . $short . '_fk';
		$raw = preg_replace( '/[^A-Za-z0-9_]/', '', $raw );
		return substr( (string) $raw, 0, 64 );
	}
}
