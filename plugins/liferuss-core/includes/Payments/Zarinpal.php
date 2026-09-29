<?php
/**
 * Zarinpal driver. Amounts are Toman (IRT).
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Payments;

use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zarinpal v4 request and verify.
 */
class Zarinpal implements Gateway {

	/**
	 * Create an authority and return the StartPay URL.
	 *
	 * @param array<string, mixed> $payment Payment row.
	 * @return array{ok: bool, url: string, authority: string, message: string}
	 */
	public function request( array $payment ): array {
		$empty    = array(
			'ok'        => false,
			'url'       => '',
			'authority' => '',
			'message'   => 'درگاه پرداخت تنظیم نشده است.',
		);
		$merchant = $this->merchant();
		if ( '' === $merchant ) {
			return $empty;
		}
		$body = array(
			'merchant_id'  => $merchant,
			'amount'       => (int) $payment['amount_toman'],
			'currency'     => 'IRT',
			'callback_url' => home_url( '/pay/' . $payment['token'] . '/' ),
			'description'  => substr( (string) $payment['description'], 0, 255 ),
			'metadata'     => array(
				'order_id' => (string) $payment['id'],
			),
		);
		$data = $this->post( '/request.json', $body );
		if ( ! $data ) {
			$empty['message'] = 'اتصال به زرین‌پال برقرار نشد.';
			return $empty;
		}
		$code      = (int) ( $data['data']['code'] ?? 0 );
		$authority = (string) ( $data['data']['authority'] ?? '' );
		if ( 100 !== $code || '' === $authority ) {
			$empty['message'] = 'زرین‌پال درخواست را نپذیرفت.';
			return $empty;
		}
		$host = $this->sandbox() ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
		return array(
			'ok'        => true,
			'url'       => $host . rawurlencode( $authority ),
			'authority' => $authority,
			'message'   => '',
		);
	}

	/**
	 * Verify the callback. Code 101 is an already-verified charge.
	 *
	 * @param array<string, mixed> $payment   Payment row.
	 * @param string               $authority Gateway authority.
	 * @param string               $status    Gateway status string.
	 * @return array{ok: bool, ref_id: string, message: string}
	 */
	public function verify( array $payment, string $authority, string $status ): array {
		$fail = array(
			'ok'      => false,
			'ref_id'  => '',
			'message' => 'پرداخت تأیید نشد.',
		);
		if ( 'OK' !== strtoupper( $status ) ) {
			$fail['message'] = 'پرداخت لغو شد.';
			return $fail;
		}
		$known = (string) ( $payment['authority'] ?? '' );
		if ( '' !== $known && ! hash_equals( $known, $authority ) ) {
			return $fail;
		}
		$data = $this->post(
			'/verify.json',
			array(
				'merchant_id' => $this->merchant(),
				'amount'      => (int) $payment['amount_toman'],
				'currency'    => 'IRT',
				'authority'   => $authority,
			)
		);
		if ( ! $data ) {
			$fail['message'] = 'اتصال به زرین‌پال برقرار نشد.';
			return $fail;
		}
		$code = (int) ( $data['data']['code'] ?? 0 );
		if ( 100 !== $code && 101 !== $code ) {
			return $fail;
		}
		return array(
			'ok'      => true,
			'ref_id'  => (string) ( $data['data']['ref_id'] ?? '' ),
			'message' => '',
		);
	}

	/**
	 * Merchant id from settings.
	 */
	private function merchant(): string {
		$settings = Settings::get( 'payments' );
		return trim( (string) ( $settings['merchant_id'] ?? '' ) );
	}

	/**
	 * Sandbox toggle.
	 */
	private function sandbox(): bool {
		$settings = Settings::get( 'payments' );
		return '1' === (string) ( $settings['sandbox'] ?? '1' );
	}

	/**
	 * POST JSON and return the decoded body.
	 *
	 * @param string               $path Path under the v4 root.
	 * @param array<string, mixed> $body Payload.
	 * @return array<string, mixed>|null
	 */
	private function post( string $path, array $body ): ?array {
		$host     = $this->sandbox() ? 'https://sandbox.zarinpal.com/pg/v4/payment' : 'https://api.zarinpal.com/pg/v4/payment';
		$response = wp_remote_post(
			$host . $path,
			array(
				'timeout' => 12,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
