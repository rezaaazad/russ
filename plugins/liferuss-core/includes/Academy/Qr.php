<?php
/**
 * QR symbol for a certificate URL.
 *
 * Byte mode, error correction L, versions 1 to 6. Mask 0.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Draws a verification QR as SVG. The public code remains readable beside it.
 */
class Qr {

	/**
	 * SVG markup for a URL.
	 *
	 * @param string $text Payload.
	 */
	public static function svg( string $text ): string {
		$bits = self::matrix( $text );
		if ( ! $bits ) {
			return '';
		}
		$size  = count( $bits );
		$cell  = 4;
		$dim   = ( $size + 8 ) * $cell;
		$rects = '';
		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				if ( ! empty( $bits[ $y ][ $x ] ) ) {
					$rects .= '<rect x="' . ( ( $x + 4 ) * $cell ) . '" y="' . ( ( $y + 4 ) * $cell ) . '" width="' . $cell . '" height="' . $cell . '"/>';
				}
			}
		}
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim . '" width="168" height="168" role="img" aria-label="QR"><rect width="100%" height="100%" fill="#fff"/>' . $rects . '</svg>';
	}

	/**
	 * Module matrix, or an empty array when the text does not fit.
	 *
	 * @param string $text Payload.
	 * @return array<int, array<int, int>>
	 */
	private static function matrix( string $text ): array {
		$unpacked = unpack( 'C*', $text );
		$bytes    = is_array( $unpacked ) ? array_values( $unpacked ) : array();
		$length   = count( $bytes );
		$version  = 0;
		$caps     = array(
			1 => 17,
			2 => 32,
			3 => 53,
			4 => 78,
			5 => 106,
			6 => 134,
		);
		foreach ( $caps as $ver => $cap ) {
			if ( $length <= $cap ) {
				$version = $ver;
				break;
			}
		}
		if ( 0 === $version ) {
			return array();
		}
		$data  = array( 0x40 | ( $length >> 4 ), ( ( $length & 0x0f ) << 4 ) );
		$carry = $length & 0x0f;
		$shift = 4;
		foreach ( $bytes as $byte ) {
			$data[ count( $data ) - 1 ] |= ( $byte >> ( 8 - $shift ) );
			$data[]                      = ( $byte << $shift ) & 0xff;
			unset( $carry );
		}
		$capacity = self::data_codewords( $version );
		$filled   = count( $data );
		while ( $filled < $capacity ) {
			$data[] = 0xec;
			++$filled;
			if ( $filled < $capacity ) {
				$data[] = 0x11;
				++$filled;
			}
		}
		$data = array_slice( $data, 0, $capacity );
		$full = self::assemble( $data, $version );
		$size = 17 + ( 4 * $version );
		$grid = array_fill( 0, $size, array_fill( 0, $size, 0 ) );
		$set  = array_fill( 0, $size, array_fill( 0, $size, false ) );
		self::finder( $grid, $set, 0, 0 );
		self::finder( $grid, $set, $size - 7, 0 );
		self::finder( $grid, $set, 0, $size - 7 );
		self::timing( $grid, $set, $size );
		self::alignments( $grid, $set, $version );
		self::reserve_format( $grid, $set, $size );
		self::place( $grid, $set, $full, $size );
		self::mask( $grid, $set, $size );
		self::format( $grid, $size );
		return $grid;
	}

	/**
	 * Data codewords plus their error blocks. Version 6 uses two interleaved blocks.
	 *
	 * @param int[] $data    Data codewords.
	 * @param int   $version Version.
	 * @return int[]
	 */
	private static function assemble( array $data, int $version ): array {
		if ( 6 !== $version ) {
			return array_merge( $data, self::reed_solomon( $data, self::ecc_length( $version ) ) );
		}
		$left  = array_slice( $data, 0, 68 );
		$right = array_slice( $data, 68, 68 );
		$ecc_l = self::reed_solomon( $left, 18 );
		$ecc_r = self::reed_solomon( $right, 18 );
		$out   = array();
		for ( $i = 0; $i < 68; $i++ ) {
			$out[] = $left[ $i ];
			$out[] = $right[ $i ];
		}
		for ( $i = 0; $i < 18; $i++ ) {
			$out[] = $ecc_l[ $i ];
			$out[] = $ecc_r[ $i ];
		}
		return $out;
	}

	/**
	 * Data codeword counts for ECC level L, versions 1–6.
	 *
	 * @param int $version Version.
	 */
	private static function data_codewords( int $version ): int {
		$map = array(
			1 => 19,
			2 => 34,
			3 => 55,
			4 => 80,
			5 => 108,
			6 => 136,
		);
		return $map[ $version ] ?? 19;
	}

	/**
	 * ECC codeword counts for ECC level L, versions 1–6.
	 *
	 * @param int $version Version.
	 */
	private static function ecc_length( int $version ): int {
		$map = array(
			1 => 7,
			2 => 10,
			3 => 15,
			4 => 20,
			5 => 26,
			6 => 36,
		);
		return $map[ $version ] ?? 7;
	}

	/**
	 * Reed-Solomon remainder over GF(256).
	 *
	 * @param int[] $data Data codewords.
	 * @param int   $ecc  ECC length.
	 * @return int[]
	 */
	private static function reed_solomon( array $data, int $ecc ): array {
		$gen   = self::generator( $ecc );
		$block = array_merge( $data, array_fill( 0, $ecc, 0 ) );
		$last  = count( $data );
		for ( $k = 0; $k < $last; $k++ ) {
			$coef = $block[ $k ];
			if ( 0 === $coef ) {
				continue;
			}
			$log = self::log( $coef );
			for ( $n = 0; $n < $ecc; $n++ ) {
				$block[ $k + $n + 1 ] ^= self::exp( $log + $gen[ $n ] );
			}
		}
		return array_slice( $block, $last );
	}

	/**
	 * Generator polynomial for one ECC length, as log coefficients, high degree first.
	 *
	 * @param int $ecc ECC length.
	 * @return int[]
	 */
	private static function generator( int $ecc ): array {
		static $cache = array();
		if ( isset( $cache[ $ecc ] ) ) {
			return $cache[ $ecc ];
		}
		$poly = array( 1 );
		for ( $i = 0; $i < $ecc; $i++ ) {
			$next = array_fill( 0, count( $poly ) + 1, 0 );
			foreach ( $poly as $j => $coef ) {
				$next[ $j ]     ^= self::mul( $coef, self::exp( $i ) );
				$next[ $j + 1 ] ^= $coef;
			}
			$poly = $next;
		}
		$high       = array_reverse( $poly );
		$logs       = array();
		$high_count = count( $high );
		for ( $j = 1; $j < $high_count; $j++ ) {
			$logs[] = self::log( $high[ $j ] );
		}
		$cache[ $ecc ] = $logs;
		return $logs;
	}

	/**
	 * GF multiply.
	 *
	 * @param int $left  Left.
	 * @param int $right Right.
	 */
	private static function mul( int $left, int $right ): int {
		if ( 0 === $left || 0 === $right ) {
			return 0;
		}
		return self::exp( ( self::log( $left ) + self::log( $right ) ) % 255 );
	}

	/**
	 * Exp table.
	 *
	 * @param int $n Exponent.
	 */
	private static function exp( int $n ): int {
		static $table = null;
		if ( null === $table ) {
			$table = array();
			$x     = 1;
			for ( $i = 0; $i < 512; $i++ ) {
				$table[ $i ] = $x;
				$x         <<= 1;
				if ( $x & 0x100 ) {
					$x ^= 0x11d;
				}
			}
		}
		return $table[ $n % 255 ];
	}

	/**
	 * Log table.
	 *
	 * @param int $n Value.
	 */
	private static function log( int $n ): int {
		static $table = null;
		if ( null === $table ) {
			$table = array();
			for ( $i = 0; $i < 255; $i++ ) {
				$table[ self::exp( $i ) ] = $i;
			}
		}
		return $table[ $n ] ?? 0;
	}

	/**
	 * Draw one finder.
	 *
	 * @param array<int, array<int, int>>  $grid Grid.
	 * @param array<int, array<int, bool>> $set  Reserved.
	 * @param int                          $x    Left.
	 * @param int                          $y    Top.
	 */
	private static function finder( array &$grid, array &$set, int $x, int $y ): void {
		for ( $dy = -1; $dy <= 7; $dy++ ) {
			for ( $dx = -1; $dx <= 7; $dx++ ) {
				$xx    = $x + $dx;
				$yy    = $y + $dy;
				$limit = count( $grid );
				if ( $xx < 0 || $yy < 0 || $yy >= $limit || $xx >= $limit ) {
					continue;
				}
				$edge               = ( 0 === $dx || 6 === $dx || 0 === $dy || 6 === $dy );
				$dark               = ( $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6 && ( $edge || ( $dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4 ) ) );
				$grid[ $yy ][ $xx ] = $dark ? 1 : 0;
				$set[ $yy ][ $xx ]  = true;
			}
		}
	}

	/**
	 * Alignment patterns for versions 2–6. Finder overlaps are left alone.
	 *
	 * @param array<int, array<int, int>>  $grid    Grid.
	 * @param array<int, array<int, bool>> $set     Reserved.
	 * @param int                          $version Version.
	 */
	private static function alignments( array &$grid, array &$set, int $version ): void {
		$centers = array(
			2 => array( 6, 18 ),
			3 => array( 6, 22 ),
			4 => array( 6, 26 ),
			5 => array( 6, 30 ),
			6 => array( 6, 34 ),
		);
		if ( ! isset( $centers[ $version ] ) ) {
			return;
		}
		foreach ( $centers[ $version ] as $cy ) {
			foreach ( $centers[ $version ] as $cx ) {
				if ( ! empty( $set[ $cy ][ $cx ] ) ) {
					continue;
				}
				for ( $dy = -2; $dy <= 2; $dy++ ) {
					for ( $dx = -2; $dx <= 2; $dx++ ) {
						$edge                           = ( -2 === $dx || 2 === $dx || -2 === $dy || 2 === $dy );
						$grid[ $cy + $dy ][ $cx + $dx ] = ( $edge || ( 0 === $dx && 0 === $dy ) ) ? 1 : 0;
						$set[ $cy + $dy ][ $cx + $dx ]  = true;
					}
				}
			}
		}
	}

	/**
	 * Timing patterns.
	 *
	 * @param array<int, array<int, int>>  $grid Grid.
	 * @param array<int, array<int, bool>> $set  Reserved.
	 * @param int                          $size Size.
	 */
	private static function timing( array &$grid, array &$set, int $size ): void {
		for ( $i = 8; $i < $size - 8; $i++ ) {
			$bit           = 0 === ( $i % 2 ) ? 1 : 0;
			$grid[6][ $i ] = $bit;
			$grid[ $i ][6] = $bit;
			$set[6][ $i ]  = true;
			$set[ $i ][6]  = true;
		}
	}

	/**
	 * Place data bits, zigzag, skipping reserved modules.
	 *
	 * @param array<int, array<int, int>>  $grid Grid.
	 * @param array<int, array<int, bool>> $set  Reserved.
	 * @param int[]                        $data Codewords.
	 * @param int                          $size Size.
	 */
	private static function place( array &$grid, array &$set, array $data, int $size ): void {
		$bits = '';
		foreach ( $data as $byte ) {
			$bits .= str_pad( decbin( $byte ), 8, '0', STR_PAD_LEFT );
		}
		$i  = 0;
		$up = true;
		for ( $x = $size - 1; $x > 0; $x -= 2 ) {
			if ( 6 === $x ) {
				--$x;
			}
			for ( $step = 0; $step < $size; $step++ ) {
				$y = $up ? ( $size - 1 - $step ) : $step;
				foreach ( array( $x, $x - 1 ) as $xx ) {
					if ( ! empty( $set[ $y ][ $xx ] ) ) {
						continue;
					}
					$grid[ $y ][ $xx ] = isset( $bits[ $i ] ) && '1' === $bits[ $i ] ? 1 : 0;
					++$i;
				}
			}
			$up = ! $up;
		}
	}

	/**
	 * Apply mask 0: (row + column) even.
	 *
	 * @param array<int, array<int, int>>  $grid Grid.
	 * @param array<int, array<int, bool>> $set  Reserved.
	 * @param int                          $size Size.
	 */
	private static function mask( array &$grid, array &$set, int $size ): void {
		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				if ( ! empty( $set[ $y ][ $x ] ) ) {
					continue;
				}
				if ( 0 === ( ( $x + $y ) % 2 ) ) {
					$grid[ $y ][ $x ] ^= 1;
				}
			}
		}
	}

	/**
	 * Keep format modules and the dark module out of the data stream.
	 *
	 * @param array<int, array<int, int>>  $grid Grid.
	 * @param array<int, array<int, bool>> $set  Reserved.
	 * @param int                          $size Size.
	 */
	private static function reserve_format( array &$grid, array &$set, int $size ): void {
		$coords = array(
			array( 8, 0 ),
			array( 8, 1 ),
			array( 8, 2 ),
			array( 8, 3 ),
			array( 8, 4 ),
			array( 8, 5 ),
			array( 8, 7 ),
			array( 8, 8 ),
			array( 7, 8 ),
			array( 5, 8 ),
			array( 4, 8 ),
			array( 3, 8 ),
			array( 2, 8 ),
			array( 1, 8 ),
			array( 0, 8 ),
		);
		foreach ( $coords as $xy ) {
			$set[ $xy[1] ][ $xy[0] ] = true;
		}
		for ( $i = 0; $i < 7; $i++ ) {
			$set[ $size - 1 - $i ][8] = true;
		}
		for ( $i = 0; $i < 8; $i++ ) {
			$set[8][ $size - 8 + $i ] = true;
		}
		$grid[ $size - 8 ][8] = 1;
		$set[ $size - 8 ][8]  = true;
	}

	/**
	 * Format bits for ECC L and mask 0. The 15-bit string is 111011111000100.
	 *
	 * @param array<int, array<int, int>> $grid Grid.
	 * @param int                         $size Size.
	 */
	private static function format( array &$grid, int $size ): void {
		$info    = 0x77c4;
		$voffset = 0;
		$hoffset = 0;
		for ( $i = 0; $i < 8; $i++ ) {
			$vbit = ( $info >> $i ) & 1;
			$hbit = ( $info >> ( 14 - $i ) ) & 1;
			if ( 6 === $i ) {
				++$voffset;
				$hoffset = 1;
			}
			$grid[ $i + $voffset ][8]  = $vbit;
			$grid[8][ $i + $hoffset ]  = $hbit;
			$grid[8][ $size - 1 - $i ] = $vbit;
			$grid[ $size - 1 - $i ][8] = $hbit;
		}
		$grid[ $size - 8 ][8] = 1;
	}
}
