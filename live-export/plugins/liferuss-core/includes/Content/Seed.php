<?php
/**
 * Draft study-path and immigration placeholders.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds drafts once. Publishing is a manual step.
 */
class Seed {

	/**
	 * Hook the seeder.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe_seed' ), 40 );
	}

	/**
	 * Create draft pages, scholarships, and non-public FAQ rows.
	 */
	public static function maybe_seed(): void {
		if ( '2' === (string) get_option( 'lr_path_seed' ) ) {
			return;
		}
		$faq     = self::note(
			'lr_faq',
			'این متن نمونه است؟',
			'بله. این پرسش نمونه است و تا وقتی صفحهٔ مادر منتشر نشود در سایت دیده نمی‌شود.'
		);
		$quote   = self::note(
			'lr_testimonial',
			'نمونه نظر',
			'مسیر پذیرش برای ما روشن بود. این نظر نمونه است.'
		);
		$parents = array();
		foreach ( self::pages() as $page ) {
			$parent = 0;
			if ( '' !== $page['parent'] && isset( $parents[ $page['parent'] ] ) ) {
				$parent = $parents[ $page['parent'] ];
			}
			$id = self::page( $page, $parent, $faq, $quote );
			if ( 0 === $parent ) {
				$parents[ $page['slug'] ] = $id;
			}
		}
		self::scholarship( 'government-quota', 'سهمیه دولتی روسیه (نمونه)', '۱۵ اسفند', 'شهریه و خوابگاه', 'معدل و سن زیر ۳۵', $faq );
		self::scholarship( 'university-grant', 'گرنت دانشگاه (نمونه)', 'پایان فروردین', 'تخفیف شهریه', 'پذیرش همان دانشگاه', $faq );
		self::scholarship( 'open-doors', 'المپیاد درهای باز (نمونه)', 'آبان', 'شهریه رایگان', 'رتبه المپیاد', $faq );
		self::prep();
		update_option( 'lr_path_seed', '2', false );
	}

