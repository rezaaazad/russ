<?php
/**
 * Academy tables. Suffixes are prefixed with lr_ by the migrator.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom tables for the education line. Every row has a stable id, sort where it is ordered, status, and timestamps.
 */
class Schema {

	/**
	 * Definitions in dependency order.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function tables(): array {
		$id  = 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
		$fk  = 'bigint(20) unsigned NOT NULL';
		$fkn = 'bigint(20) unsigned DEFAULT NULL';
		$dt  = 'datetime NOT NULL';
		$dtn = 'datetime DEFAULT NULL';
		$st  = "enum('draft','published','archived') NOT NULL DEFAULT 'draft'";
		$on  = "enum('draft','published') NOT NULL DEFAULT 'draft'";

		return array(
			'academy_course_categories'  => self::def(
				array(
					'id'          => $id,
					'slug'        => 'varchar(80) NOT NULL',
					'title'       => 'varchar(190) NOT NULL',
					'description' => 'text',
					'sort_order'  => 'int(11) NOT NULL DEFAULT 0',
					'status'      => $on,
					'created_at'  => $dt,
					'updated_at'  => $dt,
				),
				array( 'UNIQUE KEY slug (slug)', 'KEY status_sort (status, sort_order)' )
			),
			'academy_instructors'        => self::def(
				array(
					'id'         => $id,
					'user_id'    => $fkn,
					'slug'       => 'varchar(80) NOT NULL',
					'name'       => 'varchar(190) NOT NULL',
					'bio'        => 'text',
					'photo'      => 'varchar(500) NOT NULL DEFAULT \'\'',
					'sort_order' => 'int(11) NOT NULL DEFAULT 0',
					'status'     => $on,
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'UNIQUE KEY slug (slug)', 'UNIQUE KEY user_id (user_id)', 'KEY status_sort (status, sort_order)' )
			),
			'academy_courses'            => self::def(
				array(
					'id'                       => $id,
					'slug'                     => 'varchar(120) NOT NULL',
					'title'                    => 'varchar(190) NOT NULL',
					'description'              => 'longtext',
					'price'                    => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'discount_price'           => $fkn,
					'currency'                 => "char(3) NOT NULL DEFAULT 'IRT'",
					'instructor_id'            => $fkn,
					'category_id'              => $fkn,
					'level'                    => 'varchar(40) NOT NULL DEFAULT \'\'',
					'duration'                 => 'varchar(40) NOT NULL DEFAULT \'\'',
					'thumbnail'                => 'varchar(500) NOT NULL DEFAULT \'\'',
					'intro_video'              => 'varchar(500) NOT NULL DEFAULT \'\'',
					'intro_provider'           => "varchar(20) NOT NULL DEFAULT ''",
					'status'                   => $st,
					'is_free'                  => 'tinyint(1) NOT NULL DEFAULT 0',
					'is_featured'              => 'tinyint(1) NOT NULL DEFAULT 0',
					'tier'                     => "enum('standard','premium') NOT NULL DEFAULT 'standard'",
					'included_in_subscription' => 'tinyint(1) NOT NULL DEFAULT 0',
					'certificate_enabled'      => 'tinyint(1) NOT NULL DEFAULT 0',
					'seo_title'                => 'varchar(190) NOT NULL DEFAULT \'\'',
					'seo_description'          => 'varchar(300) NOT NULL DEFAULT \'\'',
					'published_at'             => $dtn,
					'sort_order'               => 'int(11) NOT NULL DEFAULT 0',
					'legacy_post_id'           => $fkn,
					'created_at'               => $dt,
					'updated_at'               => $dt,
				),
				array(
					'UNIQUE KEY slug (slug)',
					'KEY status_sort (status, sort_order)',
					'KEY category_id (category_id)',
					'KEY instructor_id (instructor_id)',
					'KEY featured (is_featured, status)',
				),
				array(
					self::fk( 'ac_course_inst', 'instructor_id', 'academy_instructors' ),
					self::fk( 'ac_course_cat', 'category_id', 'academy_course_categories' ),
				)
			),
			'academy_course_modules'     => self::def(
				array(
					'id'         => $id,
					'course_id'  => $fk,
					'title'      => 'varchar(190) NOT NULL',
					'summary'    => 'text',
					'sort_order' => 'int(11) NOT NULL DEFAULT 0',
					'status'     => $on,
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'KEY course_sort (course_id, sort_order)' ),
				array( self::fk( 'ac_mod_course', 'course_id', 'academy_courses' ) )
			),
			'academy_course_lessons'     => self::def(
				array(
					'id'               => $id,
					'module_id'        => $fk,
					'slug'             => 'varchar(120) NOT NULL',
					'title'            => 'varchar(190) NOT NULL',
					'content'          => 'longtext',
					'type'             => "enum('video','text','quiz','live') NOT NULL DEFAULT 'video'",
					'sort_order'       => 'int(11) NOT NULL DEFAULT 0',
					'status'           => $on,
					'is_preview'       => 'tinyint(1) NOT NULL DEFAULT 0',
					'duration_seconds' => 'int(10) unsigned NOT NULL DEFAULT 0',
					'legacy_post_id'   => $fkn,
					'created_at'       => $dt,
					'updated_at'       => $dt,
				),
				array(
					'UNIQUE KEY module_slug (module_id, slug)',
					'KEY legacy_post_id (legacy_post_id)',
					'KEY module_sort (module_id, sort_order)',
				),
				array( self::fk( 'ac_les_mod', 'module_id', 'academy_course_modules' ) )
			),
			'academy_lesson_videos'      => self::def(
				array(
					'id'          => $id,
					'lesson_id'   => $fk,
					'provider'    => "enum('arvan_vod','bunny','aparat','youtube','upload') NOT NULL DEFAULT 'arvan_vod'",
					'external_id' => 'varchar(190) NOT NULL DEFAULT \'\'',
					'duration'    => 'int(10) unsigned NOT NULL DEFAULT 0',
					'poster'      => 'varchar(500) NOT NULL DEFAULT \'\'',
					'sort_order'  => 'int(11) NOT NULL DEFAULT 0',
					'status'      => $on,
					'created_at'  => $dt,
					'updated_at'  => $dt,
				),
				array( 'KEY lesson_sort (lesson_id, sort_order)' ),
				array( self::fk( 'ac_vid_les', 'lesson_id', 'academy_course_lessons' ) )
			),
			'academy_lesson_attachments' => self::def(
				array(
					'id'           => $id,
					'lesson_id'    => $fk,
					'title'        => 'varchar(190) NOT NULL',
					'storage_path' => 'varchar(500) NOT NULL',
					'mime'         => 'varchar(80) NOT NULL DEFAULT \'application/pdf\'',
					'bytes'        => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'sort_order'   => 'int(11) NOT NULL DEFAULT 0',
					'status'       => $on,
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array( 'KEY lesson_sort (lesson_id, sort_order)' ),
				array( self::fk( 'ac_att_les', 'lesson_id', 'academy_course_lessons' ) )
			),
			'academy_students'           => self::def(
				array(
					'id'           => $id,
					'user_id'      => $fk,
					'phone'        => 'varchar(20) NOT NULL DEFAULT \'\'',
					'display_name' => 'varchar(190) NOT NULL DEFAULT \'\'',
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array( 'UNIQUE KEY user_id (user_id)' )
			),
			'academy_subscription_plans' => self::def(
				array(
					'id'               => $id,
					'slug'             => 'varchar(80) NOT NULL',
					'title'            => 'varchar(190) NOT NULL',
					'description'      => 'text',
					'price'            => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'billing_interval' => "enum('month','year') NOT NULL DEFAULT 'month'",
					'tier'             => "enum('standard','premium') NOT NULL DEFAULT 'standard'",
					'sort_order'       => 'int(11) NOT NULL DEFAULT 0',
					'status'           => $on,
					'created_at'       => $dt,
					'updated_at'       => $dt,
				),
				array( 'UNIQUE KEY slug (slug)', 'KEY status_sort (status, sort_order)' )
			),
			'academy_coupons'            => self::def(
				array(
					'id'           => $id,
					'code'         => 'varchar(40) NOT NULL',
					'type'         => "enum('percent','fixed') NOT NULL DEFAULT 'percent'",
					'amount'       => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'scope'        => "enum('all','courses','plans') NOT NULL DEFAULT 'all'",
					'scope_ids'    => 'text',
					'max_uses'     => 'int(10) unsigned NOT NULL DEFAULT 0',
					'max_per_user' => 'int(10) unsigned NOT NULL DEFAULT 0',
					'used_count'   => 'int(10) unsigned NOT NULL DEFAULT 0',
					'expires_at'   => $dtn,
					'status'       => $on,
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array( 'UNIQUE KEY code (code)', 'KEY status (status)' )
			),
			'academy_orders'             => self::def(
				array(
					'id'         => $id,
					'student_id' => $fkn,
					'user_id'    => $fk,
					'code'       => 'varchar(20) NOT NULL',
					'status'     => "enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending'",
					'currency'   => "char(3) NOT NULL DEFAULT 'IRT'",
					'subtotal'   => 'bigint(20) NOT NULL DEFAULT 0',
					'discount'   => 'bigint(20) NOT NULL DEFAULT 0',
					'total'      => 'bigint(20) NOT NULL DEFAULT 0',
					'coupon_id'  => $fkn,
					'paid_at'    => $dtn,
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'UNIQUE KEY code (code)', 'KEY user_status (user_id, status)', 'KEY status_paid (status, paid_at)' )
			),
			'academy_order_items'        => self::def(
				array(
					'id'           => $id,
					'order_id'     => $fk,
					'item_type'    => "enum('course','plan','private_class','group_class','bundle') NOT NULL",
					'item_id'      => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'title'        => 'varchar(190) NOT NULL',
					'amount_toman' => 'bigint(20) NOT NULL DEFAULT 0',
					'meta'         => 'longtext',
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array( 'KEY order_id (order_id)', 'KEY item (item_type, item_id)' ),
				array( self::fk( 'ac_item_order', 'order_id', 'academy_orders' ) )
			),
			'academy_payments'           => self::def(
				array(
					'id'           => $id,
					'order_id'     => $fk,
					'token'        => 'char(32) NOT NULL',
					'amount_toman' => 'bigint(20) NOT NULL DEFAULT 0',
					'revenue_line' => "varchar(20) NOT NULL DEFAULT 'academy'",
					'status'       => "enum('pending','paid','failed','expired','cancelled','refund') NOT NULL DEFAULT 'pending'",
					'gateway'      => "varchar(20) NOT NULL DEFAULT 'zarinpal'",
					'authority'    => 'varchar(64) NOT NULL DEFAULT \'\'',
					'ref_id'       => 'varchar(64) NOT NULL DEFAULT \'\'',
					'reason'       => 'varchar(255) NOT NULL DEFAULT \'\'',
					'refund_of'    => $fkn,
					'paid_at'      => $dtn,
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array(
					'UNIQUE KEY token (token)',
					'KEY order_status (order_id, status)',
					'KEY line_paid (revenue_line, status, paid_at)',
				),
				array( self::fk( 'ac_pay_order', 'order_id', 'academy_orders' ) )
			),
			'academy_coupon_redemptions' => self::def(
				array(
					'id'         => $id,
					'coupon_id'  => $fk,
					'order_id'   => $fk,
					'user_id'    => $fk,
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'KEY coupon_user (coupon_id, user_id)', 'KEY order_id (order_id)' ),
				array( self::fk( 'ac_red_coupon', 'coupon_id', 'academy_coupons' ) )
			),
			'academy_subscriptions'      => self::def(
				array(
					'id'          => $id,
					'student_id'  => $fk,
					'plan_id'     => $fk,
					'order_id'    => $fkn,
					'status'      => "enum('active','past_due','expired','cancelled') NOT NULL DEFAULT 'active'",
					'starts_at'   => $dt,
					'ends_at'     => $dt,
					'grace_until' => $dt,
					'reminded_at' => $dtn,
					'created_at'  => $dt,
					'updated_at'  => $dt,
				),
				array( 'KEY student_status (student_id, status)', 'KEY ends_at (ends_at)' ),
				array(
					self::fk( 'ac_sub_plan', 'plan_id', 'academy_subscription_plans' ),
					self::fk( 'ac_sub_student', 'student_id', 'academy_students' ),
				)
			),
			'academy_enrollments'        => self::def(
				array(
					'id'         => $id,
					'student_id' => $fk,
					'course_id'  => $fk,
					'order_id'   => $fkn,
					'source'     => "enum('purchase','subscription','free','bundle','migration') NOT NULL DEFAULT 'purchase'",
					'tier'       => "enum('standard','premium') NOT NULL DEFAULT 'standard'",
					'status'     => "enum('active','revoked') NOT NULL DEFAULT 'active'",
					'granted_at' => $dt,
					'revoked_at' => $dtn,
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'UNIQUE KEY student_course (student_id, course_id)', 'KEY course_id (course_id)', 'KEY order_id (order_id)' ),
				array(
					self::fk( 'ac_enr_stu', 'student_id', 'academy_students' ),
					self::fk( 'ac_enr_course', 'course_id', 'academy_courses' ),
				)
			),
			'academy_course_progress'    => self::def(
				array(
					'id'               => $id,
					'student_id'       => $fk,
					'course_id'        => $fk,
					'lesson_id'        => $fk,
					'completed'        => 'tinyint(1) NOT NULL DEFAULT 0',
					'progress_percent' => 'tinyint(3) unsigned NOT NULL DEFAULT 0',
					'last_position'    => 'int(10) unsigned NOT NULL DEFAULT 0',
					'created_at'       => $dt,
					'updated_at'       => $dt,
				),
				array( 'UNIQUE KEY student_lesson (student_id, lesson_id)', 'KEY course_student (course_id, student_id)', 'KEY updated_at (updated_at)' ),
				array( self::fk( 'ac_prog_les', 'lesson_id', 'academy_course_lessons' ) )
			),
			'academy_quizzes'            => self::def(
				array(
					'id'           => $id,
					'scope_type'   => "enum('course','module','lesson') NOT NULL",
					'scope_id'     => $fk,
					'title'        => 'varchar(190) NOT NULL',
					'pass_percent' => 'tinyint(3) unsigned NOT NULL DEFAULT 70',
					'sort_order'   => 'int(11) NOT NULL DEFAULT 0',
					'status'       => $on,
					'created_at'   => $dt,
					'updated_at'   => $dt,
				),
				array( 'KEY scope (scope_type, scope_id, sort_order)' )
			),
			'academy_quiz_questions'     => self::def(
				array(
					'id'         => $id,
					'quiz_id'    => $fk,
					'type'       => "enum('choice','match','fill') NOT NULL DEFAULT 'choice'",
					'prompt'     => 'text',
					'payload'    => 'longtext',
					'sort_order' => 'int(11) NOT NULL DEFAULT 0',
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'KEY quiz_sort (quiz_id, sort_order)' ),
				array( self::fk( 'ac_qq_quiz', 'quiz_id', 'academy_quizzes' ) )
			),
			'academy_quiz_results'       => self::def(
				array(
					'id'         => $id,
					'quiz_id'    => $fk,
					'student_id' => $fk,
					'score'      => 'smallint(5) unsigned NOT NULL DEFAULT 0',
					'max_score'  => 'smallint(5) unsigned NOT NULL DEFAULT 0',
					'passed'     => 'tinyint(1) NOT NULL DEFAULT 0',
					'detail'     => 'text',
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'KEY quiz_student (quiz_id, student_id)', 'KEY student_id (student_id)' ),
				array( self::fk( 'ac_qr_quiz', 'quiz_id', 'academy_quizzes' ) )
			),
			'academy_class_sessions'     => self::def(
				array(
					'id'            => $id,
					'instructor_id' => $fkn,
					'kind'          => "enum('private','group') NOT NULL DEFAULT 'group'",
					'title'         => 'varchar(190) NOT NULL',
					'starts_at'     => $dt,
					'ends_at'       => $dtn,
					'capacity'      => 'int(10) unsigned NOT NULL DEFAULT 1',
					'price'         => 'bigint(20) unsigned NOT NULL DEFAULT 0',
					'meeting_url'   => 'varchar(500) NOT NULL DEFAULT \'\'',
					'sort_order'    => 'int(11) NOT NULL DEFAULT 0',
					'status'        => "enum('open','full','cancelled','done') NOT NULL DEFAULT 'open'",
					'created_at'    => $dt,
					'updated_at'    => $dt,
				),
				array( 'KEY kind_start (kind, starts_at)', 'KEY instructor_id (instructor_id)', 'KEY status (status)' )
			),
			'academy_class_bookings'     => self::def(
				array(
					'id'         => $id,
					'session_id' => $fk,
					'student_id' => $fk,
					'order_id'   => $fkn,
					'status'     => "enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'",
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'KEY session_status (session_id, status)', 'KEY student_id (student_id)', 'KEY order_id (order_id)' ),
				array( self::fk( 'ac_book_sess', 'session_id', 'academy_class_sessions' ) )
			),
			'academy_certificates'       => self::def(
				array(
					'id'          => $id,
					'student_id'  => $fk,
					'course_id'   => $fk,
					'code'        => 'varchar(32) NOT NULL',
					'issued_at'   => $dt,
					'jalali_date' => 'varchar(20) NOT NULL DEFAULT \'\'',
					'created_at'  => $dt,
					'updated_at'  => $dt,
				),
				array( 'UNIQUE KEY code (code)', 'KEY student_course (student_id, course_id)' ),
				array(
					self::fk( 'ac_cert_stu', 'student_id', 'academy_students' ),
					self::fk( 'ac_cert_course', 'course_id', 'academy_courses' ),
				)
			),
			'academy_course_views_daily' => self::def(
				array(
					'id'         => $id,
					'course_id'  => $fk,
					'stat_date'  => 'date NOT NULL',
					'views'      => 'int(10) unsigned NOT NULL DEFAULT 0',
					'uniques'    => 'int(10) unsigned NOT NULL DEFAULT 0',
					'created_at' => $dt,
					'updated_at' => $dt,
				),
				array( 'UNIQUE KEY course_date (course_id, stat_date)' ),
				array( self::fk( 'ac_view_course', 'course_id', 'academy_courses' ) )
			),
		);
	}

	/**
	 * One table definition.
	 *
	 * @param array<string, string>                  $columns Columns.
	 * @param string[]                               $indexes Indexes.
	 * @param array<int, array<string, string>>|null $fks     Foreign keys.
	 * @return array<string, mixed>
	 */
	private static function def( array $columns, array $indexes, ?array $fks = null ): array {
		return array(
			'soft'    => false,
			'columns' => $columns,
			'primary' => 'id',
			'indexes' => $indexes,
			'fks'     => $fks ? $fks : array(),
		);
	}

	/**
	 * Foreign key descriptor.
	 *
	 * @param string $name   Short constraint name.
	 * @param string $column Local column.
	 * @param string $table  Referenced suffix.
	 * @return array<string, string>
	 */
	private static function fk( string $name, string $column, string $table ): array {
		return array(
			'name'       => $name,
			'column'     => $column,
			'ref_table'  => $table,
			'ref_column' => 'id',
		);
	}
}
