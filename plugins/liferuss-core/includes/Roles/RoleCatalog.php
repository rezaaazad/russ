<?php
/**
 * Nine-role capability matrix from the admin-panel spec.
 *
 * Super Admin is the WordPress `administrator` role, as specified.
 * The other eight roles use the lr_* prefix.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role labels and capabilities.
 */
class RoleCatalog {

	/**
	 * Role slug => label and caps (cap => true).
	 *
	 * @return array<string, array{label: string, caps: array<string, bool>}>
	 */
	public static function roles(): array {
		return array(
			'administrator'        => array(
				'label' => 'مدیر ارشد',
				'caps'  => self::grant( self::super_admin() ),
			),
			'lr_admin'             => array(
				'label' => 'مدیر',
				'caps'  => self::grant( self::admin() ),
			),
			'lr_content_manager'   => array(
				'label' => 'مدیر محتوا',
				'caps'  => self::grant( self::content_manager() ),
			),
			'lr_writer'            => array(
				'label' => 'نویسنده',
				'caps'  => self::grant( self::writer() ),
			),
			'lr_seo_manager'       => array(
				'label' => 'مدیر سئو',
				'caps'  => self::grant( self::seo_manager() ),
			),
			'lr_consultant'        => array(
				'label' => 'مشاور',
				'caps'  => self::grant( self::consultant() ),
			),
			'lr_exchange_operator' => array(
				'label' => 'اپراتور صرافی',
				'caps'  => self::grant( self::operator( 'lr_manage_exchange_requests' ) ),
			),
			'lr_cargo_operator'    => array(
				'label' => 'اپراتور کارگو',
				'caps'  => self::grant( self::operator( 'lr_manage_cargo_requests' ) ),
			),
			'lr_trade_operator'    => array(
				'label' => 'اپراتور تجارت',
				'caps'  => self::grant( self::operator( 'lr_manage_trade_requests' ) ),
			),
		);
	}

	/**
	 * Every capability this plugin knows about.
	 *
	 * @return string[]
	 */
	public static function known_caps(): array {
		$caps = array();
		foreach ( self::roles() as $role ) {
			foreach ( array_keys( $role['caps'] ) as $cap ) {
				$caps[ $cap ] = true;
			}
		}
		return array_keys( $caps );
	}

	/**
	 * Stable hash so role sync stays idempotent.
	 */
	public static function hash(): string {
		return md5( (string) wp_json_encode( self::roles() ) );
	}

	/**
	 * Turn capability names into a role map.
	 *
	 * @param string[] $caps Capability names.
	 * @return array<string, bool>
	 */
	private static function grant( array $caps ): array {
		$out = array( 'read' => true );
		foreach ( array_unique( $caps ) as $cap ) {
			$out[ $cap ] = true;
		}
		return $out;
	}

	/**
	 * Caps added on top of the limited manager.
	 *
	 * @return string[]
	 */
	private static function super_admin(): array {
		return array_merge(
			self::admin(),
			array(
				'lr_manage_roles',
				'lr_manage_tracking',
				'lr_manage_security',
				'lr_manage_backup',
				'lr_delete_permanently',
			)
		);
	}

	/**
	 * Limited manager: business modules, no plugins, themes, or core settings.
	 *
	 * @return string[]
	 */
	private static function admin(): array {
		return array_merge(
			self::content_manager(),
			self::crm_full(),
			array(
				'lr_manage_services',
				'lr_manage_settings',
				'lr_access_settings',
				'lr_view_activity_log',
				'lr_view_all_dashboard',
				'list_users',
				'create_users',
				'edit_users',
				'delete_users',
				'promote_users',
				'remove_users',
			)
		);
	}