	/**
	 * Page definitions. Slugs are the public path segments.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function pages(): array {
		$lead = 'متن نمونه. تا وقتی این پیش‌نویس منتشر نشود در سایت دیده نمی‌شود.';
		return array(
			array(
				'title'   => 'تحصیل در روسیه (نمونه)',
				'slug'    => 'study-russia',
				'parent'  => '',
				'catalog' => 'children',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'کارشناسی (نمونه)',
				'slug'    => 'bachelor',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'bachelor',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'کارشناسی ارشد (نمونه)',
				'slug'    => 'master',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'master',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'دکتری (نمونه)',
				'slug'    => 'phd',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'phd',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'پزشکی (نمونه)',
				'slug'    => 'medicine',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'specialist',
				'field'   => 'general-medicine',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'دندانپزشکی (نمونه)',
				'slug'    => 'dentistry',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'specialist',
				'field'   => 'dentistry',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'داروسازی (نمونه)',
				'slug'    => 'pharmacy',
				'parent'  => 'study-russia',
				'catalog' => 'degree',
				'degree'  => 'specialist',
				'field'   => 'pharmacy',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'مراحل پذیرش (نمونه)',
				'slug'    => 'admission-steps',
				'parent'  => 'study-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'مدارک لازم (نمونه)',
				'slug'    => 'documents',
				'parent'  => 'study-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'ویزای تحصیلی (نمونه)',
				'slug'    => 'visa',
				'parent'  => 'study-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'پادفک (نمونه)',
				'slug'    => 'padfak',
				'parent'  => '',
				'catalog' => 'padfak',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'padfak',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'پذیرش مستقیم (نمونه)',
				'slug'    => 'direct-course',
				'parent'  => '',
				'catalog' => 'direct',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'direct_course',
				'schema'  => 'course',
				'lead'    => $lead,
			),
			array(
				'title'   => 'مهاجرت (نمونه)',
				'slug'    => 'migration-russia',
				'parent'  => '',
				'catalog' => 'children',
				'degree'  => '',
				'field'   => '',
				'form'    => 'immigration',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'اقامت (نمونه)',
				'slug'    => 'residence',
				'parent'  => 'migration-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'immigration',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'کار در روسیه (نمونه)',
				'slug'    => 'work',
				'parent'  => 'migration-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'immigration',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'تابعیت (نمونه)',
				'slug'    => 'citizenship',
				'parent'  => 'migration-russia',
				'catalog' => '',
				'degree'  => '',
				'field'   => '',
				'form'    => 'immigration',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'درخواست پذیرش (نمونه)',
				'slug'    => 'admission',
				'parent'  => '',
				'catalog' => 'ranked',
				'degree'  => '',
				'field'   => '',
				'form'    => 'admission',
				'program' => 'degree',
				'schema'  => '',
				'lead'    => $lead,
			),
			array(
				'title'   => 'استعلام نرخ ارز (نمونه)',
				'slug'    => 'exchange',
				'parent'  => '',
				'catalog' => 'children',
				'degree'  => '',
				'field'   => '',
				'form'    => 'exchange',
				'program' => 'degree',
				'schema'  => 'service',
				'lead'    => 'فقط استعلام نرخ. این صفحه نمونه و پیش‌نویس است.',
			),
		);
	}

	/**
	 * One draft page.
	 *
	 * @param array<string, string> $page   Definition.
	 * @param int                   $parent_id Parent id.
	 * @param int                   $faq    FAQ id.
	 * @param int                   $quote  Testimonial id.
	 */
	private static function page( array $page, int $parent_id, int $faq, int $quote ): int {
		$found = self::find_page( $page['slug'], $parent_id );
		if ( $found && 'templates/path.php' === get_post_meta( $found, '_wp_page_template', true ) ) {
			return $found;
		}
		$id = $found;
		if ( ! $id ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_parent'  => $parent_id,
					'post_content' => '<p>محتوای نمونه. مدیر محتوا بخش‌ها، سؤال‌ها و فرم را از همین صفحه ویرایش می‌کند.</p>',
				)
			);
		}
		if ( $id < 1 ) {
			return 0;
		}
		update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
		update_post_meta( $id, '_lr_demo', '1' );
		update_post_meta( $id, '_lr_lead', $page['lead'] );
		update_post_meta( $id, '_lr_eyebrow', 'نمونه' );
		update_post_meta(
			$id,
			'_lr_sections',
			wp_json_encode(
				array(
					array(
						'title' => 'این بخش نمونه است',
						'body'  => 'مدیر محتوا عنوان و متن هر بخش را عوض می‌کند. سازندهٔ آزاد بخش در این نسخه نیست.',
					),
				),
				JSON_UNESCAPED_UNICODE
			)
		);
		update_post_meta( $id, '_lr_catalog', $page['catalog'] );
		update_post_meta( $id, '_lr_degree', $page['degree'] );
		update_post_meta( $id, '_lr_field', $page['field'] );
		update_post_meta( $id, '_lr_form', $page['form'] );
		update_post_meta( $id, '_lr_program', $page['program'] );
		update_post_meta( $id, '_lr_schema', $page['schema'] );
		update_post_meta( $id, '_lr_faq_ids', (string) $faq );
		update_post_meta( $id, '_lr_testimonial_ids', (string) $quote );
		return $id;
	}

	/**
	 * Draft scholarship.
	 *
	 * @param string $slug        Slug.
	 * @param string $title       Title.
	 * @param string $deadline    Deadline label.
	 * @param string $coverage    Coverage.
	 * @param string $eligibility Eligibility.
	 * @param int    $faq         FAQ id.
	 */
	private static function scholarship( string $slug, string $title, string $deadline, string $coverage, string $eligibility, int $faq ): void {
		$existing = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'lr_scholarship',
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
				'post_type'    => 'lr_scholarship',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_excerpt' => 'دادهٔ نمونه. منتشر نکنید مگر برای آزمون.',
				'post_content' => '<p>شرح نمونهٔ بورسیه. پوشش، مهلت و شرایط از جعبهٔ جزئیات ویرایش می‌شود.</p>',
			)
		);
		if ( $id < 1 ) {
			return;
		}
		update_post_meta( $id, '_lr_demo', '1' );
		update_post_meta( $id, '_lr_deadline', $deadline );
		update_post_meta( $id, '_lr_coverage', $coverage );
		update_post_meta( $id, '_lr_eligibility', $eligibility );
		update_post_meta( $id, '_lr_source', 'demo' );
		update_post_meta( $id, '_lr_last_verified_at', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $id, '_lr_faq_ids', (string) $faq );
	}

	/**
	 * One padfak row and one direct-course row on demo universities, when those rows exist.
	 */
	private static function prep(): void {
		$msu = Repository::for( 'universities' )->find_by( 'slug', 'msu', true );
		$hse = Repository::for( 'universities' )->find_by( 'slug', 'hse', true );
		if ( $msu ) {
			self::prep_row( (int) $msu['id'], 'padfak', 10, 220000 );
		}
		if ( $hse ) {
			self::prep_row( (int) $hse['id'], 'direct_course', 2, 90000 );
		}
	}

	/**
	 * Insert a prep row when that university does not already have one of this type.
	 *
	 * @param int    $university_id University id.
	 * @param string $type          padfak or direct_course.
	 * @param int    $months        Duration.
	 * @param float  $tuition       Native tuition.
	 */
	private static function prep_row( int $university_id, string $type, int $months, float $tuition ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_prep_programs';
		$sql   = "SELECT id FROM `{$table}` WHERE university_id = %d AND program_type = %s AND deleted_at IS NULL LIMIT 1";
		$found = (int) $wpdb->get_var( $wpdb->prepare( $sql, $university_id, $type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		if ( $found ) {
			return;
		}
		Repository::for( 'prep_programs' )->insert(
			array(
				'university_id'    => $university_id,
				'program_type'     => $type,
				'track'            => 'general',
				'format'           => 'onsite',
				'duration_months'  => $months,
				'tuition'          => $tuition,
				'currency'         => 'RUB',
				'academic_year'    => '2025/2026',
				'status'           => 'active',
				'source'           => 'demo',
				'last_verified_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Find a page by slug and parent, including drafts.
	 *
	 * @param string $slug   Slug.
	 * @param int    $parent_id Parent id.
	 */
	private static function find_page( string $slug, int $parent_id ): int {
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

	/**
	 * Non-public note. These types have no front URL.
	 *
	 * @param string $type    Post type.
	 * @param string $title   Title.
	 * @param string $content Content.
	 */
	private static function note( string $type, string $title, string $content ): int {
		$slug  = 'lr_faq' === $type ? 'sample-path-faq' : 'sample-path-quote';
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $posts ) {
			return (int) $posts[0];
		}
		return (int) wp_insert_post(
			array(
				'post_type'    => $type,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);
	}
}
