<?php
/**
 * Optional exchange-rate fetch. Manual locks always win.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Currency;

use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron fetch from a custom JSON URL, plus history and the public stamp.
 */
class Rates {

	/**
	 * Register cron, the failure notice, and a one-time lock backfill.
	 */
	public static function hooks(): void {
		add_filter( 'cron_schedules', array( self::class, 'schedules' ) );
		add_action( 'init', array( self::class, 'boot' ), 20 );
		add_action( 'lr_fx_update', array( self::class, 'fetch' ) );
		add_action( 'admin_notices', array( self::class, 'notice' ) );
	}

	/**
	 * Six-hour interval for the settings select.
	 *
	 * @param array<string, array<string, mixed>> $schedules Schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public static function schedules( array $schedules ): array {
		$schedules['lr_six_hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => 'هر ۶ ساعت',
		);
		return $schedules;
	}

	/**
	 * Backfill locks and keep the cron matched to settings.
	 */
	public static function boot(): void {
		self::backfill_locks();
		self::reschedule();
	}

	/**
	 * Existing typed rates stay locked so the first fetch cannot replace them.
	 */
	public static function backfill_locks(): void {
		if ( get_option( 'lr_fx_locks_backfill' ) ) {
			return;
		}
		$stored = get_option( Settings::option_name( 'currency' ), array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$rates = isset( $stored['rates'] ) && is_array( $stored['rates'] ) ? $stored['rates'] : array();
		foreach ( Settings::CURRENCIES as $code ) {
			if ( ! isset( $rates[ $code ] ) || ! is_array( $rates[ $code ] ) ) {
				continue;
			}
			if ( array_key_exists( 'manual_lock', $rates[ $code ] ) ) {
				continue;
			}
			if ( '' === (string) ( $rates[ $code ]['usd_per_unit'] ?? '' ) ) {
				continue;
			}
			$rates[ $code ]['manual_lock'] = '1';
		}
		$stored['rates'] = $rates;
		update_option( Settings::option_name( 'currency' ), $stored, true );
		update_option( 'lr_fx_locks_backfill', '1', false );
	}

	/**
	 * Schedule or clear the fetch.
	 */
	public static function reschedule(): void {
		$settings = Settings::get( 'currency' );
		$wanted   = self::recurrence( (string) ( $settings['interval'] ?? 'daily' ) );
		$active   = 'json' === (string) ( $settings['provider'] ?? 'manual' ) && '' !== (string) ( $settings['json_url'] ?? '' );
		$current  = wp_get_schedule( 'lr_fx_update' );
		if ( ! $active ) {
			wp_clear_scheduled_hook( 'lr_fx_update' );
			return;
		}
		if ( $current === $wanted ) {
			return;
		}
		wp_clear_scheduled_hook( 'lr_fx_update' );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, $wanted, 'lr_fx_update' );
	}

