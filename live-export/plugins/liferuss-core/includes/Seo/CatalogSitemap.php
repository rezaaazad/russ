<?php
/**
 * Sitemap entries for program and field×city URLs, plus lastmod from verification.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Extra indexable URLs that are not their own posts.
 */
class CatalogSitemap extends \WP_Sitemaps_Provider {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_sitemaps_init', array( self::class, 'register' ) );
		add_filter( 'wp_sitemaps_posts_entry', array( self::class, 'entry' ), 10, 2 );
	}

	/**
	 * Register the provider when core sitemaps are in use.
	 *
	 * @param \WP_Sitemaps $sitemaps Registry.
	 */
	public static function register( $sitemaps ): void {
		if ( ! is_object( $sitemaps ) || ! isset( $sitemaps->registry ) ) {
			return;
		}
		$provider              = new self();
		$provider->name        = 'lr_catalog';
		$provider->object_type = 'liferuss';
		$sitemaps->registry->add_provider( 'lr_catalog', $provider );
	}

	/**
	 * Constructor leaves the name for register().
	 */
	public function __construct() {
		$this->name        = 'lr_catalog';
		$this->object_type = 'liferuss';
	}

	/**
	 * One page is enough for the catalog combinations.
	 *
	 * @param string $object_subtype Subtype.
	 */
	public function get_max_num_pages( $object_subtype = '' ): int {
		unset( $object_subtype );
		return 1;
	}

	/**
	 * Program and field×city URLs that pass the quality gate.
	 *
	 * @param int    $page_num       Page.
	 * @param string $object_subtype Subtype.
	 * @return array<int, array<string, string>>
	 */
	public function get_url_list( $page_num, $object_subtype = '' ): array {
		unset( $page_num, $object_subtype );
		$entries = array();
		global $wpdb;
		$programs = $wpdb->prefix . 'lr_university_fields';
		$unis     = $wpdb->prefix . 'lr_universities';
		$fields   = $wpdb->prefix . 'lr_fields';
		$sql      = "SELECT p.id AS program_id, p.academic_year, u.post_id, u.slug AS uni_slug, f.slug AS field_slug FROM `{$programs}` p INNER JOIN `{$unis}` u ON u.id = p.university_id INNER JOIN `{$fields}` f ON f.id = p.field_id WHERE p.status = 'active' AND p.deleted_at IS NULL AND u.status = 'published' AND u.deleted_at IS NULL AND (p.academic_year <> '' OR p.tuition IS NOT NULL) LIMIT 200";
		foreach ( (array) $wpdb->get_results( $sql, ARRAY_A ) as $row ) {
			if ( ! Quality::program_indexable( (int) $row['post_id'], (int) $row['program_id'] ) ) {
				continue;
			}
			$loc   = home_url( '/universities/' . rawurlencode( (string) $row['uni_slug'] ) . '/' . rawurlencode( (string) $row['field_slug'] ) . '/' );
			$entry = array( 'loc' => $loc );
			$mod   = self::iso( Facts::verified_at( 'program', (int) $row['program_id'] ) );
			if ( '' === $mod ) {
				$mod = self::modified( (int) $row['post_id'] );
			}
			if ( '' !== $mod ) {
				$entry['lastmod'] = $mod;
			}
			$entries[] = $entry;
		}
		$pairs = "SELECT f.post_id, f.id AS field_id, f.slug AS field_slug, c.slug AS city_slug, c.id AS city_id FROM `{$fields}` f INNER JOIN `{$programs}` p ON p.field_id = f.id AND p.status = 'active' AND p.deleted_at IS NULL INNER JOIN `{$unis}` u ON u.id = p.university_id AND u.status = 'published' AND u.deleted_at IS NULL INNER JOIN `{$wpdb->prefix}lr_cities` c ON c.id = u.city_id AND c.deleted_at IS NULL WHERE f.status = 'published' AND f.deleted_at IS NULL GROUP BY f.post_id, f.id, f.slug, c.slug, c.id HAVING COUNT(DISTINCT u.id) >= 2 LIMIT 100";
		foreach ( (array) $wpdb->get_results( $pairs, ARRAY_A ) as $row ) {
			if ( ! Quality::indexable_post( (int) $row['post_id'] ) ) {
				continue;
			}
			$entries[] = array(
				'loc' => home_url( '/fields/' . rawurlencode( (string) $row['field_slug'] ) . '/' . rawurlencode( (string) $row['city_slug'] ) . '/' ),
			);
		}
		return $entries;
	}

	/**
	 * Set lastmod from the latest verification and drop nothing here.
	 * Exclusion stays in the sitemap query so an empty entry is never appended.
	 *
	 * @param array<string, string> $entry Sitemap entry.
	 * @param \WP_Post              $post  Post.
	 * @return array<string, string>
	 */
	public static function entry( $entry, $post ): array {
		if ( ! is_array( $entry ) || ! $post instanceof \WP_Post ) {
			return is_array( $entry ) ? $entry : array();
		}
		$mod = self::modified( (int) $post->ID );
		if ( '' !== $mod ) {
			$entry['lastmod'] = $mod;
		}
		return $entry;
	}

	/**
	 * W3C timestamp from the newest fact on the shadow row.
	 *
	 * @param int $post_id Post id.
	 */
	public static function modified( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$map = array(
			'lr_university'  => array( 'university', 'universities' ),
			'lr_field'       => array( 'field', 'fields' ),
			'lr_city'        => array( 'city', 'cities' ),
			'lr_scholarship' => array( 'scholarship', 'scholarships' ),
		);
		if ( isset( $map[ $post->post_type ] ) ) {
			$row = Repository::for( $map[ $post->post_type ][1] )->find_by( 'post_id', $post_id );
			if ( $row ) {
				$iso = self::iso( Facts::verified_at( $map[ $post->post_type ][0], (int) $row['id'] ) );
				if ( '' !== $iso ) {
					return $iso;
				}
			}
		}
		if ( in_array( $post->post_type, array( 'post', 'lr_guide' ), true ) ) {
			$reviewed = (string) get_post_meta( $post_id, '_lr_reviewed_at', true );
			if ( '' !== $reviewed ) {
				$stamp = strtotime( $reviewed . ' UTC' );
				return $stamp ? gmdate( 'c', $stamp ) : '';
			}
		}
		return '';
	}

	/**
	 * MySQL datetime to an ISO-8601 UTC string.
	 *
	 * @param string $mysql Datetime.
	 */
	public static function iso( string $mysql ): string {
		if ( '' === $mysql || str_starts_with( $mysql, '0000' ) ) {
			return '';
		}
		$stamp = strtotime( $mysql . ' UTC' );
		return $stamp ? gmdate( 'c', $stamp ) : '';
	}
}
