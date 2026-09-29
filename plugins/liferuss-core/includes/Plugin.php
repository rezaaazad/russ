<?php
/**
 * Plugin bootstrap.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

use LifeRuss\Core\Admin\Assets;
use LifeRuss\Core\Admin\Menu;
use LifeRuss\Core\Database\Migrator;
use LifeRuss\Core\PostTypes\PostTypeRegistrar;
use LifeRuss\Core\PostTypes\RequestGuard;
use LifeRuss\Core\PostTypes\ShadowSync;
use LifeRuss\Core\PostTypes\TaxonomyRegistrar;
use LifeRuss\Core\Roles\LimitedAdmin;
use LifeRuss\Core\Roles\RoleRegistrar;
use LifeRuss\Core\Settings\SettingsPage;
use LifeRuss\Core\Users\Profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires hooks after plugins_loaded.
 */
class Plugin {

	/**
	 * Boot services.
	 */
	public static function boot(): void {
		Migrator::maybe_upgrade();

		PostTypeRegistrar::hooks();
		TaxonomyRegistrar::hooks();
		ShadowSync::hooks();
		RequestGuard::hooks();
		RoleRegistrar::hooks();
		LimitedAdmin::hooks();
		Profile::hooks();

		if ( is_admin() ) {
			Menu::hooks();
			SettingsPage::hooks();
			Assets::hooks();
		}
	}
}
