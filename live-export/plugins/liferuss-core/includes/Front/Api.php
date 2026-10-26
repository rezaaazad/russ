<?php
/**
 * Cached public REST for search and comparison.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Front;

use LifeRuss\Core\Compare\Set;
use LifeRuss\Core\Search\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET endpoints under liferuss/v1.
 */
class Api {

	/**
	 * Register routes.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register' ) );
	}

	/**
	 * Routes.
	 */
	public static function register(): void {
		register_rest_route(
			'liferuss/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'search' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/search/suggest',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'suggest' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/compare',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'compare' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Faceted search. Results are cached; this call is logged.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function search( \WP_REST_Request $request ): \WP_REST_Response {
		$q    = self::text( $request, 'q' );
		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		$city = sanitize_title( (string) $request->get_param( 'city' ) );
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$data = Engine::search( $q, $type, $city, $page, true );
		return self::respond( $data, 120 );
	}

	/**
	 * Suggestions. Not logged.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function suggest( \WP_REST_Request $request ): \WP_REST_Response {
		$items = Engine::suggest( self::text( $request, 'q' ) );
		return self::respond( array( 'items' => $items ), 120 );
	}

	/**
	 * Comparison payload.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function compare( \WP_REST_Request $request ): \WP_REST_Response {
		$slugs  = Set::parse_list( self::text( $request, 'u' ) );
		$field  = sanitize_title( (string) $request->get_param( 'field' ) );
		$degree = sanitize_key( (string) $request->get_param( 'degree' ) );
		$key    = 'lr_cmp_' . md5( implode( ',', $slugs ) . '|' . $field . '|' . $degree );
		$data   = get_transient( $key );
		if ( ! is_array( $data ) ) {
			$data = self::public_compare( Set::build( $slugs, $field, $degree, null ) );
			set_transient( $key, $data, 5 * MINUTE_IN_SECONDS );
		}
		$status = ! empty( $data['ok'] ) ? 200 : 400;
		return self::respond( $data, 300, $status );
	}

	/**
	 * A query parameter as text.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @param string           $key     Parameter.
	 */
	private static function text( \WP_REST_Request $request, string $key ): string {
		$value = $request->get_param( $key );
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * JSON response with a public cache header.
	 *
	 * @param array<string, mixed> $data   Payload.
	 * @param int                  $maxage Seconds.
	 * @param int                  $status HTTP status.
	 */
	private static function respond( array $data, int $maxage, int $status = 200 ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data, $status );
		$response->header( 'Cache-Control', 'public, max-age=' . $maxage );
		return $response;
	}

	/**
	 * REST-safe comparison without raw catalog rows.
	 *
	 * @param array<string, mixed> $data Built set.
	 * @return array<string, mixed>
	 */
	private static function public_compare( array $data ): array {
		$columns = array();
		foreach ( (array) $data['columns'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$columns[] = array(
				'slug' => (string) ( $row['slug'] ?? '' ),
				'name' => (string) ( $row['name_fa'] ?? '' ),
				'url'  => (string) get_permalink( (int) ( $row['post_id'] ?? 0 ) ),
				'city' => (string) ( $row['city']['name_fa'] ?? '' ),
			);
		}
		return array(
			'ok'          => ! empty( $data['ok'] ),
			'error'       => (string) ( $data['error'] ?? '' ),
			'slugs'       => $data['slugs'],
			'field'       => $data['field'],
			'degree'      => $data['degree'],
			'title'       => $data['title'],
			'description' => $data['description'],
			'names'       => $data['names'],
			'columns'     => $columns,
			'rows'        => $data['rows'],
			'fields'      => $data['fields'],
			'degrees'     => $data['degrees'],
		);
	}
}
