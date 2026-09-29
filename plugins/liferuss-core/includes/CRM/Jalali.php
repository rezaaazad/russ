<?php
/**
 * Jalali display for UTC timestamps.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats a UTC datetime as Jalali in Asia/Tehran, with a Gregorian tooltip.
 */
class Jalali {

	/**
	 * HTML time element. Empty input returns an em dash.
	 *
	 * @param string $utc MySQL datetime in UTC.
	 */
	public static function html( string $utc ): string {
		$utc = trim( $utc );
		if ( '' === $utc || '0000-00-00 00:00:00' === $utc ) {
			return '—';
		}
		$stamp = strtotime( $utc . ' UTC' );
		if ( ! $stamp ) {
			return esc_html( $utc );
		}
		$local = $stamp + (int) ( 3.5 * HOUR_IN_SECONDS );
		$parts = self::to_jalali( (int) gmdate( 'Y', $local ), (int) gmdate( 'n', $local ), (int) gmdate( 'j', $local ) );
		$label = sprintf( '%04d/%02d/%02d %s', $parts[0], $parts[1], $parts[2], gmdate( 'H:i', $local ) );
		$tip   = gmdate( 'Y-m-d H:i', $stamp ) . ' UTC';
		return '<time datetime="' . esc_attr( gmdate( 'c', $stamp ) ) . '" title="' . esc_attr( $tip ) . '">' . esc_html( $label ) . '</time>';
	}

	/**
	 * Plain Jalali string for CSV.
	 *
	 * @param string $utc MySQL datetime in UTC.
	 */
	public static function text( string $utc ): string {
		$utc = trim( $utc );
		if ( '' === $utc ) {
			return '';
		}
		$stamp = strtotime( $utc . ' UTC' );
		if ( ! $stamp ) {
			return $utc;
		}
		$local = $stamp + (int) ( 3.5 * HOUR_IN_SECONDS );
		$parts = self::to_jalali( (int) gmdate( 'Y', $local ), (int) gmdate( 'n', $local ), (int) gmdate( 'j', $local ) );
		return sprintf( '%04d/%02d/%02d %s', $parts[0], $parts[1], $parts[2], gmdate( 'H:i', $local ) );
	}

	/**
	 * UTC timestamp for the start of today or this week in Asia/Tehran.
	 *
	 * The Iranian week starts on Saturday.
	 *
	 * @param string $which today or week.
	 */
	public static function period_start_utc( string $which ): string {
		$offset = (int) ( 3.5 * HOUR_IN_SECONDS );
		$local  = time() + $offset;
		$back   = 0;
		if ( 'week' === $which ) {
			$weekday = (int) gmdate( 'w', $local );
			$back    = ( $weekday + 1 ) % 7;
		}
		$midnight = strtotime( gmdate( 'Y-m-d', $local ) . ' 00:00:00 UTC' );
		if ( ! $midnight ) {
			return gmdate( 'Y-m-d H:i:s', time() - $offset );
		}
		return gmdate( 'Y-m-d H:i:s', $midnight - ( $back * DAY_IN_SECONDS ) - $offset );
	}

	/**
	 * Gregorian Y-m-d to a Jalali year/month/day.
	 *
	 * @param int $gy Year.
	 * @param int $gm Month.
	 * @param int $gd Day.
	 * @return array{0: int, 1: int, 2: int}
	 */
	public static function to_jalali( int $gy, int $gm, int $gd ): array {
		$g_days = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2    = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days   = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_days[ $gm - 1 ];
		$jy     = -1595 + ( 33 * (int) ( $days / 12053 ) );
		$days  %= 12053;
		$jy    += 4 * (int) ( $days / 1461 );
		$days  %= 1461;
		if ( $days > 365 ) {
			$jy += (int) ( ( $days - 1 ) / 365 );
			--$days;
			$days %= 365;
		}
		if ( $days < 186 ) {
			$jm = 1 + (int) ( $days / 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + (int) ( ( $days - 186 ) / 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}
		return array( $jy, $jm, $jd );
	}
}
