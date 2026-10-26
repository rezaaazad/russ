<?php
/**
 * Taxonomies.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field groups, guide categories, FAQ groups, and language levels.
 */
class TaxonomyRegistrar {

	/**
	 * Hook registration.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	/**
	 * Register taxonomies.
	 */
	public static function register(): void {
		register_taxonomy(
			'lr_field_group',
			array( 'lr_field' ),
			array(
				'labels'            => array(
					'name'          => 'گروه‌های رشته',
					'singular_name' => 'گروه رشته',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
				'capabilities'      => self::caps( 'lr_field_groups' ),
			)
		);

		register_taxonomy(
			'lr_guide_cat',
			array( 'lr_guide' ),
			array(
				'labels'            => array(
					'name'          => 'دسته‌های دانستنی',
					'singular_name' => 'دسته دانستنی',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
				'query_var'         => 'lr_guide_cat',
				'capabilities'      => self::caps( 'lr_guide_cats' ),
			)
		);

		register_taxonomy(
			'lr_faq_group',
			array( 'lr_faq' ),
			array(
				'labels'       => array(
					'name'          => 'گروه‌های سؤال',
					'singular_name' => 'گروه سؤال',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite'      => false,
				'capabilities' => self::caps( 'lr_faq_groups' ),
			)
		);

		register_taxonomy(
			'lr_level',
			array( 'lr_course', 'lr_lesson' ),
			array(
				'labels'            => array(
					'name'          => 'سطوح زبان',
					'singular_name' => 'سطح',
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
				'capabilities'      => self::caps( 'lr_levels' ),
			)
		);
	}

	/**
	 * Taxonomy capability map.
	 *
	 * @param string $suffix Capability suffix.
	 * @return array<string, string>
	 */
	private static function caps( string $suffix ): array {
		return array(
			'manage_terms' => 'manage_' . $suffix,
			'edit_terms'   => 'edit_' . $suffix,
			'delete_terms' => 'delete_' . $suffix,
			'assign_terms' => 'assign_' . $suffix,
		);
	}
}
