<?php
/**
 * Idempotent, resumable import from the liferuss_lead post type.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Moves legacy leads and support users into the core CRM.
 */
class LegacyMigrator {

	/**
	 * Run one batch, or a full dry-run report.
	 *
	 * @param bool $dry_run When true, nothing is written.
	 * @param int  $batch   Posts per real run.
	 * @return array<string, mixed>
	 */
	public static function run( bool $dry_run, int $batch = 50 ): array {
		$batch  = min( 200, max( 1, $batch ) );
		$report = array(
			'dry_run'   => $dry_run,
			'users'     => self::users( $dry_run ),
			'created'   => 0,
			'skipped'   => 0,
			'remaining' => 0,
			'errors'    => array(),
			'sample'    => array(),
		);

		if ( $dry_run ) {
			$ids = self::post_ids( 0, 5000 );
		} else {
			$ids = self::post_ids( (int) get_option( 'lr_leads_migration_cursor', 0 ), $batch );
		}

		$cursor = (int) get_option( 'lr_leads_migration_cursor', 0 );
		foreach ( $ids as $post_id ) {
			$post_id  = (int) $post_id;
			$post_row = get_post( $post_id );
			$existing = Repository::for( 'leads' )->find_by( 'legacy_post_id', $post_id );
			$title    = $post_row ? (string) $post_row->post_title : '';
			if ( count( $report['sample'] ) < 8 ) {
				$report['sample'][] = array(
					'post_id' => $post_id,
					'title'   => $title,
					'action'  => $existing ? 'skip' : 'create',
				);
			}
			if ( $existing ) {
				++$report['skipped'];
				$cursor = $post_id;
				continue;
			}
			if ( $dry_run ) {
				++$report['created'];
				continue;
			}
			$result = self::import_post( $post_id );
			if ( is_wp_error( $result ) ) {
				$report['errors'][] = $post_id . ': ' . $result->get_error_message();
				break;
			}
			++$report['created'];
			$cursor = $post_id;
		}

		if ( ! $dry_run ) {
			update_option( 'lr_leads_migration_cursor', $cursor, false );
			$report['cursor'] = $cursor;
		}
		$report['remaining'] = count( self::post_ids( $dry_run ? 0 : $cursor, 5000 ) );
		if ( $dry_run ) {
			$report['remaining'] = 0;
		}
		update_option( 'lr_leads_migration_report', $report, false );
		return $report;
	}

