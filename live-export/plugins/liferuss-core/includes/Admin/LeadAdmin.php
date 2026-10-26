<?php
/**
 * Lead list, detail, queues, export, and the legacy migration button.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Account\Portal;
use LifeRuss\Core\CRM\Catalog;
use LifeRuss\Core\CRM\Files;
use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\CRM\LeadWriter;
use LifeRuss\Core\CRM\LegacyMigrator;
use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Roles\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRM screens described in the admin-panel spec.
 */
class LeadAdmin {

	/**
	 * Register hidden pages and POST actions.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( self::class, 'hidden' ) );
		add_action( 'admin_post_lr_lead_save', array( self::class, 'save' ) );
		add_action( 'admin_post_lr_leads_bulk', array( self::class, 'bulk' ) );
		add_action( 'admin_post_lr_export_leads', array( self::class, 'export' ) );
		add_action( 'admin_post_lr_migrate_leads', array( self::class, 'migrate' ) );
	}

	/**
	 * Detail screen is linked, not shown in the menu.
	 */
	public static function hidden(): void {
		add_submenu_page( null, 'لید', 'لید', 'lr_access_crm', 'lr-lead', array( self::class, 'detail' ) );
	}

	/**
	 * Every lead the user is allowed to manage.
	 */
	public static function all(): void {
		self::guard( 'lr_manage_leads' );
		self::list_screen( 'همه لیدها', array() );
	}

	/**
	 * Leads assigned to the current user.
	 */
	public static function mine(): void {
		self::guard( 'lr_view_own_leads' );
		$scope = Access::apply( array(), Access::lead_scope() );
		self::list_screen( 'لیدهای من', $scope ?? array( 'consultant_id' => -1 ) );
	}

	/**
	 * Unassigned queue. Only distributors see it.
	 */
	public static function queue(): void {
		self::guard( 'lr_assign_leads' );
		self::list_screen( 'صف مشترک — ارجاع‌نشده', array( 'unassigned' => true ) );
	}

