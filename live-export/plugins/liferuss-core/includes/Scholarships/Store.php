<?php
/**
 * Structured scholarship rows. The CPT stays for content and SEO.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Scholarships;

use LifeRuss\Core\Catalog\Store as CatalogStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

/**
 * Read and write lr_scholarships.
 */
class Store {

	/**
	 * Coverage labels.
	 *
	 * @return array<string, string>
	 */
	public static function coverage_types(): array {
		return array(
			'full'    => 'پوشش کامل',
			'partial' => 'پوشش جزئی',
			'tuition' => 'شهریه',
			'stipend' => 'کمک‌هزینه',
			'other'   => 'سایر',
		);
	}

	/**
	 * Degree labels.
	 *
	 * @return array<string, string>
	 */
	public static function degrees(): array {
		return array(
			''           => 'نامشخص',
			'bachelor'   => 'کارشناسی',
			'specialist' => 'تخصصی',
			'master'     => 'کارشناسی ارشد',
			'phd'        => 'دکتری',
			'residency'  => 'رزیدنتی',
		);
	}

	/**
	 * One row for a scholarship post.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>|null
	 */
	public static function for_post( int $post_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_scholarships';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE post_id = %d", $post_id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Rows linked to a university.
	 *
	 * @param int $university_id University id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_university( int $university_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_scholarships';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE university_id = %d ORDER BY deadline ASC, id DESC", $university_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Post ids matching public filters. Null when no filter is active.
	 *
	 * @param array<string, string> $filters degree, coverage, language, university.
	 * @return int[]|null
	 */
	public static function matching_posts( array $filters ): ?array {
		$degree     = sanitize_key( (string) ( $filters['degree'] ?? '' ) );
		$coverage   = sanitize_key( (string) ( $filters['coverage'] ?? '' ) );
		$language   = sanitize_key( (string) ( $filters['language'] ?? '' ) );
		$university = absint( $filters['university'] ?? 0 );
		if ( '' === $degree && '' === $coverage && '' === $language && ! $university ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_scholarships';
		$where = array( '1=1' );
		$args  = array();
		if ( isset( self::degrees()[ $degree ] ) && '' !== $degree ) {
			$where[] = 'degree = %s';
			$args[]  = $degree;
		}
		if ( isset( self::coverage_types()[ $coverage ] ) ) {
			$where[] = 'coverage_type = %s';
			$args[]  = $coverage;
		}
		if ( in_array( $language, array( 'ru', 'en', 'ru_en' ), true ) ) {
			$where[] = 'language = %s';
			$args[]  = $language;
		}
		if ( $university ) {
			$where[] = 'university_id = %d';
			$args[]  = $university;
		}
		$sql = "SELECT post_id FROM `{$table}` WHERE " . implode( ' AND ', $where );
		if ( $args ) {
			$sql = $wpdb->prepare( $sql, $args );
		}
		$ids = $wpdb->get_col( $sql );
		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * Insert or update the structured row for a post.
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $data    Columns.
	 */
	public static function upsert( int $post_id, array $data ): void {
		global $wpdb;
		$table    = $wpdb->prefix . 'lr_scholarships';
		$degree   = sanitize_key( (string) ( $data['degree'] ?? '' ) );
		$cover    = sanitize_key( (string) ( $data['coverage_type'] ?? '' ) );
		$lang     = sanitize_key( (string) ( $data['language'] ?? '' ) );
		$percent  = isset( $data['coverage_percent'] ) && is_numeric( $data['coverage_percent'] ) ? min( 100, absint( $data['coverage_percent'] ) ) : null;
		$quota    = isset( $data['quota'] ) && is_numeric( $data['quota'] ) && absint( $data['quota'] ) > 0 ? absint( $data['quota'] ) : null;
		$uni      = absint( $data['university_id'] ?? 0 );
		$row      = array(
			'post_id'          => $post_id,
			'university_id'    => $uni ? $uni : null,
			'field_name'       => substr( sanitize_text_field( (string) ( $data['field_name'] ?? '' ) ), 0, 120 ),
			'degree'           => isset( self::degrees()[ $degree ] ) ? $degree : '',
			'coverage_type'    => isset( self::coverage_types()[ $cover ] ) ? $cover : 'other',
			'coverage_percent' => $percent,
			'quota'            => $quota,
			'deadline'         => substr( sanitize_text_field( (string) ( $data['deadline'] ?? '' ) ), 0, 40 ),
			'language'         => in_array( $lang, array( 'ru', 'en', 'ru_en' ), true ) ? $lang : '',
			'requirements'     => sanitize_textarea_field( (string) ( $data['requirements'] ?? '' ) ),
			'source'           => substr( sanitize_text_field( (string) ( $data['source'] ?? '' ) ), 0, 255 ),
			'last_updated'     => gmdate( 'Y-m-d H:i:s' ),
		);
		$existing = self::for_post( $post_id );
		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'post_id' => $post_id ) );
			return;
		}
		$wpdb->insert( $table, $row );
	}

	/**
	 * Copy CPT meta into the table.
	 *
	 * @param bool $dry When true, change nothing.
	 * @return array{posts: int, existing: int, inserted: int}
	 */
	public static function migrate( bool $dry ): array {
		global $wpdb;
		$posts  = $wpdb->get_col( "SELECT ID FROM `{$wpdb->posts}` WHERE post_type = 'lr_scholarship' AND post_status <> 'auto-draft'" );
		$posts  = is_array( $posts ) ? $posts : array();
		$report = array(
			'posts'    => count( $posts ),
			'existing' => 0,
			'inserted' => 0,
		);
		foreach ( $posts as $post_id ) {
			$post_id = (int) $post_id;
			if ( self::for_post( $post_id ) ) {
				++$report['existing'];
				continue;
			}
			++$report['inserted'];
			if ( $dry ) {
				continue;
			}
			self::upsert( $post_id, self::from_meta( $post_id ) );
		}
		return $report;
	}

	/**
	 * Map legacy meta onto table columns.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	public static function from_meta( int $post_id ): array {
		$coverage = (string) get_post_meta( $post_id, '_lr_coverage', true );
		$percent  = null;
		$type     = 'other';
		if ( preg_match( '/(\d{1,3})\s*%/u', $coverage, $match ) ) {
			$percent = min( 100, (int) $match[1] );
			$type    = 100 === $percent ? 'full' : 'partial';
		} elseif ( str_contains( $coverage, 'کامل' ) || str_contains( strtolower( $coverage ), 'full' ) ) {
			$type = 'full';
		}
		return array(
			'field_name'       => (string) get_post_meta( $post_id, '_lr_field', true ),
			'degree'           => (string) get_post_meta( $post_id, '_lr_degree', true ),
			'coverage_type'    => $type,
			'coverage_percent' => $percent,
			'deadline'         => (string) get_post_meta( $post_id, '_lr_deadline', true ),
			'requirements'     => (string) get_post_meta( $post_id, '_lr_eligibility', true ),
			'source'           => (string) get_post_meta( $post_id, '_lr_source', true ),
			'university_id'    => self::university_from_meta( $post_id ),
		);
	}

	/**
	 * University choices for the meta box and filters.
	 *
	 * @return array<int, string>
	 */
	public static function filter_universities(): array {
		global $wpdb;
		$unis  = $wpdb->prefix . 'lr_universities';
		$table = $wpdb->prefix . 'lr_scholarships';
		$rows  = $wpdb->get_results( "SELECT u.id, u.name_fa FROM `{$unis}` u INNER JOIN `{$table}` s ON s.university_id = u.id WHERE u.deleted_at IS NULL GROUP BY u.id, u.name_fa ORDER BY u.name_fa ASC", ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['id'] ] = (string) $row['name_fa'];
		}
		return $out;
	}

	/**
	 * University choices for the meta box and filters.
	 *
	 * @return array<int, string>
	 */
	public static function universities(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_universities';
		$rows  = $wpdb->get_results( "SELECT id, name_fa FROM `{$table}` WHERE deleted_at IS NULL ORDER BY name_fa ASC LIMIT 500", ARRAY_A );
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['id'] ] = (string) $row['name_fa'];
		}
		return $out;
	}

	/**
	 * University id stored beside a scholarship, if the catalog row exists.
	 *
	 * @param int $post_id Scholarship post id.
	 */
	private static function university_from_meta( int $post_id ): int {
		$raw = absint( get_post_meta( $post_id, '_lr_university_id', true ) );
		if ( ! $raw ) {
			return 0;
		}
		$row = CatalogStore::row_for_post( 'universities', $raw );
		return $row ? (int) $row['id'] : $raw;
	}
}
