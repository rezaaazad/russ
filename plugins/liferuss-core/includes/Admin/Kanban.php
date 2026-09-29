<?php
/**
 * Drag-and-drop lead board.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\CRM\Catalog;
use LifeRuss\Core\CRM\LeadWriter;
use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Roles\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nine status columns. A drop writes history through LeadWriter.
 */
class Kanban {

	/**
	 * Ajax hook. The screen itself is a menu page.
	 */
	public static function hooks(): void {
		add_action( 'wp_ajax_lr_kanban_move', array( self::class, 'move' ) );
	}

	/**
	 * Board for the leads this user may see.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_access_crm' ) ) {
			wp_die( esc_html__( 'به کانبان دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$scope = Access::apply( array(), Access::lead_scope() );
		if ( null === $scope ) {
			wp_die( esc_html__( 'به کانبان دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'کانبان لیدها', 'liferuss-core' ) . '</h1>';
		echo '<div class="lr-kanban">';
		foreach ( Catalog::lead_statuses() as $key => $label ) {
			$rows = Repository::for( 'leads' )->paginate(
				array_merge(
					$scope,
					array(
						'status'   => $key,
						'per_page' => 40,
						'orderby'  => 'id',
						'order'    => 'DESC',
					)
				)
			);
			echo '<section class="lr-kanban-col" data-status="' . esc_attr( $key ) . '">';
			echo '<h2>' . esc_html( $label ) . ' <span>(' . esc_html( (string) count( $rows['items'] ) ) . ')</span></h2>';
			echo '<div class="lr-kanban-list">';
			foreach ( $rows['items'] as $lead ) {
				$url = add_query_arg(
					array(
						'page' => 'lr-lead',
						'id'   => (int) $lead['id'],
					),
					admin_url( 'admin.php' )
				);
				echo '<article class="lr-kanban-card" draggable="true" data-id="' . esc_attr( (string) $lead['id'] ) . '">';
				echo '<a href="' . esc_url( $url ) . '">' . esc_html( (string) $lead['name'] ) . '</a>';
				echo '<div>' . esc_html( (string) $lead['lead_code'] ) . '</div>';
				echo '</article>';
			}
			echo '</div></section>';
		}
		echo '</div></div>';
	}

	/**
	 * Move one card. The same status is a no-op success.
	 */
	public static function move(): void {
		check_ajax_referer( 'lr_kanban' );
		if ( ! current_user_can( 'lr_access_crm' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$lead    = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead || ! isset( Catalog::lead_statuses()[ $status ] ) || ! LeadWriter::can_view( $lead_id ) ) {
			wp_send_json_error( array( 'message' => 'denied' ), 403 );
		}
		if ( (string) $lead['status'] === $status ) {
			wp_send_json_success();
		}
		$ok = LeadWriter::change_status( $lead_id, $status, 'کانبان' );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'unchanged' ), 400 );
		}
		wp_send_json_success();
	}
}
