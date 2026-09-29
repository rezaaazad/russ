<?php
/**
 * Writes leads, request rows, notes, tasks, and status history.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Roles\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRM writes go through this class so history and purge stay consistent.
 */
class LeadWriter {

	/**
	 * Insert a lead and its request row.
	 *
	 * @param array<string, mixed> $data Lead and request fields.
	 * @return int|\WP_Error Lead id.
	 */
	public static function create( array $data ) {
		$form = Catalog::legacy_form( (string) ( $data['form_type'] ?? 'consult' ) );
		$map  = Catalog::forms()[ $form ];
		$svc  = Repository::for( 'services' )->find_by( 'slug', $map['service'] );
		if ( ! $svc ) {
			return new \WP_Error( 'lr_service', __( 'سرویس فرم پیدا نشد.', 'liferuss-core' ) );
		}

		$phone = (string) ( $data['phone'] ?? '' );
		$email = (string) ( $data['email'] ?? '' );
		$now   = ! empty( $data['created_at'] ) ? (string) $data['created_at'] : gmdate( 'Y-m-d H:i:s' );
		$code  = self::unique_code();
		$row   = array(
			'lead_code'        => $code,
			'name'             => (string) ( $data['name'] ?? '' ),
			'phone'            => $phone,
			'phone_normalized' => self::digits( $phone ),
			'email'            => '' !== $email ? $email : null,
			'service_id'       => (int) $svc['id'],
			'form_type'        => $form,
			'message'          => (string) ( $data['message'] ?? '' ),
			'lang'             => (string) ( $data['lang'] ?? 'fa' ),
			'source'           => (string) ( $data['source'] ?? 'website_form' ),
			'landing_page'     => (string) ( $data['landing_page'] ?? '' ),
			'referrer'         => (string) ( $data['referrer'] ?? '' ),
			'utm_source'       => (string) ( $data['utm_source'] ?? '' ),
			'utm_medium'       => (string) ( $data['utm_medium'] ?? '' ),
			'utm_campaign'     => (string) ( $data['utm_campaign'] ?? '' ),
			'utm_content'      => (string) ( $data['utm_content'] ?? '' ),
			'utm_term'         => (string) ( $data['utm_term'] ?? '' ),
			'status'           => isset( Catalog::lead_statuses()[ (string) ( $data['status'] ?? '' ) ] ) ? (string) $data['status'] : 'new',
			'priority'         => isset( Catalog::priorities()[ (string) ( $data['priority'] ?? '' ) ] ) ? (string) $data['priority'] : 'normal',
			'consultant_id'    => ! empty( $data['consultant_id'] ) ? (int) $data['consultant_id'] : null,
			'assigned_by'      => ! empty( $data['assigned_by'] ) ? (int) $data['assigned_by'] : null,
			'assigned_at'      => ! empty( $data['consultant_id'] ) ? $now : null,
			'legacy_post_id'   => ! empty( $data['legacy_post_id'] ) ? (int) $data['legacy_post_id'] : null,
			'user_agent'       => substr( (string) ( $data['user_agent'] ?? '' ), 0, 255 ),
			'consent'          => empty( $data['consent'] ) ? 0 : 1,
			'created_at'       => $now,
			'updated_at'       => $now,
		);
		if ( in_array( $row['status'], Catalog::closed_statuses(), true ) ) {
			$row['closed_at'] = $now;
		}

		$id = Repository::for( 'leads' )->insert( $row );
		if ( ! $id ) {
			return new \WP_Error( 'lr_lead', __( 'ثبت لید ممکن نشد.', 'liferuss-core' ) );
		}
		self::store_ip( $id, (string) ( $data['ip'] ?? '' ) );
		self::history( $id, '', $row['status'], (string) ( $data['history_reason'] ?? 'create' ) );
		if ( $map['request'] ) {
			self::request( $map['request'], $id, is_array( $data['request'] ?? null ) ? $data['request'] : array(), (int) ( $data['operator_id'] ?? 0 ) );
		}
		return $id;
	}

