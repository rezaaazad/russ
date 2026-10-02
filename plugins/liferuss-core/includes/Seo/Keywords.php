<?php
/**
 * Keyword cluster map, CSV import, and a dormant Search Console pull.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\Admin\Chrome;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * One row is one keyword aimed at one URL.
 */
class Keywords {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe_seed' ) );
		add_action( 'admin_post_lr_seo_keywords_import', array( self::class, 'import_post' ) );
		add_action( 'admin_post_lr_seo_gsc_save', array( self::class, 'save_gsc' ) );
		add_action( 'lr_gsc_pull', array( self::class, 'pull' ) );
		if ( ! wp_next_scheduled( 'lr_gsc_pull' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lr_gsc_pull' );
		}
	}

	/**
	 * Admin screen.
	 */
	public static function screen(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ) );
		}
		$rows = self::all();
		Chrome::open( 'نقشه کلیدواژه', 'تنظیمات' );
		$conflicts = self::conflicts();
		if ( $conflicts ) {
			echo '<div class="notice notice-warning lr-compact-notice"><p>تداخل: یک کلیدواژه به بیش از یک نشانی اشاره دارد: ' . esc_html( implode( '، ', $conflicts ) ) . '</p></div>';
		}
		echo '<div class="lr-scroll"><table class="widefat lr-table"><thead><tr><th>کلیدواژه</th><th>خوشه</th><th>نیت</th><th>نشانی</th><th>اولویت</th><th>رتبه</th><th>یادداشت</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['keyword'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['cluster'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['intent'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['target_url'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['priority'] ) . '</td>';
			echo '<td>' . esc_html( null === $row['current_position'] ? '—' : (string) $row['current_position'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['notes'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		echo '<h2>ورود CSV</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
		wp_nonce_field( 'lr_seo_keywords_import' );
		echo '<input type="hidden" name="action" value="lr_seo_keywords_import">';
		echo '<input type="file" name="csv" accept=".csv,text/csv" required> ';
		echo '<button class="button button-primary" type="submit">وارد کردن</button></form>';
		$gsc = self::settings();
		echo '<h2>سرچ کنسول</h2><p>تا وقتی شناسه و توکن ذخیره نشود، دریافت روزانه خاموش است.</p>';
		echo '<form class="lr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_seo_gsc_save' );
		echo '<input type="hidden" name="action" value="lr_seo_gsc_save">';
		echo '<label>شناسه کلاینت<input name="client_id" value="' . esc_attr( (string) $gsc['client_id'] ) . '"></label>';
		echo '<label>رمز کلاینت<input name="client_secret" value="' . esc_attr( (string) $gsc['client_secret'] ) . '"></label>';
		echo '<label>توکن تازه‌سازی<input name="refresh_token" value="' . esc_attr( (string) $gsc['refresh_token'] ) . '"></label>';
		echo '<label>ویژگی<input name="property" value="' . esc_attr( (string) $gsc['property'] ) . '" placeholder="sc-domain:liferuss.com"></label>';
		echo '<label><input type="checkbox" name="enabled" value="1" ' . checked( ! empty( $gsc['enabled'] ), true, false ) . '> دریافت روزانه فعال باشد</label>';
		echo '<p><button class="button lr-btn" type="submit">ذخیره اتصال</button></p></form>';
		Chrome::close();
	}

	/**
	 * Every keyword row.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}lr_seo_keywords` ORDER BY cluster ASC, priority ASC, id ASC", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Keywords aimed at two different URLs.
	 *
	 * @return string[]
	 */
	public static function conflicts(): array {
		global $wpdb;
		$sql  = "SELECT keyword FROM `{$wpdb->prefix}lr_seo_keywords` GROUP BY keyword HAVING COUNT(DISTINCT target_url) > 1";
		$rows = $wpdb->get_col( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Insert the map once.
	 */
	public static function maybe_seed(): void {
		if ( '1.14.0' === (string) get_option( 'lr_seo_keywords_seeded' ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_keywords';
		$now   = current_time( 'mysql', true );
		foreach ( self::catalogue() as $row ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE keyword = %s AND target_url = %s LIMIT 1", $row[0], $row[3] ) );
			if ( $exists ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'keyword'    => $row[0],
					'cluster'    => $row[1],
					'intent'     => $row[2],
					'target_url' => $row[3],
					'priority'   => (int) $row[4],
					'notes'      => $row[5],
					'created_at' => $now,
					'updated_at' => $now,
				)
			);
		}
		update_option( 'lr_seo_keywords_seeded', '1.14.0', false );
	}

	/**
	 * CSV import. Header: keyword,cluster,intent,target_url,priority,notes.
	 */
	public static function import_post(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_seo_keywords_import' );
		$file   = isset( $_FILES['csv']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['csv']['tmp_name'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a local path, checked before fopen.
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			wp_safe_redirect( admin_url( 'admin.php?page=lr-seo-keywords&lr_note=' . rawurlencode( 'فایل خوانده نشد.' ) . '&lr_kind=err' ) );
			exit;
		}
		$header = fgetcsv( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
		$header = is_array( $header ) ? array_map( 'sanitize_key', $header ) : array();
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_keywords';
		$now   = current_time( 'mysql', true );
		while ( ( $data = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv, Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$row = array();
			foreach ( $header as $index => $key ) {
				$row[ $key ] = isset( $data[ $index ] ) ? trim( (string) $data[ $index ] ) : '';
			}
			if ( '' === ( $row['keyword'] ?? '' ) || '' === ( $row['target_url'] ?? '' ) ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'keyword'    => sanitize_text_field( $row['keyword'] ),
					'cluster'    => sanitize_key( $row['cluster'] ?? 'universities' ),
					'intent'     => sanitize_key( $row['intent'] ?? 'informational' ),
					'target_url' => esc_url_raw( $row['target_url'] ),
					'priority'   => absint( $row['priority'] ?? 3 ),
					'notes'      => sanitize_text_field( $row['notes'] ?? '' ),
					'created_at' => $now,
					'updated_at' => $now,
				)
			);
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		wp_safe_redirect( admin_url( 'admin.php?page=lr-seo-keywords&lr_note=' . rawurlencode( 'کلیدواژه‌ها وارد شد.' ) ) );
		exit;
	}

	/**
	 * Save OAuth settings. The cron stays idle until enabled is checked.
	 */
	public static function save_gsc(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_seo_gsc_save' );
		update_option(
			'lr_gsc',
			array(
				'client_id'     => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
				'client_secret' => isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '',
				'refresh_token' => isset( $_POST['refresh_token'] ) ? sanitize_text_field( wp_unslash( $_POST['refresh_token'] ) ) : '',
				'property'      => isset( $_POST['property'] ) ? sanitize_text_field( wp_unslash( $_POST['property'] ) ) : '',
				'enabled'       => isset( $_POST['enabled'] ) ? 1 : 0,
			),
			false
		);
		wp_safe_redirect( admin_url( 'admin.php?page=lr-seo-keywords&lr_note=' . rawurlencode( 'اتصال ذخیره شد.' ) ) );
		exit;
	}

	/**
	 * Stored Search Console settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		$stored = get_option( 'lr_gsc', array() );
		$stored = is_array( $stored ) ? $stored : array();
		return array_merge(
			array(
				'client_id'     => '',
				'client_secret' => '',
				'refresh_token' => '',
				'property'      => '',
				'enabled'       => 0,
			),
			$stored
		);
	}

	/**
	 * Daily position pull. Returns immediately until credentials are enabled.
	 */
	public static function pull(): void {
		$settings = self::settings();
		if ( empty( $settings['enabled'] ) || '' === $settings['refresh_token'] || '' === $settings['client_id'] || '' === $settings['property'] ) {
			return;
		}
		$token = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
					'refresh_token' => $settings['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
			)
		);
		if ( is_wp_error( $token ) ) {
			return;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $token ), true );
		if ( empty( $body['access_token'] ) ) {
			return;
		}
		$property = rawurlencode( (string) $settings['property'] );
		$report   = wp_remote_post(
			'https://searchconsole.googleapis.com/webmasters/v3/sites/' . $property . '/searchAnalytics/query',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $body['access_token'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'startDate'  => gmdate( 'Y-m-d', time() - ( 28 * DAY_IN_SECONDS ) ),
						'endDate'    => gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ),
						'dimensions' => array( 'query' ),
						'rowLimit'   => 200,
					)
				),
			)
		);
		if ( is_wp_error( $report ) ) {
			return;
		}
		$payload = json_decode( (string) wp_remote_retrieve_body( $report ), true );
		if ( empty( $payload['rows'] ) || ! is_array( $payload['rows'] ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_keywords';
		foreach ( $payload['rows'] as $hit ) {
			$query = isset( $hit['keys'][0] ) ? (string) $hit['keys'][0] : '';
			if ( '' === $query ) {
				continue;
			}
			$wpdb->update(
				$table,
				array(
					'current_position' => isset( $hit['position'] ) ? (float) $hit['position'] : null,
					'clicks'           => isset( $hit['clicks'] ) ? (int) $hit['clicks'] : null,
					'updated_at'       => current_time( 'mysql', true ),
				),
				array( 'keyword' => $query )
			);
		}
	}

	/**
	 * Six head keywords and the long-tail map.
	 *
	 * @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}>
	 */
	private static function catalogue(): array {
		$heads = array(
			array( 'دانشگاه‌های روسیه', 'universities', 'informational', '/universities/', 1, 'سرخوشه' ),
			array( 'پزشکی و دندانپزشکی روسیه', 'medicine', 'commercial', '/medicine/', 1, 'سرخوشه' ),
			array( 'بورسیه روسیه', 'scholarship', 'commercial', '/scholarships/', 1, 'سرخوشه' ),
			array( 'پادفک و کورس مستقیم', 'padfak', 'commercial', '/padfak/', 1, 'سرخوشه' ),
			array( 'زندگی و دانستنی‌های روسیه', 'guide', 'informational', '/russia-guide/', 1, 'سرخوشه' ),
			array( 'مهاجرت و قوانین روسیه', 'immigration', 'informational', '/immigration/', 1, 'سرخوشه' ),
			array( 'دانشگاه‌های روسیه', 'universities', 'informational', '/study-russia/', 2, 'نمونهٔ تداخل برای هشدار هم‌خواری' ),
		);
		$tails = array(
			array( 'شهریه دانشگاه‌های روسیه ۲۰۲۶', 'universities', 'commercial', '/universities/', 2, '' ),
			array( 'بهترین دانشگاه‌های روسیه', 'universities', 'commercial', '/universities/', 2, '' ),
			array( 'دانشگاه دولتی روسیه', 'universities', 'informational', '/universities/', 2, '' ),
			array( 'رنکینگ دانشگاه‌های روسیه', 'universities', 'informational', '/universities/', 2, '' ),
			array( 'دانشگاه مسکو', 'universities', 'navigational', '/cities/moscow/', 2, '' ),
			array( 'دانشگاه سن پترزبورگ', 'universities', 'navigational', '/cities/saint-petersburg/', 2, '' ),
			array( 'دانشگاه کازان', 'universities', 'navigational', '/cities/kazan/', 2, '' ),
			array( 'دانشگاه سچنوف', 'medicine', 'navigational', '/universities/sechenov/', 2, '' ),
			array( 'دانشگاه سچنوف پزشکی', 'medicine', 'commercial', '/universities/sechenov/general-medicine/', 2, '' ),
			array( 'پذیرش دانشگاه روسیه', 'universities', 'transactional', '/study-russia/', 2, '' ),
			array( 'مدارک پذیرش دانشگاه روسیه', 'universities', 'informational', '/documents/', 3, '' ),
			array( 'رشته‌های دانشگاه روسیه', 'universities', 'informational', '/fields/', 2, '' ),
			array( 'پزشکی روسیه بدون کنکور', 'medicine', 'commercial', '/medicine/', 2, '' ),
			array( 'دندانپزشکی روسیه', 'medicine', 'commercial', '/dentistry/', 2, '' ),
			array( 'داروسازی روسیه', 'medicine', 'commercial', '/pharmacy/', 2, '' ),
			array( 'شهریه پزشکی روسیه', 'medicine', 'commercial', '/fields/general-medicine/', 2, '' ),
			array( 'تأیید وزارت بهداشت دانشگاه روسیه', 'medicine', 'informational', '/medicine/', 2, '' ),
			array( 'دانشگاه مورد تأیید وزارت بهداشت', 'medicine', 'commercial', '/universities/', 2, '' ),
			array( 'پادفک پزشکی', 'medicine', 'commercial', '/padfak/', 2, '' ),
			array( 'شرایط پزشکی عمومی روسیه', 'medicine', 'informational', '/fields/general-medicine/', 3, '' ),
			array( 'هزینه دندانپزشکی در روسیه', 'medicine', 'commercial', '/dentistry/', 3, '' ),
			array( 'رزیدنتی پزشکی روسیه', 'medicine', 'informational', '/medicine/', 3, '' ),
			array( 'بورسیه تحصیلی روسیه ۲۰۲۶', 'scholarship', 'commercial', '/scholarships/', 2, '' ),
			array( 'بورسیه دولت روسیه', 'scholarship', 'informational', '/scholarships/', 2, '' ),
			array( 'بورسیه پزشکی روسیه', 'scholarship', 'commercial', '/scholarships/', 2, '' ),
			array( 'کمک‌هزینه تحصیل در روسیه', 'scholarship', 'informational', '/scholarships/', 3, '' ),
			array( 'ددلاین بورسیه روسیه', 'scholarship', 'informational', '/scholarships/', 3, '' ),
			array( 'بورسیه دانشگاه سچنوف', 'scholarship', 'navigational', '/universities/sechenov/', 3, '' ),
			array( 'شرایط بورسیه روسیه', 'scholarship', 'informational', '/scholarships/', 2, '' ),
			array( 'بورسیه کارشناسی ارشد روسیه', 'scholarship', 'commercial', '/master/', 3, '' ),
			array( 'پادفک چیست', 'padfak', 'informational', '/padfak/', 2, '' ),
			array( 'هزینه پادفک روسیه', 'padfak', 'commercial', '/padfak/', 2, '' ),
			array( 'کورس مستقیم روسیه', 'padfak', 'commercial', '/direct-course/', 2, '' ),
			array( 'پادفک مسکو', 'padfak', 'commercial', '/cities/moscow/', 3, '' ),
			array( 'مدت دوره پادفک', 'padfak', 'informational', '/padfak/', 3, '' ),
			array( 'پذیرش مستقیم بدون پادفک', 'padfak', 'commercial', '/direct-course/', 2, '' ),
			array( 'آزمون پادفک', 'padfak', 'informational', '/padfak/', 3, '' ),
			array( 'زبان روسی برای دانشگاه', 'padfak', 'informational', '/russian-language/', 3, '' ),
			array( 'زندگی در مسکو', 'guide', 'informational', '/cities/moscow/', 2, '' ),
			array( 'هزینه زندگی در روسیه', 'guide', 'informational', '/costs/', 2, '' ),
			array( 'خوابگاه دانشجویی روسیه', 'guide', 'informational', '/russia-guide/', 2, '' ),
			array( 'دانستنی‌های روسیه', 'guide', 'informational', '/russia-guide/', 2, '' ),
			array( 'آب و هوای روسیه', 'guide', 'informational', '/russia-guide/', 3, '' ),
			array( 'زندگی دانشجویی در سن پترزبورگ', 'guide', 'informational', '/cities/saint-petersburg/', 3, '' ),
			array( 'کازان برای دانشجو', 'guide', 'informational', '/cities/kazan/', 3, '' ),
			array( 'حمل و نقل در روسیه', 'guide', 'informational', '/russia-guide/', 3, '' ),
			array( 'ویزای تحصیلی روسیه', 'immigration', 'transactional', '/visa/', 2, '' ),
			array( 'اقامت روسیه بعد از تحصیل', 'immigration', 'informational', '/residence/', 2, '' ),
			array( 'قوانین کار دانشجویی روسیه', 'immigration', 'informational', '/work/', 2, '' ),
			array( 'تابعیت روسیه', 'immigration', 'informational', '/citizenship/', 3, '' ),
			array( 'مهاجرت تحصیلی به روسیه', 'immigration', 'commercial', '/immigration/', 2, '' ),
			array( 'تمدید ویزای دانشجویی', 'immigration', 'informational', '/visa/', 3, '' ),
			array( 'ثبت‌نام مهاجرت روسیه', 'immigration', 'transactional', '/immigration/', 3, '' ),
			array( 'انتقالی به دانشگاه روسیه', 'universities', 'commercial', '/transfer-to-russia/', 3, '' ),
			array( 'پرداخت شهریه دانشگاه روسیه', 'universities', 'transactional', '/tuition-payment/', 3, '' ),
			array( 'مقایسه دانشگاه‌های روسیه', 'universities', 'commercial', '/compare/', 2, '' ),
			array( 'کارشناسی روسیه', 'universities', 'informational', '/bachelor/', 3, '' ),
			array( 'دکترا در روسیه', 'universities', 'informational', '/phd/', 3, '' ),
			array( 'مراحل پذیرش روسیه', 'universities', 'informational', '/admission-steps/', 2, '' ),
			array( 'وبلاگ تحصیل در روسیه', 'guide', 'navigational', '/blog/', 3, '' ),
			array( 'نرخ ارز برای شهریه', 'guide', 'transactional', '/exchange/', 3, '' ),
			array( 'شهرهای دانشجویی روسیه', 'guide', 'informational', '/cities/', 2, '' ),
			array( 'رشته پزشکی در مسکو', 'medicine', 'commercial', '/fields/general-medicine/moscow/', 2, '' ),
			array( 'خوابگاه دانشگاه سچنوف', 'medicine', 'informational', '/universities/sechenov/', 3, '' ),
			array( 'قوانین اقامت دانشجو', 'immigration', 'informational', '/immigration/', 2, '' ),
		);
		return array_merge( $heads, $tails );
	}
}