	/**
	 * Map liferuss_support users onto lr_* roles.
	 *
	 * @param bool $dry_run Skip writes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function users( bool $dry_run ): array {
		$users = get_users(
			array(
				'role'   => 'liferuss_support',
				'fields' => array( 'ID', 'display_name' ),
			)
		);
		$out   = array();
		foreach ( $users as $user ) {
			$forms    = get_user_meta( $user->ID, '_liferuss_allowed_forms', true );
			$forms    = is_array( $forms ) ? array_map( 'sanitize_key', $forms ) : array();
			$services = self::services_for_forms( $forms );
			$role     = self::role_for_forms( $forms );
			$out[]    = array(
				'id'       => (int) $user->ID,
				'name'     => $user->display_name,
				'role'     => $role,
				'services' => $services,
			);
			if ( $dry_run || get_user_meta( $user->ID, 'lr_legacy_mapped', true ) ) {
				continue;
			}
			$wp_user = get_user_by( 'id', $user->ID );
			if ( $wp_user instanceof \WP_User && ! in_array( $role, $wp_user->roles, true ) ) {
				$wp_user->add_role( $role );
			}
			update_user_meta( $user->ID, 'lr_allowed_services', $services );
			update_user_meta( $user->ID, 'lr_legacy_mapped', '1' );
		}
		return $out;
	}

	/**
	 * Import one private lead post.
	 *
	 * @param int $post_id Post id.
	 * @return int|\WP_Error
	 */
	private static function import_post( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'liferuss_lead' !== $post->post_type ) {
			return new \WP_Error( 'lr_legacy', 'not a lead' );
		}
		$slug = (string) get_post_meta( $post_id, '_liferuss_type_slug', true );
		if ( '' === $slug && function_exists( 'liferuss_leads_post_type_slug' ) ) {
			$slug = (string) liferuss_leads_post_type_slug( $post_id );
		}
		$form   = Catalog::legacy_form( $slug );
		$status = Catalog::legacy_status( (string) get_post_meta( $post_id, '_liferuss_status', true ) );
		$extra  = get_post_meta( $post_id, '_liferuss_extra', true );
		$level  = (string) get_post_meta( $post_id, '_liferuss_level', true );
		$lines  = array();
		if ( $level ) {
			$lines[] = 'مقطع: ' . $level;
		}
		if ( is_array( $extra ) ) {
			foreach ( $extra as $label => $value ) {
				if ( '' !== (string) $value ) {
					$lines[] = $label . ': ' . $value;
				}
			}
		}
		$assignee = (int) get_post_meta( $post_id, '_liferuss_assignee', true );
		$created  = $post->post_date_gmt && '0000-00-00 00:00:00' !== $post->post_date_gmt ? $post->post_date_gmt : gmdate( 'Y-m-d H:i:s' );
		$lead_id  = LeadWriter::create(
			array(
				'name'           => $post->post_title ? $post->post_title : '(بدون نام)',
				'phone'          => (string) get_post_meta( $post_id, '_liferuss_phone', true ),
				'form_type'      => $form,
				'status'         => $status,
				'message'        => implode( "\n", $lines ),
				'lang'           => (string) get_post_meta( $post_id, '_liferuss_lang', true ),
				'source'         => 'migration',
				'ip'             => (string) get_post_meta( $post_id, '_liferuss_ip', true ),
				'consultant_id'  => $assignee,
				'assigned_by'    => $assignee,
				'legacy_post_id' => $post_id,
				'created_at'     => $created,
				'history_reason' => 'migration',
				'request'        => self::request_from_extra( $form, $level, is_array( $extra ) ? $extra : array() ),
			)
		);
		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}
		$file_id = (int) get_post_meta( $post_id, '_liferuss_file_id', true );
		if ( $file_id ) {
			$path = get_attached_file( $file_id );
			if ( $path ) {
				$doc = ( 'freight' === $form || 'cargo' === $form ) ? 'cargo_doc' : ( 'trade' === $form ? 'trade_doc' : 'other' );
				Files::store_path( (int) $lead_id, $path, basename( $path ), $doc );
			}
		}
		return (int) $lead_id;
	}

	/**
	 * Best-effort request columns from the Persian extra array.
	 *
	 * @param string               $form  Form type.
	 * @param string               $level Level label.
	 * @param array<string, mixed> $extra Extra fields.
	 * @return array<string, mixed>
	 */
	private static function request_from_extra( string $form, string $level, array $extra ): array {
		$pick = static function ( array $keys ) use ( $extra ): string {
			foreach ( $keys as $key ) {
				if ( isset( $extra[ $key ] ) && '' !== (string) $extra[ $key ] ) {
					return (string) $extra[ $key ];
				}
			}
			return '';
		};
		if ( 'consult' === $form || 'admission' === $form ) {
			return array(
				'current_education' => substr( $level, 0, 60 ),
				'stage'             => 'new',
			);
		}
		if ( 'freight' === $form || 'cargo' === $form ) {
			return array(
				'direction'        => 'ir_to_ru',
				'cargo_type'       => 'documents',
				'origin_city'      => $pick( array( 'مبدأ' ) ),
				'destination_city' => $pick( array( 'مقصد' ) ),
				'description'      => $pick( array( 'توضیحات', 'نوع محموله' ) ),
				'stage'            => 'new',
			);
		}
		if ( 'trade' === $form ) {
			return array(
				'direction'           => 'ir_to_ru',
				'request_type'        => 'sourcing',
				'product_category'    => $pick( array( 'دسته‌بندی', 'دستهبندی' ) ),
				'product_description' => $pick( array( 'نام کالا', 'توضیحات' ) ),
				'stage'               => 'new',
			);
		}
		if ( 'exchange' === $form ) {
			return array(
				'description' => $pick( array( 'توضیحات' ) ),
				'stage'       => 'new',
			);
		}
		return array();
	}

	/**
	 * Post ids after a cursor.
	 *
	 * @param int $after  Last imported id.
	 * @param int $limit  Max rows.
	 * @return int[]
	 */
	private static function post_ids( int $after, int $limit ): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status <> 'auto-draft' AND ID > %d ORDER BY ID ASC LIMIT %d",
				'liferuss_lead',
				$after,
				$limit
			)
		);
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Allowed service groups for a support user's form list.
	 *
	 * @param string[] $forms Form slugs.
	 * @return string[]
	 */
	private static function services_for_forms( array $forms ): array {
		$map = array(
			'consult'   => 'education',
			'admission' => 'education',
			'freight'   => 'cargo',
			'cargo'     => 'cargo',
			'trade'     => 'trade',
			'exchange'  => 'exchange',
			'contact'   => 'general',
		);
		$out = array();
		foreach ( $forms as $form ) {
			if ( isset( $map[ $form ] ) ) {
				$out[] = $map[ $form ];
			}
		}
		if ( ! $out ) {
			$out[] = 'education';
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Single role. Mixed inboxes become a consultant with several services.
	 *
	 * @param string[] $forms Form slugs.
	 */
	private static function role_for_forms( array $forms ): string {
		$services = self::services_for_forms( $forms );
		if ( array( 'cargo' ) === $services ) {
			return 'lr_cargo_operator';
		}
		if ( array( 'trade' ) === $services ) {
			return 'lr_trade_operator';
		}
		if ( array( 'exchange' ) === $services ) {
			return 'lr_exchange_operator';
		}
		return 'lr_consultant';
	}
}
