<?php
/**
 * Payment gateway contract. IDPay and Zibal can replace the driver.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start and verify one invoice.
 */
interface Gateway {

	/**
	 * Ask the gateway for a redirect URL.
	 *
	 * @param array<string, mixed> $payment Payment row.
	 * @return array{ok: bool, url: string, authority: string, message: string}
	 */
	public function request( array $payment ): array;

	/**
	 * Confirm a callback. ok is true for a first success or an already-verified charge.
	 *
	 * @param array<string, mixed> $payment   Payment row.
	 * @param string               $authority Gateway authority.
	 * @param string               $status    Gateway status string.
	 * @return array{ok: bool, ref_id: string, message: string}
	 */
	public function verify( array $payment, string $authority, string $status ): array;
}
