<?php
/**
 * Academy settings stored apart from the CRM options.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Playback, ArvanCloud, and bundle definitions.
 */
class Settings {

	/**
	 * Merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$saved = get_option( 'lr_academy_settings', array() );
		$saved = is_array( $saved ) ? $saved : array();
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Defaults. Bundles stay empty until an admin publishes one.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'playback_ttl'     => 600,
			'arvan_api_key'    => '',
			'arvan_secret'     => '',
			'default_provider' => 'arvan_vod',
			'bundles'          => array(),
		);
	}

	/**
	 * Replace the saved array.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	public static function save( array $settings ): void {
		update_option( 'lr_academy_settings', array_merge( self::defaults(), $settings ), false );
	}

	/**
	 * One published bundle by slug.
	 *
	 * @param string $slug Bundle slug.
	 * @return array<string, mixed>|null
	 */
	public static function bundle( string $slug ): ?array {
		foreach ( self::bundles( true ) as $bundle ) {
			if ( $slug === (string) $bundle['slug'] ) {
				return $bundle;
			}
		}
		return null;
	}

	/**
	 * Bundle definitions.
	 *
	 * @param bool $published_only Limit to published rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function bundles( bool $published_only = false ): array {
		$raw = self::get()['bundles'];
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) || empty( $row['slug'] ) ) {
				continue;
			}
			$bundle = array(
				'slug'       => sanitize_title( (string) $row['slug'] ),
				'title'      => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
				'price'      => max( 0, (int) ( $row['price'] ?? 0 ) ),
				'course_ids' => array_values( array_filter( array_map( 'intval', (array) ( $row['course_ids'] ?? array() ) ) ) ),
				'note'       => sanitize_textarea_field( (string) ( $row['note'] ?? '' ) ),
				'status'     => 'published' === ( $row['status'] ?? '' ) ? 'published' : 'draft',
			);
			if ( $published_only && 'published' !== $bundle['status'] ) {
				continue;
			}
			$out[] = $bundle;
		}
		return $out;
	}
}