	/**
	 * Full content access without CRM.
	 *
	 * @return string[]
	 */
	private static function content_manager(): array {
		return array_merge(
			array(
				'lr_view_dashboard',
				'lr_view_content_dashboard',
				'upload_files',
				'lr_edit_seo',
				'lr_view_redirects',
				'lr_access_seo',
				'lr_manage_vocab',
				'lr_edit_vocab',
				'lr_manage_university_data',
				'lr_view_university_data',
				'lr_access_universities',
				'lr_manage_academic_data',
				'lr_view_academic_data',
				'lr_access_places',
				'lr_access_magazine',
				'lr_access_language',
				'lr_access_faqs',
			),
			self::full_posts(),
			self::full_pages(),
			self::full_cpt( 'lr_university', 'lr_universities' ),
			self::full_cpt( 'lr_field', 'lr_fields' ),
			self::full_cpt( 'lr_city', 'lr_cities' ),
			self::full_cpt( 'lr_guide', 'lr_guides' ),
			self::full_cpt( 'lr_faq', 'lr_faqs' ),
			self::full_cpt( 'lr_testimonial', 'lr_testimonials' ),
			self::full_cpt( 'lr_course', 'lr_courses' ),
			self::full_cpt( 'lr_lesson', 'lr_lessons' ),
			self::manage_terms( 'lr_guide_cats' ),
			self::manage_terms( 'lr_field_groups' ),
			self::manage_terms( 'lr_faq_groups' ),
			self::manage_terms( 'lr_levels' )
		);
	}

	/**
	 * Own drafts for magazine, language, FAQ, and testimonials.
	 *
	 * @return string[]
	 */
	private static function writer(): array {
		return array_merge(
			array(
				'lr_view_dashboard',
				'upload_files',
				'edit_posts',
				'delete_posts',
				'lr_access_magazine',
				'lr_access_language',
				'lr_access_faqs',
				'lr_edit_vocab',
			),
			self::own_cpt( 'lr_guide', 'lr_guides' ),
			self::own_cpt( 'lr_lesson', 'lr_lessons' ),
			self::own_cpt( 'lr_course', 'lr_courses' ),
			self::own_cpt( 'lr_faq', 'lr_faqs' ),
			self::own_cpt( 'lr_testimonial', 'lr_testimonials' ),
			self::assign_terms( 'lr_guide_cats' ),
			self::assign_terms( 'lr_faq_groups' ),
			self::assign_terms( 'lr_levels' )
		);
	}

	/**
	 * Edit existing content and manage redirects and tracking.
	 *
	 * @return string[]
	 */
	private static function seo_manager(): array {
		return array_merge(
			array(
				'lr_view_dashboard',
				'lr_view_content_dashboard',
				'upload_files',
				'lr_edit_seo',
				'lr_manage_redirects',
				'lr_view_redirects',
				'lr_access_seo',
				'lr_manage_tracking',
				'lr_access_settings',
				'lr_view_university_data',
				'lr_access_universities',
				'lr_view_academic_data',
				'lr_access_places',
				'lr_access_magazine',
				'lr_access_language',
				'lr_view_faqs',
				'lr_access_faqs',
				'lr_edit_vocab',
				'edit_posts',
				'edit_others_posts',
				'edit_published_posts',
				'edit_private_posts',
				'read_private_posts',
				'edit_pages',
				'edit_others_pages',
				'edit_published_pages',
				'edit_private_pages',
				'read_private_pages',
			),
			self::edit_cpt( 'lr_university', 'lr_universities' ),
			self::edit_cpt( 'lr_field', 'lr_fields' ),
			self::edit_cpt( 'lr_city', 'lr_cities' ),
			self::edit_cpt( 'lr_guide', 'lr_guides' ),
			self::edit_cpt( 'lr_course', 'lr_courses' ),
			self::edit_cpt( 'lr_lesson', 'lr_lessons' ),
			self::assign_terms( 'lr_guide_cats' ),
			self::assign_terms( 'lr_field_groups' ),
			self::assign_terms( 'lr_levels' )
		);
	}

	/**
	 * Own leads, own admission requests, and read-only university data.
	 *
	 * @return string[]
	 */
	private static function consultant(): array {
		return array_merge(
			self::scoped_crm(),
			array(
				'lr_view_own_admission_requests',
				'lr_access_admission',
				'lr_access_requests',
				'read_lr_university',
				'lr_view_university_data',
				'lr_access_universities',
				'read_lr_field',
				'read_lr_city',
				'lr_view_academic_data',
				'lr_access_places',
			)
		);
	}

