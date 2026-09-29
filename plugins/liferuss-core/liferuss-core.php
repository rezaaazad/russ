<?php
/**
 * Plugin Name: لایف‌روس — هسته
 * Plugin URI: https://liferuss.com
 * Description: هستهٔ پلتفرم LifeRuss: جداول سفارشی، کاتالوگ دانشگاه، نقش‌ها، CRM لیدها و دریافت فرم.
 * Version: 1.7.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: LifeRuss
 * Text Domain: liferuss-core
 *
 * @package LifeRussCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIFERUSS_CORE_VERSION', '1.7.0' );
define( 'LIFERUSS_CORE_DB_VERSION', '1.8.0' );
define( 'LIFERUSS_CORE_FILE', __FILE__ );
define( 'LIFERUSS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIFERUSS_CORE_URL', plugin_dir_url( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	/**
	 * Admin notice when PHP is too old.
	 */
	function liferuss_core_php_notice() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'افزونهٔ هستهٔ لایف‌روس به PHP 8.1 یا جدیدتر نیاز دارد.', 'liferuss-core' );
		echo '</p></div>';
	}
	add_action( 'admin_notices', 'liferuss_core_php_notice' );
	return;
}

require_once LIFERUSS_CORE_DIR . 'includes/Autoloader.php';

LifeRuss\Core\Autoloader::register();

register_activation_hook( LIFERUSS_CORE_FILE, array( LifeRuss\Core\Activator::class, 'activate' ) );
register_deactivation_hook( LIFERUSS_CORE_FILE, array( LifeRuss\Core\Deactivator::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( LifeRuss\Core\Plugin::class, 'boot' ) );
