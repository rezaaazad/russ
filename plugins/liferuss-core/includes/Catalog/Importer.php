<?php
/**
 * CSV import and export for the catalog. Upsert key is the slug.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent importer. A dry run reports creates and updates without writing.
 */
class Importer {

	/**
	 * Known import types.
	 *
	 * @return string[]
	 */
	public static function types(): array {
		return array( 'universities', 'fields', 'cities', 'programs', 'rankings' );
	}

	/**
	 * Import one CSV file.
	 *
	 * @param string $type    Type slug.
	 * @param string $path    Filesystem path.
	 * @param bool   $dry_run Report only.
	 * @return array<string, mixed>
	 */
	public static function run( string $type, string $path, bool $dry_run ): array {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}
		$report = array(
			'type'    => $type,
			'dry_run' => $dry_run,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
			'rows'    => 0,
		);
		if ( ! in_array( $type, self::types(), true ) ) {
			$report['errors'][] = 'unknown type';
			return $report;
		}
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$report['errors'][] = 'unreadable file';
			return $report;
		}
		$header = fgetcsv( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$report['errors'][] = 'empty file';
			return $report;
		}
		$header = array_map( 'sanitize_key', $header );
		$line   = 1;
		while ( ( $data = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv, Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$line;
			if ( ! is_array( $data ) || ( 1 === count( $data ) && null === $data[0] ) ) {
				continue;
			}
			$row = array();
			foreach ( $header as $index => $key ) {
				$row[ $key ] = isset( $data[ $index ] ) ? trim( (string) $data[ $index ] ) : '';
			}
			if ( '' === implode( '', $row ) ) {
				continue;
			}
			++$report['rows'];
			$result = self::import_row( $type, $row, $dry_run );
			if ( is_wp_error( $result ) ) {
				$report['errors'][] = $line . ': ' . $result->get_error_message();
				++$report['skipped'];
				continue;
			}
			++$report[ $result ];
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		if ( ! $dry_run ) {
			Store::bump();
		}
		update_option( 'lr_import_report', $report, false );
		return $report;
	}

	/**
	 * Stream a CSV, or write it to a path when one is given.
	 *
	 * @param string $type Type slug.
	 * @param string $path Destination path. Empty streams to the browser and exits.
	 */
	public static function export( string $type, string $path = '' ): void {
		if ( ! in_array( $type, self::types(), true ) ) {
			if ( $path ) {
				return;
			}
			wp_die( esc_html__( 'نوع نامعتبر است.', 'liferuss-core' ), '', array( 'response' => 400 ) );
		}
		if ( '' === $path ) {
			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=liferuss-' . $type . '.csv' );
		}
		$out = fopen( '' === $path ? 'php://output' : $path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $out ) {
			return;
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		if ( 'programs' === $type ) {
			self::export_programs( $out );
		} elseif ( 'rankings' === $type ) {
			self::export_rankings( $out );
		} else {
			self::export_entities( $out, $type );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		if ( '' === $path ) {
			exit;
		}
	}

	/**
	 * Import one row.
	 *
	 * @param string                $type Type.
	 * @param array<string, string> $row  Row.
	 * @param bool                  $dry  Dry run.
	 * @return string|\WP_Error created or updated.
	 */
	private static function import_row( string $type, array $row, bool $dry ) {
		if ( 'programs' === $type ) {
			return self::import_program( $row, $dry );
		}
		if ( 'rankings' === $type ) {
			return self::import_ranking( $row, $dry );
		}
		$slug = sanitize_title( $row['slug'] ?? '' );
		$name = $row['name_fa'] ?? '';
		if ( '' === $slug || '' === $name ) {
			return new \WP_Error( 'lr_csv', 'slug and name_fa are required' );
		}
		$map      = array(
			'universities' => 'lr_university',
			'fields'       => 'lr_field',
			'cities'       => 'lr_city',
		);
		$existing = Repository::for( $type )->find_by( 'slug', $slug, true );
		if ( $dry ) {
			return $existing ? 'updated' : 'created';
		}
		$post_id = $existing ? (int) $existing['post_id'] : 0;
		$status  = self::post_status( $row['status'] ?? 'draft' );
		if ( $post_id && get_post( $post_id ) ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $name,
					'post_name'   => $slug,
					'post_status' => $status,
				)
			);
			$action = 'updated';
		} else {
			$post_id = (int) wp_insert_post(
				array(
					'post_type'   => $map[ $type ],
					'post_status' => $status,
					'post_title'  => $name,
					'post_name'   => $slug,
				),
				true
			);
			if ( $post_id < 1 ) {
				return new \WP_Error( 'lr_csv', 'could not create post' );
			}
			$action = 'created';
		}
		$shadow = Store::row_for_post( $type, $post_id );
		if ( ! $shadow ) {
			return new \WP_Error( 'lr_csv', 'shadow row missing' );
		}
		Store::update_row( $type, (int) $shadow['id'], self::columns_for( $type, $row, (int) $shadow['id'] ) );
		if ( '1' === ( $row['demo'] ?? '' ) ) {
			update_post_meta( $post_id, '_lr_demo', '1' );
		}
		return $action;
	}

	/**
	 * Columns for an entity row.
	 *
	 * @param string                $type Table suffix.
	 * @param array<string, string> $row  CSV row.
	 * @param int                   $id   Shadow id, used to resolve city after insert.
	 * @return array<string, mixed>
	 */
	private static function columns_for( string $type, array $row, int $id ): array {
		unset( $id );
		$data = array(
			'name_fa' => $row['name_fa'],
			'name_en' => $row['name_en'] ?? '',
			'name_ru' => $row['name_ru'] ?? '',
		);
		if ( 'universities' === $type ) {
			$city                            = isset( $row['city_slug'] ) ? Repository::for( 'cities' )->find_by( 'slug', sanitize_title( $row['city_slug'] ), true ) : null;
			$data['city_id']                 = $city ? (int) $city['id'] : null;
			$data['short_name']              = $row['short_name'] ?? '';
			$data['website']                 = esc_url_raw( $row['website'] ?? '' );
			$data['founded_year']            = is_numeric( $row['founded_year'] ?? '' ) ? (int) $row['founded_year'] : null;
			$data['address']                 = $row['address'] ?? '';
			$data['ownership']               = in_array( $row['ownership'] ?? '', array( 'state', 'private' ), true ) ? $row['ownership'] : null;
			$data['has_dormitory']           = self::flag( $row['has_dormitory'] ?? '' );
			$data['has_padfak']              = self::flag( $row['has_padfak'] ?? '' );
			$data['has_direct_course']       = self::flag( $row['has_direct_course'] ?? '' );
			$data['has_scholarship']         = self::flag( $row['has_scholarship'] ?? '' );
			$data['teaching_languages']      = self::set_list( $row['teaching_languages'] ?? '', array( 'ru', 'en' ) );
			$data['health_ministry_status']  = self::approval( $row['health_ministry_status'] ?? '' );
			$data['science_ministry_status'] = self::approval( $row['science_ministry_status'] ?? '' );
			$data['last_verified_at']        = self::datetime( $row['last_verified_at'] ?? '' );
		}
		if ( 'fields' === $type ) {
			$data['degree_levels']          = self::set_list( $row['degree_levels'] ?? '', array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ) );
			$data['languages']              = self::set_list( $row['languages'] ?? '', array( 'ru', 'en' ) );
			$data['default_duration_years'] = is_numeric( $row['default_duration_years'] ?? '' ) ? $row['default_duration_years'] : null;
		}
		if ( 'cities' === $type ) {
			$data['federal_subject']  = $row['federal_subject'] ?? '';
			$data['population']       = is_numeric( $row['population'] ?? '' ) ? (int) $row['population'] : null;
			$data['living_cost_min']  = is_numeric( $row['living_cost_min'] ?? '' ) ? $row['living_cost_min'] : null;
			$data['living_cost_max']  = is_numeric( $row['living_cost_max'] ?? '' ) ? $row['living_cost_max'] : null;
			$data['currency']         = self::currency( $row['currency'] ?? 'RUB' );
			$data['climate_summary']  = $row['climate_summary'] ?? '';
			$data['source']           = $row['source'] ?? '';
			$data['last_verified_at'] = self::datetime( $row['last_verified_at'] ?? '' );
		}
		return $data;
	}

	/**
	 * Program row. Both slugs must already exist.
	 *
	 * @param array<string, string> $row Row.
	 * @param bool                  $dry Dry run.
	 * @return string|\WP_Error
	 */
	private static function import_program( array $row, bool $dry ) {
		$uni   = Repository::for( 'universities' )->find_by( 'slug', sanitize_title( $row['university_slug'] ?? '' ), true );
		$field = Repository::for( 'fields' )->find_by( 'slug', sanitize_title( $row['field_slug'] ?? '' ), true );
		if ( ! $uni || ! $field ) {
			return new \WP_Error( 'lr_csv', 'university or field slug was not found' );
		}
		$degree = sanitize_key( $row['degree'] ?? '' );
		$lang   = sanitize_key( $row['language'] ?? 'ru' );
		if ( ! in_array( $degree, array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ), true ) ) {
			return new \WP_Error( 'lr_csv', 'bad degree' );
		}
		if ( ! in_array( $lang, array( 'ru', 'en', 'ru_en' ), true ) ) {
			$lang = 'ru';
		}
		if ( $dry ) {
			return 'updated';
		}
		$tuition  = is_numeric( $row['tuition'] ?? '' ) ? (float) $row['tuition'] : null;
		$currency = self::currency( $row['currency'] ?? 'RUB' );
		Store::upsert_program(
			(int) $uni['id'],
			(int) $field['id'],
			$degree,
			$lang,
			is_numeric( $row['duration_years'] ?? '' ) ? (float) $row['duration_years'] : 4,
			$tuition,
			$currency,
			$row['academic_year'] ?? '',
			true
		);
		return 'updated';
	}

	/**
	 * Ranking row.
	 *
	 * @param array<string, string> $row Row.
	 * @param bool                  $dry Dry run.
	 * @return string|\WP_Error
	 */
	private static function import_ranking( array $row, bool $dry ) {
		$uni      = Repository::for( 'universities' )->find_by( 'slug', sanitize_title( $row['university_slug'] ?? '' ), true );
		$provider = Repository::for( 'ranking_providers' )->find_by( 'code', sanitize_key( $row['provider'] ?? '' ) );
		if ( ! $uni || ! $provider ) {
			return new \WP_Error( 'lr_csv', 'university or ranking provider was not found' );
		}
		$scope = sanitize_key( $row['scope'] ?? 'world' );
		if ( ! in_array( $scope, array( 'world', 'national', 'regional', 'subject' ), true ) ) {
			$scope = 'world';
		}
		$year = (int) ( $row['year'] ?? 0 );
		if ( $year < 1990 ) {
			return new \WP_Error( 'lr_csv', 'year is required' );
		}
		if ( $dry ) {
			return 'updated';
		}
		Store::upsert_ranking(
			(int) $uni['id'],
			(int) $provider['id'],
			$scope,
			sanitize_text_field( $row['subject'] ?? '' ),
			(int) ( $row['rank_value'] ?? 0 ),
			sanitize_text_field( $row['rank_band'] ?? '' ),
			$year,
			true
		);
		return 'updated';
	}

	/**
	 * Entity export.
	 *
	 * @param resource $out  Handle.
	 * @param string   $type Type.
	 */
	private static function export_entities( $out, string $type ): void {
		$headers = array(
			'universities' => array( 'slug', 'name_fa', 'name_en', 'name_ru', 'city_slug', 'ownership', 'founded_year', 'website', 'teaching_languages', 'has_dormitory', 'health_ministry_status', 'science_ministry_status', 'status' ),
			'fields'       => array( 'slug', 'name_fa', 'name_en', 'name_ru', 'degree_levels', 'languages', 'default_duration_years', 'status' ),
			'cities'       => array( 'slug', 'name_fa', 'name_en', 'name_ru', 'federal_subject', 'population', 'living_cost_min', 'living_cost_max', 'currency', 'climate_summary', 'status' ),
		);
		fputcsv( $out, $headers[ $type ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		$page = 1;
		do {
			$batch = Repository::for( $type )->paginate(
				array(
					'page'     => $page,
					'per_page' => 100,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			);
			foreach ( $batch['items'] as $row ) {
				$line = array();
				foreach ( $headers[ $type ] as $column ) {
					if ( 'city_slug' === $column ) {
						$city   = $row['city_id'] ? Repository::for( 'cities' )->find( (int) $row['city_id'] ) : null;
						$line[] = $city ? $city['slug'] : '';
						continue;
					}
					$line[] = $row[ $column ] ?? '';
				}
				fputcsv( $out, $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			}
			$found = count( $batch['items'] );
			++$page;
		} while ( 100 === $found );
	}

	/**
	 * Program export.
	 *
	 * @param resource $out Handle.
	 */
	private static function export_programs( $out ): void {
		fputcsv( $out, array( 'university_slug', 'field_slug', 'degree', 'language', 'duration_years', 'tuition', 'currency', 'academic_year' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		$page = 1;
		do {
			$batch = Repository::for( 'university_fields' )->paginate(
				array(
					'page'     => $page,
					'per_page' => 100,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			);
			foreach ( $batch['items'] as $row ) {
				$uni   = Repository::for( 'universities' )->find( (int) $row['university_id'] );
				$field = Repository::for( 'fields' )->find( (int) $row['field_id'] );
				fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
					$out,
					array(
						$uni ? $uni['slug'] : '',
						$field ? $field['slug'] : '',
						$row['degree'],
						$row['language'],
						$row['duration_years'],
						$row['tuition'],
						$row['currency'],
						$row['academic_year'],
					)
				);
			}
			$found = count( $batch['items'] );
			++$page;
		} while ( 100 === $found );
	}

	/**
	 * Ranking export.
	 *
	 * @param resource $out Handle.
	 */
	private static function export_rankings( $out ): void {
		fputcsv( $out, array( 'university_slug', 'provider', 'scope', 'subject', 'rank_value', 'rank_band', 'year' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		$page = 1;
		do {
			$batch = Repository::for( 'university_rankings' )->paginate(
				array(
					'page'     => $page,
					'per_page' => 100,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			);
			foreach ( $batch['items'] as $row ) {
				$uni      = Repository::for( 'universities' )->find( (int) $row['university_id'] );
				$provider = Repository::for( 'ranking_providers' )->find( (int) $row['ranking_provider_id'] );
				fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
					$out,
					array(
						$uni ? $uni['slug'] : '',
						$provider ? $provider['code'] : '',
						$row['scope'],
						$row['subject'],
						$row['rank_value'],
						$row['rank_band'],
						$row['year'],
					)
				);
			}
			$found = count( $batch['items'] );
			++$page;
		} while ( 100 === $found );
	}

	/**
	 * CSV status to a post status.
	 *
	 * @param string $status CSV value.
	 */
	private static function post_status( string $status ): string {
		$map = array(
			'published' => 'publish',
			'draft'     => 'draft',
			'review'    => 'pending',
			'archived'  => 'lr_archived',
			'publish'   => 'publish',
		);
		return $map[ sanitize_key( $status ) ] ?? 'draft';
	}

	/**
	 * Approval enum or unknown.
	 *
	 * @param string $status Raw value.
	 */
	private static function approval( string $status ): string {
		$status = sanitize_key( $status );
		return in_array( $status, array( 'approved', 'conditional', 'not_approved', 'unknown' ), true ) ? $status : 'unknown';
	}

	/**
	 * Checkbox-like CSV flag.
	 *
	 * @param string $value Raw value.
	 */
	private static function flag( string $value ): int {
		return in_array( strtolower( $value ), array( '1', 'yes', 'true', 'y' ), true ) ? 1 : 0;
	}

	/**
	 * Comma list limited to an allow-list, for MySQL SET columns.
	 *
	 * @param string   $value   Raw list.
	 * @param string[] $allowed Allowed tokens.
	 */
	private static function set_list( string $value, array $allowed ): string {
		$out = array();
		foreach ( explode( ',', $value ) as $part ) {
			$part = sanitize_key( $part );
			if ( in_array( $part, $allowed, true ) ) {
				$out[] = $part;
			}
		}
		return implode( ',', array_unique( $out ) );
	}

	/**
	 * ISO currency, default RUB.
	 *
	 * @param string $code Raw code.
	 */
	private static function currency( string $code ): string {
		$code = strtoupper( $code );
		return preg_match( '/^[A-Z]{3}$/', $code ) ? $code : 'RUB';
	}

	/**
	 * Datetime or null.
	 *
	 * @param string $value Raw value.
	 */
	private static function datetime( string $value ): ?string {
		if ( '' === $value ) {
			return null;
		}
		$stamp = strtotime( $value . ' UTC' );
		return $stamp ? gmdate( 'Y-m-d H:i:s', $stamp ) : null;
	}
}
