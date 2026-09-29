<?php
/**
 * Keep existing theme pages in front of new CPT archives.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * If a published page already owns the path, the page wins.
 */
class RequestGuard {

	/**
	 * Hook the request filter.
	 */
	public static function hooks(): void {
		add_filter( 'request', array( self::class, 'prefer_existing_page' ) );
	}

	/**
	 * Restore the current theme page when a CPT rule would replace it.
	 *
	 * @param array<string, mixed> $vars Query vars.
	 * @return array<string, mixed>
	 */
	public static function prefer_existing_page( array $vars ): array {
		if ( is_admin() ) {
			return $vars;
		}
		$types = array( 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_course', 'lr_lesson' );
		$hit   = false;
		if ( isset( $vars['post_type'] ) && in_array( $vars['post_type'], $types, true ) ) {
			$hit = true;
		}
		foreach ( array( 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_course', 'lr_lesson', 'lr_guide_cat' ) as $key ) {
			if ( isset( $vars[ $key ] ) ) {
				$hit = true;
			}
		}
		if ( ! $hit ) {
			return $vars;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$path = trim( (string) $path, '/' );
		if ( '' === $path ) {
			return $vars;
		}
		$page = get_page_by_path( $path );
		if ( $page instanceof \WP_Post && 'publish' === $page->post_status ) {
			return array( 'page_id' => (int) $page->ID );
		}
		return $vars;
	}
}
