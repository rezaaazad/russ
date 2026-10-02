<?php
/**
 * Versioned academic facts. The latest row is what the site shows.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * History of tuition, approvals, dorms, rankings, and admission rules.
 */
class Facts {

	/**
	 * Academic year that starts this September, such as 2026-27.
	 */
	public static function academic_year(): string {
		$year = (int) gmdate( 'Y' );
		if ( (int) gmdate( 'n' ) < 9 ) {
			--$year;
		}
		return $year . '-' . substr( (string) ( $year + 1 ), -2 );
	}

	/**
	 * First year of a stored academic-year string.
	 *
	 * @param string $year Year text.
	 */
	public static function year_start( string $year ): int {
		if ( preg_match( '/(20\d{2})/', $year, $match ) ) {
			return (int) $match[1];
		}
		return 0;
	}

	/**
	 * Append one fact version and return its id.
	 *
	 * @param string $entity_type university, program, dorm, approval, ranking, admission, city.
	 * @param int    $entity_id   Row id.
	 * @param string $fact_key    Fact name.
	 * @param string $value       Display value.
	 * @param string $year        Academic year.
	 * @param string $label       Source label.
	 * @param string $url         Source URL.
	 * @param string $verified    MySQL datetime.
	 * @param int    $user_id     Reviewer.
	 */
	public static function record( string $entity_type, int $entity_id, string $fact_key, string $value, string $year, string $label, string $url, string $verified, int $user_id = 0 ): int {
		global $wpdb;
		if ( $entity_id < 1 || '' === $fact_key ) {
			return 0;
		}
		if ( '' === $verified ) {
			$verified = current_time( 'mysql', true );
		}
		$wpdb->insert(
			$wpdb->prefix . 'lr_fact_versions',
			array(
				'entity_type'      => sanitize_key( $entity_type ),
				'entity_id'        => $entity_id,
				'fact_key'         => sanitize_key( $fact_key ),
				'academic_year'    => sanitize_text_field( $year ),
				'value_text'       => $value,
				'source_label'     => sanitize_text_field( $label ),
				'source_url'       => esc_url_raw( $url ),
				'last_verified_at' => $verified,
				'verified_by'      => $user_id > 0 ? $user_id : null,
				'created_at'       => current_time( 'mysql', true ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Newest fact for an entity, optionally one key.
	 *
	 * @param string $entity_type Type.
	 * @param int    $entity_id   Id.
	 * @param string $fact_key    Optional key.
	 * @return array<string, mixed>|null
	 */
	public static function latest( string $entity_type, int $entity_id, string $fact_key = '' ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_fact_versions';
		if ( '' === $fact_key ) {
			$sql = $wpdb->prepare( "SELECT * FROM `{$table}` WHERE entity_type = %s AND entity_id = %d ORDER BY last_verified_at DESC, id DESC LIMIT 1", $entity_type, $entity_id );
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM `{$table}` WHERE entity_type = %s AND entity_id = %d AND fact_key = %s ORDER BY last_verified_at DESC, id DESC LIMIT 1", $entity_type, $entity_id, $fact_key );
		}
		$row = $wpdb->get_row( $sql, ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Verification time for schema dateModified.
	 *
	 * @param string $entity_type Type.
	 * @param int    $entity_id   Id.
	 */
	public static function verified_at( string $entity_type, int $entity_id ): string {
		$row = self::latest( $entity_type, $entity_id );
		return $row ? (string) $row['last_verified_at'] : '';
	}

	/**
	 * Persian freshness line, or empty when nothing has been reviewed.
	 *
	 * @param string $entity_type Type.
	 * @param int    $entity_id   Id.
	 * @param string $fact_key    Optional key such as tuition or profile.
	 */
	public static function line( string $entity_type, int $entity_id, string $fact_key = '' ): string {
		$row = self::latest( $entity_type, $entity_id, $fact_key );
		if ( ! $row ) {
			return '';
		}
		$source = '' !== (string) $row['source_label'] ? (string) $row['source_label'] : (string) $row['source_url'];
		if ( '' === $source ) {
			$source = 'ثبت داخلی';
		}
		$date = self::jalali_date( (string) $row['last_verified_at'] );
		$year = self::display_year( (string) $row['academic_year'] );
		return 'منبع: ' . $source . ' | آخرین بررسی: ' . $date . ' | سال تحصیلی ' . $year;
	}

	/**
	 * Facts older than 180 days, or tied to an earlier academic year.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function stale(): array {
		global $wpdb;
		$table  = $wpdb->prefix . 'lr_fact_versions';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 180 * DAY_IN_SECONDS ) );
		$start  = self::year_start( self::academic_year() );
		$sql    = $wpdb->prepare(
			"SELECT f.* FROM `{$table}` f INNER JOIN (SELECT MAX(id) AS id FROM `{$table}` GROUP BY entity_type, entity_id, fact_key) newest ON newest.id = f.id WHERE f.last_verified_at < %s ORDER BY f.last_verified_at ASC LIMIT 80",
			$cutoff
		);
		$rows   = $wpdb->get_results( $sql, ARRAY_A );
		$out    = array();
		foreach ( (array) $rows as $row ) {
			$out[] = self::queue_row( $row, '۱۸۰ روز' );
		}
		$year_sql = "SELECT f.* FROM `{$table}` f INNER JOIN (SELECT MAX(id) AS id FROM `{$table}` GROUP BY entity_type, entity_id, fact_key) newest ON newest.id = f.id WHERE f.academic_year <> '' LIMIT 200";
		foreach ( (array) $wpdb->get_results( $year_sql, ARRAY_A ) as $row ) {
			$began = self::year_start( (string) $row['academic_year'] );
			if ( $began > 0 && $began < $start ) {
				$out[] = self::queue_row( $row, 'سال تحصیلی گذشته' );
			}
		}
		return array_merge( $out, self::live( $cutoff, $start ) );
	}

	/**
	 * Live catalog rows that are old or tied to a previous academic year.
	 *
	 * @param string $cutoff MySQL cutoff.
	 * @param int    $start  Current academic-year start.
	 * @return array<int, array<string, string>>
	 */
	private static function live( string $cutoff, int $start ): array {
		global $wpdb;
		$out   = array();
		$dated = array(
			'tuition_fees'           => 'شهریه',
			'dormitory_fees'         => 'خوابگاه',
			'university_approvals'   => 'تأییدیه',
			'admission_requirements' => 'پذیرش',
		);
		foreach ( $dated as $suffix => $label ) {
			$table = $wpdb->prefix . 'lr_' . $suffix;
			$sql   = $wpdb->prepare( "SELECT id, academic_year, last_verified_at, source FROM `{$table}` WHERE deleted_at IS NULL AND (last_verified_at IS NULL OR last_verified_at < %s) ORDER BY id ASC LIMIT 30", $cutoff );
			foreach ( (array) $wpdb->get_results( $sql, ARRAY_A ) as $row ) {
				$out[] = self::live_row( $label, $suffix, $row, '۱۸۰ روز' );
			}
			$year_sql = "SELECT id, academic_year, last_verified_at, source FROM `{$table}` WHERE deleted_at IS NULL AND academic_year <> '' LIMIT 80";
			foreach ( (array) $wpdb->get_results( $year_sql, ARRAY_A ) as $row ) {
				$began = self::year_start( (string) $row['academic_year'] );
				if ( $began > 0 && $began < $start ) {
					$out[] = self::live_row( $label, $suffix, $row, 'سال تحصیلی گذشته' );
				}
			}
		}
		$programs = $wpdb->prefix . 'lr_university_fields';
		$prog_sql = "SELECT id, academic_year, tuition, updated_at FROM `{$programs}` WHERE deleted_at IS NULL AND academic_year <> '' LIMIT 80";
		foreach ( (array) $wpdb->get_results( $prog_sql, ARRAY_A ) as $row ) {
			$began = self::year_start( (string) $row['academic_year'] );
			if ( $began > 0 && $began < $start ) {
				$out[] = array(
					'entity'  => 'رشته #' . (string) $row['id'],
					'fact'    => 'tuition',
					'year'    => (string) $row['academic_year'],
					'value'   => (string) $row['tuition'],
					'checked' => self::jalali_date( (string) $row['updated_at'] ),
					'reason'  => 'سال تحصیلی گذشته',
					'source'  => '',
				);
			}
		}
		$unis = $wpdb->prefix . 'lr_universities';
		$uni  = $wpdb->prepare( "SELECT id, name_fa, last_verified_at, source_label FROM `{$unis}` WHERE deleted_at IS NULL AND (last_verified_at IS NULL OR last_verified_at < %s) ORDER BY id ASC LIMIT 30", $cutoff );
		foreach ( (array) $wpdb->get_results( $uni, ARRAY_A ) as $row ) {
			$out[] = array(
				'entity'  => (string) $row['name_fa'],
				'fact'    => 'profile',
				'year'    => '',
				'value'   => '',
				'checked' => self::jalali_date( (string) $row['last_verified_at'] ),
				'reason'  => '۱۸۰ روز',
				'source'  => (string) $row['source_label'],
			);
		}
		return $out;
	}

	/**
	 * One live-table queue line.
	 *
	 * @param string               $label Human type.
	 * @param string               $fact  Fact key.
	 * @param array<string, mixed> $row   Database row.
	 * @param string               $reason Why it is stale.
	 * @return array<string, string>
	 */
	private static function live_row( string $label, string $fact, array $row, string $reason ): array {
		return array(
			'entity'  => $label . ' #' . (string) $row['id'],
			'fact'    => $fact,
			'year'    => (string) ( $row['academic_year'] ?? '' ),
			'value'   => '',
			'checked' => self::jalali_date( (string) ( $row['last_verified_at'] ?? '' ) ),
			'reason'  => $reason,
			'source'  => (string) ( $row['source'] ?? '' ),
		);
	}

	/**
	 * One queue line.
	 *
	 * @param array<string, mixed> $row    Fact.
	 * @param string               $reason Why it is stale.
	 * @return array<string, string>
	 */
	private static function queue_row( array $row, string $reason ): array {
		return array(
			'entity'  => (string) $row['entity_type'] . ' #' . (string) $row['entity_id'],
			'fact'    => (string) $row['fact_key'],
			'year'    => (string) $row['academic_year'],
			'value'   => wp_html_excerpt( (string) $row['value_text'], 80, '…' ),
			'checked' => self::jalali_date( (string) $row['last_verified_at'] ),
			'reason'  => $reason,
			'source'  => (string) $row['source_label'],
		);
	}

	/**
	 * Jalali date for a UTC timestamp.
	 *
	 * @param string $mysql MySQL datetime.
	 */
	public static function jalali_date( string $mysql ): string {
		$stamp = strtotime( $mysql . ' UTC' );
		if ( ! $stamp ) {
			return '';
		}
		$local = $stamp + (int) ( 3.5 * HOUR_IN_SECONDS );
		$parts = Jalali::to_jalali( (int) gmdate( 'Y', $local ), (int) gmdate( 'n', $local ), (int) gmdate( 'j', $local ) );
		return Jalali::fa_digits( sprintf( '%04d/%02d/%02d', $parts[0], $parts[1], $parts[2] ) );
	}

	/**
	 * Academic year with Persian digits.
	 *
	 * @param string $year Stored year.
	 */
	public static function display_year( string $year ): string {
		if ( '' === $year ) {
			$year = self::academic_year();
		}
		return Jalali::fa_digits( str_replace( array( '/', '-' ), '–', $year ) );
	}
}
