<?php
/**
 * Plugin bootstrap.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

use LifeRuss\Core\Admin\Assets;
use LifeRuss\Core\Admin\LeadAdmin;
use LifeRuss\Core\Admin\Menu;
use LifeRuss\Core\Catalog\Demo;
use LifeRuss\Core\Catalog\Editor;
use LifeRuss\Core\Catalog\Rest;
use LifeRuss\Core\Content\Editor as ContentEditor;
use LifeRuss\Core\Content\Seed as ContentSeed;
use LifeRuss\Core\CRM\Cli as CrmCli;
use LifeRuss\Core\Catalog\Cli as CatalogCli;
use LifeRuss\Core\CRM\Files;
use LifeRuss\Core\CRM\Intake;
use LifeRuss\Core\CRM\Notifier;
use LifeRuss\Core\CRM\Purge;
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
		Intake::hooks();
		Notifier::hooks();
		Purge::hooks();
		Files::hooks();
		Rest::hooks();
		Editor::hooks();
		Demo::hooks();
		ContentEditor::hooks();
		ContentSeed::hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			CrmCli::hooks();
			CatalogCli::hooks();
		}

		if ( is_admin() ) {
			Menu::hooks();
			LeadAdmin::hooks();
			SettingsPage::hooks();
			Assets::hooks();
		}
	}
}
