<?php
/**
 * Public catalog reads. Results are cached until a catalog row is saved.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Filtered university, field, and city queries.
 */
class Query {

	/**
	 * Page size for archives.
	 */
	public const PER_PAGE = 12;

	/**
	 * Published universities matching archive filters.
	 *
	 * @param array<string, mixed> $filters Filters.
	 * @param int                  $page    Page number.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	public static function universities( array $filters, int $page = 1 ): array {
		$page = max( 1, $page );
		$key  = self::cache_key( 'unis', $filters, $page );
		$hit  = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}

		global $wpdb;
		$unis   = $wpdb->prefix . 'lr_universities';
		$cities = $wpdb->prefix . 'lr_cities';
		$where  = array( 'u.deleted_at IS NULL', "u.status = 'published'" );
		$params = array();
		self::university_filters( $where, $params, $filters );
		$sql_where = implode( ' AND ', $where );
		$join      = "LEFT JOIN `{$cities}` c ON c.id = u.city_id AND c.deleted_at IS NULL";

		$count_sql = "SELECT COUNT(*) FROM `{$unis}` u {$join} WHERE {$sql_where}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pages     = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$offset    = ( $page - 1 ) * self::PER_PAGE;
		$list_sql  = "SELECT u.*, c.name_fa AS city_name, c.slug AS city_slug FROM `{$unis}` u {$join} WHERE {$sql_where} ORDER BY u.is_featured DESC, u.best_world_rank IS NULL, u.best_world_rank ASC, u.name_fa ASC LIMIT %d OFFSET %d";
		$list_args = array_merge( $params, array( self::PER_PAGE, $offset ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		self::prime_posts( (array) $rows );
		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = self::university_card( $row );
		}
		$result = array(
			'items' => $items,
			'total' => $total,
			'pages' => $pages,
		);
		set_transient( $key, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	/**
	 * One published university with programs, rankings, and dorm fees.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public static function university( string $slug ): ?array {
		$key = self::cache_key( 'uni', array( 'slug' => $slug ), 1 );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$row = self::published_by_slug( 'lr_universities', $slug );
		if ( ! $row ) {
			return null;
		}
		$row['programs'] = self::programs( (int) $row['id'] );
		$row['rankings'] = self::rankings( (int) $row['id'] );
		$row['dorm']     = self::dorm( (int) $row['id'] );
		$row['city']     = $row['city_id'] ? self::by_id( 'lr_cities', (int) $row['city_id'] ) : null;
		set_transient( $key, $row, 10 * MINUTE_IN_SECONDS );
		return $row;
	}

	/**
	 * Published fields.
	 *
	 * @param int $page Page.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	public static function fields( int $page = 1 ): array {
		return self::simple_list( 'lr_fields', $page, 'fields' );
	}

	/**
	 * One published field.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public static function field( string $slug ): ?array {
		$row = self::published_by_slug( 'lr_fields', $slug );
		if ( ! $row ) {
			return null;
		}
		$row['universities'] = self::universities_for_field( (int) $row['id'] );
		return $row;
	}

	/**
	 * Published cities.
	 *
	 * @param int $page Page.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	public static function cities( int $page = 1 ): array {
		return self::simple_list( 'lr_cities', $page, 'cities' );
	}

	/**
	 * One published city.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public static function city( string $slug ): ?array {
		$row = self::published_by_slug( 'lr_cities', $slug );
		if ( ! $row ) {
			return null;
		}
		$row['universities'] = self::universities_for_city( (int) $row['id'] );
		return $row;
	}

	/**
	 * Related published universities in the same city, excluding one id.
	 *
	 * @param int $city_id City id.
	 * @param int $except  University id to skip.
	 * @return array<int, array<string, mixed>>
	 */
	public static function related( int $city_id, int $except ): array {
		if ( $city_id < 1 ) {
			return array();
		}
		$result = self::universities(
			array(
				'city_id' => $city_id,
				'except'  => $except,
			),
			1
		);
		return $result['items'];
	}

