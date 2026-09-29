<?php
/**
 * Query scope for consultants and operators.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the permission matrix into repository filters.
 */
class Access {

	/**
	 * Lead list scope.
	 *
	 * Administrators with lr_manage_leads see every lead. Consultants and
	 * operators only see leads assigned to themselves.
	 *
	 * @return array{mode: string, consultant_id?: int}
	 */
	public static function lead_scope(): array {
		if ( current_user_can( 'lr_manage_leads' ) ) {
			return array( 'mode' => 'all' );
		}
		if ( current_user_can( 'lr_view_own_leads' ) ) {
			return array(
				'mode'          => 'own',
				'consultant_id' => get_current_user_id(),
			);
		}
		return array( 'mode' => 'deny' );
	}

	/**
	 * Admission-request scope.
	 *
	 * @return array{mode: string, consultant_id?: int}
	 */
	public static function admission_scope(): array {
		if ( current_user_can( 'lr_manage_admission_requests' ) ) {
			return array( 'mode' => 'all' );
		}
		if ( current_user_can( 'lr_view_own_admission_requests' ) ) {
			return array(
				'mode'          => 'own',
				'consultant_id' => get_current_user_id(),
			);
		}
		return array( 'mode' => 'deny' );
	}

	/**
	 * Exchange, cargo, or trade request scope.
	 *
	 * Full managers see every row. Operators see unassigned rows plus rows
	 * assigned to themselves, which is the service queue described in the spec.
	 *
	 * @param string $cap Manage capability for that request type.
	 * @return array{mode: string, operator_id?: int}
	 */
	public static function operator_request_scope( string $cap ): array {
		if ( ! current_user_can( $cap ) ) {
			return array( 'mode' => 'deny' );
		}
		if ( current_user_can( 'lr_manage_leads' ) ) {
			return array( 'mode' => 'all' );
		}
		return array(
			'mode'        => 'queue',
			'operator_id' => get_current_user_id(),
		);
	}

	/**
	 * Merge a scope into repository arguments.
	 *
	 * @param array<string, mixed> $args  Existing arguments.
	 * @param array<string, mixed> $scope Scope from the methods above.
	 * @return array<string, mixed>|null Null when the user may not query.
	 */
	public static function apply( array $args, array $scope ): ?array {
		if ( 'deny' === ( $scope['mode'] ?? 'deny' ) ) {
			return null;
		}
		if ( isset( $scope['consultant_id'] ) && 'own' === $scope['mode'] ) {
			if ( isset( $args['lead_consultant'] ) ) {
				$args['lead_consultant_id'] = (int) $scope['consultant_id'];
			} else {
				$args['consultant_id'] = (int) $scope['consultant_id'];
			}
		}
		if ( isset( $scope['operator_id'] ) && 'queue' === $scope['mode'] ) {
			$args['operator_scope'] = (int) $scope['operator_id'];
		}
		return $args;
	}

	/**
	 * Default service groups a role may be assigned.
	 *
	 * @param string $role Role slug.
	 * @return string[]
	 */
	public static function default_services_for_role( string $role ): array {
		$map = array(
			'lr_consultant'        => array( 'education' ),
			'lr_exchange_operator' => array( 'exchange' ),
			'lr_cargo_operator'    => array( 'cargo' ),
			'lr_trade_operator'    => array( 'trade' ),
		);
		return $map[ $role ] ?? array();
	}

	/**
	 * Whether the current user is the WordPress administrator (Super Admin).
	 *
	 * @param int $user_id User id, 0 for current.
	 */
	public static function is_super( int $user_id = 0 ): bool {
		$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
		if ( ! $user instanceof \WP_User ) {
			return false;
		}
		return in_array( 'administrator', (array) $user->roles, true );
	}
}
