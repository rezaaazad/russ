<?php
/**
 * Idempotent reference data.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Database;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds ranking providers and the service tree.
 */
class Seeder {

	/**
	 * Insert missing reference rows. Existing slugs and codes are left untouched.
	 */
	public static function seed(): void {
		if ( get_option( 'lr_db_version' ) !== LIFERUSS_CORE_DB_VERSION ) {
			return;
		}
		self::ranking_providers();
		self::services();
		update_option( 'lr_seed_version', LIFERUSS_CORE_DB_VERSION, false );
	}

	/**
	 * QS and THE, as named in the ERD.
	 */
	private static function ranking_providers(): void {
		$repo = Repository::for( 'ranking_providers' );
		$rows = array(
			array(
				'code'       => 'qs',
				'name'       => 'QS World University Rankings',
				'website'    => 'https://www.topuniversities.com',
				'sort_order' => 1,
			),
			array(
				'code'       => 'the',
				'name'       => 'Times Higher Education',
				'website'    => 'https://www.timeshighereducation.com',
				'sort_order' => 2,
			),
		);
		foreach ( $rows as $row ) {
			$existing = $repo->find_by( 'code', $row['code'] );
			if ( $existing ) {
				continue;
			}
			$row['is_active'] = 1;
			$repo->insert( $row );
		}
	}

	/**
	 * Service tree aligned with the sitemap landing groups.
	 */
	private static function services(): void {
		$repo  = Repository::for( 'services' );
		$order = 0;
		foreach ( self::service_tree() as $parent ) {
			++$order;
			$parent_id   = self::ensure_service( $repo, $parent, null, $order );
			$child_order = 0;
			foreach ( $parent['children'] as $child ) {
				++$child_order;
				$child['service_group'] = $parent['service_group'];
				$child['form_type']     = $child['form_type'] ?? $parent['form_type'];
				$child['request_table'] = $child['request_table'] ?? $parent['request_table'];
				$child['operator_role'] = $child['operator_role'] ?? $parent['operator_role'];
				self::ensure_service( $repo, $child, $parent_id, $child_order );
			}
		}
	}

	/**
	 * Insert a service when its slug is missing.
	 *
	 * @param Repository           $repo      Services repository.
	 * @param array<string, mixed> $row       Service fields.
	 * @param int|null             $parent_id Parent id.
	 * @param int                  $order     Sort order.
	 */
	private static function ensure_service( Repository $repo, array $row, ?int $parent_id, int $order ): int {
		$existing = $repo->find_by( 'slug', (string) $row['slug'] );
		if ( $existing ) {
			return (int) $existing['id'];
		}
		$id = $repo->insert(
			array(
				'parent_id'     => $parent_id,
				'slug'          => $row['slug'],
				'name_fa'       => $row['name_fa'],
				'service_group' => $row['service_group'],
				'form_type'     => $row['form_type'],
				'request_table' => $row['request_table'],
				'operator_role' => $row['operator_role'],
				'is_active'     => 1,
				'sort_order'    => $order,
			)
		);
		return $id;
	}

	/**
	 * Canonical services. Children are filled in by the caller.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function service_tree(): array {
		return array(
			array(
				'slug'          => 'education',
				'name_fa'       => 'تحصیل',
				'service_group' => 'education',
				'form_type'     => 'admission',
				'request_table' => 'lr_admission_requests',
				'operator_role' => 'lr_consultant',
				'children'      => array(
					array(
						'slug'    => 'admission',
						'name_fa' => 'پذیرش تحصیلی',
					),
				),
			),
			array(
				'slug'          => 'russian-language',
				'name_fa'       => 'آموزش زبان روسی',
				'service_group' => 'language',
				'form_type'     => 'consult',
				'request_table' => null,
				'operator_role' => null,
				'children'      => array(),
			),
			array(
				'slug'          => 'migration',
				'name_fa'       => 'مهاجرت و زندگی',
				'service_group' => 'migration',
				'form_type'     => 'consult',
				'request_table' => null,
				'operator_role' => null,
				'children'      => array(),
			),
			array(
				'slug'          => 'exchange',
				'name_fa'       => 'صرافی',
				'service_group' => 'exchange',
				'form_type'     => 'exchange',
				'request_table' => 'lr_exchange_requests',
				'operator_role' => 'lr_exchange_operator',
				'children'      => array(
					array(
						'slug'    => 'toman-to-ruble',
						'name_fa' => 'تومان به روبل',
					),
					array(
						'slug'    => 'ruble-to-toman',
						'name_fa' => 'روبل به تومان',
					),
					array(
						'slug'    => 'russia-transfer',
						'name_fa' => 'حواله روسیه',
					),
					array(
						'slug'    => 'tuition-payment',
						'name_fa' => 'پرداخت شهریه',
					),
					array(
						'slug'    => 'dormitory-payment',
						'name_fa' => 'پرداخت خوابگاه',
					),
					array(
						'slug'    => 'student-finance',
						'name_fa' => 'خدمات مالی دانشجویی',
					),
				),
			),
			array(
				'slug'          => 'cargo',
				'name_fa'       => 'کارگو و باربری',
				'service_group' => 'cargo',
				'form_type'     => 'cargo',
				'request_table' => 'lr_cargo_requests',
				'operator_role' => 'lr_cargo_operator',
				'children'      => array(
					array(
						'slug'    => 'iran-to-russia',
						'name_fa' => 'ایران به روسیه',
					),
					array(
						'slug'    => 'russia-to-iran',
						'name_fa' => 'روسیه به ایران',
					),
					array(
						'slug'    => 'documents',
						'name_fa' => 'ارسال مدارک',
					),
					array(
						'slug'    => 'personal-items',
						'name_fa' => 'وسایل شخصی',
					),
					array(
						'slug'    => 'samples',
						'name_fa' => 'نمونه کالا',
					),
					array(
						'slug'    => 'commercial',
						'name_fa' => 'بار تجاری',
					),
				),
			),
			array(
				'slug'          => 'trade',
				'name_fa'       => 'تجارت',
				'service_group' => 'trade',
				'form_type'     => 'trade',
				'request_table' => 'lr_trade_requests',
				'operator_role' => 'lr_trade_operator',
				'children'      => array(
					array(
						'slug'    => 'sourcing',
						'name_fa' => 'سورسینگ',
					),
					array(
						'slug'    => 'export-consulting',
						'name_fa' => 'مشاوره صادرات',
					),
					array(
						'slug'    => 'import-consulting',
						'name_fa' => 'مشاوره واردات',
					),
					array(
						'slug'    => 'supplier-verification',
						'name_fa' => 'اعتبارسنجی تأمین‌کننده',
					),
					array(
						'slug'    => 'b2b',
						'name_fa' => 'خدمات B2B',
					),
				),
			),
			array(
				'slug'          => 'contact',
				'name_fa'       => 'تماس',
				'service_group' => 'general',
				'form_type'     => 'contact',
				'request_table' => null,
				'operator_role' => null,
				'children'      => array(
					array(
						'slug'      => 'consult',
						'name_fa'   => 'مشاوره عمومی',
						'form_type' => 'consult',
					),
				),
			),
		);
	}
}
