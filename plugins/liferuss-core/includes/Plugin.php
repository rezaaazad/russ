<?php
/**
 * Plugin bootstrap.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

use LifeRuss\Core\Admin\Assets;
use LifeRuss\Core\Admin\Kanban;
use LifeRuss\Core\Admin\LeadAdmin;
use LifeRuss\Core\Admin\Menu;
use LifeRuss\Core\Admin\NotFoundScreen;
use LifeRuss\Core\Admin\RedirectScreen;
use LifeRuss\Core\Account\Portal;
use LifeRuss\Core\Course\Editor as CourseEditor;
use LifeRuss\Core\Course\Front as CourseFront;
use LifeRuss\Core\Course\Seed as CourseSeed;
use LifeRuss\Core\Admin\CompareScreen;
use LifeRuss\Core\Catalog\Demo;
use LifeRuss\Core\Catalog\Editor;
use LifeRuss\Core\Catalog\ProgramsScreen;
use LifeRuss\Core\Catalog\Rest;
use LifeRuss\Core\Front\Api;
use LifeRuss\Core\Front\Routes;
use LifeRuss\Core\Search\Cli as SearchCli;
use LifeRuss\Core\Search\Indexer;
use LifeRuss\Core\Content\Editor as ContentEditor;
use LifeRuss\Core\Content\Seed as ContentSeed;
use LifeRuss\Core\Content\ServiceSeed;
use LifeRuss\Core\Redirects\Runner;
use LifeRuss\Core\Currency\Rates;
use LifeRuss\Core\Payments\Admin as PaymentAdmin;
use LifeRuss\Core\Payments\Checkout;
use LifeRuss\Core\Scholarships\Admin as ScholarshipAdmin;
use LifeRuss\Core\CRM\Automation;
use LifeRuss\Core\CRM\Cli as CrmCli;
use LifeRuss\Core\Catalog\Cli as CatalogCli;
use LifeRuss\Core\CRM\Files;
use LifeRuss\Core\CRM\Intake;
use LifeRuss\Core\CRM\Notifier;
use LifeRuss\Core\CRM\Purge;
use LifeRuss\Core\Database\Migrator;
use LifeRuss\Core\I18n\Polylang;
use LifeRuss\Core\Monitor\NotFound;
use LifeRuss\Core\PostTypes\PostTypeRegistrar;
use LifeRuss\Core\PostTypes\RequestGuard;
use LifeRuss\Core\PostTypes\ShadowSync;
use LifeRuss\Core\PostTypes\TaxonomyRegistrar;
use LifeRuss\Core\Roles\LimitedAdmin;
use LifeRuss\Core\Roles\RoleRegistrar;
use LifeRuss\Core\Security\Hardening;
use LifeRuss\Core\Security\TwoFactor;
use LifeRuss\Core\Seo\RankMath;
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
		Automation::hooks();
		Rates::hooks();
		Checkout::hooks();
		ScholarshipAdmin::hooks();
		Portal::hooks();
		CourseEditor::hooks();
		CourseFront::hooks();
		CourseSeed::hooks();
		Notifier::hooks();
		Purge::hooks();
		Files::hooks();
		Rest::hooks();
		Editor::hooks();
		Demo::hooks();
		ContentEditor::hooks();
		ContentSeed::hooks();
		ServiceSeed::hooks();
		Runner::hooks();
		NotFound::hooks();
		TwoFactor::hooks();
		Hardening::hooks();
		RankMath::hooks();
		Polylang::hooks();
		Routes::hooks();
		Api::hooks();
		Indexer::hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			CrmCli::hooks();
			CatalogCli::hooks();
			SearchCli::hooks();
		}

		if ( is_admin() ) {
			Menu::hooks();
			RedirectScreen::hooks();
			NotFoundScreen::hooks();
			CompareScreen::hooks();
			LeadAdmin::hooks();
			PaymentAdmin::hooks();
			ProgramsScreen::hooks();
			Kanban::hooks();
			SettingsPage::hooks();
			Assets::hooks();
		}
	}
}
