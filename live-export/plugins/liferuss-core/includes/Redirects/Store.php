<?php
/**
 * Read and write lr_redirects.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Redirects;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Exact, prefix, and regex redirects with loop-safe resolution.
 */
class Store {

	/**
	 * Upsert by source hash. Hits are kept on update.
	 *
	 * @param array<string, mixed> $row Columns.
	 */
	public static function upsert( array $row ): int {
		$path = self::path( (string) ( $row['source_path'] ?? '' ) );
		if ( '/' === $path ) {
			return 0;
		}
		$hash = sha1( $path );
		$data = array(
			'source_path' => $path,
			'source_hash' => $hash,
			'target_url'  => self::target( (string) ( $row['target_url'] ?? '' ) ),
			'status_code' => self::code( (int) ( $row['status_code'] ?? 301 ) ),
			'match_type'  => self::match_type( (string) ( $row['match_type'] ?? 'exact' ) ),
			'origin'      => self::origin( (string) ( $row['origin'] ?? 'manual' ) ),
			'is_active'   => empty( $row['is_active'] ) ? 0 : 1,
			'deleted_at'  => null,
		);
		if ( ! empty( $row['object_type'] ) ) {
			$data['object_type'] = sanitize_key( (string) $row['object_type'] );
		}
		if ( ! empty( $row['object_id'] ) ) {
			$data['object_id'] = (int) $row['object_id'];
		}
		if ( get_current_user_id() ) {
			$data['created_by'] = get_current_user_id();
		}
		$repo  = Repository::for( 'redirects' );
		$found = $repo->find_by( 'source_hash', $hash, true );
		self::bust();
		if ( $found ) {
			unset( $data['created_by'] );
			$repo->update( (int) $found['id'], $data );
			return (int) $found['id'];
		}
		return $repo->insert( $data );
	}

	/**
	 * Resolve one request path to a single hop, or null when the chain loops.
	 *
	 * @param string $path Request path.
	 * @return array{code: int, target: string, id: int}|null
	 */
	public static function decide( string $path ): ?array {
		$path  = self::path( $path );
		$seen  = array( $path );
		$first = null;
		$now   = $path;
		for ( $hop = 0; $hop < 5; $hop++ ) {
			$row = self::match( $now );
			if ( ! $row ) {
				break;
			}
			if ( null === $first ) {
				$first = $row;
			}
			$code = self::code( (int) $row['status_code'] );
			if ( 410 === $code ) {
				return array(
					'code'   => 410,
					'target' => '',
					'id'     => (int) $first['id'],
				);
			}
			$target = self::target( (string) $row['target_url'] );
			if ( self::external( $target ) ) {
				if ( in_array( $target, $seen, true ) ) {
					return null;
				}
				return array(
					'code'   => $code,
					'target' => $target,
					'id'     => (int) $first['id'],
				);
			}
			$next = self::path( $target );
			if ( in_array( $next, $seen, true ) ) {
				return null;
			}
			$seen[] = $next;
			$now    = $next;
		}
		if ( null === $first || $now === $path ) {
			return null;
		}
		return array(
			'code'   => self::code( (int) $first['status_code'] ),
			'target' => $now,
			'id'     => (int) $first['id'],
		);
	}

	/**
	 * Count one hit on the rule that matched the request.
	 *
	 * @param int $id Row id.
	 */
	public static function hit( int $id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_redirects';
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET hits = hits + 1, last_hit_at = %s WHERE id = %d",
				gmdate( 'Y-m-d H:i:s' ),
				$id
			)
		);
	}

	/**
	 * Active rows for the admin list.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_redirects';
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 200", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One row, including inactive.
	 *
	 * @param int $id Row id.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $id ): ?array {
		$row = Repository::for( 'redirects' )->find( $id );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Normalize a request path to /leading/and/trailing/.
	 *
	 * @param string $raw Path or URL.
	 */
	public static function path( string $raw ): string {
		$parsed = wp_parse_url( $raw, PHP_URL_PATH );
		$path   = is_string( $parsed ) && '' !== $parsed ? $parsed : $raw;
		$path   = rawurldecode( $path );
		$path   = '/' . trim( $path, '/' );
		if ( '/' === $path ) {
			return '/';
		}
		return trailingslashit( $path );
	}

	/**
	 * Drop the cached prefix and regex rules.
	 */
	public static function bust(): void {
		delete_transient( 'lr_redirect_extra' );
	}

	/**
	 * First matching active rule.
	 *
	 * @param string $path Normalized path.
	 * @return array<string, mixed>|null
	 */
	private static function match( string $path ): ?array {
		$row = Repository::for( 'redirects' )->find_by( 'source_hash', sha1( $path ) );
		if ( $row && self::usable( $row ) && 'exact' === $row['match_type'] ) {
			return $row;
		}
		foreach ( self::extra() as $rule ) {
			if ( 'prefix' === $rule['match_type'] && str_starts_with( $path, (string) $rule['source_path'] ) ) {
				return $rule;
			}
			if ( 'regex' === $rule['match_type'] ) {
				$pattern = (string) $rule['source_path'];
				$ok      = @preg_match( $pattern, $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( 1 === $ok ) {
					return $rule;
				}
			}
		}
		return null;
	}

	/**
	 * Prefix and regex rules, longest prefix first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function extra(): array {
		$cached = get_transient( 'lr_redirect_extra' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_redirects';
		$rows  = $wpdb->get_results(
			"SELECT * FROM `{$table}` WHERE deleted_at IS NULL AND is_active = 1 AND match_type IN ('prefix','regex') ORDER BY CHAR_LENGTH(source_path) DESC LIMIT 100",
			ARRAY_A
		);
		$rows  = is_array( $rows ) ? $rows : array();
		set_transient( 'lr_redirect_extra', $rows, 10 * MINUTE_IN_SECONDS );
		return $rows;
	}

	/**
	 * Whether the row should run.
	 *
	 * @param array<string, mixed> $row Row.
	 */
	private static function usable( array $row ): bool {
		return empty( $row['deleted_at'] ) && ! empty( $row['is_active'] );
	}

	/**
	 * Keep a relative path slashed, or an absolute URL.
	 *
	 * @param string $target Target.
	 */
	private static function target( string $target ): string {
		$target = trim( $target );
		if ( self::external( $target ) ) {
			return esc_url_raw( $target );
		}
		return self::path( $target );
	}

	/**
	 * Absolute http(s) target.
	 *
	 * @param string $target Target.
	 */
	private static function external( string $target ): bool {
		return (bool) preg_match( '#^https?://#i', $target );
	}

	/**
	 * Allowed status.
	 *
	 * @param int $code Code.
	 */
	private static function code( int $code ): int {
		return in_array( $code, array( 301, 302, 410 ), true ) ? $code : 301;
	}

	/**
	 * Allowed match.
	 *
	 * @param string $type Type.
	 */
	private static function match_type( string $type ): string {
		return in_array( $type, array( 'exact', 'prefix', 'regex' ), true ) ? $type : 'exact';
	}

	/**
	 * Allowed origin.
	 *
	 * @param string $origin Origin.
	 */
	private static function origin( string $origin ): string {
		return in_array( $origin, array( 'manual', 'slug_change', 'import', 'migration' ), true ) ? $origin : 'manual';
	}
}
