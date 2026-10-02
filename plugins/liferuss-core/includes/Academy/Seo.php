<?php
/**
 * Course schema, noindex on paid lessons, and Rank Math fields.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO for Academy screens. Rank Math reads the theme title and description filters.
 */
class Seo {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_head', array( self::class, 'schema' ), 20 );
		add_filter( 'wp_robots', array( self::class, 'robots' ) );
		add_filter( 'document_title_parts', array( self::class, 'document_title' ) );
	}

	/**
	 * Schema.org Course on a course page.
	 */
	public static function schema(): void {
		$context = Front::context();
		if ( 'course' !== ( $context['screen'] ?? '' ) || empty( $context['course'] ) ) {
			return;
		}
		$course = $context['course'];
		$data   = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Course',
			'name'        => (string) $course['title'],
			'description' => wp_strip_all_tags( (string) ( $course['seo_description'] ? $course['seo_description'] : $course['description'] ) ),
			'provider'    => array(
				'@type'  => 'Organization',
				'name'   => 'لایف‌روس',
				'sameAs' => home_url( '/' ),
			),
			'inLanguage'  => 'fa',
			'url'         => home_url( '/academy/courses/' . $course['slug'] . '/' ),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	/**
	 * Paid lessons are noindex.
	 *
	 * @param array<string, bool|string> $robots Robots.
	 * @return array<string, bool|string>
	 */
	public static function robots( array $robots ): array {
		if ( Front::noindex() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		if ( 'academy' === (string) get_query_var( 'lr_account' ) ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * Title for the theme and Rank Math.
	 */
	public static function plain(): string {
		return self::meta_title();
	}

	/**
	 * Description for the theme and Rank Math.
	 */
	public static function description(): string {
		$context = Front::context();
		$screen  = (string) ( $context['screen'] ?? '' );
		if ( 'course' === $screen && ! empty( $context['course']['seo_description'] ) ) {
			return (string) $context['course']['seo_description'];
		}
		if ( 'landing' === $screen ) {
			return 'دوره‌های آموزشی لایف‌روس: زبان روسی، پادفک، زندگی در روسیه و کلاس‌های آنلاین.';
		}
		return '';
	}

	/**
	 * Canonical path for an Academy screen.
	 */
	public static function path(): string {
		$context = Front::context();
		$screen  = (string) ( $context['screen'] ?? '' );
		if ( 'course' === $screen && ! empty( $context['course'] ) ) {
			return '/academy/courses/' . $context['course']['slug'] . '/';
		}
		if ( 'lesson' === $screen && ! empty( $context['course'] ) && ! empty( $context['lesson'] ) ) {
			return '/academy/courses/' . $context['course']['slug'] . '/' . $context['lesson']['slug'] . '/';
		}
		if ( 'category' === $screen && ! empty( $context['category'] ) ) {
			return '/academy/category/' . $context['category']['slug'] . '/';
		}
		if ( 'plans' === $screen ) {
			return '/academy/plans/';
		}
		if ( 'courses' === $screen ) {
			return '/academy/courses/';
		}
		if ( 'certificate' === $screen && ! empty( $context['certificate'] ) ) {
			return '/academy/certificate/' . $context['certificate']['code'] . '/';
		}
		return '/academy/';
	}

	/**
	 * Document title parts.
	 *
	 * @param array<string, string> $parts Parts.
	 * @return array<string, string>
	 */
	public static function document_title( array $parts ): array {
		$custom = self::meta_title();
		if ( '' !== $custom ) {
			$parts['title'] = $custom;
		}
		return $parts;
	}

	/**
	 * Screen title.
	 */
	private static function meta_title(): string {
		$context = Front::context();
		$screen  = (string) ( $context['screen'] ?? '' );
		if ( 'course' === $screen && ! empty( $context['course'] ) ) {
			$seo = (string) $context['course']['seo_title'];
			return '' !== $seo ? $seo : (string) $context['course']['title'];
		}
		if ( 'lesson' === $screen && ! empty( $context['lesson'] ) ) {
			return (string) $context['lesson']['title'];
		}
		if ( 'landing' === $screen ) {
			return 'آکادمی لایف‌روس';
		}
		if ( 'plans' === $screen ) {
			return 'طرح‌های اشتراک آکادمی';
		}
		if ( 'certificate' === $screen && ! empty( $context['certificate'] ) ) {
			return 'گواهی آکادمی';
		}
		return '';
	}
}
