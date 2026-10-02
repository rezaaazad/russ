<?php
/**
 * Shared query normalization for Meilisearch and MySQL.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persian letters, half-space, and case folding.
 */
class Text {

	/**
	 * Fold a string so Arabic and Persian variants match.
	 *
	 * @param string $value Raw text.
	 */
	public static function normalize( string $value ): string {
		$value = str_replace(
			array( "\u{200C}", "\u{200D}", "\u{200B}", "\u{FEFF}" ),
			'',
			$value
		);
		$value = str_replace( "\u{00A0}", ' ', $value );
		$value = strtr(
			$value,
			array(
				'ي' => 'ی',
				'ى' => 'ی',
				'ك' => 'ک',
				'ة' => 'ه',
				'ۀ' => 'ه',
			)
		);
		$value = mb_strtolower( $value, 'UTF-8' );
		$value = preg_replace( '/\s+/u', ' ', $value );
		return trim( (string) $value );
	}

	/**
	 * Normalized text with spaces removed, for half-space and spaced variants.
	 *
	 * @param string $value Raw text.
	 */
	public static function compact( string $value ): string {
		return str_replace( ' ', '', self::normalize( $value ) );
	}

	/**
	 * Both forms, for the stored search document.
	 *
	 * @param string $value Raw text.
	 */
	public static function index_text( string $value ): string {
		$spaced  = self::normalize( $value );
		$compact = str_replace( ' ', '', $spaced );
		if ( '' === $compact || $compact === $spaced ) {
			return $spaced;
		}
		return $spaced . ' ' . $compact;
	}
}
