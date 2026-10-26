<?php
/**
 * SMS delivery. The stub records the message; a real provider can replace it.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Account;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One method so registration does not depend on a vendor SDK.
 */
class Sms {

	/**
	 * Send a text. The stub always succeeds and keeps the last few messages.
	 *
	 * @param string $phone Recipient.
	 * @param string $text  Message.
	 */
	public static function send( string $phone, string $text ): bool {
		$sent = apply_filters( 'liferuss_sms_send', null, $phone, $text );
		if ( is_bool( $sent ) ) {
			return $sent;
		}
		$provider = (string) Settings::get( 'account' )['sms_provider'];
		if ( 'stub' !== $provider ) {
			return false;
		}
		$log   = get_option( 'lr_sms_log', array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'phone' => $phone,
			'text'  => $text,
			'at'    => gmdate( 'Y-m-d H:i:s' ),
		);
		update_option( 'lr_sms_log', array_slice( $log, -20 ), false );
		return true;
	}
}
