<?php
/**
 * Option groups for the LifeRuss settings screen.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Settings;

use LifeRuss\Core\Repositories\ActivityLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes lr_settings_* options.
 */
class Settings {

	/**
	 * Currencies converted into USD for tuition.
	 *
	 * @var string[]
	 */
	public const CURRENCIES = array( 'RUB', 'IRR', 'EUR' );

	/**
	 * Notification buckets, one per service group.
	 *
	 * @var string[]
	 */
	public const NOTICE_SERVICES = array( 'education', 'language', 'exchange', 'cargo', 'trade', 'contact' );

	/**
	 * Stored value merged over defaults.
	 *
	 * @param string $group Group slug.
	 * @return array<string, mixed>
	 */
	public static function get( string $group ): array {
		$stored = get_option( self::option_name( $group ), array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$value = array_replace_recursive( self::defaults( $group ), $stored );
		if ( 'security' === $group ) {
			$value['delete_data_on_uninstall'] = ( '1' === (string) get_option( 'lr_delete_data_on_uninstall', '0' ) ) ? '1' : '0';
		}
		return $value;
	}

	/**
	 * Persist a group.
	 *
	 * @param string               $group Group slug.
	 * @param array<string, mixed> $value Sanitized value.
	 */
	public static function update( string $group, array $value ): void {
		update_option( self::option_name( $group ), $value, self::autoload( $group ) );
	}

	/**
	 * USD value of one unit of the given currency. Null when unset.
	 *
	 * @param string $currency ISO code.
	 */
	public static function usd_per_unit( string $currency ): ?float {
		if ( 'USD' === $currency ) {
			return 1.0;
		}
		$settings = self::get( 'currency' );
		$raw      = $settings['rates'][ $currency ]['usd_per_unit'] ?? '';
		if ( '' === (string) $raw || ! is_numeric( $raw ) ) {
			return null;
		}
		return (float) $raw;
	}

	/**
	 * Save manual rates. A changed rate rewrites tuition amount_usd.
	 *
	 * @param array<string, string> $incoming Currency code => raw rate.
	 * @return string[] Currencies that changed.
	 */
	public static function save_currency( array $incoming ): array {
		$current = self::get( 'currency' );
		$changed = array();

		foreach ( self::CURRENCIES as $code ) {
			if ( ! array_key_exists( $code, $incoming ) ) {
				continue;
			}
			$normalized = self::normalize_rate( (string) $incoming[ $code ] );
			if ( null === $normalized ) {
				continue;
			}
			$previous = (string) ( $current['rates'][ $code ]['usd_per_unit'] ?? '' );
			if ( $normalized === $previous ) {
				continue;
			}
			$current['rates'][ $code ]['usd_per_unit'] = $normalized;
			$current['rates'][ $code ]['updated_at']   = gmdate( 'Y-m-d H:i:s' );
			$current['rates'][ $code ]['updated_by']   = get_current_user_id();
			$changed[ $code ]                          = $normalized;
		}

		if ( ! $changed ) {
			return array();
		}

		self::update( 'currency', $current );

		foreach ( $changed as $code => $rate ) {
			$rows = '';
			if ( '' !== $rate ) {
				$rows = (string) self::recalculate_tuition( $code, (float) $rate );
			}
			ActivityLog::record(
				'currency.update',
				'settings',
				0,
				sprintf( 'نرخ %s به دلار به‌روز شد.', $code ),
				array(
					'currency'     => $code,
					'usd_per_unit' => $rate,
					'tuition_rows' => $rows,
				)
			);
		}

		return array_keys( $changed );
	}

	/**
	 * Rewrite amount_usd for current tuition rows in one currency.
	 *
	 * @param string $currency      ISO code stored on the fee row.
	 * @param float  $usd_per_unit  Dollars per one unit.
	 */
	public static function recalculate_tuition( string $currency, float $usd_per_unit ): int {
		global $wpdb;

		$table  = $wpdb->prefix . 'lr_tuition_fees';
		$now    = gmdate( 'Y-m-d H:i:s' );
		$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE `{$table}` SET amount_usd = ROUND(amount * %f, 2), fx_rate_at = %s, updated_at = %s WHERE currency = %s AND deleted_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$usd_per_unit,
				$now,
				$now,
				$currency
			)
		);

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Blank string keeps the field empty. Null rejects a bad number.
	 *
	 * @param string $raw Posted value.
	 */
	public static function normalize_rate( string $raw ): ?string {
		$raw = str_replace( ',', '.', trim( $raw ) );
		if ( '' === $raw ) {
			return '';
		}
		if ( ! is_numeric( $raw ) || (float) $raw < 0 ) {
			return null;
		}
		$formatted = rtrim( rtrim( sprintf( '%.8F', (float) $raw ), '0' ), '.' );
		return '' === $formatted ? '0' : $formatted;
	}

	/**
	 * Option name for a group.
	 *
	 * @param string $group Group slug.
	 */
	public static function option_name( string $group ): string {
		return 'lr_settings_' . $group;
	}

	/**
	 * Defaults for one group.
	 *
	 * @param string $group Group slug.
	 * @return array<string, mixed>
	 */
	public static function defaults( string $group ): array {
		$rate  = array(
			'usd_per_unit' => '',
			'updated_at'   => '',
			'updated_by'   => 0,
		);
		$rates = array();
		foreach ( self::CURRENCIES as $code ) {
			$rates[ $code ] = $rate;
		}

		$service  = array(
			'email'   => '',
			'chat_id' => '',
		);
		$services = array();
		foreach ( self::NOTICE_SERVICES as $code ) {
			$services[ $code ] = $service;
		}

		$map = array(
			'general'       => array(
				'brand_name'    => 'لایف‌روس',
				'academic_year' => '',
				'support_email' => '',
			),
			'contact'       => array(
				'phone'     => '',
				'whatsapp'  => '',
				'telegram'  => '',
				'instagram' => '',
				'email'     => '',
				'address'   => '',
			),
			'footer'        => array(
				'about'     => '',
				'copyright' => '',
			),
			'currency'      => array(
				'rates' => $rates,
			),
			'notifications' => array(
				'telegram_enabled' => '0',
				'bot_token'        => '',
				'chat_id'          => '',
				'email_enabled'    => '0',
				'from_email'       => '',
				'from_name'        => 'لایف‌روس',
				'services'         => $services,
			),
			'tracking'      => array(
				'gtm_id'       => '',
				'ga4_id'       => '',
				'head_scripts' => '',
				'body_scripts' => '',
			),
			'forms'         => array(
				'success_message' => 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.',
				'notify_email'    => '',
			),
			'security'      => array(
				'delete_data_on_uninstall' => '0',
			),
			'languages'     => array(
				'default_language' => 'fa',
			),
			'backup'        => array(
				'retention_days' => '30',
			),
		);

		return $map[ $group ] ?? array();
	}

	/**
	 * Large script options stay out of autoload.
	 *
	 * @param string $group Group slug.
	 */
	private static function autoload( string $group ): bool {
		return ! in_array( $group, array( 'tracking', 'backup' ), true );
	}
}
