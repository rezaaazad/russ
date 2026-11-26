<?php
/**
 * Blog categories for «دانستنی‌های روسیه».
 *
 * Created once on admin_init. The lock is written before any term insert.
 * No HTTP request runs here.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent category seed.
 */
class GuideTerms {

	/**
	 * Hook the one-time seed.
	 */
	public static function hooks(): void {
		add_action( 'admin_init', array( self::class, 'ensure' ), 63 );
	}

	/**
	 * Persian names for the public category slugs.
	 *
	 * @return array<string, string>
	 */
	public static function names(): array {
		return array(
			'russia-souvenirs'   => 'سوغات',
			'russia-cities'      => 'شهرها',
			'russia-food'        => 'غذاها',
			'russia-culture'     => 'فرهنگ و آداب',
			'russia-attractions' => 'جاذبه‌های گردشگری',
			'russia-weather'     => 'آب و هوا',
			'russia-transport'   => 'حمل‌ونقل',
		);
	}

	/**
	 * Insert any missing category. Existing terms are left unchanged.
	 */
	public static function ensure(): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( '1' === (string) get_option( 'lr_guide_cats_ready', '' ) ) {
			return;
		}
		if ( '1' === (string) get_option( 'lr_guide_cats_lock', '' ) ) {
			return;
		}
		update_option( 'lr_guide_cats_lock', '1', false );
		foreach ( self::names() as $slug => $name ) {
			$existing = get_term_by( 'slug', $slug, 'category' );
			if ( $existing instanceof \WP_Term ) {
				continue;
			}
			wp_insert_term(
				$name,
				'category',
				array(
					'slug' => $slug,
				)
			);
		}
		update_option( 'lr_guide_cats_ready', '1', false );
		delete_option( 'lr_guide_cats_lock' );
	}
}
