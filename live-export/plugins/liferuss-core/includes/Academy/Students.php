<?php
/**
 * Academy profile rows linked to WordPress users.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One student row per user. Login stays on the existing OTP account.
 */
class Students {

	/**
	 * Find or create the profile for a user.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>|null
	 */
	public static function ensure( int $user_id ): ?array {
		if ( $user_id < 1 ) {
			return null;
		}
		$found = Db::find_by( 'students', 'user_id', (string) $user_id );
		if ( $found ) {
			return $found;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}
		$phone = (string) get_user_meta( $user_id, 'lr_phone', true );
		$id    = Db::insert(
			'students',
			array(
				'user_id'      => $user_id,
				'phone'        => $phone,
				'display_name' => $user->display_name,
			)
		);
		return $id ? Db::find( 'students', $id ) : null;
	}

	/**
	 * Profile for a user, if they have opened Academy.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>|null
	 */
	public static function by_user( int $user_id ): ?array {
		if ( $user_id < 1 ) {
			return null;
		}
		return Db::find_by( 'students', 'user_id', (string) $user_id );
	}
}
