<?php
/**
 * RFC 6238 TOTP (SHA-1, 30 seconds, 6 digits).
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authenticator-app codes. No third-party library.
 */
class Totp {

	private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * New Base32 secret (160 bits).
	 */
	public static function secret(): string {
		return self::encode( random_bytes( 20 ) );
	}

	/**
	 * Authenticator URI (otpauth) for an app.
	 *
	 * @param string $secret Base32 secret.
	 * @param string $label  Account label.
	 */
	public static function uri( string $secret, string $label ): string {
		$issuer = rawurlencode( 'LifeRuss' );
		$label  = rawurlencode( $label );
		return 'otpauth://totp/' . $issuer . ':' . $label . '?secret=' . rawurlencode( $secret ) . '&issuer=' . $issuer . '&period=30&digits=6';
	}

	/**
	 * Whether the code matches the current window, plus or minus one step.
	 *
	 * @param string $secret Base32 secret.
	 * @param string $code   Six digits.
	 */
	public static function verify( string $secret, string $code ): bool {
		$code = preg_replace( '/\s+/', '', $code );
		if ( ! is_string( $code ) || ! preg_match( '/^\d{6}$/', $code ) ) {
			return false;
		}
		$slice = (int) floor( time() / 30 );
		for ( $i = -1; $i <= 1; $i++ ) {
			if ( hash_equals( self::code_at( $secret, $slice + $i ), $code ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Code for one time slice. Used by tests and by verify().
	 *
	 * @param string $secret Base32 secret.
	 * @param int    $slice  Counter.
	 */
	public static function code_at( string $secret, int $slice ): string {
		$key      = self::decode( $secret );
		$binary   = pack( 'N*', 0 ) . pack( 'N*', $slice );
		$hash     = hash_hmac( 'sha1', $binary, $key, true );
		$offset   = ord( substr( $hash, -1 ) ) & 0x0f;
		$piece    = substr( $hash, $offset, 4 );
		$unpacked = unpack( 'N', $piece );
		$trunc    = ( is_array( $unpacked ) ? (int) $unpacked[1] : 0 ) & 0x7fffffff;
		return str_pad( (string) ( $trunc % 1000000 ), 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Base32-encode bytes without padding.
	 *
	 * @param string $raw Bytes.
	 */
	private static function encode( string $raw ): string {
		$bits   = '';
		$length = strlen( $raw );
		for ( $i = 0; $i < $length; $i++ ) {
			$bits .= str_pad( decbin( ord( $raw[ $i ] ) ), 8, '0', STR_PAD_LEFT );
		}
		$out = '';
		foreach ( str_split( $bits, 5 ) as $chunk ) {
			if ( strlen( $chunk ) < 5 ) {
				$chunk = str_pad( $chunk, 5, '0', STR_PAD_RIGHT );
			}
			$out .= self::ALPHABET[ bindec( $chunk ) ];
		}
		return $out;
	}

	/**
	 * Base32-decode a secret.
	 *
	 * @param string $secret Secret.
	 */
	private static function decode( string $secret ): string {
		$secret = strtoupper( preg_replace( '/[^A-Z2-7]/', '', $secret ) );
		$bits   = '';
		$length = strlen( $secret );
		for ( $i = 0; $i < $length; $i++ ) {
			$pos = strpos( self::ALPHABET, $secret[ $i ] );
			if ( false === $pos ) {
				continue;
			}
			$bits .= str_pad( decbin( $pos ), 5, '0', STR_PAD_LEFT );
		}
		$raw = '';
		foreach ( str_split( $bits, 8 ) as $chunk ) {
			if ( 8 !== strlen( $chunk ) ) {
				continue;
			}
			$raw .= chr( bindec( $chunk ) );
		}
		return $raw;
	}
}
