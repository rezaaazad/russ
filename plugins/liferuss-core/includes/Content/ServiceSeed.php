<?php
/**
 * Draft exchange, cargo, and trade sub-pages, plus one guide.
 *
 * Hubs keep the existing landing templates. Sub-pages stay drafts.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

use LifeRuss\Core\Redirects\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds service paths once.
 */
class ServiceSeed {

	/**
	 * Hook the seeder.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe_seed' ), 45 );
	}

	/**
	 * Create drafts and the freight redirect.
	 */
	public static function maybe_seed(): void {
		if ( '1' === (string) get_option( 'lr_service_seed' ) ) {
			return;
		}
		$cargo = self::relocate_freight();
		Store::upsert(
			array(
				'source_path' => '/freight/',
				'target_url'  => '/cargo/',
				'status_code' => 301,
				'match_type'  => 'exact',
				'origin'      => 'migration',
				'is_active'   => 1,
				'object_type' => 'page',
				'object_id'   => $cargo,
			)
		);
		$faq      = self::faq_id();
		$exchange = self::page_id( 'exchange', 0 );
		$trade    = self::page_id( 'trade', 0 );
		self::mark_exchange_hub( $exchange );
		$lead = 'متن نمونه. تا وقتی این پیش‌نویس منتشر نشود در سایت دیده نمی‌شود.';
		self::children(
			$exchange,
			'exchange',
			array(
				array(
					'slug'  => 'transfer-to-russia',
					'title' => 'حواله به روسیه (نمونه)',
				),
				array(
					'slug'  => 'tuition-payment',
					'title' => 'پرداخت شهریه (نمونه)',
				),
				array(
					'slug'  => 'currencies',
					'title' => 'انواع ارز (نمونه)',
				),
			),
			$lead,
			$faq
		);
		self::children(
			$cargo,
			'freight',
			array(
				array(
					'slug'  => 'air',
					'title' => 'حمل هوایی (نمونه)',
				),
				array(
					'slug'  => 'road',
					'title' => 'حمل جاده‌ای (نمونه)',
				),
				array(
					'slug'  => 'rail',
					'title' => 'حمل ریلی (نمونه)',
				),
				array(
					'slug'  => 'sea',
					'title' => 'حمل دریایی (نمونه)',
				),
				array(
					'slug'  => 'customs',
					'title' => 'ترخیص گمرک (نمونه)',
				),
			),
			$lead,
			$faq
		);
		self::children(
			$trade,
			'trade',
			array(
				array(
					'slug'  => 'sourcing',
					'title' => 'سورسینگ (نمونه)',
				),
				array(
					'slug'  => 'import-export',
					'title' => 'مشاوره واردات و صادرات (نمونه)',
				),
				array(
					'slug'  => 'representation',
					'title' => 'نمایندگی (نمونه)',
				),
			),
			$lead,
			$faq
		);
		self::guide( $faq );
		update_option( 'lr_service_seed', '1', false );
	}

	/**
	 * Service schema and child cards on the existing exchange hub.
	 *
	 * @param int $id Page id.
	 */
	private static function mark_exchange_hub( int $id ): void {
		if ( $id < 1 ) {
			return;
		}
		if ( '' === (string) get_post_meta( $id, '_lr_schema', true ) ) {
			update_post_meta( $id, '_lr_schema', 'service' );
		}
		if ( '' === (string) get_post_meta( $id, '_lr_catalog', true ) ) {
			update_post_meta( $id, '_lr_catalog', 'children' );
		}
	}

	/**
	 * Point the published freight landing at /cargo/ without changing its template.
	 */
	private static function relocate_freight(): int {
		$cargo = self::page_id( 'cargo', 0 );
		if ( $cargo ) {
			return $cargo;
		}
		$freight = self::page_id( 'freight', 0 );
		if ( ! $freight ) {
			return 0;
		}
		wp_update_post(
			array(
				'ID'        => $freight,
				'post_name' => 'cargo',
			)
		);
		return $freight;
	}

