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
			'/cargo/',
			'/russia-guide/',
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
		foreach ( $this->paths() as $path ) {
			foreach ( $this->complete_langs( $path ) as $code ) {
				$entries[] = array(
					'loc' => liferuss_url( $path, $code ),
				);
			}
		}
		return $entries;
	}

	/**
	 * Persian plus any language marked complete for the page behind this path.
	 *
	 * @param string $path Public path.
	 * @return string[]
	 */
	protected function complete_langs( $path ) {
		$langs = array( 'fa' );
		if ( function_exists( 'liferuss_index_incomplete' ) && liferuss_index_incomplete() ) {
			return array_keys( liferuss_languages() );
		}
		$page_id = 0;
		if ( '/' !== $path ) {
			$page = get_page_by_path( trim( $path, '/' ) );
			if ( $page instanceof WP_Post ) {
				$page_id = (int) $page->ID;
			}
		} else {
			$page_id = (int) get_option( 'page_on_front' );
		}
		foreach ( array( 'en', 'ru', 'ar' ) as $code ) {
			if ( function_exists( 'liferuss_translation_complete' ) && liferuss_translation_complete( $code, $page_id ) ) {
				$langs[] = $code;
			}
		}
		return $langs;
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
