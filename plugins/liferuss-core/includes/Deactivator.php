<?php
/**
 * Deactivation. Does not drop data.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flush rewrite rules when the plugin is deactivated.
 */
class Deactivator {

	/**
	 * Leave tables, options, and roles in place.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'lr_search_queue' );
		wp_clear_scheduled_hook( 'lr_crm_sla' );
		wp_clear_scheduled_hook( 'lr_crm_digest' );
		wp_clear_scheduled_hook( 'lr_fx_update' );
		wp_clear_scheduled_hook( 'lr_pay_expire' );
		flush_rewrite_rules( false );
	}
}
