<?php
/**
 * Persian admin chrome for LifeRuss staff.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Staff who have not chosen a language get fa_IR in wp-admin.
 */
class Locale {


	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_filter( 'determine_locale', array( self::class, 'admin' ) );
		add_action( 'admin_init', array( self::class, 'persist' ) );
	}

	/**
	 * Store fa_IR once so the profile and body class match the admin UI.
	 */
	public static function persist(): void {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! self::staff( $user_id ) ) {
			return;
		}
		$chosen = get_user_meta( $user_id, 'locale', true );
		if ( is_string( $chosen ) && '' !== $chosen ) {
			return;
		}
		update_user_meta( $user_id, 'locale', 'fa_IR' );
	}

	/**
	 * Default the admin language to Persian for staff roles.
	 *
	 * @param string $locale Resolved locale.
	 */
	public static function admin( string $locale ): string {
		if ( ! is_admin() ) {
			return $locale;
		}
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return $locale;
		}
		$chosen = get_user_meta( $user_id, 'locale', true );
		if ( is_string( $chosen ) && '' !== $chosen ) {
			return $locale;
		}
		if ( self::staff( $user_id ) ) {
			return 'fa_IR';
		}
		return $locale;
	}

	/**
	 * Administrator and LifeRuss staff roles.
	 *
	 * @param int $user_id User id.
	 */
	private static function staff( int $user_id ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		foreach ( (array) $user->roles as $role ) {
			if ( 'administrator' === $role || str_starts_with( (string) $role, 'lr_' ) ) {
				return true;
			}
		}
		return false;
	}
}
