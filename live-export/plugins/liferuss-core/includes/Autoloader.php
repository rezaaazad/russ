<?php
/**
 * PSR-4 autoloader for LifeRuss\Core.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps LifeRuss\Core classes onto includes/.
 */
class Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file when it belongs to this plugin.
	 *
	 * @param string $class_name Class name.
	 */
	public static function load( string $class_name ): void {
		$prefix = 'LifeRuss\\Core\\';
		if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
