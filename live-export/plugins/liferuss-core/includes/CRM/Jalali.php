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
		$label = self::plain( $utc );
		if ( '—' === $label ) {
			return '—';
		}
		$stamp = strtotime( trim( $utc ) . ' UTC' );
		if ( ! $stamp ) {
			return esc_html( $utc );
		}
		$tip = gmdate( 'Y-m-d H:i', $stamp ) . ' UTC';
		return '<time datetime="' . esc_attr( gmdate( 'c', $stamp ) ) . '" title="' . esc_attr( $tip ) . '">' . esc_html( $label ) . '</time>';
	}

	/**
	 * Plain Jalali label for text that will be escaped by the caller.
	 *
	 * @param string $utc MySQL datetime in UTC.
	 */
	public static function plain( string $utc ): string {
		$utc = trim( $utc );
		if ( '' === $utc || '0000-00-00 00:00:00' === $utc ) {
			return '—';
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

	/**
	 * Jalali year/month/day to a Gregorian date.
	 *
	 * @param int $jy Year.
	 * @param int $jm Month.
	 * @param int $jd Day.
	 * @return array{0: int, 1: int, 2: int}
	 */
	public static function to_gregorian( int $jy, int $jm, int $jd ): array {
		$jy   += 1595;
		$days  = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd;
		$days += ( $jm < 7 ) ? ( ( $jm - 1 ) * 31 ) : ( ( ( $jm - 7 ) * 30 ) + 186 );
		$gy    = 400 * (int) ( $days / 146097 );
		$days %= 146097;
		if ( $days > 36524 ) {
			--$days;
			$gy   += 100 * (int) ( $days / 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				++$days;
			}
		}
		$gy   += 4 * (int) ( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			--$days;
			$gy   += (int) ( $days / 365 );
			$days %= 365;
		}
		$gd    = $days + 1;
		$leap  = ( 0 === $gy % 4 && 0 !== $gy % 100 ) || 0 === $gy % 400;
		$mdays = array( 0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		$gm    = 1;
		while ( $gm < 13 && $gd > $mdays[ $gm ] ) {
			$gd -= $mdays[ $gm ];
			++$gm;
		}
		return array( $gy, $gm, $gd );
	}

	/**
	 * A Jalali or Gregorian day as a UTC datetime for lead filters.
	 *
	 * Jalali input is a Tehran calendar day. A year of 1700 or more is Gregorian UTC.
	 *
	 * @param string $input Date text.
	 * @param bool   $end   End of the day.
	 */
	public static function filter_utc( string $input, bool $end ): string {
		$input = strtr(
			$input,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
			)
		);
		$input = str_replace( '-', '/', trim( $input ) );
		if ( ! preg_match( '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $input, $match ) ) {
			return '';
		}
		$year  = (int) $match[1];
		$month = (int) $match[2];
		$day   = (int) $match[3];
		if ( $month < 1 || $month > 12 || $day < 1 || $day > 31 ) {
			return '';
		}
		if ( $year >= 1700 ) {
			$day_text = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			return $end ? $day_text . ' 23:59:59' : $day_text . ' 00:00:00';
		}
		if ( $year < 1200 ) {
			return '';
		}
		$gregorian = self::to_gregorian( $year, $month, $day );
		$local     = gmmktime( 0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0] );
		if ( false === $local ) {
			return '';
		}
		$start = $local - (int) ( 3.5 * HOUR_IN_SECONDS );
		if ( $end ) {
			$start += DAY_IN_SECONDS - 1;
		}
		return gmdate( 'Y-m-d H:i:s', $start );
	}

	/**
	 * Tehran-local Jalali parts for today plus a day offset.
	 *
	 * @param int $day_offset Days from today. Negative is earlier.
	 * @return array{0: int, 1: int, 2: int}
	 */
	public static function tehran_parts( int $day_offset = 0 ): array {
		$local = time() + (int) ( 3.5 * HOUR_IN_SECONDS ) + ( $day_offset * DAY_IN_SECONDS );
		return self::to_jalali( (int) gmdate( 'Y', $local ), (int) gmdate( 'n', $local ), (int) gmdate( 'j', $local ) );
	}

	/**
	 * ASCII Jalali date.
	 *
	 * @param array{0: int, 1: int, 2: int} $parts Year, month, day.
	 */
	public static function ymd( array $parts ): string {
		return sprintf( '%04d/%02d/%02d', $parts[0], $parts[1], $parts[2] );
	}

	/**
	 * Western digits to Persian digits.
	 *
	 * @param string $ascii Digits.
	 */
	public static function fa_digits( string $ascii ): string {
		return strtr(
			$ascii,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}

	/**
	 * Dashboard presets in the current Jalali year.
	 *
	 * @return array<string, array{label: string, from: string, to: string}>
	 */
	public static function presets(): array {
		$today = self::tehran_parts( 0 );
		$to    = self::ymd( $today );
		return array(
			'today' => array(
				'label' => 'امروز',
				'from'  => $to,
				'to'    => $to,
			),
			'7'     => array(
				'label' => '۷ روز',
				'from'  => self::ymd( self::tehran_parts( -6 ) ),
				'to'    => $to,
			),
			'30'    => array(
				'label' => "\u{200F}۳۰ روز",
				'from'  => self::ymd( self::tehran_parts( -29 ) ),
				'to'    => $to,
			),
			'month' => array(
				'label' => 'این ماه',
				'from'  => sprintf( '%04d/%02d/01', $today[0], $today[1] ),
				'to'    => $to,
			),
			'year'  => array(
				'label' => 'امسال',
				'from'  => sprintf( '%04d/01/01', $today[0] ),
				'to'    => $to,
			),
		);
	}

	/**
	 * Placeholder pair for the current Jalali year, in Persian digits.
	 *
	 * @return array{0: string, 1: string}
	 */
	public static function year_hint(): array {
		$year  = self::tehran_parts( 0 )[0];
		$probe = self::to_gregorian( $year, 12, 30 );
		$back  = self::to_jalali( $probe[0], $probe[1], $probe[2] );
		$last  = ( $back[0] === $year && 12 === $back[1] ) ? 30 : 29;
		return array(
			self::fa_digits( sprintf( '%04d/01/01', $year ) ),
			self::fa_digits( sprintf( '%04d/12/%02d', $year, $last ) ),
		);
	}
}
