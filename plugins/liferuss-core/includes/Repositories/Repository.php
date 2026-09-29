<?php
/**
 * Prepared-statement access to lr_* tables.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Repositories;

use LifeRuss\Core\Database\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small repository for one custom table.
 */
class Repository {

	/**
	 * Table suffix without the lr_ prefix.
	 *
	 * @var string
	 */
	protected string $suffix;

	/**
	 * Bind the repository to one table suffix.
	 *
	 * @param string $suffix Table suffix.
	 */
	public function __construct( string $suffix ) {
		$this->suffix = $suffix;
	}

	/**
	 * Factory.
	 *
	 * @param string $suffix Table suffix.
	 */
	public static function for( string $suffix ): self {
		return new self( $suffix );
	}

	/**
	 * Fully qualified table name.
	 */
	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lr_' . $this->suffix;
	}

	/**
	 * Insert a row. Timestamps default to UTC now.
	 *
	 * @param array<string, mixed> $data Column values.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );
		if ( $this->has_column( 'created_at' ) && ! array_key_exists( 'created_at', $data ) ) {
			$data['created_at'] = $now;
		}
		if ( $this->has_column( 'updated_at' ) && ! array_key_exists( 'updated_at', $data ) ) {
			$data['updated_at'] = $now;
		}

		$data = $this->only_columns( $data );
		if ( ! $data ) {
			return 0;
		}

		$ok = $wpdb->insert( $this->table(), $data, $this->formats( $data ) );
		if ( false === $ok ) {
			return 0;
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a row by primary id.
	 *
	 * @param int                  $id   Row id.
	 * @param array<string, mixed> $data Columns.
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		if ( $this->has_column( 'updated_at' ) && ! array_key_exists( 'updated_at', $data ) ) {
			$data['updated_at'] = gmdate( 'Y-m-d H:i:s' );
		}
		$data = $this->only_columns( $data );
		unset( $data['id'] );
		if ( ! $data ) {
			return false;
		}
		$result = $wpdb->update(
			$this->table(),
			$data,
			array( 'id' => $id ),
			$this->formats( $data ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Soft-delete when the table has deleted_at, otherwise hard-delete.
	 *
	 * @param int $id Row id.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		if ( Tables::soft_deletes( $this->suffix ) ) {
			return $this->update(
				$id,
				array(
					'deleted_at' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}
		$result = $wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Hard-delete a row. Used when the owning post is permanently removed.
	 *
	 * @param int $id Row id.
	 */
	public function hard_delete( int $id ): bool {
		global $wpdb;
		$result = $wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Find one row by id.
	 *
	 * @param int  $id            Row id.
	 * @param bool $with_trashed  Include soft-deleted rows.
	 * @return array<string, mixed>|null
	 */
	public function find( int $id, bool $with_trashed = false ): ?array {
		global $wpdb;

		$sql    = "SELECT * FROM {$this->table()} WHERE id = %d";
		$params = array( $id );
		if ( ! $with_trashed && Tables::soft_deletes( $this->suffix ) ) {
			$sql .= ' AND deleted_at IS NULL';
		}
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find one row by an exact column match.
	 *
	 * @param string $column       Whitelisted column.
	 * @param mixed  $value        Value.
	 * @param bool   $with_trashed Include soft-deleted rows.
	 * @return array<string, mixed>|null
	 */
	public function find_by( string $column, $value, bool $with_trashed = false ): ?array {
		global $wpdb;

		if ( ! $this->has_column( $column ) ) {
			return null;
		}
		$sql    = "SELECT * FROM {$this->table()} WHERE {$column} = " . $this->format_for( $column );
		$params = array( $value );
		if ( ! $with_trashed && Tables::soft_deletes( $this->suffix ) ) {
			$sql .= ' AND deleted_at IS NULL';
		}
		$sql .= ' LIMIT 1';
		$row  = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Count rows, honoring soft delete.
	 *
	 * @param array<string, mixed> $args Optional filters: consultant_id, operator_scope.
	 */
	public function count( array $args = array() ): int {
		$result = $this->paginate(
			array_merge(
				$args,
				array(
					'page'     => 1,
					'per_page' => 1,
				)
			)
		);
		return (int) $result['total'];
	}

	/**
	 * Paginate with prepared filters.
	 *
	 * @param array<string, mixed> $args page, per_page, search, search_columns, orderby, order, filters.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function paginate( array $args ): array {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( Tables::soft_deletes( $this->suffix ) && empty( $args['with_trashed'] ) ) {
			$where[] = 'deleted_at IS NULL';
		}

		if ( isset( $args['consultant_id'] ) ) {
			$where[]  = 'consultant_id = %d';
			$params[] = (int) $args['consultant_id'];
		}

		if ( isset( $args['operator_scope'] ) ) {
			$operator = (int) $args['operator_scope'];
			if ( $operator < 1 ) {
				$where[] = '1=0';
			} else {
				$where[]  = '(operator_id = %d OR operator_id IS NULL)';
				$params[] = $operator;
			}
		}

		if ( isset( $args['lead_consultant_id'] ) ) {
			$leads    = $wpdb->prefix . 'lr_leads';
			$where[]  = "lead_id IN (SELECT id FROM {$leads} WHERE consultant_id = %d AND deleted_at IS NULL)";
			$params[] = (int) $args['lead_consultant_id'];
		}

		if ( ! empty( $args['stale_before'] ) && $this->has_column( 'last_verified_at' ) ) {
			$where[]  = 'last_verified_at < %s';
			$params[] = (string) $args['stale_before'];
		}

		$search = isset( $args['search'] ) ? (string) $args['search'] : '';
		$cols   = isset( $args['search_columns'] ) && is_array( $args['search_columns'] ) ? $args['search_columns'] : array();
		if ( '' !== $search && $cols ) {
			$like  = '%' . $wpdb->esc_like( $search ) . '%';
			$parts = array();
			foreach ( $cols as $col ) {
				$col = (string) $col;
				if ( ! $this->has_column( $col ) ) {
					continue;
				}
				$parts[]  = "{$col} LIKE %s";
				$params[] = $like;
			}
			if ( $parts ) {
				$where[] = '(' . implode( ' OR ', $parts ) . ')';
			}
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where_sql}";
		if ( $params ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$page    = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per     = min( 100, max( 1, (int) ( $args['per_page'] ?? 20 ) ) );
		$offset  = ( $page - 1 ) * $per;
		$orderby = $this->orderby( (string) ( $args['orderby'] ?? 'id' ) );
		$order   = ( isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ) ? 'ASC' : 'DESC';

		$list_sql    = "SELECT * FROM {$this->table()} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$list_params = array_merge( $params, array( $per, $offset ) );
		$items       = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Whether the catalogue contains the column.
	 *
	 * @param string $column Column name.
	 */
	public function has_column( string $column ): bool {
		return in_array( $column, Tables::columns( $this->suffix ), true );
	}

	/**
	 * Drop unknown columns.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @return array<string, mixed>
	 */
	private function only_columns( array $data ): array {
		$allowed = array_flip( Tables::columns( $this->suffix ) );
		return array_intersect_key( $data, $allowed );
	}

	/**
	 * Formats passed to wpdb, aligned with the catalogue types.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return string[]
	 */
	private function formats( array $data ): array {
		$formats = array();
		foreach ( array_keys( $data ) as $column ) {
			$formats[] = $this->format_for( (string) $column );
		}
		return $formats;
	}

	/**
	 * Placeholder for one column.
	 *
	 * @param string $column Column name.
	 */
	private function format_for( string $column ): string {
		$def  = Tables::get( $this->suffix );
		$type = isset( $def['columns'][ $column ] ) ? strtolower( (string) $def['columns'][ $column ] ) : '';
		if ( preg_match( '/^(bigint|int|tinyint|smallint)/', $type ) ) {
			return '%d';
		}
		if ( str_starts_with( $type, 'decimal' ) ) {
			return '%f';
		}
		return '%s';
	}

	/**
	 * Whitelist ORDER BY columns.
	 *
	 * @param string $column Requested column.
	 */
	private function orderby( string $column ): string {
		if ( $this->has_column( $column ) ) {
			return $column;
		}
		if ( $this->has_column( 'id' ) ) {
			return 'id';
		}
		return Tables::columns( $this->suffix )[0] ?? 'id';
	}
}