	/**
	 * Draft children under one hub.
	 *
	 * @param int                                            $parent_id Parent id.
	 * @param string                                         $form      Form type.
	 * @param array<int, array{slug: string, title: string}> $pages     Pages.
	 * @param string                                         $lead      Intro.
	 * @param int                                            $faq       FAQ id.
	 */
	private static function children( int $parent_id, string $form, array $pages, string $lead, int $faq ): void {
		if ( $parent_id < 1 ) {
			return;
		}
		foreach ( $pages as $page ) {
			self::draft( $page['slug'], $page['title'], $parent_id, $form, $lead, $faq );
		}
	}

	/**
	 * One draft path page.
	 *
	 * @param string $slug   Slug.
	 * @param string $title  Title.
	 * @param int    $parent_id Parent id.
	 * @param string $form      Form type.
	 * @param string $lead      Intro.
	 * @param int    $faq       FAQ id.
	 */
	private static function draft( string $slug, string $title, int $parent_id, string $form, string $lead, int $faq ): void {
		$found = self::page_id( $slug, $parent_id );
		if ( $found && 'templates/path.php' === get_post_meta( $found, '_wp_page_template', true ) ) {
			return;
		}
		$id = $found;
		if ( ! $id ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_parent'  => $parent_id,
					'post_content' => '<p>محتوای نمونه. مدیر محتوا بخش‌ها و فرم را از همین برگه ویرایش می‌کند.</p>',
				)
			);
		}
		if ( $id < 1 ) {
			return;
		}
		update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
		update_post_meta( $id, '_lr_demo', '1' );
		update_post_meta( $id, '_lr_eyebrow', 'نمونه' );
		update_post_meta( $id, '_lr_lead', $lead );
		update_post_meta(
			$id,
			'_lr_sections',
			wp_json_encode(
				array(
					array(
						'title' => 'این بخش نمونه است',
						'body'  => 'نرخ زنده و پرداخت در این نسخه نیست. فرم فقط درخواست را در صف می‌نویسد.',
					),
				),
				JSON_UNESCAPED_UNICODE
			)
		);
		update_post_meta( $id, '_lr_catalog', '' );
		update_post_meta( $id, '_lr_form', $form );
		update_post_meta( $id, '_lr_program', 'degree' );
		update_post_meta( $id, '_lr_schema', 'service' );
		if ( $faq ) {
			update_post_meta( $id, '_lr_faq_ids', (string) $faq );
		}
	}

	/**
	 * One draft guide in a sample category.
	 *
	 * @param int $faq FAQ id.
	 */
	private static function guide( int $faq ): void {
		$term = term_exists( 'life', 'lr_guide_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( 'زندگی در روسیه (نمونه)', 'lr_guide_cat', array( 'slug' => 'life' ) );
		}
		$term_id  = is_array( $term ) ? (int) $term['term_id'] : 0;
		$existing = get_posts(
			array(
				'name'           => 'student-dorms',
				'post_type'      => 'lr_guide',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			return;
		}
		$id = (int) wp_insert_post(
			array(
				'post_type'    => 'lr_guide',
				'post_status'  => 'draft',
				'post_title'   => 'خوابگاه دانشجویی (نمونه)',
				'post_name'    => 'student-dorms',
				'post_excerpt' => 'راهنمای نمونه. منتشر نکنید مگر برای آزمون.',
				'post_content' => '<p>این دانستنی نمونه است. هزینه و شرایط خوابگاه را مدیر محتوا عوض می‌کند.</p>',
			)
		);
		if ( $id < 1 ) {
			return;
		}
		if ( $term_id ) {
			wp_set_object_terms( $id, array( $term_id ), 'lr_guide_cat' );
		}
		update_post_meta( $id, '_lr_demo', '1' );
		if ( $faq ) {
			update_post_meta( $id, '_lr_faq_ids', (string) $faq );
		}
	}

	/**
	 * Sample FAQ created with the study-path seed.
	 */
	private static function faq_id(): int {
		$posts = get_posts(
			array(
				'name'           => 'sample-path-faq',
				'post_type'      => 'lr_faq',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}

	/**
	 * Page id by slug and parent, any status.
	 *
	 * @param string $slug   Slug.
	 * @param int    $parent_id Parent id.
	 */
	private static function page_id( string $slug, int $parent_id ): int {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'page',
				'post_status'    => 'any',
				'post_parent'    => $parent_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}
}
