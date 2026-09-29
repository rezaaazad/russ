<?php
/**
 * Assignment, SLA, follow-up tasks, digests, and duplicate merges.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Rules from the CRM settings tab. Cron never fatals the public form.
 */
class Automation {

	/**
	 * Roles that can receive a lead.
	 *
	 * @var string[]
	 */
	private const ROLES = array( 'lr_consultant', 'lr_exchange_operator', 'lr_cargo_operator', 'lr_trade_operator' );

	/**
	 * Register cron and the status hook is called from LeadWriter.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'lr_crm_sla', array( self::class, 'sweep_sla' ) );
		add_action( 'lr_crm_digest', array( self::class, 'digest' ) );
	}

	/**
	 * Hourly SLA sweep and a daily digest.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'lr_crm_sla' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'lr_crm_sla' );
		}
		if ( ! wp_next_scheduled( 'lr_crm_digest' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'lr_crm_digest' );
		}
	}

	/**
	 * After a brand-new lead is stored: deadline, then round-robin.
	 *
	 * @param int $lead_id Lead id.
	 */
	public static function after_create( int $lead_id ): void {
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead ) {
			return;
		}
		$rules = Settings::get( 'crm' );
		$hours = max( 1, (int) $rules['sla_hours'] );
		$due   = gmdate( 'Y-m-d H:i:s', time() + ( $hours * HOUR_IN_SECONDS ) );
		Repository::for( 'leads' )->update(
			$lead_id,
			array(
				'sla_due_at'  => $due,
				'sla_overdue' => 0,
			)
		);
		if ( '1' === (string) $rules['auto_assign'] && empty( $lead['consultant_id'] ) ) {
			self::assign_next( $lead_id, (int) $lead['service_id'] );
		}
	}

	/**
	 * Stamp first contact, clear the overdue flag, and open a follow-up task.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $from    Previous status.
	 * @param string $to      New status.
	 */
	public static function after_status( int $lead_id, string $from, string $to ): void {
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead ) {
			return;
		}
		if ( 'new' === $from && empty( $lead['first_response_at'] ) ) {
			Repository::for( 'leads' )->update(
				$lead_id,
				array(
					'first_response_at' => gmdate( 'Y-m-d H:i:s' ),
					'sla_overdue'       => 0,
				)
			);
		}
		$days = self::followup_days( $to );
		if ( $days < 1 ) {
			return;
		}
		$assignee = (int) $lead['consultant_id'];
		if ( $assignee < 1 ) {
			$assignee = get_current_user_id();
		}
		if ( $assignee < 1 ) {
			return;
		}
		$label = Catalog::lead_statuses()[ $to ] ?? $to;
		$title = 'پیگیری: ' . $label;
		if ( self::open_task_exists( $lead_id, $title ) ) {
			return;
		}
		$due = gmdate( 'Y-m-d H:i:s', time() + ( $days * DAY_IN_SECONDS ) );
		LeadWriter::add_system_task( $lead_id, $title, $due, $assignee, 'documents' === $to ? 'document' : 'call' );
	}

	/**
	 * Existing lead with the same phone or email inside the window.
	 *
	 * @param string $phone Raw phone.
	 * @param string $email Email.
	 * @return array<string, mixed>|null
	 */
	public static function find_duplicate( string $phone, string $email ): ?array {
		$rules = Settings::get( 'crm' );
		$days  = max( 1, (int) $rules['dedupe_days'] );
		$phone = LeadWriter::digits( $phone );
		$email = sanitize_email( $email );
		if ( strlen( $phone ) < 8 && ! is_email( $email ) ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$where = array( 'deleted_at IS NULL', 'created_at >= %s', 'duplicate_of IS NULL' );
		$args  = array( $since );
		$match = array();
		if ( strlen( $phone ) >= 8 ) {
			$match[] = 'phone_normalized = %s';
			$args[]  = $phone;
		}
		if ( is_email( $email ) ) {
			$match[] = 'email = %s';
			$args[]  = $email;
		}
		$where[] = '(' . implode( ' OR ', $match ) . ')';
		$sql     = 'SELECT * FROM `' . $table . '` WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT 1';
		$row     = $wpdb->get_row( $wpdb->prepare( $sql, $args ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Keep the older lead and record the repeat, including UTM.
	 *
	 * @param array<string, mixed> $lead Incoming match.
	 * @param array<string, mixed> $data New submission.
	 */
	public static function merge( array $lead, array $data ): void {
		$id     = (int) $lead['id'];
		$bits   = array();
		$source = (string) ( $data['source'] ?? '' );
		if ( $source ) {
			$bits[] = 'منبع ' . $source;
		}
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ) as $key ) {
			$value = trim( (string) ( $data[ $key ] ?? '' ) );
			if ( '' !== $value ) {
				$bits[] = $key . '=' . $value;
			}
		}
		$reason = 'ادغام تکرار' . ( $bits ? ': ' . implode( '، ', $bits ) : '' );
		LeadWriter::log( $id, (string) $lead['status'], (string) $lead['status'], substr( $reason, 0, 255 ) );
		$note = trim( (string) ( $data['message'] ?? '' ) );
		if ( '' !== $note ) {
			$current = trim( (string) $lead['message'] );
			$stamp   = gmdate( 'Y-m-d H:i' );
			$merged  = $current . ( $current ? "\n\n" : '' ) . '— تکرار ' . $stamp . " —\n" . $note;
			Repository::for( 'leads' )->update( $id, array( 'message' => $merged ) );
		}
	}

	/**
	 * Flag overdue first-contact leads and tell administrators once.
	 */
	public static function sweep_sla(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$now   = gmdate( 'Y-m-d H:i:s' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE deleted_at IS NULL AND status = 'new' AND first_response_at IS NULL AND sla_due_at IS NOT NULL AND sla_due_at < %s AND sla_escalated_at IS NULL LIMIT 40",
				$now
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return;
		}
		foreach ( $rows as $lead ) {
			Repository::for( 'leads' )->update(
				(int) $lead['id'],
				array(
					'sla_overdue'      => 1,
					'sla_escalated_at' => $now,
				)
			);
			$body = 'مهلت اولین تماس گذشت.' . "\n" . 'کد: ' . $lead['lead_code'] . "\n" . 'نام: ' . $lead['name'] . "\n" . 'تلفن: ' . $lead['phone'];
			Notifier::notify_admins( 'تأخیر SLA ' . $lead['lead_code'], $body );
		}
	}

	/**
	 * One email per consultant listing tasks due today or earlier.
	 */
	public static function digest(): void {
		$rules = Settings::get( 'crm' );
		if ( '1' !== (string) $rules['daily_digest'] ) {
			return;
		}
		global $wpdb;
		$tasks = $wpdb->prefix . 'lr_lead_tasks';
		$leads = $wpdb->prefix . 'lr_leads';
		$now   = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.assigned_to, t.title, t.due_at, l.lead_code, l.name
				FROM `{$tasks}` t
				INNER JOIN `{$leads}` l ON l.id = t.lead_id AND l.deleted_at IS NULL
				WHERE t.deleted_at IS NULL AND t.status = 'open' AND t.due_at <= %s
				ORDER BY t.assigned_to ASC, t.due_at ASC
				LIMIT 400",
				$now
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) || ! $rows ) {
			return;
		}
		$by_user = array();
		foreach ( $rows as $row ) {
			$by_user[ (int) $row['assigned_to'] ][] = $row;
		}
		foreach ( $by_user as $user_id => $items ) {
			$user = get_userdata( $user_id );
			if ( ! $user || ! is_email( $user->user_email ) ) {
				continue;
			}
			$lines = array( 'وظایف سررسیدشده یا امروز:' );
			foreach ( $items as $item ) {
				$lines[] = $item['lead_code'] . ' — ' . $item['name'] . ' — ' . $item['title'] . ' — ' . $item['due_at'];
			}
			Notifier::mail_to( $user->user_email, 'یادآوری وظایف لایف‌روس', implode( "\n", $lines ) );
		}
	}

	/**
	 * Next active user for this service group.
	 *
	 * @param int $lead_id    Lead id.
	 * @param int $service_id Service id.
	 */
	private static function assign_next( int $lead_id, int $service_id ): void {
		$service = Repository::for( 'services' )->find( $service_id );
		$group   = $service ? (string) $service['service_group'] : '';
		if ( '' === $group ) {
			return;
		}
		$pool = self::pool( $group );
		if ( ! $pool ) {
			return;
		}
		$cursor = (int) get_option( 'lr_rr_' . $group, 0 );
		$next   = $pool[0];
		foreach ( $pool as $user_id ) {
			if ( $user_id > $cursor ) {
				$next = $user_id;
				break;
			}
		}
		update_option( 'lr_rr_' . $group, $next, false );
		LeadWriter::assign_system( $lead_id, $next );
	}

	/**
	 * Active users whose allowed services include the group.
	 *
	 * @param string $group Service group.
	 * @return int[]
	 */
	private static function pool( string $group ): array {
		$users = get_users(
			array(
				'role__in' => self::ROLES,
				'number'   => 200,
				'fields'   => array( 'ID' ),
			)
		);
		$ids   = array();
		foreach ( $users as $user ) {
			$user_id = (int) $user->ID;
			if ( get_user_meta( $user_id, 'lr_deleted_at', true ) ) {
				continue;
			}
			$allowed = get_user_meta( $user_id, 'lr_allowed_services', true );
			if ( ! is_array( $allowed ) || ! in_array( $group, $allowed, true ) ) {
				continue;
			}
			$ids[] = $user_id;
		}
		sort( $ids );
		return $ids;
	}

	/**
	 * Configured delay for a status. Zero means no automatic task.
	 *
	 * @param string $status Status slug.
	 */
	private static function followup_days( string $status ): int {
		$rules = Settings::get( 'crm' );
		$map   = array(
			'contacted' => 'followup_contacted',
			'documents' => 'followup_documents',
			'qualified' => 'followup_qualified',
		);
		if ( ! isset( $map[ $status ] ) ) {
			return 0;
		}
		return max( 0, (int) $rules[ $map[ $status ] ] );
	}

	/**
	 * Whether an open task with this title already exists.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $title   Title.
	 */
	private static function open_task_exists( int $lead_id, string $title ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_lead_tasks';
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE lead_id = %d AND title = %s AND status = 'open' AND deleted_at IS NULL LIMIT 1",
				$lead_id,
				$title
			)
		);
		return (bool) $found;
	}
}
