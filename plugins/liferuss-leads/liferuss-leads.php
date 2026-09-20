<?php
/**
 * Plugin Name: لایف روس — درخواست‌ها
 * Plugin URI: https://liferuss.com
 * Description: صندوق ورودی فرم‌ها با نوع‌های قابل گسترش و دسترسی پشتیبانی به تفکیک نوع درخواست.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: LifeRuss
 * Text Domain: liferuss-leads
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIFERUSS_LEADS_ACTIVE', true );
define( 'LIFERUSS_LEADS_VERSION', '1.0.0' );
define( 'LIFERUSS_LEADS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIFERUSS_LEADS_URI', plugin_dir_url( __FILE__ ) );

require_once LIFERUSS_LEADS_DIR . 'includes/types.php';
require_once LIFERUSS_LEADS_DIR . 'includes/access.php';
require_once LIFERUSS_LEADS_DIR . 'includes/roles.php';
require_once LIFERUSS_LEADS_DIR . 'includes/handler.php';
require_once LIFERUSS_LEADS_DIR . 'includes/admin.php';

/**
 * Activation: role, caps, default settings.
 */
function liferuss_leads_activate() {
	liferuss_leads_register_role();
	liferuss_leads_grant_admin_caps();
	if ( false === get_option( 'liferuss_leads_settings', false ) ) {
		add_option( 'liferuss_leads_settings', liferuss_leads_default_settings() );
	}
	flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'liferuss_leads_activate' );

/**
 * Load admin assets on plugin screens.
 *
 * @param string $hook Hook.
 */
function liferuss_leads_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'liferuss-leads' ) && 'user-edit.php' !== $hook && 'profile.php' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'liferuss-leads-admin',
		LIFERUSS_LEADS_URI . 'assets/admin.css',
		array(),
		LIFERUSS_LEADS_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'liferuss_leads_admin_assets' );
