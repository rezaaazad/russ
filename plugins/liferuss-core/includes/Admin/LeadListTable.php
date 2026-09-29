<?php
/**
 * Filterable lead list.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\CRM\Catalog;
use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Leads backed by lr_leads.
 */
class LeadListTable extends \WP_List_Table {

	/**
	 * Repository filters already scoped to the user.
	 *
	 * @var array<string, mixed>
	 */
	private array $query;

	/**
	 * Build the list from filters already scoped to the current user.
	 *
	 * @param array<string, mixed> $query Repository arguments.
	 */
	public function __construct( array $query ) {
		parent::__construct(
			array(
				'singular' => 'lead',
				'plural'   => 'leads',
				'ajax'     => false,
			)
		);
		$this->query = $query;
	}

	/**
	 * Columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		$columns = array(
			'cb'            => '<input type="checkbox">',
			'lead_code'     => 'کد',
			'name'          => 'نام',
			'phone'         => 'تلفن',
			'status'        => 'وضعیت',
			'service_id'    => 'سرویس',
			'source'        => 'منبع',
			'consultant_id' => 'مسئول',
			'priority'      => 'اولویت',
			'created_at'    => 'تاریخ',
		);
		if ( ! current_user_can( 'lr_assign_leads' ) ) {
			unset( $columns['cb'] );
		}
		return $columns;
	}

	/**
	 * Checkbox column.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_cb( $item ): string {
		return '<input type="checkbox" name="lead_ids[]" value="' . esc_attr( (string) $item['id'] ) . '">';
	}

	/**
	 * Name links to the detail screen.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_name( $item ): string {
		$url = add_query_arg(
			array(
				'page' => 'lr-lead',
				'id'   => (int) $item['id'],
			),
			admin_url( 'admin.php' )
		);
		return '<a href="' . esc_url( $url ) . '"><strong>' . esc_html( (string) $item['name'] ) . '</strong></a>';
	}

	/**
	 * Jalali date with a Gregorian tooltip.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_created_at( $item ): string {
		return Jalali::html( (string) $item['created_at'] );
	}

	/**
	 * Status label.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_status( $item ): string {
		$statuses = Catalog::lead_statuses();
		$key      = (string) $item['status'];
		return esc_html( $statuses[ $key ] ?? $key );
	}

	/**
	 * Service name.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_service_id( $item ): string {
		$service = Repository::for( 'services' )->find( (int) $item['service_id'] );
		return esc_html( $service ? (string) $service['name_fa'] : (string) $item['service_id'] );
	}

	/**
	 * Assignee display name.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_consultant_id( $item ): string {
		$user_id = (int) $item['consultant_id'];
		if ( ! $user_id ) {
			return '—';
		}
		$user = get_userdata( $user_id );
		return esc_html( $user ? $user->display_name : (string) $user_id );
	}

	/**
	 * Priority label.
	 *
	 * @param array<string, mixed> $item Row.
	 */
	public function column_priority( $item ): string {
		$labels = Catalog::priorities();
		$key    = (string) $item['priority'];
		return esc_html( $labels[ $key ] ?? $key );
	}

	/**
	 * Default column.
	 *
	 * @param array<string, mixed> $item        Row.
	 * @param string               $column_name Column.
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( (string) ( $item[ $column_name ] ?? '' ) );
	}

	/**
	 * Load the page.
	 */
	public function prepare_items(): void {
		$per_page              = 20;
		$paged                 = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$result                = Repository::for( 'leads' )->paginate(
			array_merge(
				$this->query,
				array(
					'page'     => $paged,
					'per_page' => $per_page,
					'orderby'  => 'id',
					'order'    => 'DESC',
				)
			)
		);
		$this->items           = $result['items'];
		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}
}
