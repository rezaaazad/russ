<?php
/**
 * Public read endpoints for the catalog.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET collections. Writes stay in wp-admin.
 */
class Rest {

	/**
	 * Register routes.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register' ) );
	}

	/**
	 * Routes under liferuss/v1.
	 */
	public static function register(): void {
		register_rest_route(
			'liferuss/v1',
			'/universities',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'universities' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/universities/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'university' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/fields',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'fields' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/fields/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'field' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/cities',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'cities' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/programs',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'programs' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'liferuss/v1',
			'/cities/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'city' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * University collection.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function universities( \WP_REST_Request $request ): \WP_REST_Response {
		$filters = Query::filters_from_request();
		foreach ( array( 'city', 'field', 'degree', 'lang', 'ministry' ) as $key ) {
			$value = $request->get_param( $key );
			if ( is_string( $value ) && '' !== $value && ! isset( $filters[ $key ] ) ) {
				$filters[ $key ] = sanitize_key( $value );
			}
		}
		if ( is_numeric( $request->get_param( 'tuition_min' ) ) ) {
			$filters['tuition_min'] = (float) $request->get_param( 'tuition_min' );
		}
		if ( is_numeric( $request->get_param( 'tuition_max' ) ) ) {
			$filters['tuition_max'] = (float) $request->get_param( 'tuition_max' );
		}
		if ( is_numeric( $request->get_param( 'rank' ) ) ) {
			$filters['rank'] = (int) $request->get_param( 'rank' );
		}
		$page = max( 1, (int) $request->get_param( 'page' ) );
		return new \WP_REST_Response( Query::universities( $filters, $page ) );
	}

	/**
	 * One university.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function university( \WP_REST_Request $request ): \WP_REST_Response {
		$row = Query::university( sanitize_title( (string) $request->get_param( 'slug' ) ) );
		if ( ! $row ) {
			return new \WP_REST_Response( array( 'message' => 'not found' ), 404 );
		}
		return new \WP_REST_Response( $row );
	}

	/**
	 * Fields.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function fields( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( Query::fields( max( 1, (int) $request->get_param( 'page' ) ) ) );
	}

	/**
	 * One field.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function field( \WP_REST_Request $request ): \WP_REST_Response {
		$row = Query::field( sanitize_title( (string) $request->get_param( 'slug' ) ) );
		if ( ! $row ) {
			return new \WP_REST_Response( array( 'message' => 'not found' ), 404 );
		}
		return new \WP_REST_Response( $row );
	}

	/**
	 * Cities.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	/**
	 * Programs.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function programs( \WP_REST_Request $request ): \WP_REST_Response {
		$filters = array();
		foreach ( array( 'university', 'field', 'degree', 'lang' ) as $key ) {
			$value = $request->get_param( $key );
			if ( is_string( $value ) && '' !== $value ) {
				$filters[ $key ] = sanitize_title( $value );
			}
		}
		if ( isset( $filters['degree'] ) ) {
			$filters['degree'] = sanitize_key( (string) $filters['degree'] );
		}
		if ( isset( $filters['lang'] ) ) {
			$filters['lang'] = sanitize_key( (string) $filters['lang'] );
		}
		return new \WP_REST_Response( Query::program_search( $filters, max( 1, (int) $request->get_param( 'page' ) ) ) );
	}

	/**
	 * Cities.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function cities( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( Query::cities( max( 1, (int) $request->get_param( 'page' ) ) ) );
	}

	/**
	 * One city.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function city( \WP_REST_Request $request ): \WP_REST_Response {
		$row = Query::city( sanitize_title( (string) $request->get_param( 'slug' ) ) );
		if ( ! $row ) {
			return new \WP_REST_Response( array( 'message' => 'not found' ), 404 );
		}
		return new \WP_REST_Response( $row );
	}
}