	/**
	 * Whitelisted archive filters from the query string.
	 *
	 * @return array<string, mixed>
	 */
	public static function filters_from_request(): array {
		// Public catalog filters are query-string reads, not a privileged form.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters  = array();
		$city     = isset( $_GET['city'] ) ? sanitize_title( wp_unslash( $_GET['city'] ) ) : '';
		$field    = isset( $_GET['field'] ) ? sanitize_title( wp_unslash( $_GET['field'] ) ) : '';
		$degree   = isset( $_GET['degree'] ) ? sanitize_key( wp_unslash( $_GET['degree'] ) ) : '';
		$lang     = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
		$min      = isset( $_GET['tuition_min'] ) ? sanitize_text_field( wp_unslash( $_GET['tuition_min'] ) ) : '';
		$max      = isset( $_GET['tuition_max'] ) ? sanitize_text_field( wp_unslash( $_GET['tuition_max'] ) ) : '';
		$rank     = isset( $_GET['rank'] ) ? sanitize_text_field( wp_unslash( $_GET['rank'] ) ) : '';
		$ministry = isset( $_GET['ministry'] ) ? sanitize_key( wp_unslash( $_GET['ministry'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $city ) {
			$filters['city'] = $city;
		}
		if ( $field ) {
			$filters['field'] = $field;
		}
		if ( in_array( $degree, array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ), true ) ) {
			$filters['degree'] = $degree;
		}
		if ( in_array( $lang, array( 'ru', 'en', 'ru_en' ), true ) ) {
			$filters['lang'] = $lang;
		}
		if ( is_numeric( $min ) ) {
			$filters['tuition_min'] = (float) $min;
		}
		if ( is_numeric( $max ) ) {
			$filters['tuition_max'] = (float) $max;
		}
		if ( is_numeric( $rank ) ) {
			$filters['rank'] = max( 1, (int) $rank );
		}
		if ( 'approved' === $ministry ) {
			$filters['ministry'] = 'approved';
		}
		return $filters;
	}

	/**
	 * Whether the current university archive URL carries a filter or a page.
	 */
	public static function request_is_filtered(): bool {
		if ( self::filters_from_request() ) {
			return true;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged_raw = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$paged = (int) $paged_raw;
		return max( $paged, (int) get_query_var( 'paged' ) ) > 1;
	}

	/**
	 * Published slug/name pairs for filter dropdowns.
	 *
	 * @param string $table Table fragment such as lr_cities.
	 * @return array<int, array<string, mixed>>
	 */
	public static function options( string $table ): array {
		if ( ! in_array( $table, array( 'lr_cities', 'lr_fields' ), true ) ) {
			return array();
		}
		$key = self::cache_key( 'opt_' . $table, array(), 1 );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		global $wpdb;
		$name = $wpdb->prefix . $table;
		$rows = $wpdb->get_results( "SELECT slug, name_fa FROM `{$name}` WHERE status = 'published' AND deleted_at IS NULL ORDER BY name_fa ASC LIMIT 500", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = is_array( $rows ) ? $rows : array();
		set_transient( $key, $rows, 10 * MINUTE_IN_SECONDS );
		return $rows;
	}

	/**
	 * Published programs with filters.
	 *
	 * @param array<string, mixed> $filters Filters.
	 * @param int                  $page    Page.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	public static function program_search( array $filters, int $page = 1 ): array {
		$page = max( 1, $page );
		$key  = self::cache_key( 'progs', $filters, $page );
		$hit  = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		global $wpdb;
		$progs  = $wpdb->prefix . 'lr_university_fields';
		$unis   = $wpdb->prefix . 'lr_universities';
		$fields = $wpdb->prefix . 'lr_fields';
		$fees   = $wpdb->prefix . 'lr_tuition_fees';
		$where  = array( 'p.deleted_at IS NULL', "p.status = 'active'", 'u.deleted_at IS NULL', "u.status = 'published'", 'f.deleted_at IS NULL' );
		$params = array();
		if ( ! empty( $filters['university'] ) ) {
			$where[]  = 'u.slug = %s';
			$params[] = (string) $filters['university'];
		}
		if ( ! empty( $filters['field'] ) ) {
			$where[]  = 'f.slug = %s';
			$params[] = (string) $filters['field'];
		}
		if ( ! empty( $filters['degree'] ) ) {
			$where[]  = 'p.degree = %s';
			$params[] = (string) $filters['degree'];
		}
		if ( ! empty( $filters['lang'] ) ) {
			$where[]  = 'p.language = %s';
			$params[] = (string) $filters['lang'];
		}
		$join      = "FROM `{$progs}` p INNER JOIN `{$unis}` u ON u.id = p.university_id INNER JOIN `{$fields}` f ON f.id = p.field_id LEFT JOIN `{$fees}` t ON t.university_field_id = p.id AND t.is_current = 1 AND t.deleted_at IS NULL";
		$sql_where = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) {$join} WHERE {$sql_where}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pages     = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$list_sql  = "SELECT p.degree, p.language, p.duration_years, p.tuition, p.currency, p.academic_year, f.name_fa AS field_name, f.slug AS field_slug, u.name_fa AS university_name, u.slug AS university_slug, u.post_id, t.amount_usd {$join} WHERE {$sql_where} ORDER BY u.name_fa ASC LIMIT %d OFFSET %d";
		$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $params, array( self::PER_PAGE, ( $page - 1 ) * self::PER_PAGE ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items     = array();
		foreach ( (array) $rows as $row ) {
			$row['url'] = get_permalink( (int) $row['post_id'] );
			$items[]    = $row;
		}
		$result = array(
			'items' => $items,
			'total' => $total,
			'pages' => $pages,
		);
		set_transient( $key, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	/**
	 * Apply university filters to a WHERE list.
	 *
	 * @param string[]             $where   Clauses.
	 * @param array<int, mixed>    $params  Prepare values.
	 * @param array<string, mixed> $filters Filters.
	 */
	private static function university_filters( array &$where, array &$params, array $filters ): void {
		global $wpdb;
		$progs  = $wpdb->prefix . 'lr_university_fields';
		$fields = $wpdb->prefix . 'lr_fields';
		$cities = $wpdb->prefix . 'lr_cities';

		if ( ! empty( $filters['city_id'] ) ) {
			$where[]  = 'u.city_id = %d';
			$params[] = (int) $filters['city_id'];
		} elseif ( ! empty( $filters['city'] ) ) {
			$where[]  = "u.city_id IN (SELECT id FROM `{$cities}` WHERE slug = %s AND deleted_at IS NULL)";
			$params[] = (string) $filters['city'];
		}
		if ( ! empty( $filters['except'] ) ) {
			$where[]  = 'u.id <> %d';
			$params[] = (int) $filters['except'];
		}
		if ( ! empty( $filters['field'] ) || ! empty( $filters['degree'] ) || ! empty( $filters['lang'] ) ) {
			$bits = array( 'p.university_id = u.id', 'p.deleted_at IS NULL', "p.status = 'active'" );
			if ( ! empty( $filters['field'] ) ) {
				$bits[]   = "p.field_id IN (SELECT id FROM `{$fields}` WHERE slug = %s AND deleted_at IS NULL)";
				$params[] = (string) $filters['field'];
			}
			if ( ! empty( $filters['degree'] ) ) {
				$bits[]   = 'p.degree = %s';
				$params[] = (string) $filters['degree'];
			}
			if ( ! empty( $filters['lang'] ) ) {
				$bits[]   = 'p.language = %s';
				$params[] = (string) $filters['lang'];
			}
			$where[] = 'EXISTS (SELECT 1 FROM `' . $progs . '` p WHERE ' . implode( ' AND ', $bits ) . ')';
		}
		if ( isset( $filters['tuition_min'] ) ) {
			$where[]  = 'u.min_tuition_usd >= %f';
			$params[] = (float) $filters['tuition_min'];
		}
		if ( isset( $filters['tuition_max'] ) ) {
			$where[]  = 'u.min_tuition_usd > 0 AND u.min_tuition_usd <= %f';
			$params[] = (float) $filters['tuition_max'];
		}
		if ( ! empty( $filters['rank'] ) ) {
			$where[]  = 'u.best_world_rank IS NOT NULL AND u.best_world_rank <= %d';
			$params[] = (int) $filters['rank'];
		}
		if ( ! empty( $filters['ministry'] ) ) {
			$where[] = "(u.health_ministry_status = 'approved' OR u.science_ministry_status = 'approved')";
		}
	}

	/**
	 * Load posts and thumbnails once for a page of rows.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows with post_id.
	 */
	private static function prime_posts( array $rows ): void {
		$ids = array();
		foreach ( $rows as $row ) {
			if ( ! empty( $row['post_id'] ) ) {
				$ids[] = (int) $row['post_id'];
			}
		}
		if ( $ids ) {
			_prime_post_caches( $ids, false, true );
		}
	}

	/**
	 * Card payload for JSON and templates.
	 *
	 * @param array<string, mixed> $row University row.
	 * @return array<string, mixed>
	 */
	private static function university_card( array $row ): array {
		$post_id = (int) $row['post_id'];
		return array(
			'id'              => (int) $row['id'],
			'post_id'         => $post_id,
			'slug'            => (string) $row['slug'],
			'name'            => (string) $row['name_fa'],
			'name_en'         => (string) $row['name_en'],
			'name_ru'         => (string) $row['name_ru'],
			'city'            => (string) ( $row['city_name'] ?? '' ),
			'city_slug'       => (string) ( $row['city_slug'] ?? '' ),
			'url'             => get_permalink( $post_id ),
			'min_tuition_usd' => $row['min_tuition_usd'],
			'best_world_rank' => $row['best_world_rank'],
			'ownership'       => (string) $row['ownership'],
			'health'          => (string) $row['health_ministry_status'],
			'science'         => (string) $row['science_ministry_status'],
			'languages'       => (string) $row['teaching_languages'],
			'thumbnail'       => get_the_post_thumbnail_url( $post_id, 'medium_large' ),
		);
	}

	/**
	 * Programs with field names and current USD tuition.
	 *
	 * @param int $university_id University id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function programs( int $university_id ): array {
		global $wpdb;
		$progs  = $wpdb->prefix . 'lr_university_fields';
		$fields = $wpdb->prefix . 'lr_fields';
		$fees   = $wpdb->prefix . 'lr_tuition_fees';
		$sql    = "SELECT p.*, f.name_fa AS field_name, f.slug AS field_slug, t.amount_usd
			FROM `{$progs}` p
			INNER JOIN `{$fields}` f ON f.id = p.field_id AND f.deleted_at IS NULL
			LEFT JOIN `{$fees}` t ON t.university_field_id = p.id AND t.is_current = 1 AND t.deleted_at IS NULL
			WHERE p.university_id = %d AND p.deleted_at IS NULL AND p.status = 'active'
			ORDER BY f.name_fa ASC, p.degree ASC";
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $university_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Rankings joined to provider names.
	 *
	 * @param int $university_id University id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function rankings( int $university_id ): array {
		global $wpdb;
		$ranks = $wpdb->prefix . 'lr_university_rankings';
		$prov  = $wpdb->prefix . 'lr_ranking_providers';
		$sql   = "SELECT r.*, p.code AS provider_code, p.name AS provider_name
			FROM `{$ranks}` r
			INNER JOIN `{$prov}` p ON p.id = r.ranking_provider_id AND p.deleted_at IS NULL
			WHERE r.university_id = %d AND r.deleted_at IS NULL
			ORDER BY r.year DESC, r.rank_value ASC";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $university_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Latest dorm fee.
	 *
	 * @param int $university_id University id.
	 * @return array<string, mixed>|null
	 */
	private static function dorm( int $university_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_dormitory_fees';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
				$university_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Universities that offer a field, with a tuition range.
	 *
	 * @param int $field_id Field id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function universities_for_field( int $field_id ): array {
		global $wpdb;
		$unis   = $wpdb->prefix . 'lr_universities';
		$fees   = $wpdb->prefix . 'lr_tuition_fees';
		$cities = $wpdb->prefix . 'lr_cities';
		$sql    = "SELECT u.id, u.post_id, u.slug, u.name_fa, u.name_en, c.name_fa AS city_name,
				MIN(t.amount_usd) AS min_usd, MAX(t.amount_usd) AS max_usd
			FROM `{$unis}` u
			INNER JOIN `{$fees}` t ON t.university_id = u.id AND t.field_id = %d AND t.is_current = 1 AND t.deleted_at IS NULL
			LEFT JOIN `{$cities}` c ON c.id = u.city_id
			WHERE u.deleted_at IS NULL AND u.status = 'published'
			GROUP BY u.id, u.post_id, u.slug, u.name_fa, u.name_en, c.name_fa
			ORDER BY min_usd ASC, u.name_fa ASC
			LIMIT 100";
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $field_id ), ARRAY_A );
		$out    = array();
		foreach ( (array) $rows as $row ) {
			$row['url'] = get_permalink( (int) $row['post_id'] );
			$out[]      = $row;
		}
		return $out;
	}

	/**
	 * Published universities in a city.
	 *
	 * @param int $city_id City id.
	 * @return array<int, array<string, mixed>>
	 */
	private static function universities_for_city( int $city_id ): array {
		$result = self::universities( array( 'city_id' => $city_id ), 1 );
		return $result['items'];
	}

	/**
	 * Published row by slug.
	 *
	 * @param string $table Table with prefix lr_ already? Pass wp table name fragment lr_universities.
	 * @param string $slug  Slug.
	 * @return array<string, mixed>|null
	 */
	private static function published_by_slug( string $table, string $slug ): ?array {
		global $wpdb;
		$name = $wpdb->prefix . $table;
		$row  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$name}` WHERE slug = %s AND status = 'published' AND deleted_at IS NULL LIMIT 1",
				$slug
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Row by id, any status except soft-deleted.
	 *
	 * @param string $table Table fragment.
	 * @param int    $id    Id.
	 * @return array<string, mixed>|null
	 */
	private static function by_id( string $table, int $id ): ?array {
		global $wpdb;
		$name = $wpdb->prefix . $table;
		$row  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$name}` WHERE id = %d AND deleted_at IS NULL LIMIT 1",
				$id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Paginated published list for fields or cities.
	 *
	 * @param string $table Table fragment.
	 * @param int    $page  Page.
	 * @param string $kind  Cache kind.
	 * @return array{items: array<int, array<string, mixed>>, total: int, pages: int}
	 */
	private static function simple_list( string $table, int $page, string $kind ): array {
		$page = max( 1, $page );
		$key  = self::cache_key( $kind, array(), $page );
		$hit  = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		global $wpdb;
		$name  = $wpdb->prefix . $table;
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$name}` WHERE status = 'published' AND deleted_at IS NULL" );
		$pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$name}` WHERE status = 'published' AND deleted_at IS NULL ORDER BY name_fa ASC LIMIT %d OFFSET %d",
				self::PER_PAGE,
				( $page - 1 ) * self::PER_PAGE
			),
			ARRAY_A
		);
		$rows  = is_array( $rows ) ? $rows : array();
		self::prime_posts( $rows );
		$items = array();
		foreach ( $rows as $row ) {
			$row['url'] = get_permalink( (int) $row['post_id'] );
			$items[]    = $row;
		}
		$result = array(
			'items' => $items,
			'total' => $total,
			'pages' => $pages,
		);
		set_transient( $key, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	/**
	 * Counts, USD tuition range, and the best-ranked universities for a filter.
	 *
	 * @param array<string, mixed> $filters Same filters as universities().
	 * @return array{total: int, min_usd: float, max_usd: float, items: array<int, array<string, mixed>>}
	 */
	public static function snapshot( array $filters ): array {
		$key = self::cache_key( 'snap', $filters, 1 );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		global $wpdb;
		$unis   = $wpdb->prefix . 'lr_universities';
		$cities = $wpdb->prefix . 'lr_cities';
		$where  = array( 'u.deleted_at IS NULL', "u.status = 'published'" );
		$params = array();
		self::university_filters( $where, $params, $filters );
		$sql_where = implode( ' AND ', $where );
		$join      = "LEFT JOIN `{$cities}` c ON c.id = u.city_id AND c.deleted_at IS NULL";
		$sql       = "SELECT COUNT(*) AS total, MIN(CASE WHEN u.min_tuition_usd > 0 THEN u.min_tuition_usd END) AS min_usd, MAX(u.min_tuition_usd) AS max_usd FROM `{$unis}` u {$join} WHERE {$sql_where}";
		$row       = $params ? $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_row( $sql, ARRAY_A );
		$list_sql  = "SELECT u.*, c.name_fa AS city_name, c.slug AS city_slug FROM `{$unis}` u {$join} WHERE {$sql_where} AND u.best_world_rank IS NOT NULL ORDER BY u.best_world_rank ASC LIMIT 6";
		$rows      = $params ? $wpdb->get_results( $wpdb->prepare( $list_sql, $params ), ARRAY_A ) : $wpdb->get_results( $list_sql, ARRAY_A );
		$items     = array();
		foreach ( (array) $rows as $item ) {
			$items[] = self::university_card( $item );
		}
		$result = array(
			'total'   => (int) ( $row['total'] ?? 0 ),
			'min_usd' => (float) ( $row['min_usd'] ?? 0 ),
			'max_usd' => (float) ( $row['max_usd'] ?? 0 ),
			'items'   => $items,
		);
		set_transient( $key, $result, 10 * MINUTE_IN_SECONDS );
		return $result;
	}

	/**
	 * Published universities that offer padfak or a direct course, with price and duration.
	 *
	 * @param string $type padfak or direct_course.
	 * @return array<int, array<string, mixed>>
	 */
	public static function prep( string $type ): array {
		if ( ! in_array( $type, array( 'padfak', 'direct_course' ), true ) ) {
			return array();
		}
		$key = self::cache_key( 'prep_' . $type, array(), 1 );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		global $wpdb;
		$prep   = $wpdb->prefix . 'lr_prep_programs';
		$unis   = $wpdb->prefix . 'lr_universities';
		$cities = $wpdb->prefix . 'lr_cities';
		$sql    = "SELECT p.duration_months, p.tuition, p.currency, p.track, p.format, u.id AS university_id, u.post_id, u.name_fa, u.slug, c.name_fa AS city_name
			FROM `{$prep}` p
			INNER JOIN `{$unis}` u ON u.id = p.university_id AND u.deleted_at IS NULL AND u.status = 'published'
			LEFT JOIN `{$cities}` c ON c.id = u.city_id
			WHERE p.program_type = %s AND p.status = 'active' AND p.deleted_at IS NULL
			ORDER BY u.name_fa ASC
			LIMIT 100";
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $type ), ARRAY_A );
		$seen   = array();
		$items  = array();
		foreach ( (array) $rows as $row ) {
			$seen[ (int) $row['university_id'] ] = true;
			$row['url']                          = get_permalink( (int) $row['post_id'] );
			$row['usd']                          = Store::usd( (string) $row['currency'], (float) $row['tuition'] );
			$items[]                             = $row;
		}
		$flag = 'padfak' === $type ? 'has_padfak' : 'has_direct_course';
		$more = $wpdb->get_results(
			"SELECT u.post_id, u.name_fa, u.slug, c.name_fa AS city_name, u.id AS university_id FROM `{$unis}` u LEFT JOIN `{$cities}` c ON c.id = u.city_id WHERE u.deleted_at IS NULL AND u.status = 'published' AND u.{$flag} = 1 ORDER BY u.name_fa ASC LIMIT 100",
			ARRAY_A
		);
		foreach ( (array) $more as $row ) {
			if ( isset( $seen[ (int) $row['university_id'] ] ) ) {
				continue;
			}
			$row['url']             = get_permalink( (int) $row['post_id'] );
			$row['duration_months'] = null;
			$row['tuition']         = null;
			$row['currency']        = '';
			$row['usd']             = 0;
			$row['track']           = '';
			$items[]                = $row;
		}
		set_transient( $key, $items, 10 * MINUTE_IN_SECONDS );
		return $items;
	}

	/**
	 * Transient key tied to the catalog generation.
	 *
	 * @param string               $kind    Kind.
	 * @param array<string, mixed> $filters Filters.
	 * @param int                  $page    Page.
	 */
	private static function cache_key( string $kind, array $filters, int $page ): string {
		return 'lr_cat_' . Store::generation() . '_' . md5( $kind . '|' . $page . '|' . wp_json_encode( $filters ) );
	}
}
