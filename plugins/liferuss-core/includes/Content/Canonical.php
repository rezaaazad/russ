<?php
/**
 * Public hub slugs and one-hop redirects from the old paths.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

use LifeRuss\Core\Redirects\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the canonical hubs and records 301s so old menu URLs resolve.
 */
class Canonical {

	/**
	 * Old public slug => canonical slug.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		return array(
			'study'            => 'study-russia',
			'podfak'           => 'padfak',
			'direct-admission' => 'direct-course',
			'immigration'      => 'migration-russia',
		);
	}

	/**
	 * Run once per stamp, including on activation.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe' ), 45 );
	}

	/**
	 * Idempotent upgrade.
	 */
	public static function maybe(): void {
		if ( '1' === (string) get_option( 'lr_canonical_routes' ) ) {
			return;
		}
		self::ensure();
		update_option( 'lr_canonical_routes', '1', false );
	}

	/**
	 * Publish hubs, rename leftovers, and store redirects.
	 */
	public static function ensure(): void {
		$titles = array(
			'study-russia'     => 'تحصیل در روسیه',
			'padfak'           => 'پادفک',
			'direct-course'    => 'پذیرش مستقیم',
			'migration-russia' => 'مهاجرت به روسیه',
			'exchange'         => 'استعلام نرخ ارز',
		);
		foreach ( self::map() as $old => $new ) {
			self::hub( $old, $new, $titles[ $new ] );
			Store::upsert(
				array(
					'source_path' => '/' . $old . '/',
					'target_url'  => '/' . $new . '/',
					'status_code' => 301,
					'match_type'  => 'exact',
					'origin'      => 'slug_change',
					'is_active'   => 1,
				)
			);
		}
		self::hub( '', 'exchange', $titles['exchange'] );
		self::drop_sample_page();
		flush_rewrite_rules( false );
	}

	/**
	 * One published top-level page at the canonical slug.
	 *
	 * @param string $old   Previous slug, or empty.
	 * @param string $slug  Canonical slug.
	 * @param string $title Title when creating.
	 */
	private static function hub( string $old, string $slug, string $title ): void {
		$target = self::find( $slug );
		if ( ! $target && '' !== $old ) {
			$source = self::find( $old );
			if ( $source ) {
				wp_update_post(
					array(
						'ID'          => $source->ID,
						'post_name'   => $slug,
						'post_status' => 'publish',
					)
				);
				$target = get_post( $source->ID );
			}
		}
		if ( ! $target instanceof \WP_Post ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => '',
				)
			);
			if ( $id > 0 ) {
				update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
			}
			return;
		}
		if ( 'publish' !== $target->post_status ) {
			wp_update_post(
				array(
					'ID'          => $target->ID,
					'post_status' => 'publish',
				)
			);
		}
		$template = (string) get_post_meta( $target->ID, '_wp_page_template', true );
		if ( '' === $template || 'default' === $template ) {
			update_post_meta( $target->ID, '_wp_page_template', 'templates/path.php' );
		}
	}

	/**
	 * Page by slug in any status. Top-level only.
	 *
	 * @param string $slug Slug.
	 */
	private static function find( string $slug ): ?\WP_Post {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'post_parent'    => 0,
			)
		);
		return ( $posts && $posts[0] instanceof \WP_Post ) ? $posts[0] : null;
	}

	/**
	 * Default WordPress sample page should not stay in the public sitemap.
	 */
	private static function drop_sample_page(): void {
		foreach ( array( 'sample-page', 'برگه-نمونه' ) as $slug ) {
			$page = self::find( $slug );
			if ( ! $page ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'          => $page->ID,
					'post_status' => 'draft',
				)
			);
		}
	}
}
