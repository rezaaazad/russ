<?php
/**
 * Meilisearch HTTP client. Failures never surface as fatals.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One index, `{prefix}_content`, keyed by type:postId.
 */
class Meili {

	/**
	 * Skip the network for this long after a failure.
	 *
	 * @var int
	 */
	private static $down_until = 0;

	/**
	 * Host and key are both required.
	 */
	public static function configured(): bool {
		$settings = Settings::get( 'search' );
		return '' !== trim( (string) $settings['host'] ) && '' !== trim( (string) $settings['api_key'] );
	}

	/**
	 * Index uid from the prefix setting.
	 */
	public static function index_uid(): string {
		$settings = Settings::get( 'search' );
		$prefix   = sanitize_key( (string) $settings['index_prefix'] );
		if ( '' === $prefix ) {
			$prefix = 'liferuss';
		}
		return $prefix . '_content';
	}

	/**
	 * True when /health answers within the timeout.
	 */
	public static function reachable(): bool {
		if ( ! self::configured() ) {
			return false;
		}
		if ( self::$down_until > time() ) {
			return false;
		}
		$response = self::request( 'GET', '/health', null );
		if ( null === $response ) {
			self::$down_until = time() + 30;
			return false;
		}
		return true;
	}

	/**
	 * Create the index and searchable attributes when missing.
	 */
	public static function ensure(): bool {
		if ( ! self::reachable() ) {
			return false;
		}
		$uid = self::index_uid();
		self::request(
			'POST',
			'/indexes',
			array(
				'uid'        => $uid,
				'primaryKey' => 'id',
			)
		);
		$settings = self::request(
			'PATCH',
			'/indexes/' . rawurlencode( $uid ) . '/settings',
			array(
				'searchableAttributes' => array( 'title', 'aliases', 'text' ),
				'filterableAttributes' => array( 'type', 'city_slug' ),
				'typoTolerance'        => array( 'enabled' => true ),
			)
		);
		return null !== $settings;
	}

	/**
	 * Add or replace one document.
	 *
	 * @param array<string, mixed> $doc Document.
	 */
	public static function upsert( array $doc ): bool {
		if ( ! self::reachable() ) {
			return false;
		}
		$result = self::request( 'POST', '/indexes/' . rawurlencode( self::index_uid() ) . '/documents', array( $doc ) );
		return null !== $result;
	}

	/**
	 * Add a batch. Caller already checked reachability.
	 *
	 * @param array<int, array<string, mixed>> $docs Documents.
	 */
	public static function upsert_many( array $docs ): bool {
		if ( ! $docs || ! self::reachable() ) {
			return false;
		}
		$result = self::request( 'POST', '/indexes/' . rawurlencode( self::index_uid() ) . '/documents', array_values( $docs ) );
		return null !== $result;
	}

	/**
	 * Remove one document. A missing document is success.
	 *
	 * @param string $doc_key Document id.
	 */
	public static function delete( string $doc_key ): bool {
		if ( ! self::reachable() ) {
			return false;
		}
		$path     = '/indexes/' . rawurlencode( self::index_uid() ) . '/documents/' . rawurlencode( $doc_key );
		$response = self::raw( 'DELETE', $path, null );
		if ( is_wp_error( $response ) ) {
			self::$down_until = time() + 30;
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code < 400 || 404 === $code;
	}

	/**
	 * Search. Null means the caller should use MySQL.
	 *
	 * @param string $query Query.
	 * @param string $type  Type facet, or empty.
	 * @param string $city  City slug, or empty.
	 * @param int    $page  Page, 1-based.
	 * @param int    $limit Page size.
	 * @return array<string, mixed>|null
	 */
	public static function search( string $query, string $type, string $city, int $page, int $limit ): ?array {
		if ( ! self::reachable() ) {
			return null;
		}
		$body   = array(
			'q'                    => $query,
			'limit'                => $limit,
			'offset'               => max( 0, ( $page - 1 ) * $limit ),
			'facets'               => array( 'type' ),
			'attributesToSearchOn' => array( 'title', 'aliases', 'text' ),
		);
		$filter = self::filter( $type, $city );
		if ( $filter ) {
			$body['filter'] = $filter;
		}
		$data = self::request( 'POST', '/indexes/' . rawurlencode( self::index_uid() ) . '/search', $body );
		if ( ! is_array( $data ) || ! isset( $data['hits'] ) || ! is_array( $data['hits'] ) ) {
			self::$down_until = time() + 30;
			return null;
		}
		$items = array();
		foreach ( $data['hits'] as $hit ) {
			if ( ! is_array( $hit ) ) {
				continue;
			}
			$items[] = array(
				'type'    => (string) ( $hit['type'] ?? '' ),
				'title'   => (string) ( $hit['title'] ?? '' ),
				'excerpt' => (string) ( $hit['excerpt'] ?? '' ),
				'url'     => (string) ( $hit['url'] ?? '' ),
				'slug'    => (string) ( $hit['slug'] ?? '' ),
				'city'    => (string) ( $hit['city'] ?? '' ),
			);
		}
		$facets = array();
		if ( isset( $data['facetDistribution']['type'] ) && is_array( $data['facetDistribution']['type'] ) ) {
			foreach ( $data['facetDistribution']['type'] as $key => $count ) {
				$facets[ (string) $key ] = (int) $count;
			}
		}
		$total = isset( $data['estimatedTotalHits'] ) ? (int) $data['estimatedTotalHits'] : count( $items );
		return array(
			'items'  => $items,
			'total'  => $total,
			'facets' => $facets,
			'engine' => 'meilisearch',
		);
	}

	/**
	 * Meilisearch filter expression.
	 *
	 * @param string $type Type.
	 * @param string $city City slug.
	 */
	private static function filter( string $type, string $city ): string {
		$parts = array();
		if ( '' !== $type ) {
			$parts[] = 'type = "' . str_replace( '"', '', $type ) . '"';
		}
		if ( '' !== $city ) {
			$parts[] = 'city_slug = "' . str_replace( '"', '', $city ) . '"';
		}
		return implode( ' AND ', $parts );
	}

	/**
	 * Decoded JSON, or null on failure.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path beginning with /.
	 * @param array|null $body   Body, or null.
	 * @return array<string, mixed>|null
	 */
	private static function request( string $method, string $path, ?array $body ): ?array {
		$response = self::raw( $method, $path, $body );
		if ( is_wp_error( $response ) ) {
			self::$down_until = time() + 30;
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 && 409 !== $code ) {
			if ( 404 === $code && 'GET' !== $method ) {
				return null;
			}
			if ( $code >= 400 ) {
				self::$down_until = time() + 30;
				return null;
			}
		}
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Raw HTTP response.
	 *
	 * @param string                    $method HTTP method.
	 * @param string                    $path   Path.
	 * @param array<string, mixed>|null $body   Body.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function raw( string $method, string $path, ?array $body ) {
		$settings = Settings::get( 'search' );
		$host     = untrailingslashit( trim( (string) $settings['host'] ) );
		$timeout  = (float) apply_filters( 'liferuss_meili_timeout', 2 );
		$args     = array(
			'method'  => $method,
			'timeout' => max( 1, $timeout ),
			'headers' => array(
				'Authorization' => 'Bearer ' . (string) $settings['api_key'],
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		return wp_remote_request( $host . $path, $args );
	}
}
