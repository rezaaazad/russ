<?php
/**
 * Academy bootstrap.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the education line without touching the CRM menu.
 */
class Module {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( Front::class, 'rules' ) );
		add_filter( 'query_vars', array( Front::class, 'vars' ) );
		add_filter( 'pre_handle_404', array( Front::class, 'keep' ), 10, 2 );
		add_action( 'template_redirect', array( Front::class, 'handle' ), 1 );
		add_action( 'init', array( self::class, 'install' ), 40 );
		add_action( 'init', array( self::class, 'rewrites' ), 99 );
		add_action( 'lr_academy_daily', array( Billing::class, 'tick' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'wp_sitemaps_init', array( Sitemap::class, 'register' ) );
		Seo::hooks();
		if ( is_admin() ) {
			Admin::hooks();
		}
	}

	/**
	 * Categories, the migrated language course, and service-invoice lines.
	 */
	public static function install(): void {
		Catalog::seed_categories();
		Legacy::maybe();
		Finance::backfill();
	}

	/**
	 * Flush permalinks once per plugin version.
	 */
	public static function rewrites(): void {
		if ( get_option( 'lr_academy_rewrite' ) === LIFERUSS_CORE_VERSION ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'lr_academy_rewrite', LIFERUSS_CORE_VERSION, false );
	}

	/**
	 * Daily reminder and grace cron.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'lr_academy_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lr_academy_daily' );
		}
	}
}
