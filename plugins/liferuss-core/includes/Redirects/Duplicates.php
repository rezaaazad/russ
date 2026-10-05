<?php
/**
 * 301s from WordPress "-2" copies to the canonical page.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cargo and trade duplicates, plus any other published page whose slug ends in -2
 * when a sibling without that suffix exists.
 */
class Duplicates {

	/**
	 * Known live duplicates. Stored even before the page scan.
	 *
	 * @return array<string, string>
	 */
	public static function known(): array {
		return array(
			'/cargo/sea-2/'             => '/cargo/sea/',
			'/trade/sourcing-2/'        => '/trade/sourcing/',
			'/trade/import-export-2/'   => '/trade/import-export/',
			'/trade/representation-2/'  => '/trade/representation/',
		);
	}

	/**
	 * Record the redirects once per release.
	 */
	public static function ensure(): void {
		foreach ( self::known() as $from => $to ) {
			if ( ! self::published_page( $to ) ) {
				continue;
			}
			Store::upsert(
				array(
					'source_path' => $from,
					'target_url'  => $to,
					'status_code' => 301,
					'match_type'  => 'exact',
					'origin'      => 'slug_change',
					'is_active'   => 1,
				)
			);
		}
		self::scan_pages();
	}

	/**
	 * Published page at a site path such as cargo/sea.
	 *
	 * @param string $path Path with or without slashes.
	 */
	private static function published_page( string $path ): bool {
		$path = trim( $path, '/' );
		if ( '' === $path ) {
			return false;
		}
		$page = get_page_by_path( $path );
		return $page instanceof \WP_Post && 'publish' === $page->post_status;
	}

	/**
	 * Published pages named like sea-2 when sea exists under the same parent.
	 */
	private static function scan_pages(): void {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( '-2' );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_name, post_parent FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_name LIKE %s",
				$like
			)
		);
		if ( ! is_array( $rows ) ) {
			return;
		}
		foreach ( $rows as $row ) {
			$name = (string) $row->post_name;
			if ( ! str_ends_with( $name, '-2' ) ) {
				continue;
			}
			$base = substr( $name, 0, -2 );
			if ( '' === $base ) {
				continue;
			}
			$parent = (int) $row->post_parent;
			$prefix = $parent ? trim( (string) get_page_uri( $parent ), '/' ) : '';
			$target = trim( $prefix . '/' . $base, '/' );
			$page   = get_page_by_path( $target );
			if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status || (int) $page->ID === (int) $row->ID ) {
				continue;
			}
			$from = '/' . trim( (string) get_page_uri( (int) $row->ID ), '/' ) . '/';
			$to   = '/' . trim( (string) get_page_uri( (int) $page->ID ), '/' ) . '/';
			if ( $from === $to || '/' === $from ) {
				continue;
			}
			Store::upsert(
				array(
					'source_path' => $from,
					'target_url'  => $to,
					'status_code' => 301,
					'match_type'  => 'exact',
					'origin'      => 'slug_change',
					'is_active'   => 1,
				)
			);
		}
	}
}
