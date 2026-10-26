<?php
/**
 * Public Academy URLs in the core sitemap.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Landing, courses, categories, instructors, and plans. Paid lessons stay out.
 */
class Sitemap extends \WP_Sitemaps_Provider {

	/**
	 * Register the provider.
	 *
	 * @param \WP_Sitemaps $sitemaps Sitemaps.
	 */
	public static function register( $sitemaps ): void {
		$sitemaps->registry->add_provider( 'academy', new self() );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = 'academy';
		$this->object_type = 'academy';
	}

	/**
	 * One page is enough for the catalogue.
	 *
	 * @param string $object_subtype Subtype.
	 */
	public function get_max_num_pages( $object_subtype = '' ): int {
		unset( $object_subtype );
		return 1;
	}

	/**
	 * URL list.
	 *
	 * @param int    $page_num       Page.
	 * @param string $object_subtype Subtype.
	 * @return array<int, array<string, string>>
	 */
	public function get_url_list( $page_num, $object_subtype = '' ): array {
		unset( $page_num, $object_subtype );
		$paths = array( '/academy/', '/academy/courses/', '/academy/plans/' );
		foreach ( Db::published( 'courses' ) as $course ) {
			$paths[] = '/academy/courses/' . $course['slug'] . '/';
			if ( ! empty( $course['is_free'] ) ) {
				foreach ( Catalog::lessons( (int) $course['id'], true ) as $lesson ) {
					$paths[] = '/academy/courses/' . $course['slug'] . '/' . $lesson['slug'] . '/';
				}
			}
		}
		foreach ( Db::published( 'course_categories' ) as $category ) {
			$paths[] = '/academy/category/' . $category['slug'] . '/';
		}
		foreach ( Db::published( 'instructors' ) as $instructor ) {
			$paths[] = '/academy/instructors/' . $instructor['slug'] . '/';
		}
		$out = array();
		foreach ( $paths as $path ) {
			$out[] = array( 'loc' => home_url( $path ) );
		}
		return $out;
	}
}
