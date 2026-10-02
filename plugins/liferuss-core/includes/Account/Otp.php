<?php
/**
 * One-time codes for client registration and login.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Account;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email delivery, with SMS behind Sms::send().
 */
class Otp {

	/**
	 * Issue a six-digit code. The return value is for trusted callers; pages must not print it.
	 *
	 * @param string $channel email or phone.
	 * @param string $target  Address or phone.
	 */
	public static function issue( string $channel, string $target ): string {
		$code = (string) wp_rand( 100000, 999999 );
		set_transient( self::key( $channel, $target ), wp_hash_password( $code ), 10 * MINUTE_IN_SECONDS );
		$text = 'کد ورود لایف‌روس: ' . $code;
		if ( 'phone' === $channel ) {
			Sms::send( $target, $text );
		} else {
			wp_mail( $target, 'کد ورود لایف‌روس', $text );
		}
		return $code;
	}

	/**
	 * Check a code once. A match deletes it.
	 *
	 * @param string $channel email or phone.
	 * @param string $target  Address or phone.
	 * @param string $code    Digits.
	 */
	public static function check( string $channel, string $target, string $code ): bool {
		$key  = self::key( $channel, $target );
		$hash = get_transient( $key );
		if ( ! is_string( $hash ) || '' === $hash ) {
			return false;
		}
		if ( ! wp_check_password( preg_replace( '/\D/', '', $code ), $hash ) ) {
			return false;
		}
		delete_transient( $key );
		return true;
	}

	/**
	 * Transient name.
	 *
	 * @param string $channel Channel.
	 * @param string $target  Target.
	 */
	private static function key( string $channel, string $target ): string {
		return 'lr_otp_' . md5( $channel . '|' . strtolower( trim( $target ) ) );
	}
}
