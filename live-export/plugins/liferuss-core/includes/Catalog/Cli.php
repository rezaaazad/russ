<?php
/**
 * Catalog WP-CLI commands.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp liferuss import` and `wp liferuss export`.
 */
class Cli {

	/**
	 * Register commands.
	 */
	public static function hooks(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( 'liferuss import', array( self::class, 'import' ) );
		\WP_CLI::add_command( 'liferuss export', array( self::class, 'export' ) );
	}

	/**
	 * Upsert a catalog CSV by slug.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : universities, fields, cities, programs, or rankings.
	 *
	 * <file>
	 * : Path to the CSV file.
	 *
	 * [--dry-run]
	 * : Report creates and updates without writing.
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public static function import( array $args, array $assoc_args ): void {
		$type = $args[0] ?? '';
		$file = $args[1] ?? '';
		if ( '' === $file || ! is_readable( $file ) ) {
			\WP_CLI::error( 'CSV file is not readable.' );
		}
		$report = Importer::run( $type, $file, isset( $assoc_args['dry-run'] ) );
		\WP_CLI::log( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		if ( ! empty( $report['errors'] ) ) {
			\WP_CLI::warning( 'Some rows were skipped.' );
		}
		\WP_CLI::success( isset( $assoc_args['dry-run'] ) ? 'Dry run finished.' : 'Import finished.' );
	}

	/**
	 * Write a catalog CSV.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : universities, fields, cities, programs, or rankings.
	 *
	 * <file>
	 * : Destination path.
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public static function export( array $args, array $assoc_args ): void {
		unset( $assoc_args );
		$type = $args[0] ?? '';
		$file = $args[1] ?? '';
		if ( ! in_array( $type, Importer::types(), true ) ) {
			\WP_CLI::error( 'Unknown type.' );
		}
		if ( '' === $file ) {
			\WP_CLI::error( 'Destination file is required.' );
		}
		Importer::export( $type, $file );
		if ( ! is_readable( $file ) ) {
			\WP_CLI::error( 'Could not write the file.' );
		}
		\WP_CLI::success( 'Wrote ' . $file );
	}
}
