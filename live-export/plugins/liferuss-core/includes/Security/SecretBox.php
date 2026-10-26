<?php
/**
 * Encrypt short secrets with the WordPress auth salt.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AES-256-CBC sealed values. The key is derived from wp_salt( 'auth' ).
 */
class SecretBox {

	/**
	 * Seal a plaintext secret.
	 *
	 * @param string $plain Secret.
	 */
	public static function seal( string $plain ): string {
		$key = self::key();
		$iv  = random_bytes( 16 );
		$raw = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $raw ) {
			return '';
		}
		return base64_encode( $iv . $raw ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Open a sealed secret. Empty string when the payload is not ours.
	 *
	 * @param string $stored Sealed value.
	 */
	public static function open( string $stored ): string {
		$bin = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! is_string( $bin ) || strlen( $bin ) < 17 ) {
			return '';
		}
		$plain = openssl_decrypt( substr( $bin, 16 ), 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, substr( $bin, 0, 16 ) );
		return is_string( $plain ) ? $plain : '';
	}

	/**
	 * 32-byte key from the auth salt.
	 */
	private static function key(): string {
		return hash( 'sha256', wp_salt( 'auth' ) . '|lr-totp', true );
	}
}