	/**
	 * Own leads plus one service queue.
	 *
	 * @param string $request_cap Service request capability.
	 * @return string[]
	 */
	private static function operator( string $request_cap ): array {
		return array_merge(
			self::scoped_crm(),
			array(
				$request_cap,
				'lr_access_requests',
			)
		);
	}

	/**
	 * CRM caps limited to the current user's leads.
	 *
	 * @return string[]
	 */
	private static function scoped_crm(): array {
		return array(
			'lr_view_dashboard',
			'lr_view_own_leads',
			'lr_access_crm',
			'lr_manage_lead_files',
		);
	}

	/**
	 * Unscoped CRM and every request type.
	 *
	 * @return string[]
	 */
	private static function crm_full(): array {
		return array(
			'lr_manage_leads',
			'lr_assign_leads',
			'lr_export_leads',
			'lr_delete_leads',
			'lr_access_crm',
			'lr_manage_lead_files',
			'lr_manage_admission_requests',
			'lr_manage_exchange_requests',
			'lr_manage_cargo_requests',
			'lr_manage_trade_requests',
			'lr_access_requests',
			'lr_access_admission',
		);
	}

	/**
	 * Editor-level post capabilities.
	 *
	 * @return string[]
	 */
	private static function full_posts(): array {
		return array(
			'edit_posts',
			'edit_others_posts',
			'edit_published_posts',
			'edit_private_posts',
			'publish_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_published_posts',
			'delete_private_posts',
			'read_private_posts',
		);
	}

	/**
	 * Editor-level page capabilities.
	 *
	 * @return string[]
	 */
	private static function full_pages(): array {
		return array(
			'edit_pages',
			'edit_others_pages',
			'edit_published_pages',
			'edit_private_pages',
			'publish_pages',
			'delete_pages',
			'delete_others_pages',
			'delete_published_pages',
			'delete_private_pages',
			'read_private_pages',
		);
	}

	/**
	 * Publish, edit, and delete every item of one post type.
	 *
	 * @param string $singular Meta capability stem.
	 * @param string $plural   Primitive capability stem.
	 * @return string[]
	 */
	private static function full_cpt( string $singular, string $plural ): array {
		return array(
			"edit_{$singular}",
			"read_{$singular}",
			"delete_{$singular}",
			"edit_{$plural}",
			"edit_others_{$plural}",
			"publish_{$plural}",
			"read_private_{$plural}",
			"delete_{$plural}",
			"delete_private_{$plural}",
			"delete_published_{$plural}",
			"delete_others_{$plural}",
			"edit_private_{$plural}",
			"edit_published_{$plural}",
			"create_{$plural}",
		);
	}

	/**
	 * Edit existing items, including published ones, without publish or delete.
	 *
	 * @param string $singular Meta capability stem.
	 * @param string $plural   Primitive capability stem.
	 * @return string[]
	 */
	private static function edit_cpt( string $singular, string $plural ): array {
		return array(
			"edit_{$singular}",
			"read_{$singular}",
			"edit_{$plural}",
			"edit_others_{$plural}",
			"edit_published_{$plural}",
			"edit_private_{$plural}",
			"read_private_{$plural}",
		);
	}

	/**
	 * Create and edit own unpublished items.
	 *
	 * @param string $singular Meta capability stem.
	 * @param string $plural   Primitive capability stem.
	 * @return string[]
	 */
	private static function own_cpt( string $singular, string $plural ): array {
		return array(
			"edit_{$singular}",
			"read_{$singular}",
			"delete_{$singular}",
			"edit_{$plural}",
			"delete_{$plural}",
			"create_{$plural}",
		);
	}

	/**
	 * Manage, edit, delete, and assign one taxonomy.
	 *
	 * @param string $taxonomy Capability suffix.
	 * @return string[]
	 */
	private static function manage_terms( string $taxonomy ): array {
		return array(
			"manage_{$taxonomy}",
			"edit_{$taxonomy}",
			"delete_{$taxonomy}",
			"assign_{$taxonomy}",
		);
	}

	/**
	 * Assign terms without managing the taxonomy.
	 *
	 * @param string $taxonomy Capability suffix.
	 * @return string[]
	 */
	private static function assign_terms( string $taxonomy ): array {
		return array( "assign_{$taxonomy}" );
	}
}
