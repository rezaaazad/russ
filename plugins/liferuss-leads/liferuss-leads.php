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

/**
 * When LifeRuss core is active, new submissions are stored in lr_leads.
 */
function liferuss_leads_defer_to_core() {
	if ( ! defined( 'LIFERUSS_CORE_VERSION' ) ) {
		return;
	}
	remove_action( 'wp_ajax_liferuss_consult', 'liferuss_leads_ajax' );
	remove_action( 'wp_ajax_nopriv_liferuss_consult', 'liferuss_leads_ajax' );
	remove_action( 'admin_post_liferuss_consult', 'liferuss_leads_admin_post' );
	remove_action( 'admin_post_nopriv_liferuss_consult', 'liferuss_leads_admin_post' );
	add_action( 'admin_notices', 'liferuss_leads_core_notice' );
}
add_action( 'plugins_loaded', 'liferuss_leads_defer_to_core', 0 );

/**
 * Tell admins this plugin no longer stores new requests.
 */
function liferuss_leads_core_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>';
	echo esc_html__( 'ذخیرهٔ درخواست‌های جدید به هستهٔ لایف‌روس منتقل شده است. این افزونه برای مشاهده و مهاجرت لیدهای قبلی می‌ماند.', 'liferuss-leads' );
	echo '</p></div>';
}
