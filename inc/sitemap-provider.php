<?php
/**
 * XML sitemap of all language variants.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme sitemap provider for fa / ru / ar / en URLs.
 */
class LifeRuss_Sitemap_Provider extends WP_Sitemaps_Provider {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = 'langs';
		$this->object_type = 'liferuss';
	}

	/**
	 * Paths that should appear in every language.
	 *
	 * @return string[]
	 */
	protected function paths() {
		return array(
			'/',
			'/about/',
			'/universities/',
			'/services/',
			'/freight/',
			'/trade/',
			'/costs/',
			'/contact/',
			'/blog/',
		);
	}

	/**
	 * URL list for one sitemap page.
	 *
	 * @param int    $page_num       Page.
	 * @param string $object_subtype Unused.
	 * @return array
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		unset( $page_num, $object_subtype );
		$entries = array();
		foreach ( liferuss_languages() as $code => $info ) {
			foreach ( $this->paths() as $path ) {
				$alternates = array();
				foreach ( liferuss_languages() as $alt => $alt_info ) {
					$alternates[] = array(
						'hreflang' => $alt_info['hreflang'],
						'loc'      => liferuss_url( $path, $alt ),
					);
				}
				$entries[] = array(
					'loc'        => liferuss_url( $path, $code ),
					'alternates' => $alternates,
				);
			}
		}
		return $entries;
	}

	/**
	 * Max pages.
	 *
	 * @param string $object_subtype Unused.
	 * @return int
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		unset( $object_subtype );
		return 1;
	}
}