	/**
	 * Open tasks.
	 */
	public static function tasks(): void {
		self::guard( 'lr_access_crm' );
		$args = array(
			'status'   => 'open',
			'page'     => 1,
			'per_page' => 50,
			'orderby'  => 'due_at',
			'order'    => 'ASC',
		);
		if ( ! current_user_can( 'lr_manage_leads' ) ) {
			$args['assigned_to'] = get_current_user_id();
		}
		$rows = Repository::for( 'lead_tasks' )->paginate( $args );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'وظایف و پیگیری', 'liferuss-core' ) . '</h1><table class="widefat striped"><thead><tr><th>وظیفه</th><th>لید</th><th>موعد</th><th>مسئول</th></tr></thead><tbody>';
		if ( ! $rows['items'] ) {
			echo '<tr><td colspan="4">وظیفه‌ای نیست.</td></tr>';
		}
		foreach ( $rows['items'] as $task ) {
			$link = add_query_arg(
				array(
					'page' => 'lr-lead',
					'id'   => (int) $task['lead_id'],
				),
				admin_url( 'admin.php' )
			);
			$user = get_userdata( (int) $task['assigned_to'] );
			echo '<tr><td>' . esc_html( (string) $task['title'] ) . '</td><td><a href="' . esc_url( $link ) . '">#' . esc_html( (string) $task['lead_id'] ) . '</a></td><td>' . Jalali::html( (string) $task['due_at'] ) . '</td><td>' . esc_html( $user ? $user->display_name : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Counts by status.
	 */
	public static function funnel(): void {
		self::guard( 'lr_manage_leads' );
		$counts = Repository::for( 'leads' )->counts_grouped( 'status' );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'گزارش قیف', 'liferuss-core' ) . '</h1><ul>';
		foreach ( Catalog::lead_statuses() as $key => $label ) {
			echo '<li>' . esc_html( $label ) . ': <strong>' . esc_html( (string) ( $counts[ $key ] ?? 0 ) ) . '</strong></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Leads grouped by UTM source, medium, campaign, and landing URL.
	 */
	public static function sources(): void {
		self::guard( 'lr_manage_leads' );
		global $wpdb;
		$table = $wpdb->prefix . 'lr_leads';
		$rows  = $wpdb->get_results( "SELECT COALESCE(utm_source, '') AS utm_source, COALESCE(utm_medium, '') AS utm_medium, COALESCE(utm_campaign, '') AS utm_campaign, landing_page, COUNT(*) AS total FROM `{$table}` WHERE deleted_at IS NULL GROUP BY utm_source, utm_medium, utm_campaign, landing_page ORDER BY total DESC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		Chrome::open( 'منبع لیدها', 'CRM' );
		echo '<p>هر لید utm_source و utm_medium و utm_campaign و نشانی فرود را نگه می‌دارد.</p>';
		if ( ! is_array( $rows ) || ! $rows ) {
			Chrome::empty( 'لیدی با این تفکیک نیست.' );
			Chrome::close();
			return;
		}
		echo '<div class="lr-scroll"><table class="widefat lr-table"><thead><tr><th>منبع</th><th>رسانه</th><th>کمپین</th><th>فرود</th><th>تعداد</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( '' !== (string) $row['utm_source'] ? (string) $row['utm_source'] : '—' ) . '</td>';
			echo '<td>' . esc_html( '' !== (string) $row['utm_medium'] ? (string) $row['utm_medium'] : '—' ) . '</td>';
			echo '<td>' . esc_html( '' !== (string) $row['utm_campaign'] ? (string) $row['utm_campaign'] : '—' ) . '</td>';
			echo '<td>' . esc_html( (string) $row['landing_page'] ) . '</td>';
			echo '<td>' . esc_html( Chrome::num( (int) $row['total'] ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		Chrome::close();
	}

	/**
	 * Export screen. The file itself is a POST so it is not cached.
	 */
	public static function export_screen(): void {
		self::guard( 'lr_export_leads' );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'خروجی لیدها', 'liferuss-core' ) . '</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_export_leads' );
		echo '<input type="hidden" name="action" value="lr_export_leads">';
		submit_button( __( 'دانلود CSV', 'liferuss-core' ) );
		echo '</form></div>';
	}

	/**
	 * Admission queue.
	 */
	public static function admission(): void {
		self::request_screen( 'admission_requests', 'lr_access_admission', 'درخواست‌های پذیرش' );
	}

	/**
	 * Exchange queue.
	 */
	public static function exchange(): void {
		self::request_screen( 'exchange_requests', 'lr_manage_exchange_requests', 'درخواست‌های صرافی' );
	}

	/**
	 * Cargo queue.
	 */
	public static function cargo(): void {
		self::request_screen( 'cargo_requests', 'lr_manage_cargo_requests', 'درخواست‌های کارگو' );
	}

	/**
	 * Trade queue.
	 */
	public static function trade(): void {
		self::request_screen( 'trade_requests', 'lr_manage_trade_requests', 'درخواست‌های تجارت' );
	}

	/**
	 * Immigration queue.
	 */
	public static function immigration(): void {
		self::request_screen( 'immigration_requests', 'lr_manage_immigration_requests', 'درخواست‌های مهاجرت' );
	}

	/**
	 * Lead detail.
	 */
	public static function detail(): void {
		self::guard( 'lr_access_crm' );
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lead = Repository::for( 'leads' )->find( $id );
		if ( ! $lead || ! LeadWriter::can_view( $id ) ) {
			wp_die( esc_html__( 'لید پیدا نشد.', 'liferuss-core' ), '', array( 'response' => 404 ) );
		}
		$service  = Repository::for( 'services' )->find( (int) $lead['service_id'] );
		$notes    = Repository::for( 'lead_notes' )->paginate(
			array(
				'lead_id'  => $id,
				'per_page' => 50,
				'order'    => 'ASC',
			)
		);
		$tasks    = Repository::for( 'lead_tasks' )->paginate(
			array(
				'lead_id'  => $id,
				'per_page' => 50,
				'order'    => 'ASC',
			)
		);
		$files    = Repository::for( 'lead_files' )->paginate(
			array(
				'lead_id'  => $id,
				'per_page' => 50,
				'order'    => 'ASC',
			)
		);
		$history  = Repository::for( 'lead_status_history' )->paginate(
			array(
				'lead_id'  => $id,
				'per_page' => 50,
				'order'    => 'ASC',
			)
		);
		$requests = LeadWriter::requests_for( $id );
		$assign   = current_user_can( 'lr_assign_leads' );
		$user     = $lead['consultant_id'] ? get_userdata( (int) $lead['consultant_id'] ) : null;

		echo '<div class="wrap lr-wrap"><h1>' . esc_html( (string) $lead['name'] ) . ' <code>' . esc_html( (string) $lead['lead_code'] ) . '</code></h1>';
		echo '<p>' . esc_html( (string) $lead['phone'] ) . ' · ' . esc_html( $service ? (string) $service['name_fa'] : '' ) . ' · ' . Jalali::html( (string) $lead['created_at'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.

		echo '<h2>' . esc_html__( 'قیف وضعیت', 'liferuss-core' ) . '</h2><ol class="lr-pipeline">';
		foreach ( Catalog::lead_statuses() as $key => $label ) {
			if ( $lead['status'] === $key ) {
				echo '<li class="is-current">' . esc_html( $label ) . '</li>';
			} else {
				echo '<li>' . esc_html( $label ) . '</li>';
			}
		}
		echo '</ol>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::save_fields( $id, 'status' );
		echo '<select name="status">';
		foreach ( Catalog::lead_statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $lead['status'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> <input type="text" name="reason" placeholder="' . esc_attr__( 'دلیل تغییر', 'liferuss-core' ) . '"> ';
		submit_button( __( 'ثبت وضعیت', 'liferuss-core' ), 'secondary', 'submit', false );
		echo '</form>';

		echo '<h2>' . esc_html__( 'مسئول', 'liferuss-core' ) . '</h2>';
		if ( $assign ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			self::save_fields( $id, 'assign' );
			echo '<select name="consultant_id"><option value="0">' . esc_html__( 'ارجاع‌نشده', 'liferuss-core' ) . '</option>';
			foreach ( self::staff() as $staff ) {
				echo '<option value="' . esc_attr( (string) $staff->ID ) . '" ' . selected( (int) $lead['consultant_id'], (int) $staff->ID, false ) . '>' . esc_html( $staff->display_name ) . '</option>';
			}
			echo '</select> ';
			submit_button( __( 'ارجاع', 'liferuss-core' ), 'secondary', 'submit', false );
			echo '</form>';
		} else {
			echo '<p>' . esc_html( $user ? $user->display_name : __( 'ارجاع‌نشده', 'liferuss-core' ) ) . '</p>';
		}

		echo '<h2>' . esc_html__( 'تاریخچه وضعیت', 'liferuss-core' ) . '</h2><ul>';
		foreach ( $history['items'] as $row ) {
			$from = (string) $row['from_status'];
			$to   = (string) $row['to_status'];
			echo '<li>' . esc_html( ( Catalog::lead_statuses()[ $from ] ?? $from ) . ' → ' . ( Catalog::lead_statuses()[ $to ] ?? $to ) ) . ' ' . Jalali::html( (string) $row['created_at'] ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
		}
		echo '</ul>';

		echo '<h2>' . esc_html__( 'یادداشت‌ها', 'liferuss-core' ) . '</h2><ul>';
		foreach ( $notes['items'] as $note ) {
			$author = get_userdata( (int) $note['user_id'] );
			echo '<li><strong>' . esc_html( $author ? $author->display_name : '' ) . '</strong> ' . Jalali::html( (string) $note['created_at'] ) . '<br>' . esc_html( (string) $note['note'] ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
		}
		echo '</ul><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::save_fields( $id, 'note' );
		echo '<textarea name="note" rows="3" class="large-text"></textarea>';
		submit_button( __( 'افزودن یادداشت', 'liferuss-core' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'گفتگو با مراجع', 'liferuss-core' ) . '</h2><ul>';
		foreach ( Portal::messages( $id ) as $message ) {
			$author = get_userdata( (int) $message['author_id'] );
			echo '<li><strong>' . esc_html( $author ? $author->display_name : __( 'مراجع', 'liferuss-core' ) ) . '</strong> ' . Jalali::html( (string) $message['created_at'] ) . '<br>' . esc_html( (string) $message['body'] ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
		}
		echo '</ul><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::save_fields( $id, 'message' );
		echo '<textarea name="message" rows="3" class="large-text"></textarea>';
		submit_button( __( 'ارسال به مراجع', 'liferuss-core' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'وظایف', 'liferuss-core' ) . '</h2><ul>';
		foreach ( $tasks['items'] as $task ) {
			echo '<li>' . esc_html( (string) $task['title'] ) . ' — ' . esc_html( (string) $task['status'] ) . ' — ' . Jalali::html( (string) $task['due_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup.
			if ( 'open' === $task['status'] ) {
				echo ' <form style="display:inline" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				self::save_fields( $id, 'task_done' );
				echo '<input type="hidden" name="task_id" value="' . esc_attr( (string) $task['id'] ) . '">';
				submit_button( __( 'انجام شد', 'liferuss-core' ), 'secondary small', 'submit', false );
				echo '</form>';
			}
			echo '</li>';
		}
		echo '</ul><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::save_fields( $id, 'task' );
		echo '<input type="text" name="task_title" class="regular-text" placeholder="' . esc_attr__( 'عنوان وظیفه', 'liferuss-core' ) . '"> ';
		echo '<input type="datetime-local" name="task_due"> ';
		echo '<select name="task_user">';
		foreach ( self::staff() as $staff ) {
			echo '<option value="' . esc_attr( (string) $staff->ID ) . '">' . esc_html( $staff->display_name ) . '</option>';
		}
		echo '</select> ';
		submit_button( __( 'افزودن وظیفه', 'liferuss-core' ), 'secondary', 'submit', false );
		echo '</form>';

		echo '<h2>' . esc_html__( 'فایل‌ها', 'liferuss-core' ) . '</h2><ul>';
		foreach ( $files['items'] as $file ) {
			if ( ! empty( $file['purged_at'] ) ) {
				echo '<li>' . esc_html( (string) $file['original_name'] ) . ' — ' . esc_html__( 'پاک شده', 'liferuss-core' ) . '</li>';
				continue;
			}
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=lr_lead_file&file=' . (int) $file['id'] ), 'lr_lead_file' );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( (string) $file['original_name'] ) . '</a> (' . esc_html( (string) $file['mime_type'] ) . ')</li>';
		}
		echo '</ul>';
		if ( current_user_can( 'lr_manage_lead_files' ) ) {
			echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			self::save_fields( $id, 'file' );
			echo '<input type="file" name="lead_file" accept=".jpg,.jpeg,.png,.webp,.pdf"> ';
			submit_button( __( 'بارگذاری', 'liferuss-core' ), 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '<h2>' . esc_html__( 'درخواست‌های مرتبط', 'liferuss-core' ) . '</h2><ul>';
		foreach ( $requests as $suffix => $row ) {
			if ( ! $row ) {
				continue;
			}
			$stages = Catalog::stages( $suffix );
			echo '<li>' . esc_html( $suffix ) . ' #' . esc_html( (string) $row['id'] ) . ' — ' . esc_html( $stages[ (string) $row['stage'] ] ?? (string) $row['stage'] ) . '</li>';
		}
		echo '</ul>';

		\LifeRuss\Core\Payments\Checkout::box( $id );

		echo '<h2>UTM</h2><ul>';
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'landing_page', 'referrer', 'source' ) as $key ) {
			echo '<li><strong>' . esc_html( $key ) . ':</strong> ' . esc_html( (string) $lead[ $key ] ) . '</li>';
		}
		echo '</ul>';
		if ( '' !== (string) $lead['message'] ) {
			echo '<h2>' . esc_html__( 'پیام', 'liferuss-core' ) . '</h2><pre>' . esc_html( (string) $lead['message'] ) . '</pre>';
		}
		echo '</div>';
	}

	/**
	 * Save detail actions.
	 */
	public static function save(): void {
		$id = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'lr_lead_' . $id ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_die( esc_html__( 'نشست منقضی شده است.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$do = self::posted_key( 'lr_do' );
		if ( 'stage' === $do ) {
			LeadWriter::change_stage( self::posted_key( 'request_table' ), absint( self::posted( 'request_id' ) ), self::posted_key( 'stage' ) );
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=lr-req-exchange' ) );
			exit;
		}
		if ( ! LeadWriter::can_view( $id ) ) {
			wp_die( esc_html__( 'به این لید دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		if ( 'status' === $do ) {
			LeadWriter::change_status( $id, self::posted_key( 'status' ), self::posted( 'reason' ) );
		} elseif ( 'assign' === $do ) {
			LeadWriter::assign( $id, absint( self::posted( 'consultant_id' ) ) );
		} elseif ( 'note' === $do ) {
			LeadWriter::add_note( $id, self::posted_area( 'note' ) );
		} elseif ( 'message' === $do ) {
			Portal::add_message( $id, get_current_user_id(), self::posted_area( 'message' ) );
		} elseif ( 'task' === $do ) {
			$due = self::posted( 'task_due' );
			$due = $due ? gmdate( 'Y-m-d H:i:s', strtotime( $due . ' UTC' ) ) : '';
			LeadWriter::add_task( $id, self::posted( 'task_title' ), $due, absint( self::posted( 'task_user' ) ) );
		} elseif ( 'task_done' === $do ) {
			LeadWriter::complete_task( absint( self::posted( 'task_id' ) ) );
		} elseif ( 'file' === $do && current_user_can( 'lr_manage_lead_files' ) ) {
			Files::store_upload( $id, 'lead_file', 'other' );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=lr-lead&id=' . $id ) );
		exit;
	}

	/**
	 * Bulk assign from the list.
	 */
	public static function bulk(): void {
		if ( ! current_user_can( 'lr_assign_leads' ) ) {
			wp_die( esc_html__( 'اجازهٔ ارجاع ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_leads_bulk' );
		$user = isset( $_POST['consultant_id'] ) ? absint( wp_unslash( $_POST['consultant_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids  = isset( $_POST['lead_ids'] ) && is_array( $_POST['lead_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['lead_ids'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $ids as $id ) {
			if ( $id ) {
				LeadWriter::assign( $id, $user );
			}
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=lr-leads' ) );
		exit;
	}

	/**
	 * CSV for managers. Consultants do not have this capability.
	 */
	public static function export(): void {
		if ( ! current_user_can( 'lr_export_leads' ) ) {
			wp_die( esc_html__( 'خروجی مجاز نیست.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_export_leads' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=liferuss-leads.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $out ) {
			exit;
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fputcsv( $out, array( 'code', 'name', 'phone', 'status', 'source', 'utm_source', 'utm_medium', 'utm_campaign', 'created_jalali', 'created_utc' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		$page = 1;
		do {
			$batch = Repository::for( 'leads' )->paginate(
				array(
					'page'     => $page,
					'per_page' => 100,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			);
			foreach ( $batch['items'] as $lead ) {
				fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
					$out,
					array(
						$lead['lead_code'],
						$lead['name'],
						$lead['phone'],
						$lead['status'],
						$lead['source'],
						$lead['utm_source'],
						$lead['utm_medium'],
						$lead['utm_campaign'],
						Jalali::text( (string) $lead['created_at'] ),
						$lead['created_at'],
					)
				);
			}
			$found = count( $batch['items'] );
			++$page;
		} while ( 100 === $found );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Admin migration button.
	 */
	public static function migrate(): void {
		if ( ! current_user_can( 'lr_manage_leads' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_migrate_leads' );
		$dry = ! empty( $_POST['dry_run'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		LegacyMigrator::run( $dry, 50 );
		wp_safe_redirect( add_query_arg( 'lr_migrated', '1', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=lr-leads' ) ) );
		exit;
	}

	/**
	 * Shared lead list.
	 *
	 * @param string               $title Title.
	 * @param array<string, mixed> $scope Scope filters.
	 */
	private static function list_screen( string $title, array $scope ): void {
		$query = array_merge( $scope, self::filters_from_request() );
		$table = new LeadListTable( $query );
		$table->prepare_items();
		Chrome::open( $title, 'CRM' );
		self::migration_box();
		self::filter_form();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_leads_bulk' );
		echo '<input type="hidden" name="action" value="lr_leads_bulk">';
		if ( current_user_can( 'lr_assign_leads' ) ) {
			echo '<p><select name="consultant_id"><option value="0">' . esc_html__( 'برداشتن ارجاع', 'liferuss-core' ) . '</option>';
			foreach ( self::staff() as $staff ) {
				echo '<option value="' . esc_attr( (string) $staff->ID ) . '">' . esc_html( $staff->display_name ) . '</option>';
			}
			echo '</select> ';
			submit_button( __( 'ارجاع انتخاب‌شده‌ها', 'liferuss-core' ), 'secondary', 'submit', false );
			echo '</p>';
		}
		$table->search_box( __( 'جستجو', 'liferuss-core' ), 'leads' );
		$table->display();
		echo '</form></div>';
	}

	/**
	 * Request queue with a stage control.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $cap    Capability.
	 * @param string $title  Title.
	 */
	private static function request_screen( string $suffix, string $cap, string $title ): void {
		self::guard( $cap );
		if ( 'admission_requests' === $suffix ) {
			$scope = Access::apply( array( 'lead_consultant' => true ), Access::admission_scope() );
		} else {
			$scope = Access::apply( array(), Access::operator_request_scope( $cap ) );
		}
		$args   = $scope ?? array( 'operator_scope' => -1 );
		$rows   = Repository::for( $suffix )->paginate(
			array_merge(
				$args,
				array(
					'page'     => 1,
					'per_page' => 50,
					'orderby'  => 'id',
					'order'    => 'DESC',
				)
			)
		);
		$stages = Catalog::stages( $suffix );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html( $title ) . '</h1><table class="widefat striped"><thead><tr><th>ID</th><th>لید</th><th>مرحله</th></tr></thead><tbody>';
		if ( ! $rows['items'] ) {
			echo '<tr><td colspan="3">' . esc_html__( 'درخواستی نیست.', 'liferuss-core' ) . '</td></tr>';
		}
		foreach ( $rows['items'] as $row ) {
			$link = admin_url( 'admin.php?page=lr-lead&id=' . (int) $row['lead_id'] );
			echo '<tr><td>' . esc_html( (string) $row['id'] ) . '</td><td><a href="' . esc_url( $link ) . '">#' . esc_html( (string) $row['lead_id'] ) . '</a></td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			self::save_fields( (int) $row['lead_id'], 'stage' );
			echo '<input type="hidden" name="request_table" value="' . esc_attr( $suffix ) . '">';
			echo '<input type="hidden" name="request_id" value="' . esc_attr( (string) $row['id'] ) . '">';
			echo '<select name="stage">';
			foreach ( $stages as $key => $label ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $row['stage'], $key, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select> ';
			submit_button( __( 'ذخیره', 'liferuss-core' ), 'secondary small', 'submit', false );
			echo '</form></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * GET filters. View-only, so no nonce.
	 *
	 * @return array<string, mixed>
	 */
	private static function filters_from_request(): array {
		$args   = array(
			'search'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search_columns' => array( 'lead_code', 'name', 'phone', 'email' ),
		);
		$status = isset( $_GET['lr_status'] ) ? sanitize_key( wp_unslash( $_GET['lr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( Catalog::lead_statuses()[ $status ] ) ) {
			$args['status'] = $status;
		}
		$service = isset( $_GET['lr_service'] ) ? absint( $_GET['lr_service'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $service ) {
			$args['service_id'] = $service;
		}
		$source = isset( $_GET['lr_source'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $source ) {
			$args['source'] = $source;
		}
		$priority = isset( $_GET['lr_priority'] ) ? sanitize_key( wp_unslash( $_GET['lr_priority'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( Catalog::priorities()[ $priority ] ) ) {
			$args['priority'] = $priority;
		}
		if ( current_user_can( 'lr_manage_leads' ) ) {
			$consultant = isset( $_GET['lr_consultant'] ) ? absint( $_GET['lr_consultant'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $consultant ) {
				$args['consultant_id'] = $consultant;
			}
		}
		$after = isset( $_GET['lr_after'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_after'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from  = Jalali::filter_utc( $after, false );
		if ( $from ) {
			$args['created_after'] = $from;
		}
		$before = isset( $_GET['lr_before'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_before'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$until  = Jalali::filter_utc( $before, true );
		if ( $until ) {
			$args['created_before'] = $until;
		}
		return $args;
	}

	/**
	 * Filter bar.
	 */
	private static function filter_form(): void {
		echo '<form method="get" class="lr-filters">';
		echo '<input type="hidden" name="page" value="' . esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'lr-leads' ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<select name="lr_status"><option value="">' . esc_html__( 'وضعیت', 'liferuss-core' ) . '</option>';
		$current = isset( $_GET['lr_status'] ) ? sanitize_key( wp_unslash( $_GET['lr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( Catalog::lead_statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> ';
		echo '<select name="lr_service"><option value="">' . esc_html__( 'سرویس', 'liferuss-core' ) . '</option>';
		$services = Repository::for( 'services' )->paginate(
			array(
				'per_page' => 100,
				'orderby'  => 'sort_order',
				'order'    => 'ASC',
			)
		);
		$svc      = isset( $_GET['lr_service'] ) ? absint( $_GET['lr_service'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $services['items'] as $service ) {
			echo '<option value="' . esc_attr( (string) $service['id'] ) . '" ' . selected( $svc, (int) $service['id'], false ) . '>' . esc_html( (string) $service['name_fa'] ) . '</option>';
		}
		echo '</select> ';
		$source = isset( $_GET['lr_source'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<input type="text" name="lr_source" placeholder="' . esc_attr__( 'منبع', 'liferuss-core' ) . '" value="' . esc_attr( $source ) . '"> ';
		echo '<select name="lr_priority"><option value="">' . esc_html__( 'اولویت', 'liferuss-core' ) . '</option>';
		$priority = isset( $_GET['lr_priority'] ) ? sanitize_key( wp_unslash( $_GET['lr_priority'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( Catalog::priorities() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $priority, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> ';
		if ( current_user_can( 'lr_manage_leads' ) ) {
			$consultant = isset( $_GET['lr_consultant'] ) ? absint( $_GET['lr_consultant'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<select name="lr_consultant"><option value="">' . esc_html__( 'مسئول', 'liferuss-core' ) . '</option>';
			foreach ( self::staff() as $staff ) {
				echo '<option value="' . esc_attr( (string) $staff->ID ) . '" ' . selected( $consultant, (int) $staff->ID, false ) . '>' . esc_html( $staff->display_name ) . '</option>';
			}
			echo '</select> ';
		}
		$after  = isset( $_GET['lr_after'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_after'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$before = isset( $_GET['lr_before'] ) ? sanitize_text_field( wp_unslash( $_GET['lr_before'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hint   = Jalali::year_hint();
		echo '<input type="text" name="lr_after" value="' . esc_attr( $after ) . '" placeholder="' . esc_attr( 'از ' . $hint[0] ) . '" inputmode="numeric"> ';
		echo '<input type="text" name="lr_before" value="' . esc_attr( $before ) . '" placeholder="' . esc_attr( 'تا ' . $hint[1] ) . '" inputmode="numeric"> ';
		submit_button( __( 'فیلتر', 'liferuss-core' ), 'secondary', '', false );
		echo '</form>';
	}

	/**
	 * Migration controls for managers.
	 */
	private static function migration_box(): void {
		if ( ! current_user_can( 'lr_manage_leads' ) ) {
			return;
		}
		$report = get_option( 'lr_leads_migration_report', array() );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:12px 0">';
		wp_nonce_field( 'lr_migrate_leads' );
		echo '<input type="hidden" name="action" value="lr_migrate_leads">';
		echo '<label><input type="checkbox" name="dry_run" value="1"> ' . esc_html__( 'فقط گزارش (dry-run)', 'liferuss-core' ) . '</label> ';
		submit_button( __( 'مهاجرت لیدهای قبلی', 'liferuss-core' ), 'secondary', 'submit', false );
		echo '</form>';
		if ( is_array( $report ) && isset( $_GET['lr_migrated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<pre class="lr-report">' . esc_html( (string) wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</pre>';
		}
	}

	/**
	 * Hidden fields shared by detail forms.
	 *
	 * @param int    $lead_id Lead id.
	 * @param string $action  Action name.
	 */
	private static function save_fields( int $lead_id, string $action ): void {
		wp_nonce_field( 'lr_lead_' . $lead_id );
		echo '<input type="hidden" name="action" value="lr_lead_save">';
		echo '<input type="hidden" name="lead_id" value="' . esc_attr( (string) $lead_id ) . '">';
		echo '<input type="hidden" name="lr_do" value="' . esc_attr( $action ) . '">';
	}

	/**
	 * Sanitized text field. Nonce is checked in the caller.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Sanitized key field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_key( string $key ): string {
		return sanitize_key( self::posted( $key ) );
	}

	/**
	 * Sanitized textarea.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_area( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_textarea_field( wp_unslash( (string) $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * People who can receive a lead.
	 *
	 * @return \WP_User[]
	 */
	private static function staff(): array {
		return get_users(
			array(
				'capability__in' => array( 'lr_view_own_leads', 'lr_manage_leads' ),
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			)
		);
	}

	/**
	 * Capability gate.
	 *
	 * @param string $cap Capability.
	 */
	private static function guard( string $cap ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'به این بخش دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
	}
}
