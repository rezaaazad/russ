<?php
/**
 * Side-by-side university comparison.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Compare;

use LifeRuss\Core\Catalog\Query;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Two to four published universities, plus the curated page when one exists.
 */
class Set {

	/**
	 * Request-scoped payload.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $current = null;

	/**
	 * Payload for the current front request.
	 *
	 * @return array<string, mixed>
	 */
	public static function current(): array {
		if ( null === self::$current ) {
			self::$current = self::from_request();
		}
		return self::$current;
	}

	/**
	 * Build from the rewrite slug or the u query argument.
	 *
	 * @return array<string, mixed>
	 */
	public static function from_request(): array {
		$slug = sanitize_title( (string) get_query_var( 'lr_compare_slug' ) );
		// Public comparison is a shareable URL, not a privileged form.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$raw_u  = isset( $_GET['u'] ) ? sanitize_text_field( wp_unslash( $_GET['u'] ) ) : '';
		$field  = isset( $_GET['field'] ) ? sanitize_title( wp_unslash( $_GET['field'] ) ) : '';
		$degree = isset( $_GET['degree'] ) ? sanitize_key( wp_unslash( $_GET['degree'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$page   = null;
		$pretty = '' !== $slug;
		if ( $pretty ) {
			$page  = Pages::by_slug( $slug );
			$slugs = $page ? explode( ',', (string) $page['uni_slugs'] ) : array();
		} else {
			$slugs = self::parse_list( $raw_u );
			$page  = Pages::by_set( $slugs );
		}
		$data              = self::build( $slugs, $field, $degree, $page );
		$data['pretty']    = $pretty;
		$data['missing']   = $pretty && ! $page;
		$data['indexable'] = $pretty && $page && ! empty( $page['is_indexable'] ) && ! empty( $data['ok'] );
		if ( $page && function_exists( 'liferuss_url' ) ) {
			$data['canonical'] = liferuss_url( '/compare/' . $page['slug'] . '/' );
		}
		return $data;
	}

	/**
	 * Comparison for an explicit slug list. Used by REST and the front page.
	 *
	 * @param string[]   $slugs  University slugs.
	 * @param string     $field  Field slug.
	 * @param string     $degree Degree slug.
	 * @param array|null $page   Curated page.
	 * @return array<string, mixed>
	 */
	public static function build( array $slugs, string $field, string $degree, ?array $page = null ): array {
		$slugs = self::clean_slugs( $slugs );
		$base  = array(
			'ok'          => false,
			'error'       => '',
			'slugs'       => $slugs,
			'field'       => $field,
			'degree'      => $degree,
			'columns'     => array(),
			'rows'        => array(),
			'fields'      => array(),
			'degrees'     => array(),
			'page'        => $page,
			'faq'         => $page ? Pages::faq( (string) ( $page['faq'] ?? '' ) ) : array(),
			'intro'       => $page ? (string) ( $page['intro'] ?? '' ) : '',
			'title'       => 'مقایسه دانشگاه‌ها',
			'description' => '',
			'canonical'   => '',
			'indexable'   => false,
			'pretty'      => false,
			'missing'     => false,
			'names'       => '',
		);
		$count = count( $slugs );
		if ( $count < 2 || $count > 4 ) {
			$base['error'] = 'دو تا چهار دانشگاه را برای مقایسه انتخاب کنید.';
			return $base;
		}
		$columns = array();
		foreach ( $slugs as $slug ) {
			$row = Query::university( $slug );
			if ( ! $row ) {
				$base['error'] = 'یکی از دانشگاه‌ها منتشر نشده است.';
				return $base;
			}
			$columns[] = $row;
		}
		$fields  = array();
		$degrees = array();
		foreach ( $columns as $row ) {
			foreach ( (array) $row['programs'] as $program ) {
				$fields[ (string) $program['field_slug'] ] = (string) $program['field_name'];
				$degrees[ (string) $program['degree'] ]    = self::degree_label( (string) $program['degree'] );
			}
		}
		$names = array();
		foreach ( $columns as $row ) {
			$names[] = (string) $row['name_fa'];
		}
		$title = 'مقایسه ' . implode( ' و ', $names );
		$intro = trim( wp_strip_all_tags( (string) $base['intro'] ) );
		$url   = '';
		if ( function_exists( 'liferuss_url' ) ) {
			$url = add_query_arg( 'u', implode( ',', $slugs ), liferuss_url( '/compare/' ) );
		}
		$base['ok']          = true;
		$base['columns']     = $columns;
		$base['rows']        = self::rows( $columns, $field, $degree );
		$base['fields']      = $fields;
		$base['degrees']     = $degrees;
		$base['title']       = $title;
		$base['description'] = $intro ? $intro : $title;
		$base['canonical']   = $url;
		$base['names']       = implode( '، ', $names );
		return $base;
	}

	/**
	 * Comma or whitespace separated slugs, order preserved, duplicates dropped.
	 *
	 * @param string $raw Raw list.
	 * @return string[]
	 */
	public static function parse_list( string $raw ): array {
		$parts = preg_split( '/[,\s]+/', $raw );
		return self::clean_slugs( is_array( $parts ) ? $parts : array() );
	}

	/**
	 * Sanitize a slug list.
	 *
	 * @param string[] $slugs Slugs.
	 * @return string[]
	 */
	private static function clean_slugs( array $slugs ): array {
		$out = array();
		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( $slug && ! in_array( $slug, $out, true ) ) {
				$out[] = $slug;
			}
		}
		return $out;
	}

	/**
	 * Table rows. The first cell of each row is the label.
	 *
	 * @param array<int, array<string, mixed>> $columns Universities.
	 * @param string                           $field   Field slug.
	 * @param string                           $degree  Degree.
	 * @return array<int, array{label: string, cells: string[]}>
	 */
	private static function rows( array $columns, string $field, string $degree ): array {
		$defs = array(
			'city'       => 'شهر',
			'founded'    => 'تأسیس',
			'ownership'  => 'نوع',
			'languages'  => 'زبان آموزش',
			'health'     => 'وزارت بهداشت',
			'science'    => 'وزارت علوم',
			'dorm'       => 'خوابگاه',
			'facilities' => 'امکانات',
			'qs'         => 'رتبه QS',
			'the'        => 'رتبه THE',
			'tuition'    => 'شهریه',
			'usd'        => 'شهریه به دلار',
		);
		$rows = array();
		foreach ( $defs as $key => $label ) {
			$cells = array();
			foreach ( $columns as $row ) {
				$cells[] = self::cell( $key, $row, $field, $degree );
			}
			$rows[] = array(
				'key'   => $key,
				'label' => $label,
				'cells' => $cells,
			);
		}
		return $rows;
	}

	/**
	 * One cell.
	 *
	 * @param string               $key    Row key.
	 * @param array<string, mixed> $row    University.
	 * @param string               $field  Field slug.
	 * @param string               $degree Degree.
	 */
	private static function cell( string $key, array $row, string $field, string $degree ): string {
		if ( 'city' === $key ) {
			return (string) ( $row['city']['name_fa'] ?? '—' );
		}
		if ( 'founded' === $key ) {
			return ! empty( $row['founded_year'] ) ? (string) $row['founded_year'] : '—';
		}
		if ( 'ownership' === $key ) {
			return 'state' === $row['ownership'] ? 'دولتی' : ( 'private' === $row['ownership'] ? 'خصوصی' : '—' );
		}
		if ( 'languages' === $key ) {
			return self::languages( (string) $row['teaching_languages'] );
		}
		if ( 'health' === $key ) {
			return self::ministry( (string) $row['health_ministry_status'] );
		}
		if ( 'science' === $key ) {
			return self::ministry( (string) $row['science_ministry_status'] );
		}
		if ( 'dorm' === $key ) {
			return self::dorm( $row );
		}
		if ( 'facilities' === $key ) {
			return self::facilities( $row );
		}
		if ( 'qs' === $key ) {
			return self::rank( (array) $row['rankings'], 'qs' );
		}
		if ( 'the' === $key ) {
			return self::rank( (array) $row['rankings'], 'the' );
		}
		$money = self::tuition( (array) $row['programs'], $field, $degree );
		return 'usd' === $key ? $money['usd'] : $money['native'];
	}

	/**
	 * Teaching languages.
	 *
	 * @param string $set Set column.
	 */
	private static function languages( string $set ): string {
		$labels = array(
			'ru' => 'روسی',
			'en' => 'انگلیسی',
		);
		$out    = array();
		foreach ( explode( ',', $set ) as $code ) {
			$code = trim( $code );
			if ( isset( $labels[ $code ] ) ) {
				$out[] = $labels[ $code ];
			}
		}
		return $out ? implode( '، ', $out ) : '—';
	}

	/**
	 * Ministry status label.
	 *
	 * @param string $status Stored enum.
	 */
	private static function ministry( string $status ): string {
		$labels = array(
			'approved'     => 'تأیید شده',
			'conditional'  => 'مشروط',
			'not_approved' => 'تأیید نشده',
			'unknown'      => 'نامشخص',
		);
		return $labels[ $status ] ?? 'نامشخص';
	}

	/**
	 * Dorm flag and latest fee.
	 *
	 * @param array<string, mixed> $row University.
	 */
	private static function dorm( array $row ): string {
		if ( empty( $row['has_dormitory'] ) ) {
			return 'ندارد';
		}
		$text = 'دارد';
		$dorm = $row['dorm'] ?? null;
		if ( is_array( $dorm ) && isset( $dorm['amount_min'] ) && is_numeric( $dorm['amount_min'] ) ) {
			$text .= ' — ' . number_format_i18n( (float) $dorm['amount_min'] ) . ' ' . (string) ( $dorm['currency'] ?? '' );
		}
		return $text;
	}

	/**
	 * Facility flags.
	 *
	 * @param array<string, mixed> $row University.
	 */
	private static function facilities( array $row ): string {
		$bits = array();
		if ( ! empty( $row['has_dormitory'] ) ) {
			$bits[] = 'خوابگاه';
		}
		if ( ! empty( $row['has_padfak'] ) ) {
			$bits[] = 'پادفک';
		}
		if ( ! empty( $row['has_direct_course'] ) ) {
			$bits[] = 'کورس مستقیم';
		}
		if ( ! empty( $row['has_scholarship'] ) ) {
			$bits[] = 'بورسیه';
		}
		return $bits ? implode( '، ', $bits ) : '—';
	}

	/**
	 * Latest world rank for a provider code, otherwise the latest other scope.
	 *
	 * @param array<int, array<string, mixed>> $rankings Rankings.
	 * @param string                           $code     qs or the.
	 */
	private static function rank( array $rankings, string $code ): string {
		$fallback = null;
		foreach ( $rankings as $rank ) {
			if ( (string) ( $rank['provider_code'] ?? '' ) !== $code ) {
				continue;
			}
			if ( 'world' === (string) ( $rank['scope'] ?? '' ) ) {
				return self::rank_label( $rank );
			}
			if ( null === $fallback ) {
				$fallback = $rank;
			}
		}
		return is_array( $fallback ) ? self::rank_label( $fallback ) : '—';
	}

	/**
	 * Rank value and year.
	 *
	 * @param array<string, mixed> $rank Ranking row.
	 */
	private static function rank_label( array $rank ): string {
		$value = $rank['rank_value'] ? (string) $rank['rank_value'] : (string) ( $rank['rank_band'] ?? '' );
		if ( '' === $value ) {
			return '—';
		}
		$year = (string) ( $rank['year'] ?? '' );
		return $year ? $value . ' (' . $year . ')' : $value;
	}

	/**
	 * Native tuition and USD for the selected field and degree.
	 *
	 * @param array<int, array<string, mixed>> $programs Programs.
	 * @param string                           $field    Field slug.
	 * @param string                           $degree   Degree.
	 * @return array{native: string, usd: string}
	 */
	private static function tuition( array $programs, string $field, string $degree ): array {
		$matches = array();
		foreach ( $programs as $program ) {
			if ( $field && (string) $program['field_slug'] !== $field ) {
				continue;
			}
			if ( $degree && (string) $program['degree'] !== $degree ) {
				continue;
			}
			$matches[] = $program;
		}
		if ( ! $matches ) {
			return array(
				'native' => '—',
				'usd'    => '—',
			);
		}
		usort(
			$matches,
			static function ( $a, $b ) {
				$left  = is_numeric( $a['amount_usd'] ?? null ) ? (float) $a['amount_usd'] : INF;
				$right = is_numeric( $b['amount_usd'] ?? null ) ? (float) $b['amount_usd'] : INF;
				return $left <=> $right;
			}
		);
		$pick     = $matches[0];
		$amount   = (float) $pick['tuition'];
		$currency = (string) $pick['currency'];
		$native   = number_format_i18n( $amount ) . ' ' . $currency;
		if ( $field || $degree ) {
			$native = (string) $pick['field_name'] . '، ' . self::degree_label( (string) $pick['degree'] ) . ': ' . $native;
		}
		$usd = is_numeric( $pick['amount_usd'] ?? null ) ? (float) $pick['amount_usd'] : null;
		if ( null === $usd ) {
			$rate = Settings::usd_per_unit( $currency );
			if ( null !== $rate ) {
				$usd = $amount * $rate;
			}
		}
		return array(
			'native' => $native,
			'usd'    => null === $usd ? '—' : number_format_i18n( $usd ) . ' USD',
		);
	}

	/**
	 * Degree label.
	 *
	 * @param string $degree Degree slug.
	 */
	private static function degree_label( string $degree ): string {
		$labels = array(
			'bachelor'   => 'کارشناسی',
			'specialist' => 'تخصصی',
			'master'     => 'کارشناسی ارشد',
			'phd'        => 'دکتری',
			'residency'  => 'رزیدنتی',
		);
		return $labels[ $degree ] ?? $degree;
	}
}
