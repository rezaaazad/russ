<?php
/**
 * Custom post types.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers CPTs with the sitemap rewrite bases.
 */
class PostTypeRegistrar {

	/**
	 * Hook registration.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
		add_filter( 'post_type_link', array( self::class, 'guide_link' ), 9, 2 );
		add_filter( 'term_link', array( self::class, 'guide_term_link' ), 9, 3 );
		add_filter( 'post_type_archive_link', array( self::class, 'guide_archive_link' ), 9, 2 );
	}

	/**
	 * Register every LifeRuss post type and the archived status.
	 */
	public static function register(): void {
		self::register_status();
		foreach ( self::types() as $slug => $args ) {
			register_post_type( $slug, $args );
		}
		self::register_rewrites();
	}

	/**
	 * Flush permalinks once per plugin version.
	 */
	public static function maybe_flush(): void {
		if ( get_option( 'lr_rewrite_version' ) === LIFERUSS_CORE_VERSION ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'lr_rewrite_version', LIFERUSS_CORE_VERSION, false );
	}

	/**
	 * Archived content status used by the ERD.
	 */
	private static function register_status(): void {
		/* translators: %s: number of archived items. */
		$archived_label = _n_noop( 'بایگانی <span class="count">(%s)</span>', 'بایگانی <span class="count">(%s)</span>', 'liferuss-core' );
		register_post_status(
			'lr_archived',
			array(
				'label'                     => 'بایگانی',
				'public'                    => false,
				'internal'                  => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => $archived_label,
			)
		);
	}

	/**
	 * Guide singles live at /russia-guide/{category}/{slug}/.
	 *
	 * @param string       $url  Generated URL.
	 * @param \WP_Post|int $post Post.
	 */
	public static function guide_link( string $url, $post ): string {
		$post = get_post( $post );
		if ( ! $post instanceof \WP_Post || 'lr_guide' !== $post->post_type ) {
			return $url;
		}
		$terms = get_the_terms( $post, 'lr_guide_cat' );
		$cat   = ( is_array( $terms ) && isset( $terms[0]->slug ) ) ? $terms[0]->slug : 'guide';
		return home_url( user_trailingslashit( 'russia-guide/' . $cat . '/' . $post->post_name ) );
	}

	/**
	 * Pretty archive URL. The CPT rewrite stays off so the custom rule owns the path.
	 *
	 * @param string $link      Generated link.
	 * @param string $post_type Post type.
	 */
	public static function guide_archive_link( string $link, string $post_type ): string {
		if ( 'lr_guide' !== $post_type ) {
			return $link;
		}
		return home_url( user_trailingslashit( 'russia-guide' ) );
	}

	/**
	 * Guide categories live at /russia-guide/{slug}/.
	 *
	 * @param string   $url      Term URL.
	 * @param \WP_Term $term     Term.
	 * @param string   $taxonomy Taxonomy.
	 */
	public static function guide_term_link( string $url, $term, string $taxonomy ): string {
		if ( 'lr_guide_cat' !== $taxonomy || ! $term instanceof \WP_Term ) {
			return $url;
		}
		return home_url( user_trailingslashit( 'russia-guide/' . $term->slug ) );
	}

	/**
	 * Nested russia-guide and russian-language routes.
	 */
	private static function register_rewrites(): void {
		add_rewrite_rule( 'russia-guide/?$', 'index.php?post_type=lr_guide', 'top' );
		add_rewrite_rule( 'russia-guide/([^/]+)/?$', 'index.php?lr_guide_cat=$matches[1]', 'top' );
		add_rewrite_rule( 'russia-guide/([^/]+)/([^/]+)/?$', 'index.php?post_type=lr_guide&name=$matches[2]', 'top' );
		add_rewrite_rule( 'russian-language/?$', 'index.php?post_type=lr_course', 'top' );
		add_rewrite_rule( 'russian-language/([^/]+)/?$', 'index.php?lr_course=$matches[1]', 'top' );
		add_rewrite_rule( 'russian-language/([^/]+)/([^/]+)/?$', 'index.php?post_type=lr_lesson&name=$matches[2]', 'top' );
	}

