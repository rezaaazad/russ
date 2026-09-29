<?php
/**
 * Idempotent role setup.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates lr_* roles and grants caps to administrator.
 */
class RoleRegistrar {

	/**
	 * Sync when the capability hash changes.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe_sync' ), 5 );
	}

	/**
	 * Sync once per hash.
	 */
	public static function maybe_sync(): void {
		if ( get_option( 'lr_roles_hash' ) === RoleCatalog::hash() ) {
			return;
		}
		self::sync();
	}

	/**
	 * Apply the matrix. Safe to run more than once.
	 */
	public static function sync(): void {
		foreach ( RoleCatalog::roles() as $slug => $def ) {
			if ( 'administrator' === $slug ) {
				self::grant_administrator( $def['caps'] );
				continue;
			}
			remove_role( $slug );
			add_role( $slug, $def['label'], $def['caps'] );
		}
		update_option( 'lr_roles_hash', RoleCatalog::hash(), false );
	}

	/**
	 * Add LifeRuss caps to the existing administrator role without removing core caps.
	 *
	 * @param array<string, bool> $caps Desired caps.
	 */
	private static function grant_administrator( array $caps ): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		foreach ( $caps as $cap => $grant ) {
			if ( $grant && ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}
