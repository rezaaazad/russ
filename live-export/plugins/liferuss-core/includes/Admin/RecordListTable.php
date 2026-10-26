<?php
/**
 * Read-only list table for a custom lr_* table.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Server-side pagination over a repository.
 */
class RecordListTable extends \WP_List_Table {

	/**
	 * Table suffix.
	 *
	 * @var string
	 */
	private string $suffix;

	/**
	 * Column labels.
	 *
	 * @var array<string, string>
	 */
	private array $column_labels;

	/**
	 * Searchable columns.
	 *
	 * @var string[]
	 */
	private array $search_columns;

	/**
	 * Extra repository arguments, including scope.
	 *
	 * @var array<string, mixed>
	 */
	private array $query_args;

	/**
	 * Configure the list from a table suffix and column map.
	 *
	 * @param array<string, mixed> $args Table configuration.
	 */
	public function __construct( array $args ) {
		parent::__construct(
			array(
				'singular' => (string) ( $args['singular'] ?? 'record' ),
				'plural'   => (string) ( $args['plural'] ?? 'records' ),
				'ajax'     => false,
			)
		);
		$this->suffix         = (string) $args['suffix'];
		$this->column_labels  = isset( $args['columns'] ) && is_array( $args['columns'] ) ? $args['columns'] : array();
		$this->search_columns = isset( $args['search_columns'] ) && is_array( $args['search_columns'] ) ? $args['search_columns'] : array();
		$this->query_args     = isset( $args['query_args'] ) && is_array( $args['query_args'] ) ? $args['query_args'] : array();
	}

	/**
	 * Columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return $this->column_labels;
	}

	/**
	 * Load the current page.
	 */
	public function prepare_items(): void {
		$per_page = 20;
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args   = array_merge(
			$this->query_args,
			array(
				'page'           => $paged,
				'per_page'       => $per_page,
				'search'         => $search,
				'search_columns' => $this->search_columns,
			)
		);
		$result = Repository::for( $this->suffix )->paginate( $args );

		$this->items = $result['items'];
		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}

	/**
	 * Default cell renderer.
	 *
	 * @param array<string, mixed> $item        Row.
	 * @param string               $column_name Column.
	 */
	public function column_default( $item, $column_name ) {
		if ( ! isset( $item[ $column_name ] ) || null === $item[ $column_name ] || '' === $item[ $column_name ] ) {
			return '—';
		}
		$value = (string) $item[ $column_name ];
		if ( in_array( $column_name, array( 'status', 'stage' ), true ) ) {
			return Chrome::pill( sanitize_html_class( $value ), Chrome::status( $value ) );
		}
		if ( str_ends_with( $column_name, '_at' ) || 'stat_date' === $column_name ) {
			return esc_html( Chrome::date( $value ) );
		}
		if ( is_numeric( $value ) && ! in_array( $column_name, array( 'phone', 'lead_code' ), true ) ) {
			return esc_html( Chrome::num( $value ) );
		}
		return esc_html( $value );
	}

	/**
	 * Empty state.
	 */
	public function no_items(): void {
		echo '<div class="lr-empty"><p>' . esc_html__( 'رکوردی نیست. یک مورد تازه اضافه کنید یا فیلتر را بردارید.', 'liferuss-core' ) . '</p></div>';
	}
}
