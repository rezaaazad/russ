<?php
/**
 * Custom table definitions from the LifeRuss ERD (v2.0).
 *
 * Physical foreign keys exist only between lr_* tables. References to
 * wp_posts, wp_users, and wp_terms are enforced in the application layer.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema catalogue for {prefix}lr_* tables.
 */
class Tables {

	/**
	 * All tables in dependency order.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		static $tables = null;
		if ( null === $tables ) {
			$tables = array_merge(
				self::core(),
				self::university(),
				self::education(),
				self::crm()
			);
		}
		return $tables;
	}

	/**
	 * One table definition.
	 *
	 * @param string $name Table suffix.
	 * @return array<string, mixed>
	 */
	public static function get( string $name ): array {
		$all = self::all();
		if ( ! isset( $all[ $name ] ) ) {
			return array();
		}
		return $all[ $name ];
	}

	/**
	 * Column names for a table.
	 *
	 * @param string $name Table suffix.
	 * @return string[]
	 */
	public static function columns( string $name ): array {
		$def = self::get( $name );
		return isset( $def['columns'] ) ? array_keys( $def['columns'] ) : array();
	}

	/**
	 * Whether the table uses deleted_at.
	 *
	 * @param string $name Table suffix.
	 */
	public static function soft_deletes( string $name ): bool {
		$def = self::get( $name );
		return ! empty( $def['soft'] );
	}