	/**
	 * Post type arguments.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function types(): array {
		return array(
			'lr_university'  => self::args(
				'دانشگاه',
				'دانشگاه‌ها',
				'lr_university',
				'lr_universities',
				array(
					'public'      => true,
					'has_archive' => 'universities',
					'rewrite'     => array(
						'slug'       => 'universities',
						'with_front' => false,
					),
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				)
			),
			'lr_field'       => self::args(
				'رشته',
				'رشته‌ها',
				'lr_field',
				'lr_fields',
				array(
					'public'      => true,
					'has_archive' => 'fields',
					'rewrite'     => array(
						'slug'       => 'fields',
						'with_front' => false,
					),
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				)
			),
			'lr_city'        => self::args(
				'شهر',
				'شهرها',
				'lr_city',
				'lr_cities',
				array(
					'public'      => true,
					'has_archive' => 'cities',
					'rewrite'     => array(
						'slug'       => 'cities',
						'with_front' => false,
					),
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				)
			),
			'lr_guide'       => self::args(
				'دانستنی',
				'دانستنی‌های روسیه',
				'lr_guide',
				'lr_guides',
				array(
					'public'      => true,
					'has_archive' => true,
					'rewrite'     => false,
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields' ),
				)
			),
			'lr_course'      => self::args(
				'دوره',
				'دوره‌های زبان',
				'lr_course',
				'lr_courses',
				array(
					'public'      => true,
					'has_archive' => false,
					'rewrite'     => false,
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'page-attributes' ),
				)
			),
			'lr_lesson'      => self::args(
				'درس',
				'درس‌ها',
				'lr_lesson',
				'lr_lessons',
				array(
					'public'      => true,
					'has_archive' => false,
					'rewrite'     => false,
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'page-attributes', 'custom-fields' ),
				)
			),
			'lr_faq'         => self::args(
				'سؤال',
				'سؤالات متداول',
				'lr_faq',
				'lr_faqs',
				array(
					'public'              => false,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'rewrite'             => false,
					'supports'            => array( 'title', 'editor', 'revisions', 'page-attributes' ),
				)
			),
			'lr_scholarship' => self::args(
				'بورسیه',
				'بورسیه‌ها',
				'lr_scholarship',
				'lr_scholarships',
				array(
					'public'      => true,
					'has_archive' => 'scholarships',
					'rewrite'     => array(
						'slug'       => 'scholarships',
						'with_front' => false,
					),
					'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				)
			),
			'lr_testimonial' => self::args(
				'نظر',
				'نظرات مشتریان',
				'lr_testimonial',
				'lr_testimonials',
				array(
					'public'              => false,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'rewrite'             => false,
					'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				)
			),
		);
	}

	/**
	 * Shared CPT arguments plus overrides.
	 *
	 * @param string               $singular Singular label.
	 * @param string               $plural   Plural label.
	 * @param string               $single   Capability stem.
	 * @param string               $many     Plural capability stem.
	 * @param array<string, mixed> $extra    Overrides.
	 * @return array<string, mixed>
	 */
	private static function args( string $singular, string $plural, string $single, string $many, array $extra ): array {
		$base = array(
			'labels'          => array(
				'name'          => $plural,
				'singular_name' => $singular,
				'add_new_item'  => 'افزودن ' . $singular,
				'edit_item'     => 'ویرایش ' . $singular,
				'menu_name'     => $plural,
			),
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'map_meta_cap'    => true,
			'capability_type' => array( $single, $many ),
			'capabilities'    => array(
				'edit_post'              => 'edit_' . $single,
				'read_post'              => 'read_' . $single,
				'delete_post'            => 'delete_' . $single,
				'edit_posts'             => 'edit_' . $many,
				'edit_others_posts'      => 'edit_others_' . $many,
				'publish_posts'          => 'publish_' . $many,
				'read_private_posts'     => 'read_private_' . $many,
				'delete_posts'           => 'delete_' . $many,
				'delete_private_posts'   => 'delete_private_' . $many,
				'delete_published_posts' => 'delete_published_' . $many,
				'delete_others_posts'    => 'delete_others_' . $many,
				'edit_private_posts'     => 'edit_private_' . $many,
				'edit_published_posts'   => 'edit_published_' . $many,
				'create_posts'           => 'create_' . $many,
			),
		);
		return array_merge( $base, $extra );
	}
}
