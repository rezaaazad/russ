<?php
/**
 * Curated comparison pages. Only these can be indexable.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Compare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Stored intro, FAQ, and index flag for a university set.
 */
class Pages {

	/**
	 * Every curated page, newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_compare_pages';
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY updated_at DESC, id DESC", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One page by id.
	 *
	 * @param int $id Id.
	 * @return array<string, mixed>|null
	 */
	public static function by_id( int $id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_compare_pages';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * One page by canonical slug.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public static function by_slug( string $slug ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_compare_pages';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE slug = %s", $slug ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Page whose university set matches, ignoring order.
	 *
	 * @param string[] $slugs Slugs.
	 * @return array<string, mixed>|null
	 */
	public static function by_set( array $slugs ): ?array {
		$want = self::signature( $slugs );
		if ( '' === $want ) {
			return null;
		}
		foreach ( self::all() as $row ) {
			$have = self::signature( explode( ',', (string) $row['uni_slugs'] ) );
			if ( $have === $want ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Insert or update.
	 *
	 * @param int                  $id   Existing id, or 0.
	 * @param array<string, mixed> $data Sanitized fields.
	 * @return int|\WP_Error
	 */
	public static function save( int $id, array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_compare_pages';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$row   = array(
			'slug'         => (string) $data['slug'],
			'uni_slugs'    => (string) $data['uni_slugs'],
			'intro'        => (string) $data['intro'],
			'faq'          => (string) $data['faq'],
			'is_indexable' => empty( $data['is_indexable'] ) ? 0 : 1,
			'updated_at'   => $now,
		);
		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
			return $id;
		}
		$row['created_at'] = $now;
		$ok                = $wpdb->insert( $table, $row );
		if ( ! $ok ) {
			return new \WP_Error( 'lr_compare', 'ذخیره نشد.' );
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete one page.
	 *
	 * @param int $id Id.
	 */
	public static function delete( int $id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'lr_compare_pages', array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Order-independent set key.
	 *
	 * @param string[] $slugs Slugs.
	 */
	public static function signature( array $slugs ): string {
		$clean = array();
		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( $slug ) {
				$clean[] = $slug;
			}
		}
		$clean = array_values( array_unique( $clean ) );
		sort( $clean );
		return implode( ',', $clean );
	}

	/**
	 * Decode the FAQ JSON.
	 *
	 * @param string $json Stored FAQ.
	 * @return array<int, array{q: string, a: string}>
	 */
	public static function faq( string $json ): array {
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}
		$out = array();
		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$q = trim( (string) ( $item['q'] ?? '' ) );
			$a = trim( (string) ( $item['a'] ?? '' ) );
			if ( '' === $q || '' === $a ) {
				continue;
			}
			$out[] = array(
				'q' => $q,
				'a' => $a,
			);
		}
		return $out;
	}
}
