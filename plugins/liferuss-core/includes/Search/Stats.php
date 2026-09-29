<?php
/**
 * Aggregated search queries.
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
 * Top queries and zero-result queries.
 */
class Stats {

	/**
	 * Count one results-page query. Suggestions are not recorded.
	 *
	 * @param string $query Normalized query.
	 * @param bool   $zero  Whether the query returned nothing.
	 */
	public static function record( string $query, bool $zero ): void {
		$query = Text::normalize( $query );
		if ( mb_strlen( $query ) < 2 ) {
			return;
		}
		$query = mb_substr( $query, 0, 191 );
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_stats';
		$hash  = sha1( $query );
		$now   = gmdate( 'Y-m-d H:i:s' );
		$zeros = $zero ? 1 : 0;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (query_hash, query_text, total, zeros, last_at) VALUES (%s, %s, 1, %d, %s)
				ON DUPLICATE KEY UPDATE total = total + 1, zeros = zeros + %d, last_at = %s, query_text = %s",
				$hash,
				$query,
				$zeros,
				$now,
				$zeros,
				$now,
				$query
			)
		);
	}

	/**
	 * Queries ordered by total hits.
	 *
	 * @param int $limit Row cap.
	 * @return array<int, array<string, mixed>>
	 */
	public static function top( int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_stats';
		$limit = max( 1, min( 200, $limit ) );
		$rows  = $wpdb->get_results( "SELECT query_text, total, zeros, last_at FROM `{$table}` ORDER BY total DESC, last_at DESC LIMIT {$limit}", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Queries that have returned no results at least once.
	 *
	 * @param int $limit Row cap.
	 * @return array<int, array<string, mixed>>
	 */
	public static function zeros( int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_stats';
		$limit = max( 1, min( 200, $limit ) );
		$rows  = $wpdb->get_results( "SELECT query_text, total, zeros, last_at FROM `{$table}` WHERE zeros > 0 ORDER BY zeros DESC, last_at DESC LIMIT {$limit}", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}
}
