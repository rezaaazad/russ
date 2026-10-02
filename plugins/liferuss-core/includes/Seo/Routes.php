<?php
/**
 * University×field and field×city URLs, plus the immigration hub.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\Redirects\Store;
use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Extra catalog routes. Existing singles stay where they are.
 */
class Routes {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'rules' ), 5 );
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
		add_filter( 'query_vars', array( self::class, 'vars' ) );
		add_filter( 'template_include', array( self::class, 'template' ) );
		add_action( 'template_redirect', array( self::class, 'guard' ), 1 );
	}

	/**
	 * Rewrite tags.
	 *
	 * @param string[] $vars Vars.
	 * @return string[]
	 */
	public static function vars( array $vars ): array {
		$vars[] = 'lr_program';
		$vars[] = 'lr_field_city';
		return $vars;
	}

	/**
	 * More specific rules than the CPT singles.
	 */
	public static function rules(): void {
		add_rewrite_rule( '^universities/([^/]+)/([^/]+)/?$', 'index.php?post_type=lr_university&name=$matches[1]&lr_program=$matches[2]', 'top' );
		add_rewrite_rule( '^fields/([^/]+)/([^/]+)/?$', 'index.php?post_type=lr_field&name=$matches[1]&lr_field_city=$matches[2]', 'top' );
	}

	/**
	 * Flush once per release and point /immigration/ at the migration hub.
	 */
	public static function maybe_flush(): void {
		if ( '1.14.0' === (string) get_option( 'lr_seo_rewrites' ) ) {
			return;
		}
		self::immigration_hub();
		flush_rewrite_rules( false );
		update_option( 'lr_seo_rewrites', '1.14.0', false );
	}

	/**
	 * /immigration/ is the cluster mother. The previous slug 301s here.
	 */
	public static function immigration_hub(): void {
		$next = get_page_by_path( 'immigration' );
		$prev = get_page_by_path( 'migration-russia' );
		if ( ! $next instanceof \WP_Post && $prev instanceof \WP_Post ) {
			wp_update_post(
				array(
					'ID'        => $prev->ID,
					'post_name' => 'immigration',
				)
			);
			$next = get_post( $prev->ID );
		}
		if ( ! $next instanceof \WP_Post ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'مهاجرت و قوانین روسیه',
					'post_name'    => 'immigration',
					'post_content' => '',
				)
			);
			if ( $id > 0 ) {
				update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
			}
		}
		Store::upsert(
			array(
				'source_path' => '/migration-russia/',
				'target_url'  => '/immigration/',
				'status_code' => 301,
				'match_type'  => 'exact',
				'origin'      => 'slug_change',
				'is_active'   => 1,
			)
		);
		$hash = sha1( '/immigration/' );
		$old  = Repository::for( 'redirects' )->find_by( 'source_hash', $hash, true );
		if ( $old ) {
			Repository::for( 'redirects' )->update( (int) $old['id'], array( 'is_active' => 0 ) );
		}
	}

	/**
	 * Program pages need a real row. Thin field×city URLs go back to the field.
	 */
	public static function guard(): void {
		$program = sanitize_title( (string) get_query_var( 'lr_program' ) );
		if ( '' !== $program && is_singular( 'lr_university' ) ) {
			$row = self::program_row( (int) get_queried_object_id(), $program );
			if ( ! $row ) {
				self::missing();
			}
			return;
		}
		$city = sanitize_title( (string) get_query_var( 'lr_field_city' ) );
		if ( '' === $city || ! is_singular( 'lr_field' ) ) {
			return;
		}
		$field = Repository::for( 'fields' )->find_by( 'post_id', get_queried_object_id() );
		$place = Repository::for( 'cities' )->find_by( 'slug', $city );
		$count = ( $field && $place ) ? self::pair_count( (int) $field['id'], (int) $place['id'] ) : 0;
		if ( $count < 2 ) {
			wp_safe_redirect( get_permalink( get_queried_object_id() ), 301 );
			exit;
		}
	}

	/**
	 * Choose the program or field-city template.
	 *
	 * @param string $template Current template.
	 */
	public static function template( string $template ): string {
		if ( is_404() ) {
			return $template;
		}
		if ( '' !== (string) get_query_var( 'lr_program' ) && is_singular( 'lr_university' ) ) {
			$found = locate_template( 'templates/program.php' );
			return $found ? $found : $template;
		}
		if ( '' !== (string) get_query_var( 'lr_field_city' ) && is_singular( 'lr_field' ) ) {
			$found = locate_template( 'templates/field-city.php' );
			return $found ? $found : $template;
		}
		return $template;
	}

	/**
	 * Program row for a university post and a field slug.
	 *
	 * @param int    $post_id    University post.
	 * @param string $field_slug Field slug.
	 * @return array<string, mixed>|null
	 */
	public static function program_row( int $post_id, string $field_slug ): ?array {
		global $wpdb;
		$uni   = Repository::for( 'universities' )->find_by( 'post_id', $post_id );
		$field = Repository::for( 'fields' )->find_by( 'slug', $field_slug );
		if ( ! $uni || ! $field ) {
			return null;
		}
		$table = $wpdb->prefix . 'lr_university_fields';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE university_id = %d AND field_id = %d AND status = 'active' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1", (int) $uni['id'], (int) $field['id'] ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		$tuition = isset( $row['tuition'] ) ? (string) $row['tuition'] : '';
		if ( '' === (string) $row['academic_year'] && '' === $tuition ) {
			return null;
		}
		$row['field']      = $field;
		$row['university'] = $uni;
		return $row;
	}

	/**
	 * Published universities that teach a field in a city.
	 *
	 * @param int $field_id Field id.
	 * @param int $city_id  City id.
	 */
	public static function pair_count( int $field_id, int $city_id ): int {
		global $wpdb;
		$programs = $wpdb->prefix . 'lr_university_fields';
		$unis     = $wpdb->prefix . 'lr_universities';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT p.university_id) FROM `{$programs}` p INNER JOIN `{$unis}` u ON u.id = p.university_id WHERE p.field_id = %d AND u.city_id = %d AND p.status = 'active' AND p.deleted_at IS NULL AND u.status = 'published' AND u.deleted_at IS NULL", $field_id, $city_id ) );
	}

	/**
	 * Universities for a field×city page.
	 *
	 * @param int $field_id Field id.
	 * @param int $city_id  City id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function pair_rows( int $field_id, int $city_id ): array {
		global $wpdb;
		$programs = $wpdb->prefix . 'lr_university_fields';
		$unis     = $wpdb->prefix . 'lr_universities';
		$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT u.*, p.tuition, p.currency, p.degree, p.academic_year FROM `{$programs}` p INNER JOIN `{$unis}` u ON u.id = p.university_id WHERE p.field_id = %d AND u.city_id = %d AND p.status = 'active' AND p.deleted_at IS NULL AND u.status = 'published' AND u.deleted_at IS NULL ORDER BY p.tuition ASC", $field_id, $city_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * 404 the current request.
	 */
	private static function missing(): void {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
