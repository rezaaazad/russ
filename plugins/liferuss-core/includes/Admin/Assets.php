<?php
/**
 * Admin styles.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the LifeRuss admin stylesheet.
 */
class Assets {

	/**
	 * Register the enqueue hook.
	 */
	public static function hooks(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Load CSS on LifeRuss screens.
	 *
	 * @param string $hook Current admin hook suffix.
	 */
	public static function enqueue( string $hook ): void {
		$ours = ( 'toplevel_page_liferuss' === $hook ) || str_starts_with( $hook, 'liferuss_page_' );
		if ( ! $ours ) {
			return;
		}

		$deps = array();
		$font = get_template_directory() . '/assets/fonts/vazirmatn.css';
		if ( is_readable( $font ) ) {
			wp_enqueue_style(
				'liferuss-vazirmatn',
				get_template_directory_uri() . '/assets/fonts/vazirmatn.css',
				array(),
				LIFERUSS_CORE_VERSION
			);
			$deps[] = 'liferuss-vazirmatn';
		}

		wp_enqueue_style(
			'liferuss-core-admin',
			LIFERUSS_CORE_URL . 'assets/admin.css',
			$deps,
			LIFERUSS_CORE_VERSION
		);
	}
}
