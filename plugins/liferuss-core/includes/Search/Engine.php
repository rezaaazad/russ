<?php
/**
 * Site search. Meilisearch when it answers, otherwise the local FULLTEXT table.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders

/**
 * Suggestions and faceted results.
 */
class Engine {

	/**
	 * Public type slugs.
	 *
	 * @return string[]
	 */
	public static function type_slugs(): array {
		return array( 'university', 'field', 'city', 'scholarship', 'guide', 'post' );
	}

	/**
	 * Persian label for a type slug.
	 *
	 * @param string $type Type slug.
	 */
	public static function type_label( string $type ): string {
		$labels = array(
			'university'  => 'دانشگاه',
			'field'       => 'رشته',
			'city'        => 'شهر',
			'scholarship' => 'بورسیه',
			'guide'       => 'دانستنی',
			'post'        => 'مجله',
		);
		return $labels[ $type ] ?? $type;
	}

	/**
	 * Faceted results. Logs analytics when asked. Suggestions must pass false.
	 *
	 * @param string $query Raw query.
	 * @param string $type  Type filter.
	 * @param string $city  City slug filter.
	 * @param int    $page  Page.
	 * @param bool   $log   Whether to record the query.
	 * @return array<string, mixed>
	 */
	public static function search( string $query, string $type, string $city, int $page, bool $log ): array {
		$query = Text::normalize( $query );
		$type  = in_array( $type, self::type_slugs(), true ) ? $type : '';
		$city  = sanitize_title( $city );
		$page  = max( 1, $page );
		if ( mb_strlen( $query ) < 2 ) {
			return self::empty_result( $query, $type, $city, $page );
		}

		$key = 'lr_se_' . md5( $query . '|' . $type . '|' . $city . '|' . $page );
		$hit = get_transient( $key );
		if ( ! is_array( $hit ) ) {
			$hit = self::query( $query, $type, $city, $page, 20 );
			set_transient( $key, $hit, 2 * MINUTE_IN_SECONDS );
		}
		if ( $log ) {
			Stats::record( $query, 0 === (int) $hit['total'] );
		}
		return $hit;
	}

	/**
	 * Instant suggestions. Not logged.
	 *
	 * @param string $query Raw query.
	 * @return array<int, array<string, string>>
	 */
	public static function suggest( string $query ): array {
		$query = Text::normalize( $query );
		if ( mb_strlen( $query ) < 2 ) {
			return array();
		}
		$key = 'lr_sg_' . md5( $query );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$result = self::query( $query, '', '', 1, 8 );
		$items  = array();
		foreach ( $result['items'] as $item ) {
			$items[] = array(
				'title' => (string) $item['title'],
				'url'   => (string) $item['url'],
				'type'  => (string) $item['type'],
				'label' => self::type_label( (string) $item['type'] ),
			);
		}
		set_transient( $key, $items, 2 * MINUTE_IN_SECONDS );
		return $items;
	}

	/**
	 * Run Meili or MySQL.
	 *
	 * @param string $query Normalized query.
	 * @param string $type  Type.
	 * @param string $city  City slug.
	 * @param int    $page  Page.
	 * @param int    $limit Limit.
	 * @return array<string, mixed>
	 */
	private static function query( string $query, string $type, string $city, int $page, int $limit ): array {
		$remote = null;
		if ( Meili::configured() ) {
			$remote = Meili::search( $query, $type, $city, $page, $limit );
		}
		if ( is_array( $remote ) ) {
			$remote['query']       = $query;
			$remote['type']        = $type;
			$remote['city']        = $city;
			$remote['page']        = $page;
			$remote['pages']       = max( 1, (int) ceil( $remote['total'] / $limit ) );
			$remote['type_facets'] = self::type_facets( $query, $city );
			$remote['cities']      = self::city_facets( $query, $type );
			return $remote;
		}
		return self::mysql( $query, $type, $city, $page, $limit );
	}

	/**
	 * Empty payload.
	 *
	 * @param string $query Query.
	 * @param string $type  Type.
	 * @param string $city  City.
	 * @param int    $page  Page.
	 * @return array<string, mixed>
	 */
	private static function empty_result( string $query, string $type, string $city, int $page ): array {
		return array(
			'items'       => array(),
			'total'       => 0,
			'facets'      => array(),
			'type_facets' => array(),
			'cities'      => array(),
			'engine'      => 'none',
			'query'       => $query,
			'type'        => $type,
			'city'        => $city,
			'page'        => $page,
			'pages'       => 1,
		);
	}