	/**
	 * Whether the current user may open this lead.
	 *
	 * @param int $lead_id Lead id.
	 */
	public static function can_view( int $lead_id ): bool {
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead ) {
			return false;
		}
		if ( current_user_can( 'lr_manage_leads' ) ) {
			return true;
		}
		if ( current_user_can( 'lr_view_own_leads' ) && get_current_user_id() === (int) $lead['consultant_id'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Move a lead along the pipeline and stamp history.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $status  New status.
	 * @param string $reason  Optional reason.
	 * @return bool
	 */
	public static function change_status( int $lead_id, string $status, string $reason = '' ): bool {
		if ( ! isset( Catalog::lead_statuses()[ $status ] ) || ! self::can_view( $lead_id ) ) {
			return false;
		}
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead || $lead['status'] === $status ) {
			return false;
		}
		$patch = array( 'status' => $status );
		if ( in_array( $status, Catalog::closed_statuses(), true ) ) {
			$patch['closed_at'] = gmdate( 'Y-m-d H:i:s' );
		}
		Repository::for( 'leads' )->update( $lead_id, $patch );
		self::history( $lead_id, (string) $lead['status'], $status, $reason );
		if ( in_array( $status, Catalog::closed_statuses(), true ) ) {
			self::arm_purge( $lead_id );
		}
		return true;
	}

	/**
	 * Assign the responsible user. Managers only.
	 *
	 * @param int $lead_id Lead id.
	 * @param int $user_id Consultant or operator id. 0 clears the assignment.
	 */
	public static function assign( int $lead_id, int $user_id ): bool {
		if ( ! current_user_can( 'lr_assign_leads' ) || ! Repository::for( 'leads' )->find( $lead_id ) ) {
			return false;
		}
		$patch = array(
			'consultant_id' => $user_id > 0 ? $user_id : null,
			'assigned_by'   => get_current_user_id(),
			'assigned_at'   => gmdate( 'Y-m-d H:i:s' ),
		);
		return Repository::for( 'leads' )->update( $lead_id, $patch );
	}

	/**
	 * Add a note. user_id is required by the table.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $note    Note text.
	 */
	public static function add_note( int $lead_id, string $note ): int {
		$note = trim( $note );
		if ( '' === $note || ! self::can_view( $lead_id ) || ! get_current_user_id() ) {
			return 0;
		}
		return Repository::for( 'lead_notes' )->insert(
			array(
				'lead_id' => $lead_id,
				'user_id' => get_current_user_id(),
				'note'    => $note,
			)
		);
	}

	/**
	 * Create a follow-up task and mirror the due date onto the lead.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $title   Title.
	 * @param string $due     UTC datetime.
	 * @param int    $user_id Assignee.
	 */
	public static function add_task( int $lead_id, string $title, string $due, int $user_id ): int {
		$title = trim( $title );
		if ( '' === $title || ! self::can_view( $lead_id ) ) {
			return 0;
		}
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return 0;
		}
		$due = $due ? $due : gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$id  = Repository::for( 'lead_tasks' )->insert(
			array(
				'lead_id'     => $lead_id,
				'title'       => $title,
				'assigned_to' => $user_id,
				'due_at'      => $due,
				'status'      => 'open',
				'created_by'  => get_current_user_id() ? get_current_user_id() : null,
			)
		);
		if ( $id ) {
			Repository::for( 'leads' )->update( $lead_id, array( 'next_follow_up_at' => $due ) );
		}
		return $id;
	}

