<?php
/**
 * Certificates with a public verification code.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * The public page is the Persian certificate. The PDF carries the code, the Jalali date, and the verification URL.
 */
class Certificates {

	/**
	 * Issue one certificate when the student has finished the course.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	public static function issue( int $student_id, int $course_id ): ?array {
		$existing = self::for_course( $student_id, $course_id );
		if ( $existing ) {
			return $existing;
		}
		return self::create( $student_id, $course_id, strtolower( bin2hex( random_bytes( 8 ) ) ) );
	}

	/**
	 * Issue a certificate with a chosen code. Used by the language-course migration.
	 *
	 * @param int    $student_id Student id.
	 * @param int    $course_id  Course id.
	 * @param string $code       Verification code.
	 * @return array<string, mixed>|null
	 */
	public static function issue_code( int $student_id, int $course_id, string $code ): ?array {
		$found = Db::find_by( 'certificates', 'code', $code );
		if ( $found ) {
			return $found;
		}
		return self::create( $student_id, $course_id, $code );
	}

	/**
	 * Certificate by public code.
	 *
	 * @param string $code Code.
	 * @return array<string, mixed>|null
	 */
	public static function by_code( string $code ): ?array {
		$code = strtolower( preg_replace( '/[^a-f0-9]/', '', $code ) ?? '' );
		if ( strlen( $code ) < 8 ) {
			return null;
		}
		return Db::find_by( 'certificates', 'code', $code );
	}

	/**
	 * Certificates of one student.
	 *
	 * @param int $student_id Student id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_student( int $student_id ): array {
		return Db::where_id( 'certificates', 'student_id', $student_id );
	}

	/**
	 * Send a one-page PDF.
	 *
	 * @param array<string, mixed> $cert   Certificate.
	 * @param string               $title  Course title.
	 * @param string               $name   Student name.
	 */
	public static function pdf( array $cert, string $title, string $name ): void {
		$url  = home_url( '/academy/certificate/' . $cert['code'] . '/' );
		$date = (string) $cert['jalali_date'];
		$body = "LifeRuss Academy\n" . $name . "\n" . $title . "\n" . $date . "\n" . $cert['code'] . "\n" . $url . "\n";
		$pdf  = self::document( $body );
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="liferuss-' . $cert['code'] . '.pdf"' );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * First certificate for a student and course.
	 *
	 * @param int $student_id Student id.
	 * @param int $course_id  Course id.
	 * @return array<string, mixed>|null
	 */
	private static function for_course( int $student_id, int $course_id ): ?array {
		global $wpdb;
		$table = Db::table( 'certificates' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE student_id = %d AND course_id = %d ORDER BY id ASC LIMIT 1", $student_id, $course_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Insert a certificate.
	 *
	 * @param int    $student_id Student id.
	 * @param int    $course_id  Course id.
	 * @param string $code       Code.
	 * @return array<string, mixed>|null
	 */
	private static function create( int $student_id, int $course_id, string $code ): ?array {
		$now = Db::now();
		$id  = Db::insert(
			'certificates',
			array(
				'student_id'  => $student_id,
				'course_id'   => $course_id,
				'code'        => $code,
				'issued_at'   => $now,
				'jalali_date' => substr( Jalali::text( $now ), 0, 10 ),
			)
		);
		return $id ? Db::find( 'certificates', $id ) : null;
	}

	/**
	 * Minimal PDF with WinAnsi text. Persian names are written as UTF-8 in the verification page; the file repeats the code and date in ASCII.
	 *
	 * @param string $text Lines.
	 */
	private static function document( string $text ): string {
		$safe    = preg_replace( '/[^\x20-\x7E\n]/', '?', $text ) ?? '';
		$safe    = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $safe );
		$stream  = 'BT /F1 14 Tf 50 780 Td 16 TL (' . str_replace( "\n", ") ' (", $safe ) . ') Tj ET';
		$objects = array(
			"1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
			"2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj\n",
			"3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n",
			'4 0 obj << /Length ' . strlen( $stream ) . " >> stream\n" . $stream . "\nendstream endobj\n",
			"5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
		);
		$pdf     = "%PDF-1.4\n";
		$xref    = array( 0 );
		foreach ( $objects as $object ) {
			$xref[] = strlen( $pdf );
			$pdf   .= $object;
		}
		$start         = strlen( $pdf );
		$pdf          .= "xref\n0 " . count( $xref ) . "\n";
		$pdf          .= "0000000000 65535 f \n";
		$objects_count = count( $xref );
		for ( $i = 1; $i < $objects_count; $i++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", $xref[ $i ] );
		}
		$pdf .= 'trailer << /Size ' . count( $xref ) . " /Root 1 0 R >>\nstartxref\n" . $start . "\n%%EOF";
		return $pdf;
	}
}
