<?php
/**
 * Writes university, field, and city rows and the cached columns filters use.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Shared catalog writes. Public reads go through Query.
 */
class Store {

	/**
	 * Bump the cache generation so public transients miss.
	 */
	public static function bump(): void {
		$next = (int) get_option( 'lr_catalog_gen', 1 ) + 1;
		update_option( 'lr_catalog_gen', $next, false );
	}

	/**
	 * Current cache generation.
	 */
	public static function generation(): int {
		return (int) get_option( 'lr_catalog_gen', 1 );
	}

	/**
	 * USD amount from the manual rate. Zero when the rate is unset.
	 *
	 * @param string $currency ISO code.
	 * @param float  $amount   Native amount.
	 */
	public static function usd( string $currency, float $amount ): float {
		$rate = Settings::usd_per_unit( strtoupper( $currency ) );
		if ( null === $rate ) {
			return 0.0;
		}
		return round( $amount * $rate, 2 );
	}

	/**
	 * Shadow row for a post, including trashed rows.
	 *
	 * @param string $suffix  Table suffix.
	 * @param int    $post_id Post id.
	 * @return array<string, mixed>|null
	 */
	public static function row_for_post( string $suffix, int $post_id ): ?array {
		$row = Repository::for( $suffix )->find_by( 'post_id', $post_id, true );
		return $row ? $row : null;
	}

	/**
	 * Update columns on a shadow row.
	 *
	 * @param string               $suffix Table suffix.
	 * @param int                  $id     Row id.
	 * @param array<string, mixed> $data   Columns.
	 */
	public static function update_row( string $suffix, int $id, array $data ): bool {
		$ok = Repository::for( $suffix )->update( $id, $data );
		self::bump();
		return $ok;
	}

