<?php
/**
 * Mobile call bar. Rendered only when a Russia or UK line is saved.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'liferuss_the_contact_directory' ) ) {
	liferuss_the_contact_directory( 'callbar' );
}
