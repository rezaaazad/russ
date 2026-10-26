<?php
/**
 * Search index WP-CLI command.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp liferuss search reindex`.
 */
class Cli {

	/**
	 * Register the command.
	 */
	public static function hooks(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( 'liferuss search reindex', array( self::class, 'reindex' ) );
	}

	/**
	 * Rebuild the local index and push it to Meilisearch when configured.
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public static function reindex( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );
		$count = Indexer::reindex();
		\WP_CLI::success( 'Indexed ' . $count . ' documents.' );
	}
}