	/**
	 * FULLTEXT plus LIKE, with a short Latin typo pass.
	 *
	 * @param string $query Normalized query.
	 * @param string $type  Type.
	 * @param string $city  City slug.
	 * @param int    $page  Page.
	 * @param int    $limit Limit.
	 * @return array<string, mixed>
	 */
	private static function mysql( string $query, string $type, string $city, int $page, int $limit ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'lr_search_docs';
		$like    = '%' . $wpdb->esc_like( $query ) . '%';
		$compact = '%' . $wpdb->esc_like( Text::compact( $query ) ) . '%';
		$where   = '(title LIKE %s OR text_norm LIKE %s OR aliases LIKE %s OR text_norm LIKE %s OR aliases LIKE %s)';
		$params  = array( $like, $like, $like, $compact, $compact );
		$bool    = trim( (string) preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $query ) );
		if ( mb_strlen( $bool ) >= 3 ) {
			$where   .= ' OR MATCH(title, text_norm, aliases) AGAINST (%s IN NATURAL LANGUAGE MODE)';
			$params[] = $bool;
		}
		$typo = self::typo_keys( $query );
		if ( $typo ) {
			$marks  = implode( ',', array_fill( 0, count( $typo ), '%s' ) );
			$where .= " OR doc_key IN ({$marks})";
			$params = array_merge( $params, $typo );
		}
		$filter = "({$where})";
		if ( $type ) {
			$filter  .= ' AND object_type = %s';
			$params[] = $type;
		}
		if ( $city ) {
			$filter  .= ' AND city_slug = %s';
			$params[] = $city;
		}
		$offset   = ( $page - 1 ) * $limit;
		$sql      = "SELECT SQL_CALC_FOUND_ROWS doc_key, object_type, title, excerpt, url, slug, city FROM `{$table}` WHERE {$filter} ORDER BY title ASC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;
		$rows     = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( ! is_array( $rows ) || $wpdb->last_error ) {
			$wpdb->last_error = '';
			$rows             = self::like_only( $query, $type, $city, $page, $limit );
			$total            = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		} else {
			$total = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}
		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = array(
				'type'    => (string) $row['object_type'],
				'title'   => (string) $row['title'],
				'excerpt' => (string) $row['excerpt'],
				'url'     => (string) $row['url'],
				'slug'    => (string) $row['slug'],
				'city'    => (string) $row['city'],
			);
		}
		return array(
			'items'       => $items,
			'total'       => $total,
			'facets'      => array(),
			'type_facets' => self::type_facets( $query, $city ),
			'cities'      => self::city_facets( $query, $type ),
			'engine'      => 'mysql',
			'query'       => $query,
			'type'        => $type,
			'city'        => $city,
			'page'        => $page,
			'pages'       => max( 1, (int) ceil( $total / $limit ) ),
		);
	}

	/**
	 * LIKE-only query used when FULLTEXT rejects the statement.
	 *
	 * @param string $query Query.
	 * @param string $type  Type.
	 * @param string $city  City.
	 * @param int    $page  Page.
	 * @param int    $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	private static function like_only( string $query, string $type, string $city, int $page, int $limit ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'lr_search_docs';
		$like    = '%' . $wpdb->esc_like( $query ) . '%';
		$compact = '%' . $wpdb->esc_like( Text::compact( $query ) ) . '%';
		$filter  = '(title LIKE %s OR text_norm LIKE %s OR aliases LIKE %s OR text_norm LIKE %s)';
		$params  = array( $like, $like, $like, $compact );
		if ( $type ) {
			$filter  .= ' AND object_type = %s';
			$params[] = $type;
		}
		if ( $city ) {
			$filter  .= ' AND city_slug = %s';
			$params[] = $city;
		}
		$params[] = $limit;
		$params[] = ( $page - 1 ) * $limit;
		$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS doc_key, object_type, title, excerpt, url, slug, city FROM `{$table}` WHERE {$filter} ORDER BY title ASC LIMIT %d OFFSET %d", $params ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Counts per type for the same text match, ignoring the type filter.
	 *
	 * @param string $query Query.
	 * @param string $city  City slug.
	 * @return array<string, int>
	 */
	private static function type_facets( string $query, string $city ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'lr_search_docs';
		$like    = '%' . $wpdb->esc_like( $query ) . '%';
		$compact = '%' . $wpdb->esc_like( Text::compact( $query ) ) . '%';
		$filter  = '(title LIKE %s OR text_norm LIKE %s OR aliases LIKE %s OR text_norm LIKE %s)';
		$params  = array( $like, $like, $like, $compact );
		if ( $city ) {
			$filter  .= ' AND city_slug = %s';
			$params[] = $city;
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT object_type, COUNT(*) AS n FROM `{$table}` WHERE {$filter} GROUP BY object_type", $params ), ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['object_type'] ] = (int) $row['n'];
		}
		return $out;
	}

	/**
	 * City counts for the current text and type.
	 *
	 * @param string $query Query.
	 * @param string $type  Type.
	 * @return array<int, array<string, mixed>>
	 */
	private static function city_facets( string $query, string $type ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'lr_search_docs';
		$like    = '%' . $wpdb->esc_like( $query ) . '%';
		$compact = '%' . $wpdb->esc_like( Text::compact( $query ) ) . '%';
		$filter  = "(city_slug <> '' AND (title LIKE %s OR text_norm LIKE %s OR aliases LIKE %s OR text_norm LIKE %s))";
		$params  = array( $like, $like, $like, $compact );
		if ( $type ) {
			$filter  .= ' AND object_type = %s';
			$params[] = $type;
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT city_slug, city, COUNT(*) AS n FROM `{$table}` WHERE {$filter} GROUP BY city_slug, city ORDER BY n DESC LIMIT 12", $params ), ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'slug'  => (string) $row['city_slug'],
				'name'  => (string) $row['city'],
				'count' => (int) $row['n'],
			);
		}
		return $out;
	}

	/**
	 * Document keys whose Latin tokens are within a small edit distance.
	 *
	 * @param string $query Normalized query.
	 * @return string[]
	 */
	private static function typo_keys( string $query ): array {
		if ( ! preg_match( '/[a-z0-9]/', $query ) ) {
			return array();
		}
		$pool = get_transient( 'lr_search_typo_pool' );
		if ( ! is_array( $pool ) ) {
			global $wpdb;
			$table = $wpdb->prefix . 'lr_search_docs';
			$pool  = $wpdb->get_results( "SELECT doc_key, slug, title, aliases FROM `{$table}` WHERE object_type = 'university' ORDER BY id ASC LIMIT 500", ARRAY_A );
			$pool  = is_array( $pool ) ? $pool : array();
			set_transient( 'lr_search_typo_pool', $pool, 10 * MINUTE_IN_SECONDS );
		}
		$needles = preg_split( '/\s+/', $query );
		$needles = is_array( $needles ) ? array_values( array_filter( $needles ) ) : array();
		if ( ! $needles ) {
			return array();
		}
		$keys = array();
		foreach ( $pool as $row ) {
			$hay    = Text::normalize( (string) $row['slug'] . ' ' . (string) $row['title'] . ' ' . (string) $row['aliases'] );
			$tokens = preg_split( '/\s+/', $hay );
			if ( ! is_array( $tokens ) ) {
				continue;
			}
			$matched = 0;
			foreach ( $needles as $needle ) {
				if ( ! preg_match( '/^[a-z0-9]+$/', $needle ) ) {
					if ( str_contains( $hay, $needle ) ) {
						++$matched;
					}
					continue;
				}
				$limit = strlen( $needle ) < 5 ? 1 : 2;
				foreach ( $tokens as $token ) {
					if ( ! preg_match( '/^[a-z0-9]+$/', $token ) || strlen( $token ) > 32 ) {
						continue;
					}
					if ( $needle === $token || levenshtein( $needle, $token ) <= $limit ) {
						++$matched;
						break;
					}
				}
			}
			if ( count( $needles ) === $matched ) {
				$keys[] = (string) $row['doc_key'];
			}
			if ( count( $keys ) >= 20 ) {
				break;
			}
		}
		return $keys;
	}
}