	/**
	 * Pull JSON and update unlocked pairs. A failed fetch keeps the last good rate.
	 */
	public static function fetch(): void {
		$settings = Settings::get( 'currency' );
		if ( 'json' !== (string) ( $settings['provider'] ?? 'manual' ) ) {
			return;
		}
		$url = (string) ( $settings['json_url'] ?? '' );
		if ( '' === $url ) {
			return;
		}
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
			)
		);
		if ( is_wp_error( $response ) ) {
			self::fail( $response->get_error_message() );
			return;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			self::fail( 'HTTP ' . $code );
			return;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			self::fail( 'JSON نامعتبر' );
			return;
		}

		$problems = array();
		$rub      = self::quote( $data, (string) ( $settings['path_usd_rub'] ?? '' ) );
		if ( null === $rub && '' !== (string) ( $settings['path_usd_rub'] ?? '' ) && '1' !== (string) ( $settings['rates']['RUB']['manual_lock'] ?? '0' ) ) {
			$problems[] = 'USD→RUB';
		} elseif ( null !== $rub && $rub > 0 ) {
			Settings::apply_auto_rate( 'RUB', (string) Settings::normalize_rate( (string) ( 1 / $rub ) ) );
		}

		$irt_path = (string) ( $settings['path_usd_irt'] ?? '' );
		$rub_path = (string) ( $settings['path_rub_irt'] ?? '' );
		$irt      = self::quote( $data, $irt_path );
		$locked   = '1' === (string) ( $settings['rates']['IRR']['manual_lock'] ?? '0' );
		if ( '' !== $irt_path ) {
			if ( null === $irt || $irt <= 0 ) {
				if ( ! $locked ) {
					$problems[] = 'USD→IRT';
				}
			} else {
				Settings::apply_auto_rate( 'IRR', (string) Settings::normalize_rate( (string) ( 1 / $irt ) ) );
			}
		} elseif ( '' !== $rub_path ) {
			$per_rub = self::quote( $data, $rub_path );
			$rub_usd = Settings::usd_per_unit( 'RUB' );
			if ( null === $per_rub || $per_rub <= 0 || null === $rub_usd || $rub_usd <= 0 ) {
				if ( ! $locked ) {
					$problems[] = 'RUB→IRT';
				}
			} else {
				Settings::apply_auto_rate( 'IRR', (string) Settings::normalize_rate( (string) ( $rub_usd / $per_rub ) ) );
			}
		}

		if ( $problems ) {
			self::fail( 'مسیر JSON خوانده نشد: ' . implode( '، ', $problems ) );
			return;
		}

		$fresh                  = Settings::get( 'currency' );
		$fresh['fail_count']    = 0;
		$fresh['last_error']    = '';
		$fresh['last_fetch_at'] = gmdate( 'Y-m-d H:i:s' );
		Settings::update( 'currency', $fresh );
	}

	/**
	 * Admin notice after three failed fetches.
	 */
	public static function notice(): void {
		if ( ! current_user_can( 'lr_manage_settings' ) ) {
			return;
		}
		$settings = Settings::get( 'currency' );
		if ( (int) ( $settings['fail_count'] ?? 0 ) < 3 ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'به‌روزرسانی خودکار نرخ ارز سه بار پشت سر هم ناموفق بود. آخرین نرخ سالم نگه داشته شده است.', 'liferuss-core' );
		$error = (string) ( $settings['last_error'] ?? '' );
		if ( '' !== $error ) {
			echo ' ' . esc_html( $error );
		}
		echo '</p></div>';
	}

	/**
	 * Append one history row.
	 *
	 * @param string $currency Code.
	 * @param string $rate     Normalized usd_per_unit.
	 * @param string $source   manual or auto.
	 */
	public static function history( string $currency, string $rate, string $source ): void {
		if ( '' === $rate ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'lr_rate_history',
			array(
				'currency'     => $currency,
				'usd_per_unit' => $rate,
				'source'       => 'auto' === $source ? 'auto' : 'manual',
				'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Newest timestamp among the tuition currencies, or one code.
	 *
	 * @param string $currency Optional ISO code.
	 */
	public static function updated_at( string $currency = '' ): string {
		$settings = Settings::get( 'currency' );
		$codes    = '' !== $currency ? array( $currency ) : Settings::CURRENCIES;
		$latest   = '';
		foreach ( $codes as $code ) {
			$at = (string) ( $settings['rates'][ $code ]['updated_at'] ?? '' );
			if ( '' !== $at && $at > $latest ) {
				$latest = $at;
			}
		}
		return $latest;
	}

	/**
	 * Escaped stamp for templates.
	 *
	 * @param string $currency Optional ISO code.
	 */
	public static function stamp_html( string $currency = '' ): string {
		$at = self::updated_at( $currency );
		if ( '' === $at ) {
			return '';
		}
		return 'آخرین به‌روزرسانی نرخ: ' . Jalali::html( $at );
	}

	/**
	 * Plain stamp. The caller escapes it.
	 *
	 * @param string $currency Optional ISO code.
	 */
	public static function stamp_text( string $currency = '' ): string {
		$at = self::updated_at( $currency );
		if ( '' === $at ) {
			return '';
		}
		return 'آخرین به‌روزرسانی نرخ: ' . Jalali::plain( $at );
	}

	/**
	 * Public pair lines for the inquiry page. Not a converter.
	 *
	 * @return string[]
	 */
	public static function pair_lines(): array {
		$lines = array();
		$rub   = Settings::usd_per_unit( 'RUB' );
		if ( null !== $rub && $rub > 0 ) {
			$lines[] = '۱ دلار = ' . self::amount( 1 / $rub ) . ' روبل';
		}
		$irr = Settings::usd_per_unit( 'IRR' );
		if ( null !== $irr && $irr > 0 ) {
			$lines[] = '۱ دلار = ' . self::amount( 1 / $irr ) . ' تومان';
		}
		return $lines;
	}

	/**
	 * Map a settings interval onto a cron recurrence.
	 *
	 * @param string $interval hourly, 6h, or daily.
	 */
	private static function recurrence( string $interval ): string {
		if ( 'hourly' === $interval ) {
			return 'hourly';
		}
		if ( '6h' === $interval ) {
			return 'lr_six_hours';
		}
		return 'daily';
	}

	/**
	 * Number at a dot path. Null when the path is empty or missing.
	 *
	 * @param array<string, mixed> $data JSON.
	 * @param string               $path Dot path.
	 */
	private static function quote( array $data, string $path ): ?float {
		$path = trim( $path );
		if ( '' === $path ) {
			return null;
		}
		$node = $data;
		foreach ( explode( '.', $path ) as $part ) {
			if ( ! is_array( $node ) || ! array_key_exists( $part, $node ) ) {
				return null;
			}
			$node = $node[ $part ];
		}
		if ( is_string( $node ) ) {
			$node = str_replace( ',', '', $node );
		}
		return is_numeric( $node ) ? (float) $node : null;
	}

	/**
	 * Remember a failure and leave stored rates unchanged.
	 *
	 * @param string $message Short reason.
	 */
	private static function fail( string $message ): void {
		$current                  = Settings::get( 'currency' );
		$current['fail_count']    = (int) ( $current['fail_count'] ?? 0 ) + 1;
		$current['last_error']    = substr( $message, 0, 180 );
		$current['last_fetch_at'] = gmdate( 'Y-m-d H:i:s' );
		Settings::update( 'currency', $current );
	}

	/**
	 * Compact amount for the inquiry page.
	 *
	 * @param float $value Units per one USD.
	 */
	private static function amount( float $value ): string {
		return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );
	}
}
