<?php
/**
 * Activation.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

use LifeRuss\Core\Content\Canonical;
use LifeRuss\Core\Content\Copy;
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
		Canonical::ensure();
		Copy::apply();
		update_option( 'lr_real_copy', '1', false );
		flush_rewrite_rules( false );
		update_option( 'lr_rewrite_version', LIFERUSS_CORE_VERSION, false );
		update_option( 'lr_core_version', LIFERUSS_CORE_VERSION, false );
	}
}
