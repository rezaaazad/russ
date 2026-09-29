<?php
/**
 * Uninstall handler. Data is kept unless lr_delete_data_on_uninstall is enabled.
 *
 * @package LifeRussCore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$lr_delete = get_option( 'lr_delete_data_on_uninstall', '0' );
if ( '1' !== (string) $lr_delete ) {
	return;
}

require_once __DIR__ . '/includes/Autoloader.php';
LifeRuss\Core\Autoloader::register();

global $wpdb;

$lr_suffixes = array_keys( LifeRuss\Core\Database\Tables::all() );
$wpdb->query( 'SET FOREIGN_KEY_CHECKS=0' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
foreach ( $lr_suffixes as $lr_suffix ) {
	$table = $wpdb->prefix . 'lr_' . $lr_suffix;
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
}
$wpdb->query( 'SET FOREIGN_KEY_CHECKS=1' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

$lr_options = array(
	'lr_db_version',
	'lr_core_version',
	'lr_roles_hash',
	'lr_rewrite_version',
	'lr_delete_data_on_uninstall',
	'lr_settings_general',
	'lr_settings_contact',
	'lr_settings_footer',
	'lr_settings_currency',
	'lr_settings_notifications',
	'lr_settings_tracking',
	'lr_settings_forms',
	'lr_settings_security',
	'lr_settings_languages',
	'lr_settings_backup',
	'lr_seed_version',
	'lr_last_migration_errors',
	'lr_leads_migration_cursor',
	'lr_leads_migration_report',
	'lr_last_notice',
	'lr_catalog_gen',
	'lr_import_report',
	'lr_demo_catalog',
	'lr_path_seed',
);

foreach ( $lr_options as $lr_option ) {
	delete_option( $lr_option );
}

foreach ( array_keys( LifeRuss\Core\Roles\RoleCatalog::roles() ) as $lr_role ) {
	if ( 'administrator' === $lr_role ) {
		continue;
	}
	remove_role( $lr_role );
}
