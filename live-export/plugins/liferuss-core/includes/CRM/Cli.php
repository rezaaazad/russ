<?php
/**
 * WP-CLI commands.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp liferuss migrate-leads`.
 */
class Cli {

	/**
	 * Register commands when WP-CLI is loaded.
	 */
	public static function hooks(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( 'liferuss migrate-leads', array( self::class, 'migrate' ) );
	}

	/**
	 * Import liferuss_lead posts. Pass --dry-run to report without writing.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * [--batch=<n>]
	 * : Posts per run. Default 50. Ignored for a full dry-run scan.
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public static function migrate( array $args, array $assoc_args ): void {
		unset( $args );
		$dry    = isset( $assoc_args['dry-run'] );
		$batch  = isset( $assoc_args['batch'] ) ? (int) $assoc_args['batch'] : 50;
		$report = LegacyMigrator::run( $dry, $batch );
		\WP_CLI::log( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		if ( ! empty( $report['errors'] ) ) {
			\WP_CLI::warning( 'Migration stopped on an error. Run again to resume.' );
			return;
		}
		\WP_CLI::success( $dry ? 'Dry run finished.' : 'Batch finished.' );
	}
}
