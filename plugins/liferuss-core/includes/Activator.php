<?php
/**
 * Activation.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

use LifeRuss\Core\Database\Migrator;
use LifeRuss\Core\Database\Seeder;
use LifeRuss\Core\PostTypes\PostTypeRegistrar;
use LifeRuss\Core\PostTypes\TaxonomyRegistrar;
use LifeRuss\Core\Roles\RoleRegistrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation.
 */
class Activator {

	/**
	 * Create schema, roles, and rewrite rules.
	 */
	public static function activate(): void {
		Migrator::migrate();
		RoleRegistrar::sync();
		Seeder::seed();
		PostTypeRegistrar::register();
		TaxonomyRegistrar::register();
		flush_rewrite_rules( false );
		update_option( 'lr_rewrite_version', LIFERUSS_CORE_VERSION, false );
		update_option( 'lr_core_version', LIFERUSS_CORE_VERSION, false );
	}
}
