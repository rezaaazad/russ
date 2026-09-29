<?php
/**
 * Hand SEO tags to Rank Math when it is active.
 *
 * Our theme stops printing the same tags. Specialized JSON-LD stays ours;
 * Rank Math's copy of those types is removed.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rank Math bridges. No-ops when the plugin is inactive.
 */
class RankMath {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_filter( 'rank_math/frontend/title', array( self::class, 'title' ) );
		add_filter( 'rank_math/frontend/description', array( self::class, 'description' ) );
		add_filter( 'rank_math/frontend/canonical', array( self::class, 'canonical' ) );
		add_filter( 'rank_math/frontend/robots', array( self::class, 'robots' ) );
		add_filter( 'rank_math/opengraph/facebook/og_title', array( self::class, 'title' ) );
		add_filter( 'rank_math/opengraph/facebook/og_description', array( self::class, 'description' ) );
		add_filter( 'rank_math/opengraph/url', array( self::class, 'canonical' ) );
		add_filter( 'rank_math/json_ld', array( self::class, 'schema' ), 99 );
		add_filter( 'rank_math/sitemap/entry', array( self::class, 'sitemap_entry' ), 10, 3 );
		add_filter( 'rank_math/sitemap/exclude_post_type', array( self::class, 'keep_public' ), 10, 2 );
	}

	/**
	 * Whether Rank Math is loaded.
	 */
	public static function active(): bool {
		return defined( 'RANK_MATH_VERSION' );
	}

	/**
	 * Prefer our stored title when one is set.
	 *
	 * @param string $title Rank Math title.
	 */
	public static function title( $title ): string {
		if ( function_exists( 'liferuss_seo_title' ) ) {
			$custom = liferuss_seo_title();
			if ( '' !== $custom ) {
				return $custom;
			}
		}
		return (string) $title;
	}

	/**
	 * Prefer our description.
	 *
	 * @param string $description Rank Math description.
	 */
	public static function description( $description ): string {
		if ( function_exists( 'liferuss_seo_description' ) ) {
			$custom = liferuss_seo_description();
			if ( '' !== $custom ) {
				return $custom;
			}
		}
		return (string) $description;
	}

	/**
	 * Language-aware canonical, including filtered archives.
	 *
	 * @param string $url Rank Math canonical.
	 */
	public static function canonical( $url ): string {
		if ( function_exists( 'liferuss_seo_canonical' ) ) {
			$custom = liferuss_seo_canonical();
			if ( '' !== $custom ) {
				return $custom;
			}
		}
		return (string) $url;
	}

	/**
	 * Force noindex for filtered archives and incomplete translations.
	 *
	 * @param array<string, string> $robots Robots parts.
	 * @return array<string, string>
	 */
	public static function robots( $robots ): array {
		$robots = is_array( $robots ) ? $robots : array();
		if ( function_exists( 'liferuss_should_noindex' ) && liferuss_should_noindex() ) {
			$robots['index'] = 'noindex';
		}
		return $robots;
	}

	/**
	 * Drop Rank Math nodes that our templates already describe.
	 *
	 * @param array<string, mixed> $data Schema graph.
	 * @return array<string, mixed>
	 */
	public static function schema( $data ): array {
		if ( ! is_array( $data ) ) {
			return array();
		}
		if ( ! self::owns_schema() ) {
			return $data;
		}
		foreach ( $data as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type = $node['@type'] ?? '';
			$type = is_array( $type ) ? implode( ',', $type ) : (string) $type;
			if ( preg_match( '/Article|BlogPosting|Course|FAQPage|Service|CollegeOrUniversity|MonetaryGrant|ItemList/', $type ) ) {
				unset( $data[ $key ] );
			}
		}
		return $data;
	}

	/**
	 * Skip noindex and unpublished rows. Public CPTs stay in the sitemap.
	 *
	 * @param array<string, mixed>|false $url   Entry.
	 * @param string                     $type  Object type.
	 * @param object                     $entry Sitemap object.
	 * @return array<string, mixed>|false
	 */
	public static function sitemap_entry( $url, $type, $entry ) {
		unset( $type );
		if ( ! is_array( $url ) ) {
			return $url;
		}
		if ( $entry instanceof \WP_Post ) {
			if ( 'publish' !== $entry->post_status ) {
				return false;
			}
			if ( function_exists( 'liferuss_post_excluded_from_sitemap' ) && liferuss_post_excluded_from_sitemap( $entry->ID ) ) {
				return false;
			}
		}
		return $url;
	}

	/**
	 * Keep every public LifeRuss type in the sitemap. Private types stay out.
	 *
	 * @param bool   $exclude Current decision.
	 * @param string $type    Post type.
	 */
	public static function keep_public( $exclude, $type ): bool {
		$public = array( 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_course', 'lr_lesson', 'lr_scholarship' );
		if ( in_array( $type, $public, true ) ) {
			return false;
		}
		if ( in_array( $type, array( 'lr_faq', 'lr_testimonial' ), true ) ) {
			return true;
		}
		return (bool) $exclude;
	}

	/**
	 * Screens where our JSON-LD is the one that should remain.
	 */
	private static function owns_schema(): bool {
		return is_singular( array( 'post', 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_scholarship', 'lr_course', 'lr_lesson' ) )
			|| is_page_template( array( 'templates/path.php', 'templates/freight.php', 'templates/trade.php' ) )
			|| is_post_type_archive( array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship', 'lr_guide' ) )
			|| is_front_page();
	}
}