	/**
	 * Insert or update one offering and its current yearly tuition.
	 *
	 * @param int        $university_id University row id.
	 * @param int        $field_id      Field row id.
	 * @param string     $degree        Degree enum.
	 * @param string     $language      Language enum.
	 * @param float      $duration      Years.
	 * @param float|null $tuition       Native amount, null to skip the fee row.
	 * @param string     $currency      ISO code.
	 * @param string     $year          Academic year label.
	 * @param bool       $recompute     Refresh cached tuition columns.
	 */
	public static function upsert_program( int $university_id, int $field_id, string $degree, string $language, float $duration, ?float $tuition, string $currency, string $year, bool $recompute = true ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'lr_university_fields';
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE university_id = %d AND field_id = %d AND degree = %s AND language = %s AND deleted_at IS NULL LIMIT 1",
				$university_id,
				$field_id,
				$degree,
				$language
			)
		);
		$data  = array(
			'university_id'  => $university_id,
			'field_id'       => $field_id,
			'degree'         => $degree,
			'language'       => $language,
			'duration_years' => $duration,
			'tuition'        => $tuition,
			'currency'       => $currency ? $currency : 'RUB',
			'academic_year'  => substr( $year, 0, 9 ),
			'status'         => 'active',
			'deleted_at'     => null,
		);
		$repo  = Repository::for( 'university_fields' );
		if ( $id ) {
			$repo->update( $id, $data );
		} else {
			$id = $repo->insert( $data );
		}
		if ( $id && null !== $tuition ) {
			self::upsert_current_fee( $university_id, $field_id, $id, $degree, $language, $tuition, $data['currency'], $data['academic_year'] );
		}
		if ( $recompute && $id ) {
			self::recompute_university( $university_id );
			self::recompute_field( $field_id );
			self::bump();
		}
		return $id;
	}

	/**
	 * One current tuition row for a program.
	 *
	 * @param int    $university_id University id.
	 * @param int    $field_id      Field id.
	 * @param int    $program_id    university_fields id.
	 * @param string $degree        Degree.
	 * @param string $language      Language.
	 * @param float  $amount        Native amount.
	 * @param string $currency      Currency.
	 * @param string $year          Academic year.
	 */
	public static function upsert_current_fee( int $university_id, int $field_id, int $program_id, string $degree, string $language, float $amount, string $currency, string $year ): void {
		global $wpdb;

		$usd   = self::usd( $currency, $amount );
		$table = $wpdb->prefix . 'lr_tuition_fees';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE university_field_id = %d AND is_current = 1 AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
				$program_id
			)
		);
		$data  = array(
			'university_id'       => $university_id,
			'field_id'            => $field_id,
			'university_field_id' => $program_id,
			'degree'              => $degree,
			'language'            => $language,
			'amount'              => $amount,
			'currency'            => $currency,
			'amount_usd'          => $usd,
			'fx_rate_at'          => $now,
			'fee_period'          => 'year',
			'academic_year'       => $year,
			'is_current'          => 1,
			'deleted_at'          => null,
		);
		$repo  = Repository::for( 'tuition_fees' );
		if ( $id ) {
			$repo->update( $id, $data );
			return;
		}
		$repo->insert( $data );
	}

	/**
	 * University-wide ministry row. field_id stays empty.
	 *
	 * @param int    $university_id University id.
	 * @param string $authority     health_ministry or science_ministry.
	 * @param string $status        Approval status.
	 * @param string $source        Source label.
	 * @param string $source_url    Source URL.
	 * @param string $verified_at   UTC datetime.
	 */
	public static function upsert_approval( int $university_id, string $authority, string $status, string $source, string $source_url, string $verified_at ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'lr_university_approvals';
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE university_id = %d AND authority = %s AND field_id IS NULL AND academic_year = '' AND deleted_at IS NULL LIMIT 1",
				$university_id,
				$authority
			)
		);
		$data  = array(
			'university_id'    => $university_id,
			'authority'        => $authority,
			'academic_year'    => '',
			'status'           => $status,
			'source'           => $source,
			'source_url'       => $source_url ? $source_url : null,
			'last_verified_at' => $verified_at ? $verified_at : gmdate( 'Y-m-d H:i:s' ),
			'verified_by'      => get_current_user_id() ? get_current_user_id() : null,
			'deleted_at'       => null,
		);
		$repo  = Repository::for( 'university_approvals' );
		if ( $id ) {
			$repo->update( $id, $data );
		} else {
			$repo->insert( $data );
		}
		$column = 'health_ministry' === $authority ? 'health_ministry_status' : 'science_ministry_status';
		Repository::for( 'universities' )->update( $university_id, array( $column => $status ) );
	}

	/**
	 * Ranking row. Empty subject is stored as an empty string so the unique key holds.
	 *
	 * @param int    $university_id University id.
	 * @param int    $provider_id   Provider id.
	 * @param string $scope         Scope enum.
	 * @param string $subject       Subject label.
	 * @param int    $rank          Rank, 0 when only a band is known.
	 * @param string $band          Rank band.
	 * @param int    $year          Year.
	 * @param bool   $recompute     Refresh best_world_rank.
	 */
	public static function upsert_ranking( int $university_id, int $provider_id, string $scope, string $subject, int $rank, string $band, int $year, bool $recompute = true ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'lr_university_rankings';
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE university_id = %d AND ranking_provider_id = %d AND scope = %s AND subject = %s AND year = %d AND deleted_at IS NULL LIMIT 1",
				$university_id,
				$provider_id,
				$scope,
				$subject,
				$year
			)
		);
		$data  = array(
			'university_id'       => $university_id,
			'ranking_provider_id' => $provider_id,
			'scope'               => $scope,
			'subject'             => $subject,
			'rank_value'          => $rank > 0 ? $rank : null,
			'rank_band'           => $band ? $band : null,
			'year'                => $year,
			'source'              => '',
			'last_verified_at'    => gmdate( 'Y-m-d H:i:s' ),
			'deleted_at'          => null,
		);
		$repo  = Repository::for( 'university_rankings' );
		if ( $id ) {
			$repo->update( $id, $data );
		} else {
			$repo->insert( $data );
		}
		if ( $recompute ) {
			self::recompute_university( $university_id );
			self::bump();
		}
	}

	/**
	 * One dormitory fee summary for the university.
	 *
	 * @param int        $university_id University id.
	 * @param float      $min           Minimum.
	 * @param float|null $max           Maximum.
	 * @param string     $currency      Currency.
	 */
	public static function upsert_dorm( int $university_id, float $min, ?float $max, string $currency ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'lr_dormitory_fees';
		$id    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
				$university_id
			)
		);
		$data  = array(
			'university_id'    => $university_id,
			'room_type'        => 'shared',
			'amount_min'       => $min,
			'amount_max'       => $max,
			'currency'         => $currency ? $currency : 'RUB',
			'fee_period'       => 'month',
			'academic_year'    => '',
			'source'           => '',
			'last_verified_at' => gmdate( 'Y-m-d H:i:s' ),
			'deleted_at'       => null,
		);
		$repo  = Repository::for( 'dormitory_fees' );
		if ( $id ) {
			$repo->update( $id, $data );
			return;
		}
		$repo->insert( $data );
	}

	/**
	 * Refresh min tuition and best world rank on one university.
	 *
	 * @param int $university_id University id.
	 */
	public static function recompute_university( int $university_id ): void {
		global $wpdb;

		$fees  = $wpdb->prefix . 'lr_tuition_fees';
		$ranks = $wpdb->prefix . 'lr_university_rankings';
		$min   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(amount_usd) FROM `{$fees}` WHERE university_id = %d AND is_current = 1 AND deleted_at IS NULL AND amount_usd > 0",
				$university_id
			)
		);
		$rank  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(rank_value) FROM `{$ranks}` WHERE university_id = %d AND scope = 'world' AND deleted_at IS NULL AND rank_value IS NOT NULL",
				$university_id
			)
		);
		Repository::for( 'universities' )->update(
			$university_id,
			array(
				'min_tuition_usd' => ( null !== $min && '' !== (string) $min ) ? $min : null,
				'best_world_rank' => ( null !== $rank && '' !== (string) $rank ) ? (int) $rank : null,
			)
		);
	}

	/**
	 * Refresh how many published universities offer a field, and their average USD tuition.
	 *
	 * @param int $field_id Field id.
	 */
	public static function recompute_field( int $field_id ): void {
		global $wpdb;

		$unis  = $wpdb->prefix . 'lr_universities';
		$progs = $wpdb->prefix . 'lr_university_fields';
		$fees  = $wpdb->prefix . 'lr_tuition_fees';
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.university_id) FROM `{$progs}` p INNER JOIN `{$unis}` u ON u.id = p.university_id WHERE p.field_id = %d AND p.deleted_at IS NULL AND p.status = 'active' AND u.deleted_at IS NULL AND u.status = 'published'",
				$field_id
			)
		);
		$avg   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(amount_usd) FROM `{$fees}` WHERE field_id = %d AND is_current = 1 AND deleted_at IS NULL AND amount_usd > 0",
				$field_id
			)
		);
		Repository::for( 'fields' )->update(
			$field_id,
			array(
				'universities_count' => $count,
				'avg_tuition_usd'    => ( null !== $avg && '' !== (string) $avg ) ? round( (float) $avg, 2 ) : null,
			)
		);
	}
}