	/**
	 * Mark a task done.
	 *
	 * @param int $task_id Task id.
	 */
	public static function complete_task( int $task_id ): bool {
		$task = Repository::for( 'lead_tasks' )->find( $task_id );
		if ( ! $task || ! self::can_view( (int) $task['lead_id'] ) ) {
			return false;
		}
		return Repository::for( 'lead_tasks' )->update(
			$task_id,
			array(
				'status'       => 'done',
				'completed_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Update a request stage when the user can see that queue.
	 *
	 * @param string $suffix Table suffix.
	 * @param int    $id     Row id.
	 * @param string $stage  New stage.
	 */
	public static function change_stage( string $suffix, int $id, string $stage ): bool {
		$stages = Catalog::stages( $suffix );
		if ( ! isset( $stages[ $stage ] ) ) {
			return false;
		}
		$row = Repository::for( $suffix )->find( $id );
		if ( ! $row || ! self::can_touch_request( $suffix, $row ) ) {
			return false;
		}
		$patch = array( 'stage' => $stage );
		if ( empty( $row['operator_id'] ) && ! current_user_can( 'lr_manage_leads' ) ) {
			$patch['operator_id'] = get_current_user_id();
		}
		return Repository::for( $suffix )->update( $id, $patch );
	}

	/**
	 * Linked request rows for a lead.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<string, array<string, mixed>|null>
	 */
	public static function requests_for( int $lead_id ): array {
		$out = array();
		foreach ( array( 'admission_requests', 'exchange_requests', 'cargo_requests', 'trade_requests' ) as $suffix ) {
			$out[ $suffix ] = Repository::for( $suffix )->find_by( 'lead_id', $lead_id );
		}
		return $out;
	}

	/**
	 * Append one history row.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $from    Previous status, empty on create.
	 * @param string $to      New status.
	 * @param string $reason  Reason.
	 */
	private static function history( int $lead_id, string $from, string $to, string $reason ): void {
		Repository::for( 'lead_status_history' )->insert(
			array(
				'lead_id'     => $lead_id,
				'from_status' => '' !== $from ? $from : null,
				'to_status'   => $to,
				'changed_by'  => get_current_user_id() ? get_current_user_id() : null,
				'reason'      => substr( $reason, 0, 255 ),
			)
		);
	}

	/**
	 * Insert the matching request row.
	 *
	 * @param string               $suffix   Table suffix.
	 * @param int                  $lead_id  Lead id.
	 * @param array<string, mixed> $fields   Already mapped columns.
	 * @param int                  $operator Operator id.
	 */
	private static function request( string $suffix, int $lead_id, array $fields, int $operator ): void {
		$fields['lead_id'] = $lead_id;
		if ( $operator > 0 ) {
			$fields['operator_id'] = $operator;
		}
		if ( 'cargo_requests' === $suffix && empty( $fields['direction'] ) ) {
			$fields['direction'] = 'ir_to_ru';
		}
		if ( 'trade_requests' === $suffix && empty( $fields['direction'] ) ) {
			$fields['direction'] = 'ir_to_ru';
		}
		Repository::for( $suffix )->insert( $fields );
	}

	/**
	 * Set purge_after on files that are still live.
	 *
	 * @param int $lead_id Lead id.
	 */
	private static function arm_purge( int $lead_id ): void {
		$files = Repository::for( 'lead_files' )->paginate(
			array(
				'lead_id'  => $lead_id,
				'page'     => 1,
				'per_page' => 100,
				'orderby'  => 'id',
				'order'    => 'ASC',
			)
		);
		$when  = gmdate( 'Y-m-d', time() + YEAR_IN_SECONDS );
		foreach ( $files['items'] as $file ) {
			if ( ! empty( $file['purge_after'] ) ) {
				continue;
			}
			Repository::for( 'lead_files' )->update( (int) $file['id'], array( 'purge_after' => $when ) );
		}
	}

	/**
	 * Store the client address as VARBINARY.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $ip      IP string.
	 */
	private static function store_ip( int $lead_id, string $ip ): void {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET ip = INET6_ATON(%s) WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$ip,
				$lead_id
			)
		);
	}

	/**
	 * Digits only, for duplicate search later.
	 *
	 * @param string $phone Phone.
	 */
	public static function digits( string $phone ): string {
		$digits = preg_replace( '/\D+/', '', $phone );
		return substr( (string) $digits, 0, 20 );
	}

	/**
	 * Unique public code.
	 */
	private static function unique_code(): string {
		$repo = Repository::for( 'leads' );
		for ( $i = 0; $i < 5; $i++ ) {
			$code = 'LR-' . gmdate( 'ymd' ) . '-' . wp_rand( 1000, 9999 );
			if ( ! $repo->find_by( 'lead_code', $code ) ) {
				return $code;
			}
		}
		return 'LR-' . gmdate( 'ymdHis' ) . '-' . wp_rand( 10, 99 );
	}

	/**
	 * Queue visibility for a request row.
	 *
	 * @param string               $suffix Table suffix.
	 * @param array<string, mixed> $row    Row.
	 */
	private static function can_touch_request( string $suffix, array $row ): bool {
		$cap    = array(
			'admission_requests' => 'lr_manage_admission_requests',
			'exchange_requests'  => 'lr_manage_exchange_requests',
			'cargo_requests'     => 'lr_manage_cargo_requests',
			'trade_requests'     => 'lr_manage_trade_requests',
		);
		$needed = $cap[ $suffix ] ?? '';
		if ( 'admission_requests' === $suffix && current_user_can( 'lr_view_own_admission_requests' ) ) {
			$scope = Access::admission_scope();
			if ( 'all' === $scope['mode'] ) {
				return true;
			}
			$lead = Repository::for( 'leads' )->find( (int) $row['lead_id'] );
			return $lead && get_current_user_id() === (int) $lead['consultant_id'];
		}
		if ( ! $needed || ! current_user_can( $needed ) ) {
			return false;
		}
		if ( current_user_can( 'lr_manage_leads' ) ) {
			return true;
		}
		$op = (int) ( $row['operator_id'] ?? 0 );
		return 0 === $op || get_current_user_id() === $op;
	}
}