	/**
	 * Core and infrastructure tables.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function core(): array {
		$id  = 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$fkn = 'bigint(20) unsigned DEFAULT NULL';
		$dt  = 'datetime NOT NULL';
		$dtn = 'datetime DEFAULT NULL';

		return array(
			'activity_logs'    => array(
				'soft'    => false,
				'columns' => array(
					'id'          => $id,
					'user_id'     => $fkn,
					'action'      => 'varchar(40) NOT NULL',
					'object_type' => 'varchar(40) NOT NULL',
					'object_id'   => $fkn,
					'summary'     => 'varchar(255) NOT NULL',
					'changes'     => 'json DEFAULT NULL',
					'ip'          => 'varbinary(16) DEFAULT NULL',
					'user_agent'  => 'varchar(255) NOT NULL DEFAULT \'\'',
					'created_at'  => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY object_lookup (object_type, object_id)',
					'KEY user_created (user_id, created_at)',
					'KEY created_at (created_at)',
				),
				'fks'     => array(),
			),
			'redirects'        => array(
				'soft'    => true,
				'columns' => array(
					'id'          => $id,
					'source_path' => 'varchar(500) NOT NULL',
					'source_hash' => 'char(40) NOT NULL',
					'target_url'  => 'varchar(500) NOT NULL',
					'status_code' => 'smallint(6) NOT NULL DEFAULT 301',
					'match_type'  => "enum('exact','prefix','regex') NOT NULL DEFAULT 'exact'",
					'lang'        => 'varchar(5) DEFAULT NULL',
					'origin'      => "enum('manual','slug_change','import','migration') NOT NULL DEFAULT 'manual'",
					'object_type' => 'varchar(40) DEFAULT NULL',
					'object_id'   => $fkn,
					'hits'        => 'int(10) unsigned NOT NULL DEFAULT 0',
					'last_hit_at' => $dtn,
					'is_active'   => 'tinyint(1) NOT NULL DEFAULT 1',
					'created_by'  => $fkn,
					'created_at'  => $dt,
					'updated_at'  => $dt,
					'deleted_at'  => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY source_hash (source_hash)',
					'KEY active_match (is_active, match_type)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(),
			),
			'not_found'        => array(
				'soft'    => false,
				'columns' => array(
					'id'          => $id,
					'path'        => 'varchar(500) NOT NULL',
					'path_hash'   => 'char(40) NOT NULL',
					'hits'        => 'int(10) unsigned NOT NULL DEFAULT 1',
					'referrer'    => 'varchar(500) NOT NULL DEFAULT \'\'',
					'last_hit_at' => $dt,
					'created_at'  => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY path_hash (path_hash)',
					'KEY last_hit_at (last_hit_at)',
				),
				'fks'     => array(),
			),
			'translations'     => array(
				'soft'    => false,
				'columns' => array(
					'id'            => $id,
					'object_type'   => 'varchar(40) NOT NULL',
					'object_id'     => 'bigint(20) unsigned NOT NULL',
					'lang'          => 'varchar(5) NOT NULL',
					'field_key'     => 'varchar(64) NOT NULL',
					'value'         => 'mediumtext NOT NULL',
					'status'        => "enum('draft','published') NOT NULL DEFAULT 'draft'",
					'translated_by' => $fkn,
					'created_at'    => $dt,
					'updated_at'    => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY object_lang_field (object_type, object_id, lang, field_key)',
					'KEY created_at (created_at)',
				),
				'fks'     => array(),
			),
			'relations'        => array(
				'soft'    => false,
				'columns' => array(
					'id'         => $id,
					'from_type'  => 'varchar(20) NOT NULL',
					'from_id'    => 'bigint(20) unsigned NOT NULL',
					'to_type'    => 'varchar(20) NOT NULL',
					'to_id'      => 'bigint(20) unsigned NOT NULL',
					'relation'   => 'varchar(30) NOT NULL',
					'sort_order' => 'smallint(6) NOT NULL DEFAULT 0',
					'created_at' => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY relation_pair (from_type, from_id, to_type, to_id, relation)',
					'KEY to_lookup (to_type, to_id)',
				),
				'fks'     => array(),
			),
			'view_stats_daily' => array(
				'soft'    => false,
				'columns' => array(
					'stat_date'   => 'date NOT NULL',
					'object_type' => 'varchar(20) NOT NULL',
					'object_id'   => 'bigint(20) unsigned NOT NULL',
					'lang'        => 'varchar(5) NOT NULL',
					'views'       => 'int(10) unsigned NOT NULL DEFAULT 0',
					'leads'       => 'int(10) unsigned NOT NULL DEFAULT 0',
				),
				'primary' => array( 'stat_date', 'object_type', 'object_id', 'lang' ),
				'indexes' => array(
					'KEY type_date (object_type, stat_date)',
				),
				'fks'     => array(),
			),
			'compare_pages'    => array(
				'soft'    => false,
				'columns' => array(
					'id'           => $id,
					'slug'         => 'varchar(191) NOT NULL',
					'uni_slugs'    => 'varchar(400) NOT NULL',
					'intro'        => 'longtext DEFAULT NULL',
					'faq'          => 'longtext DEFAULT NULL',
					'is_indexable' => 'tinyint(1) NOT NULL DEFAULT 0',
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY slug (slug)',
					'KEY is_indexable (is_indexable)',
				),
				'fks'     => array(),
			),
			'search_docs'      => array(
				'soft'    => false,
				'columns' => array(
					'id'          => $id,
					'doc_key'     => 'varchar(80) NOT NULL',
					'object_type' => 'varchar(20) NOT NULL',
					'object_id'   => 'bigint(20) unsigned NOT NULL',
					'title'       => 'varchar(255) NOT NULL DEFAULT \'\'',
					'text_norm'   => 'mediumtext NOT NULL',
					'aliases'     => 'text NOT NULL',
					'excerpt'     => 'varchar(300) NOT NULL DEFAULT \'\'',
					'url'         => 'varchar(500) NOT NULL DEFAULT \'\'',
					'slug'        => 'varchar(200) NOT NULL DEFAULT \'\'',
					'city'        => 'varchar(150) NOT NULL DEFAULT \'\'',
					'city_slug'   => 'varchar(150) NOT NULL DEFAULT \'\'',
					'updated_at'  => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY doc_key (doc_key)',
					'KEY type_city (object_type, city_slug)',
					'KEY object_id (object_id)',
					'FULLTEXT KEY doc_search (title, text_norm, aliases)',
				),
				'fks'     => array(),
			),
			'search_stats'     => array(
				'soft'    => false,
				'columns' => array(
					'id'         => $id,
					'query_hash' => 'char(40) NOT NULL',
					'query_text' => 'varchar(191) NOT NULL',
					'total'      => 'int(10) unsigned NOT NULL DEFAULT 0',
					'zeros'      => 'int(10) unsigned NOT NULL DEFAULT 0',
					'last_at'    => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY query_hash (query_hash)',
					'KEY total (total)',
					'KEY zeros (zeros)',
				),
				'fks'     => array(),
			),
			'search_queue'     => array(
				'soft'    => false,
				'columns' => array(
					'id'          => $id,
					'object_type' => 'varchar(20) NOT NULL',
					'object_id'   => 'bigint(20) unsigned NOT NULL',
					'action'      => "varchar(10) NOT NULL DEFAULT 'upsert'",
					'attempts'    => 'tinyint(3) unsigned NOT NULL DEFAULT 0',
					'created_at'  => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY object_ref (object_type, object_id)',
					'KEY created_at (created_at)',
				),
				'fks'     => array(),
			),
			'lead_messages'    => array(
				'soft'    => false,
				'columns' => array(
					'id'         => $id,
					'lead_id'    => 'bigint(20) unsigned NOT NULL',
					'author_id'  => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'body'       => 'text NOT NULL',
					'created_at' => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_created (lead_id, created_at)',
				),
				'fks'     => array(),
			),
			'lesson_progress'  => array(
				'soft'    => false,
				'columns' => array(
					'id'         => $id,
					'user_id'    => 'bigint(20) unsigned NOT NULL',
					'lesson_id'  => 'bigint(20) unsigned NOT NULL',
					'completed'  => 'tinyint(1) NOT NULL DEFAULT 0',
					'updated_at' => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY user_lesson (user_id, lesson_id)',
					'KEY lesson_id (lesson_id)',
				),
				'fks'     => array(),
			),
			'quiz_attempts'    => array(
				'soft'    => false,
				'columns' => array(
					'id'         => $id,
					'user_id'    => 'bigint(20) unsigned NOT NULL',
					'lesson_id'  => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'kind'       => "varchar(20) NOT NULL DEFAULT 'lesson'",
					'score'      => 'smallint(5) unsigned NOT NULL DEFAULT 0',
					'max_score'  => 'smallint(5) unsigned NOT NULL DEFAULT 0',
					'detail'     => 'text',
					'created_at' => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY user_kind (user_id, kind)',
					'KEY lesson_id (lesson_id)',
				),
				'fks'     => array(),
			),
			'scholarships'     => array(
				'soft'    => false,
				'columns' => array(
					'id'               => $id,
					'post_id'          => 'bigint(20) unsigned NOT NULL',
					'university_id'    => $fkn,
					'field_name'       => 'varchar(120) NOT NULL DEFAULT \'\'',
					'degree'           => 'varchar(40) NOT NULL DEFAULT \'\'',
					'coverage_type'    => 'varchar(40) NOT NULL DEFAULT \'\'',
					'coverage_percent' => 'smallint(5) unsigned DEFAULT NULL',
					'quota'            => 'int(10) unsigned DEFAULT NULL',
					'deadline'         => 'varchar(40) NOT NULL DEFAULT \'\'',
					'language'         => 'varchar(20) NOT NULL DEFAULT \'\'',
					'requirements'     => 'text',
					'source'           => 'varchar(255) NOT NULL DEFAULT \'\'',
					'last_updated'     => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY post_id (post_id)',
					'KEY university_id (university_id)',
					'KEY degree_lang (degree, language)',
				),
				'fks'     => array(),
			),
			'payments'         => array(
				'soft'    => false,
				'columns' => array(
					'id'           => $id,
					'lead_id'      => 'bigint(20) unsigned NOT NULL',
					'token'        => 'char(32) NOT NULL',
					'amount_toman' => 'bigint(20) unsigned NOT NULL',
					'description'  => 'varchar(255) NOT NULL',
					'status'       => "enum('pending','paid','failed','expired','cancelled') NOT NULL DEFAULT 'pending'",
					'expires_at'   => $dtn,
					'gateway'      => "varchar(20) NOT NULL DEFAULT 'zarinpal'",
					'authority'    => 'varchar(64) NOT NULL DEFAULT \'\'',
					'ref_id'       => 'varchar(64) NOT NULL DEFAULT \'\'',
					'paid_at'      => $dtn,
					'created_by'   => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'revenue_line' => "varchar(20) NOT NULL DEFAULT 'study'",
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY token (token)',
					'KEY lead_status (lead_id, status)',
					'KEY status_created (status, created_at)',
					'KEY revenue_paid (revenue_line, status, paid_at)',
				),
				'fks'     => array(),
			),
			'rate_history'     => array(
				'soft'    => false,
				'columns' => array(
					'id'           => $id,
					'currency'     => 'varchar(8) NOT NULL',
					'usd_per_unit' => 'varchar(32) NOT NULL',
					'source'       => "varchar(12) NOT NULL DEFAULT 'manual'",
					'created_at'   => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY currency_created (currency, created_at)',
				),
				'fks'     => array(),
			),
		);
	}

	/**
	 * University domain tables.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function university(): array {
		$id       = 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$fk       = 'bigint(20) unsigned NOT NULL';
		$fkn      = 'bigint(20) unsigned DEFAULT NULL';
		$dt       = 'datetime NOT NULL';
		$dtn      = 'datetime DEFAULT NULL';
		$status   = "enum('draft','review','scheduled','published','archived') NOT NULL DEFAULT 'draft'";
		$degree   = "enum('bachelor','specialist','master','phd','residency')";
		$degree_n = $degree . ' DEFAULT NULL';
		$degree_r = $degree . ' NOT NULL';
		$lang     = "enum('ru','en','ru_en') NOT NULL";
		$approve  = "enum('approved','conditional','not_approved','unknown') NOT NULL DEFAULT 'unknown'";
		$year     = 'char(9) NOT NULL DEFAULT \'\'';
		$src      = 'varchar(255) NOT NULL DEFAULT \'\'';
		$src_url  = 'varchar(500) DEFAULT NULL';

		return array(
			'ranking_providers'      => array(
				'soft'    => true,
				'columns' => array(
					'id'         => $id,
					'code'       => 'varchar(20) NOT NULL',
					'name'       => 'varchar(100) NOT NULL',
					'website'    => 'varchar(255) DEFAULT NULL',
					'is_active'  => 'tinyint(1) NOT NULL DEFAULT 1',
					'sort_order' => 'smallint(6) NOT NULL DEFAULT 0',
					'created_at' => $dt,
					'updated_at' => $dt,
					'deleted_at' => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY code (code)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(),
			),
			'cities'                 => array(
				'soft'    => true,
				'columns' => array(
					'id'               => $id,
					'post_id'          => $fk,
					'name_fa'          => 'varchar(150) NOT NULL',
					'name_en'          => 'varchar(150) NOT NULL DEFAULT \'\'',
					'name_ru'          => 'varchar(150) NOT NULL DEFAULT \'\'',
					'slug'             => 'varchar(150) NOT NULL',
					'federal_subject'  => 'varchar(150) DEFAULT NULL',
					'latitude'         => 'decimal(10,7) DEFAULT NULL',
					'longitude'        => 'decimal(10,7) DEFAULT NULL',
					'population'       => 'int(10) unsigned DEFAULT NULL',
					'timezone'         => 'varchar(40) DEFAULT NULL',
					'living_cost_min'  => 'decimal(12,2) DEFAULT NULL',
					'living_cost_max'  => 'decimal(12,2) DEFAULT NULL',
					'currency'         => "char(3) NOT NULL DEFAULT 'RUB'",
					'climate_summary'  => 'varchar(255) DEFAULT NULL',
					'status'           => $status,
					'source'           => $src,
					'source_url'       => $src_url,
					'last_verified_at' => $dt,
					'verified_by'      => $fkn,
					'created_at'       => $dt,
					'updated_at'       => $dt,
					'deleted_at'       => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY post_id (post_id)',
					'UNIQUE KEY slug (slug)',
					'KEY status (status)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(),
			),
			'universities'           => array(
				'soft'    => true,
				'columns' => array(
					'id'                      => $id,
					'post_id'                 => $fk,
					'name_fa'                 => 'varchar(255) NOT NULL',
					'name_en'                 => 'varchar(255) NOT NULL DEFAULT \'\'',
					'name_ru'                 => 'varchar(255) NOT NULL DEFAULT \'\'',
					'short_name'              => 'varchar(50) DEFAULT NULL',
					'slug'                    => 'varchar(200) NOT NULL',
					'city_id'                 => $fkn,
					'logo_id'                 => $fkn,
					'website'                 => 'varchar(255) DEFAULT NULL',
					'founded_year'            => 'smallint(6) DEFAULT NULL',
					'address'                 => 'varchar(500) DEFAULT NULL',
					'latitude'                => 'decimal(10,7) DEFAULT NULL',
					'longitude'               => 'decimal(10,7) DEFAULT NULL',
					'ownership'               => "enum('state','private') DEFAULT NULL",
					'has_dormitory'           => 'tinyint(1) NOT NULL DEFAULT 0',
					'has_padfak'              => 'tinyint(1) NOT NULL DEFAULT 0',
					'has_direct_course'       => 'tinyint(1) NOT NULL DEFAULT 0',
					'has_scholarship'         => 'tinyint(1) NOT NULL DEFAULT 0',
					'teaching_languages'      => "set('ru','en') NOT NULL DEFAULT ''",
					'health_ministry_status'  => $approve,
					'science_ministry_status' => $approve,
					'min_tuition_usd'         => 'decimal(12,2) DEFAULT NULL',
					'best_world_rank'         => 'int(11) DEFAULT NULL',
					'is_featured'             => 'tinyint(1) NOT NULL DEFAULT 0',
					'popularity_score'        => 'int(10) unsigned NOT NULL DEFAULT 0',
					'status'                  => $status,
					'last_verified_at'        => $dtn,
					'created_at'              => $dt,
					'updated_at'              => $dt,
					'deleted_at'              => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY post_id (post_id)',
					'UNIQUE KEY slug (slug)',
					'KEY status_city (status, city_id)',
					'KEY status_flags (status, has_padfak, has_direct_course, has_dormitory, has_scholarship)',
					'KEY city_id (city_id)',
					'KEY health_ministry_status (health_ministry_status)',
					'KEY science_ministry_status (science_ministry_status)',
					'KEY min_tuition_usd (min_tuition_usd)',
					'KEY best_world_rank (best_world_rank)',
					'KEY popularity_score (popularity_score)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
					'FULLTEXT KEY name_search (name_fa, name_en, name_ru)',
				),
				'fks'     => array(
					array(
						'column'     => 'city_id',
						'ref_table'  => 'cities',
						'ref_column' => 'id',
						'name'       => 'uni_city',
					),
				),
			),
			'fields'                 => array(
				'soft'    => true,
				'columns' => array(
					'id'                     => $id,
					'post_id'                => $fk,
					'name_fa'                => 'varchar(200) NOT NULL',
					'name_en'                => 'varchar(200) NOT NULL DEFAULT \'\'',
					'name_ru'                => 'varchar(200) NOT NULL DEFAULT \'\'',
					'slug'                   => 'varchar(200) NOT NULL',
					'field_group_id'         => $fkn,
					'degree_levels'          => "set('bachelor','specialist','master','phd','residency') NOT NULL DEFAULT ''",
					'default_duration_years' => 'decimal(3,1) DEFAULT NULL',
					'languages'              => "set('ru','en') NOT NULL DEFAULT ''",
					'avg_tuition_usd'        => 'decimal(12,2) DEFAULT NULL',
					'universities_count'     => 'smallint(5) unsigned NOT NULL DEFAULT 0',
					'status'                 => $status,
					'created_at'             => $dt,
					'updated_at'             => $dt,
					'deleted_at'             => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY post_id (post_id)',
					'UNIQUE KEY slug (slug)',
					'KEY group_status (field_group_id, status)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
					'FULLTEXT KEY name_search (name_fa, name_en, name_ru)',
				),
				'fks'     => array(),
			),
			'university_fields'      => array(
				'soft'    => true,
				'columns' => array(
					'id'             => $id,
					'university_id'  => $fk,
					'field_id'       => $fk,
					'degree'         => $degree_r,
					'language'       => $lang,
					'duration_years' => 'decimal(3,1) NOT NULL DEFAULT 0.0',
					'tuition'        => 'decimal(12,2) DEFAULT NULL',
					'currency'       => "char(3) NOT NULL DEFAULT 'RUB'",
					'academic_year'  => $year,
					'status'         => "enum('active','suspended','archived') NOT NULL DEFAULT 'active'",
					'created_at'     => $dt,
					'updated_at'     => $dt,
					'deleted_at'     => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY program (university_id, field_id, degree, language)',
					'KEY field_degree (field_id, degree, language, status)',
					'KEY uni_status (university_id, status)',
					'KEY academic_year (academic_year)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'uf_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'uf_field',
					),
				),
			),
			'tuition_fees'           => array(
				'soft'    => true,
				'columns' => array(
					'id'                  => $id,
					'university_id'       => $fk,
					'field_id'            => $fkn,
					'university_field_id' => $fkn,
					'degree'              => $degree_r,
					'language'            => $lang,
					'amount'              => 'decimal(12,2) NOT NULL DEFAULT 0.00',
					'currency'            => "char(3) NOT NULL DEFAULT 'RUB'",
					'amount_usd'          => 'decimal(12,2) NOT NULL DEFAULT 0.00',
					'fx_rate_at'          => $dtn,
					'fee_period'          => "enum('year','semester','month','total') NOT NULL DEFAULT 'year'",
					'academic_year'       => $year,
					'is_current'          => 'tinyint(1) NOT NULL DEFAULT 0',
					'notes'               => 'varchar(500) DEFAULT NULL',
					'source'              => $src,
					'source_url'          => $src_url,
					'last_verified_at'    => $dt,
					'verified_by'         => $fkn,
					'created_at'          => $dt,
					'updated_at'          => $dt,
					'deleted_at'          => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY uni_year (university_id, academic_year)',
					'KEY field_degree_year (field_id, degree, academic_year)',
					'KEY current_usd (is_current, amount_usd)',
					'KEY university_field_id (university_field_id)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'tuition_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'tuition_field',
					),
					array(
						'column'     => 'university_field_id',
						'ref_table'  => 'university_fields',
						'ref_column' => 'id',
						'name'       => 'tuition_uf',
					),
				),
			),
			'dormitory_fees'         => array(
				'soft'    => true,
				'columns' => array(
					'id'               => $id,
					'university_id'    => $fk,
					'room_type'        => "enum('single','double','triple','shared','apartment') NOT NULL DEFAULT 'shared'",
					'amount_min'       => 'decimal(12,2) NOT NULL DEFAULT 0.00',
					'amount_max'       => 'decimal(12,2) DEFAULT NULL',
					'currency'         => "char(3) NOT NULL DEFAULT 'RUB'",
					'fee_period'       => "enum('month','semester','year') NOT NULL DEFAULT 'month'",
					'academic_year'    => $year,
					'includes'         => 'varchar(255) DEFAULT NULL',
					'source'           => $src,
					'source_url'       => $src_url,
					'last_verified_at' => $dt,
					'verified_by'      => $fkn,
					'created_at'       => $dt,
					'updated_at'       => $dt,
					'deleted_at'       => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY uni_year (university_id, academic_year)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'dorm_uni',
					),
				),
			),
			'university_approvals'   => array(
				'soft'    => true,
				'columns' => array(
					'id'               => $id,
					'university_id'    => $fk,
					'authority'        => "enum('health_ministry','science_ministry') NOT NULL",
					'field_id'         => $fkn,
					'academic_year'    => $year,
					'status'           => $approve,
					'document_id'      => $fkn,
					'notes'            => 'varchar(500) DEFAULT NULL',
					'source'           => $src,
					'source_url'       => $src_url,
					'last_verified_at' => $dt,
					'verified_by'      => $fkn,
					'created_at'       => $dt,
					'updated_at'       => $dt,
					'deleted_at'       => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY approval (university_id, authority, field_id, academic_year)',
					'KEY authority_status (authority, status, academic_year)',
					'KEY field_id (field_id)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'appr_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'appr_field',
					),
				),
			),
			'university_rankings'    => array(
				'soft'    => true,
				'columns' => array(
					'id'                  => $id,
					'university_id'       => $fk,
					'ranking_provider_id' => $fk,
					'scope'               => "enum('world','national','regional','subject') NOT NULL DEFAULT 'world'",
					'subject'             => 'varchar(120) DEFAULT NULL',
					'rank_value'          => 'int(11) DEFAULT NULL',
					'rank_band'           => 'varchar(20) DEFAULT NULL',
					'year'                => 'smallint(6) NOT NULL',
					'source'              => $src,
					'source_url'          => $src_url,
					'last_verified_at'    => $dt,
					'created_at'          => $dt,
					'updated_at'          => $dt,
					'deleted_at'          => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY rank_row (university_id, ranking_provider_id, scope, subject, year)',
					'KEY provider_year (ranking_provider_id, year, rank_value)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'rank_uni',
					),
					array(
						'column'     => 'ranking_provider_id',
						'ref_table'  => 'ranking_providers',
						'ref_column' => 'id',
						'name'       => 'rank_provider',
					),
				),
			),
			'admission_requirements' => array(
				'soft'    => true,
				'columns' => array(
					'id'               => $id,
					'university_id'    => $fk,
					'degree'           => $degree_n,
					'field_id'         => $fkn,
					'requirement_type' => "enum('document','exam','language','academic','age','other') NOT NULL DEFAULT 'document'",
					'title'            => 'varchar(255) NOT NULL',
					'description'      => 'text DEFAULT NULL',
					'is_mandatory'     => 'tinyint(1) NOT NULL DEFAULT 1',
					'sort_order'       => 'smallint(6) NOT NULL DEFAULT 0',
					'academic_year'    => $year,
					'source'           => $src,
					'source_url'       => $src_url,
					'last_verified_at' => $dt,
					'created_at'       => $dt,
					'updated_at'       => $dt,
					'deleted_at'       => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY uni_degree_sort (university_id, degree, sort_order)',
					'KEY field_id (field_id)',
					'KEY academic_year (academic_year)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'req_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'req_field',
					),
				),
			),
			'intakes'                => array(
				'soft'    => true,
				'columns' => array(
					'id'                   => $id,
					'university_id'        => $fk,
					'program_type'         => "enum('degree','padfak','direct_course') NOT NULL DEFAULT 'degree'",
					'degree'               => $degree_n,
					'field_id'             => $fkn,
					'intake_label'         => 'varchar(60) NOT NULL',
					'start_date'           => 'date DEFAULT NULL',
					'application_open'     => 'date DEFAULT NULL',
					'application_deadline' => 'date DEFAULT NULL',
					'academic_year'        => $year,
					'status'               => "enum('upcoming','open','closed') NOT NULL DEFAULT 'upcoming'",
					'source'               => $src,
					'source_url'           => $src_url,
					'last_verified_at'     => $dt,
					'created_at'           => $dt,
					'updated_at'           => $dt,
					'deleted_at'           => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY deadline_status (application_deadline, status)',
					'KEY uni_year (university_id, academic_year)',
					'KEY field_id (field_id)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'intake_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'intake_field',
					),
				),
			),
			'prep_programs'          => array(
				'soft'    => true,
				'columns' => array(
					'id'               => $id,
					'university_id'    => $fk,
					'program_type'     => "enum('padfak','direct_course') NOT NULL",
					'track'            => "enum('medical','engineering','humanities','economics','general') NOT NULL DEFAULT 'general'",
					'format'           => "enum('onsite','online','hybrid') NOT NULL DEFAULT 'onsite'",
					'duration_months'  => 'tinyint(3) unsigned NOT NULL DEFAULT 0',
					'tuition'          => 'decimal(12,2) DEFAULT NULL',
					'currency'         => "char(3) NOT NULL DEFAULT 'RUB'",
					'academic_year'    => $year,
					'start_dates'      => 'varchar(120) DEFAULT NULL',
					'requirements'     => 'text DEFAULT NULL',
					'status'           => "enum('active','suspended','archived') NOT NULL DEFAULT 'active'",
					'source'           => $src,
					'source_url'       => $src_url,
					'last_verified_at' => $dt,
					'verified_by'      => $fkn,
					'created_at'       => $dt,
					'updated_at'       => $dt,
					'deleted_at'       => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY type_track (program_type, track, format, status)',
					'KEY university_id (university_id)',
					'KEY academic_year (academic_year)',
					'KEY last_verified_at (last_verified_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'prep_uni',
					),
				),
			),
		);
	}

	/**
	 * Language-course tables, including phase-2 quizzes.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function education(): array {
		$id  = 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$fk  = 'bigint(20) unsigned NOT NULL';
		$fkn = 'bigint(20) unsigned DEFAULT NULL';
		$dt  = 'datetime NOT NULL';
		$dtn = 'datetime DEFAULT NULL';

		return array(
			'lesson_vocab'   => array(
				'soft'    => false,
				'columns' => array(
					'id'              => $id,
					'lesson_id'       => $fk,
					'word_ru'         => 'varchar(150) NOT NULL',
					'transliteration' => 'varchar(150) DEFAULT NULL',
					'meaning_fa'      => 'varchar(255) NOT NULL',
					'example_ru'      => 'varchar(500) DEFAULT NULL',
					'example_fa'      => 'varchar(500) DEFAULT NULL',
					'audio_id'        => $fkn,
					'sort_order'      => 'smallint(6) NOT NULL DEFAULT 0',
					'created_at'      => $dt,
					'updated_at'      => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lesson_sort (lesson_id, sort_order)',
					'KEY word_ru (word_ru)',
					'KEY created_at (created_at)',
				),
				'fks'     => array(),
			),
			'quizzes'        => array(
				'soft'    => true,
				'columns' => array(
					'id'             => $id,
					'lesson_id'      => $fkn,
					'course_id'      => $fkn,
					'title'          => 'varchar(255) NOT NULL',
					'pass_score'     => 'tinyint(3) unsigned NOT NULL DEFAULT 0',
					'time_limit_sec' => 'smallint(5) unsigned DEFAULT NULL',
					'status'         => "enum('draft','published','archived') NOT NULL DEFAULT 'draft'",
					'created_at'     => $dt,
					'updated_at'     => $dt,
					'deleted_at'     => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lesson_id (lesson_id)',
					'KEY course_id (course_id)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(),
			),
			'quiz_questions' => array(
				'soft'    => false,
				'columns' => array(
					'id'             => $id,
					'quiz_id'        => $fk,
					'question_type'  => "enum('single','multiple','text','audio') NOT NULL DEFAULT 'single'",
					'question'       => 'text NOT NULL',
					'options'        => 'json DEFAULT NULL',
					'correct_answer' => 'json NOT NULL',
					'explanation'    => 'text DEFAULT NULL',
					'media_id'       => $fkn,
					'sort_order'     => 'smallint(6) NOT NULL DEFAULT 0',
					'created_at'     => $dt,
					'updated_at'     => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY quiz_sort (quiz_id, sort_order)',
					'KEY created_at (created_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'quiz_id',
						'ref_table'  => 'quizzes',
						'ref_column' => 'id',
						'name'       => 'question_quiz',
					),
				),
			),
		);
	}

	/**
	 * Services and CRM tables.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function crm(): array {
		$id     = 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$fk     = 'bigint(20) unsigned NOT NULL';
		$fkn    = 'bigint(20) unsigned DEFAULT NULL';
		$dt     = 'datetime NOT NULL';
		$dtn    = 'datetime DEFAULT NULL';
		$lead   = "enum('new','contacted','qualified','follow_up','documents','contract','paid','completed','lost')";
		$degree = "enum('bachelor','specialist','master','phd','residency') DEFAULT NULL";

		return array(
			'services'             => array(
				'soft'    => true,
				'columns' => array(
					'id'              => $id,
					'parent_id'       => $fkn,
					'slug'            => 'varchar(100) NOT NULL',
					'name_fa'         => 'varchar(150) NOT NULL',
					'service_group'   => "enum('education','language','migration','exchange','cargo','trade','general') NOT NULL DEFAULT 'general'",
					'landing_page_id' => $fkn,
					'form_type'       => 'varchar(40) NOT NULL DEFAULT \'consult\'',
					'request_table'   => 'varchar(40) DEFAULT NULL',
					'operator_role'   => 'varchar(40) DEFAULT NULL',
					'is_active'       => 'tinyint(1) NOT NULL DEFAULT 1',
					'sort_order'      => 'smallint(6) NOT NULL DEFAULT 0',
					'created_at'      => $dt,
					'updated_at'      => $dt,
					'deleted_at'      => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY slug (slug)',
					'KEY parent_sort (parent_id, sort_order)',
					'KEY service_group (service_group)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'parent_id',
						'ref_table'  => 'services',
						'ref_column' => 'id',
						'name'       => 'service_parent',
					),
				),
			),
			'leads'                => array(
				'soft'    => true,
				'columns' => array(
					'id'                  => $id,
					'lead_code'           => 'varchar(20) NOT NULL',
					'name'                => 'varchar(150) NOT NULL',
					'phone'               => 'varchar(32) NOT NULL',
					'phone_normalized'    => 'varchar(20) NOT NULL DEFAULT \'\'',
					'email'               => 'varchar(190) DEFAULT NULL',
					'whatsapp'            => 'varchar(32) DEFAULT NULL',
					'telegram'            => 'varchar(64) DEFAULT NULL',
					'service_id'          => $fk,
					'subservice_id'       => $fkn,
					'form_type'           => 'varchar(40) NOT NULL DEFAULT \'consult\'',
					'country'             => 'varchar(80) DEFAULT NULL',
					'city'                => 'varchar(100) DEFAULT NULL',
					'message'             => 'text DEFAULT NULL',
					'lang'                => "varchar(5) NOT NULL DEFAULT 'fa'",
					'source'              => 'varchar(40) NOT NULL DEFAULT \'website_form\'',
					'landing_page'        => 'varchar(500) NOT NULL DEFAULT \'\'',
					'landing_object_type' => 'varchar(20) DEFAULT NULL',
					'landing_object_id'   => $fkn,
					'referrer'            => 'varchar(500) DEFAULT NULL',
					'utm_source'          => 'varchar(150) DEFAULT NULL',
					'utm_medium'          => 'varchar(150) DEFAULT NULL',
					'utm_campaign'        => 'varchar(150) DEFAULT NULL',
					'utm_content'         => 'varchar(150) DEFAULT NULL',
					'utm_term'            => 'varchar(150) DEFAULT NULL',
					'gclid'               => 'varchar(255) DEFAULT NULL',
					'consultant_id'       => $fkn,
					'assigned_by'         => $fkn,
					'assigned_at'         => $dtn,
					'status'              => $lead . " NOT NULL DEFAULT 'new'",
					'priority'            => "enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal'",
					'next_follow_up_at'   => $dtn,
					'first_response_at'   => $dtn,
					'sla_due_at'          => $dtn,
					'sla_escalated_at'    => $dtn,
					'sla_overdue'         => 'tinyint(1) NOT NULL DEFAULT 0',
					'closed_at'           => $dtn,
					'lost_reason'         => 'varchar(120) DEFAULT NULL',
					'duplicate_of'        => $fkn,
					'consent'             => 'tinyint(1) NOT NULL DEFAULT 0',
					'ip'                  => 'varbinary(16) DEFAULT NULL',
					'user_agent'          => 'varchar(255) DEFAULT NULL',
					'legacy_post_id'      => $fkn,
					'created_at'          => $dt,
					'updated_at'          => $dt,
					'deleted_at'          => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'UNIQUE KEY lead_code (lead_code)',
					'KEY status_created (status, created_at)',
					'KEY consultant_status (consultant_id, status)',
					'KEY service_created (service_id, created_at)',
					'KEY source_created (source, created_at)',
					'KEY utm_created (utm_source, created_at)',
					'KEY landing_object (landing_object_type, landing_object_id)',
					'KEY phone_normalized (phone_normalized)',
					'KEY email (email)',
					'KEY subservice_id (subservice_id)',
					'KEY duplicate_of (duplicate_of)',
					'KEY legacy_post_id (legacy_post_id)',
					'KEY next_follow_up_at (next_follow_up_at)',
					'KEY sla_due_at (sla_due_at)',
					'KEY closed_at (closed_at)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'service_id',
						'ref_table'  => 'services',
						'ref_column' => 'id',
						'name'       => 'lead_service',
					),
					array(
						'column'     => 'subservice_id',
						'ref_table'  => 'services',
						'ref_column' => 'id',
						'name'       => 'lead_subservice',
					),
					array(
						'column'     => 'duplicate_of',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'lead_duplicate',
					),
				),
			),
			'lead_notes'           => array(
				'soft'    => true,
				'columns' => array(
					'id'         => $id,
					'lead_id'    => $fk,
					'user_id'    => $fk,
					'note'       => 'text NOT NULL',
					'is_pinned'  => 'tinyint(1) NOT NULL DEFAULT 0',
					'created_at' => $dt,
					'updated_at' => $dt,
					'deleted_at' => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_created (lead_id, created_at)',
					'KEY user_id (user_id)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'note_lead',
					),
				),
			),
			'lead_tasks'           => array(
				'soft'    => true,
				'columns' => array(
					'id'           => $id,
					'lead_id'      => $fk,
					'title'        => 'varchar(255) NOT NULL',
					'description'  => 'text DEFAULT NULL',
					'task_type'    => "enum('call','whatsapp','email','meeting','document','other') NOT NULL DEFAULT 'other'",
					'assigned_to'  => $fk,
					'due_at'       => $dt,
					'reminder_at'  => $dtn,
					'status'       => "enum('open','done','cancelled') NOT NULL DEFAULT 'open'",
					'priority'     => "enum('low','normal','high') NOT NULL DEFAULT 'normal'",
					'completed_at' => $dtn,
					'created_by'   => $fkn,
					'created_at'   => $dt,
					'updated_at'   => $dt,
					'deleted_at'   => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY assignee (assigned_to, status, due_at)',
					'KEY lead_id (lead_id)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'task_lead',
					),
				),
			),
			'lead_files'           => array(
				'soft'    => true,
				'columns' => array(
					'id'            => $id,
					'lead_id'       => $fk,
					'doc_type'      => "enum('passport','diploma','transcript','photo','invoice','cargo_doc','trade_doc','contract','other') NOT NULL DEFAULT 'other'",
					'original_name' => 'varchar(255) NOT NULL',
					'stored_path'   => 'varchar(500) NOT NULL',
					'mime_type'     => 'varchar(100) NOT NULL',
					'file_size'     => 'int(10) unsigned NOT NULL DEFAULT 0',
					'checksum'      => 'char(64) NOT NULL',
					'uploaded_by'   => $fkn,
					'purge_after'   => 'date DEFAULT NULL',
					'purged_at'     => $dtn,
					'created_at'    => $dt,
					'deleted_at'    => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY purge_after (purge_after)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'file_lead',
					),
				),
			),
			'lead_status_history'  => array(
				'soft'    => false,
				'columns' => array(
					'id'          => $id,
					'lead_id'     => $fk,
					'from_status' => $lead . ' DEFAULT NULL',
					'to_status'   => $lead . ' NOT NULL',
					'changed_by'  => $fkn,
					'reason'      => 'varchar(255) DEFAULT NULL',
					'created_at'  => $dt,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_created (lead_id, created_at)',
					'KEY to_status_created (to_status, created_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'history_lead',
					),
				),
			),
			'admission_requests'   => array(
				'soft'    => true,
				'columns' => array(
					'id'                => $id,
					'lead_id'           => $fk,
					'program_type'      => "enum('degree','padfak','direct_course','scholarship') NOT NULL DEFAULT 'degree'",
					'university_id'     => $fkn,
					'field_id'          => $fkn,
					'degree'            => $degree,
					'language'          => "enum('ru','en','ru_en') DEFAULT NULL",
					'intake_id'         => $fkn,
					'preferred_city_id' => $fkn,
					'current_education' => 'varchar(60) DEFAULT NULL',
					'gpa'               => 'decimal(4,2) DEFAULT NULL',
					'graduation_year'   => 'smallint(6) DEFAULT NULL',
					'birth_year'        => 'smallint(6) DEFAULT NULL',
					'passport_status'   => "enum('has','applying','none') DEFAULT NULL",
					'russian_level'     => 'varchar(10) DEFAULT NULL',
					'budget_amount'     => 'decimal(12,2) DEFAULT NULL',
					'budget_currency'   => 'char(3) DEFAULT NULL',
					'stage'             => "enum('new','collecting_docs','submitted','offer','invitation','visa','enrolled','rejected','cancelled') NOT NULL DEFAULT 'new'",
					'application_ref'   => 'varchar(60) DEFAULT NULL',
					'created_at'        => $dt,
					'updated_at'        => $dt,
					'deleted_at'        => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY stage_created (stage, created_at)',
					'KEY university_id (university_id)',
					'KEY field_id (field_id)',
					'KEY intake_id (intake_id)',
					'KEY preferred_city_id (preferred_city_id)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'adm_lead',
					),
					array(
						'column'     => 'university_id',
						'ref_table'  => 'universities',
						'ref_column' => 'id',
						'name'       => 'adm_uni',
					),
					array(
						'column'     => 'field_id',
						'ref_table'  => 'fields',
						'ref_column' => 'id',
						'name'       => 'adm_field',
					),
					array(
						'column'     => 'intake_id',
						'ref_table'  => 'intakes',
						'ref_column' => 'id',
						'name'       => 'adm_intake',
					),
					array(
						'column'     => 'preferred_city_id',
						'ref_table'  => 'cities',
						'ref_column' => 'id',
						'name'       => 'adm_city',
					),
				),
			),
			'exchange_requests'    => array(
				'soft'    => true,
				'columns' => array(
					'id'                => $id,
					'lead_id'           => $fk,
					'amount'            => 'decimal(18,2) NOT NULL DEFAULT 0.00',
					'currency_from'     => "char(3) NOT NULL DEFAULT 'IRR'",
					'currency_to'       => "char(3) NOT NULL DEFAULT 'RUB'",
					'city'              => 'varchar(100) DEFAULT NULL',
					'purpose'           => "enum('tuition','dormitory','transfer','personal','business','other') NOT NULL DEFAULT 'other'",
					'description'       => 'text DEFAULT NULL',
					'quoted_rate'       => 'decimal(18,6) DEFAULT NULL',
					'quoted_amount'     => 'decimal(18,2) DEFAULT NULL',
					'quoted_at'         => $dtn,
					'quote_valid_until' => $dtn,
					'operator_id'       => $fkn,
					'stage'             => "enum('new','quoted','accepted','in_progress','done','cancelled') NOT NULL DEFAULT 'new'",
					'created_at'        => $dt,
					'updated_at'        => $dt,
					'deleted_at'        => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY stage_created (stage, created_at)',
					'KEY operator_stage (operator_id, stage)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'ex_lead',
					),
				),
			),
			'cargo_requests'       => array(
				'soft'    => true,
				'columns' => array(
					'id'                  => $id,
					'lead_id'             => $fk,
					'direction'           => "enum('ir_to_ru','ru_to_ir') NOT NULL",
					'cargo_type'          => "enum('documents','personal_items','samples','commercial') NOT NULL DEFAULT 'documents'",
					'origin_country'      => "char(2) NOT NULL DEFAULT ''",
					'origin_city'         => 'varchar(100) NOT NULL DEFAULT \'\'',
					'destination_country' => "char(2) NOT NULL DEFAULT ''",
					'destination_city'    => 'varchar(100) NOT NULL DEFAULT \'\'',
					'weight_kg'           => 'decimal(10,2) DEFAULT NULL',
					'length_cm'           => 'decimal(8,1) DEFAULT NULL',
					'width_cm'            => 'decimal(8,1) DEFAULT NULL',
					'height_cm'           => 'decimal(8,1) DEFAULT NULL',
					'pieces'              => 'smallint(5) unsigned DEFAULT NULL',
					'declared_value'      => 'decimal(14,2) DEFAULT NULL',
					'currency'            => "char(3) NOT NULL DEFAULT 'USD'",
					'description'         => 'text DEFAULT NULL',
					'quoted_price'        => 'decimal(14,2) DEFAULT NULL',
					'tracking_code'       => 'varchar(60) DEFAULT NULL',
					'operator_id'         => $fkn,
					'stage'               => "enum('new','quoted','confirmed','picked_up','in_transit','customs','delivered','cancelled') NOT NULL DEFAULT 'new'",
					'created_at'          => $dt,
					'updated_at'          => $dt,
					'deleted_at'          => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY stage_created (stage, created_at)',
					'KEY operator_stage (operator_id, stage)',
					'KEY cargo_type (cargo_type)',
					'KEY tracking_code (tracking_code)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'cargo_lead',
					),
				),
			),
			'immigration_requests' => array(
				'soft'    => true,
				'columns' => array(
					'id'           => $id,
					'lead_id'      => $fk,
					'request_type' => "enum('visa','residency','registration','work','deportation','entry-ban') NOT NULL DEFAULT 'visa'",
					'nationality'  => 'varchar(80) NOT NULL DEFAULT \'\'',
					'current_city' => 'varchar(100) NOT NULL DEFAULT \'\'',
					'visa_status'  => 'varchar(80) NOT NULL DEFAULT \'\'',
					'expiry_date'  => 'date DEFAULT NULL',
					'documents'    => 'text',
					'operator_id'  => $fkn,
					'stage'        => "enum('new','reviewing','documents','in_progress','done','cancelled') NOT NULL DEFAULT 'new'",
					'created_at'   => $dt,
					'updated_at'   => $dt,
					'deleted_at'   => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY stage_created (stage, created_at)',
					'KEY operator_stage (operator_id, stage)',
					'KEY request_type (request_type)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'imm_lead',
					),
				),
			),
			'trade_requests'       => array(
				'soft'    => true,
				'columns' => array(
					'id'                  => $id,
					'lead_id'             => $fk,
					'request_type'        => "enum('sourcing','supplier_verification','purchase_coordination','export','import','sample_inspection','document_review','shipping_coordination','b2b_matchmaking') NOT NULL DEFAULT 'sourcing'",
					'direction'           => "enum('ir_to_ru','ru_to_ir') NOT NULL",
					'product_category'    => 'varchar(150) NOT NULL DEFAULT \'\'',
					'product_description' => 'text DEFAULT NULL',
					'hs_code'             => 'varchar(12) DEFAULT NULL',
					'quantity'            => 'decimal(14,2) DEFAULT NULL',
					'unit'                => 'varchar(20) DEFAULT NULL',
					'target_price'        => 'decimal(14,2) DEFAULT NULL',
					'currency'            => "char(3) NOT NULL DEFAULT 'USD'",
					'incoterm'            => 'char(3) DEFAULT NULL',
					'company_name'        => 'varchar(200) DEFAULT NULL',
					'company_country'     => 'char(2) DEFAULT NULL',
					'timeline'            => 'varchar(100) DEFAULT NULL',
					'operator_id'         => $fkn,
					'stage'               => "enum('new','reviewing','sourcing','proposal_sent','negotiating','in_progress','done','cancelled') NOT NULL DEFAULT 'new'",
					'created_at'          => $dt,
					'updated_at'          => $dt,
					'deleted_at'          => $dtn,
				),
				'primary' => 'id',
				'indexes' => array(
					'KEY lead_id (lead_id)',
					'KEY stage_created (stage, created_at)',
					'KEY operator_stage (operator_id, stage)',
					'KEY request_type (request_type)',
					'KEY created_at (created_at)',
					'KEY deleted_at (deleted_at)',
				),
				'fks'     => array(
					array(
						'column'     => 'lead_id',
						'ref_table'  => 'leads',
						'ref_column' => 'id',
						'name'       => 'trade_lead',
					),
				),
			),
		);
	}
}
