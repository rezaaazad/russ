<?php
/**
 * Meta boxes for university, field, and city posts.
 *
 * Student counts, contacts, gallery, FAQ links, and SEO copy are post meta.
 * The ERD tables do not have columns for them.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Edit screens mapped onto the ERD shadow rows.
 */
class Editor {

	/**
	 * Degree labels.
	 *
	 * @return array<string, string>
	 */
	public static function degrees(): array {
		return array(
			'bachelor'   => 'کارشناسی',
			'specialist' => 'تخصصی',
			'master'     => 'کارشناسی ارشد',
			'phd'        => 'دکتری',
			'residency'  => 'رزیدنتی',
		);
	}

	/**
	 * Instruction languages.
	 *
	 * @return array<string, string>
	 */
	public static function languages(): array {
		return array(
			'ru'    => 'روسی',
			'en'    => 'انگلیسی',
			'ru_en' => 'روسی و انگلیسی',
		);
	}

	/**
	 * Register meta boxes and the save handler.
	 */
	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post', array( self::class, 'save' ), 30, 2 );
	}

	/**
	 * Meta boxes.
	 */
	public static function boxes(): void {
		if ( current_user_can( 'lr_manage_university_data' ) ) {
			add_meta_box( 'lr_uni_details', 'جزئیات دانشگاه', array( self::class, 'university_box' ), 'lr_university', 'normal', 'high' );
			add_meta_box( 'lr_field_details', 'جزئیات رشته', array( self::class, 'field_box' ), 'lr_field', 'normal', 'high' );
			add_meta_box( 'lr_city_details', 'جزئیات شهر', array( self::class, 'city_box' ), 'lr_city', 'normal', 'high' );
		}
		if ( current_user_can( 'lr_edit_seo' ) ) {
			foreach ( array( 'lr_university', 'lr_field', 'lr_city' ) as $type ) {
				add_meta_box( 'lr_seo', 'سئو', array( self::class, 'seo_box' ), $type, 'side' );
			}
		}
	}

	/**
	 * University details, offerings, rankings, contacts.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function university_box( \WP_Post $post ): void {
		$row = Store::row_for_post( 'universities', $post->ID );
		if ( ! $row ) {
			echo '<p>ردیف دانشگاه بعد از اولین ذخیره ساخته می‌شود. یک بار ذخیره کنید.</p>';
			self::nonce();
			return;
		}
		self::nonce();
		echo '<input type="hidden" name="lr_uni_managed" value="1">';
		$cities = self::choice_rows( 'cities' );
		$fields = self::choice_rows( 'fields' );
		echo '<p><label>شهر <select name="lr_city_id"><option value="">—</option>';
		foreach ( $cities as $city ) {
			printf(
				'<option value="%d" %s>%s</option>',
				(int) $city['id'],
				selected( (int) $row['city_id'], (int) $city['id'], false ),
				esc_html( $city['name_fa'] )
			);
		}
		echo '</select></label></p>';
		echo '<p><label>نام انگلیسی <input class="regular-text" name="lr_name_en" value="' . esc_attr( (string) $row['name_en'] ) . '"></label></p>';
		echo '<p><label>نام روسی <input class="regular-text" name="lr_name_ru" value="' . esc_attr( (string) $row['name_ru'] ) . '"></label></p>';
		echo '<p><label>نام کوتاه <input name="lr_short_name" value="' . esc_attr( (string) $row['short_name'] ) . '"></label></p>';
		echo '<p><label>نوع <select name="lr_ownership">';
		echo '<option value="">—</option>';
		foreach ( array(
			'state'   => 'دولتی',
			'private' => 'خصوصی',
		) as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( (string) $row['ownership'], $value, false ), esc_html( $label ) );
		}
		echo '</select></label> ';
		echo '<label>سال تأسیس <input type="number" name="lr_founded_year" value="' . esc_attr( (string) $row['founded_year'] ) . '"></label></p>';
		echo '<p><label>وب‌سایت <input class="regular-text" type="url" name="lr_website" value="' . esc_attr( (string) $row['website'] ) . '"></label></p>';
		echo '<p><label>آدرس <input class="large-text" name="lr_address" value="' . esc_attr( (string) $row['address'] ) . '"></label></p>';
		echo '<p>زبان آموزش ';
		$langs = explode( ',', (string) $row['teaching_languages'] );
		foreach ( array(
			'ru' => 'روسی',
			'en' => 'انگلیسی',
		) as $value => $label ) {
			echo '<label><input type="checkbox" name="lr_teaching[]" value="' . esc_attr( $value ) . '" ' . checked( in_array( $value, $langs, true ), true, false ) . '> ' . esc_html( $label ) . '</label> ';
		}
		echo '</p><p>';
		foreach (
			array(
				'has_dormitory'     => 'خوابگاه',
				'has_padfak'        => 'پادفک',
				'has_direct_course' => 'کورس مستقیم',
				'has_scholarship'   => 'بورسیه',
				'is_featured'       => 'ویژه',
			) as $column => $label
		) {
			echo '<label><input type="checkbox" name="lr_flag_' . esc_attr( $column ) . '" value="1" ' . checked( (int) $row[ $column ], 1, false ) . '> ' . esc_html( $label ) . '</label> ';
		}
		echo '</p>';
		$dorm = self::dorm_row( (int) $row['id'] );
		echo '<p><label>شهریه خوابگاه از <input type="number" step="0.01" name="lr_dorm_min" value="' . esc_attr( $dorm ? (string) $dorm['amount_min'] : '' ) . '"></label> ';
		echo '<label>تا <input type="number" step="0.01" name="lr_dorm_max" value="' . esc_attr( $dorm ? (string) $dorm['amount_max'] : '' ) . '"></label> ';
		echo '<label>ارز <input name="lr_dorm_currency" value="' . esc_attr( $dorm ? (string) $dorm['currency'] : 'RUB' ) . '" maxlength="3"></label></p>';
		echo '<p><label>دانشجو <input type="number" name="lr_students_total" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_students_total', true ) ) . '"></label> ';
		echo '<label>دانشجوی بین‌الملل <input type="number" name="lr_students_international" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_students_international', true ) ) . '"></label></p>';
		self::approval_fields( (int) $row['id'], $row );
		echo '<p><label>تلفن <input name="lr_phone" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_phone', true ) ) . '"></label> ';
		echo '<label>ایمیل <input type="email" name="lr_email" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_email', true ) ) . '"></label> ';
		echo '<label>ایمیل پذیرش <input type="email" name="lr_admissions_email" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_admissions_email', true ) ) . '"></label></p>';
		echo '<p><label>گالری (شناسه پیوست، با ویرگول) <input class="large-text" name="lr_gallery" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_gallery', true ) ) . '"></label></p>';
		echo '<p><label>سؤالات متداول (شناسه نوشته، با ویرگول) <input class="large-text" name="lr_faq_ids" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_faq_ids', true ) ) . '"></label></p>';
		self::program_table( (int) $row['id'], $fields );
		self::ranking_table( (int) $row['id'] );
	}

	/**
	 * Field details.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function field_box( \WP_Post $post ): void {
		$row = Store::row_for_post( 'fields', $post->ID );
		self::nonce();
		if ( ! $row ) {
			echo '<p>ردیف رشته بعد از اولین ذخیره ساخته می‌شود.</p>';
			return;
		}
		echo '<input type="hidden" name="lr_field_managed" value="1">';
		echo '<p><label>نام انگلیسی <input class="regular-text" name="lr_name_en" value="' . esc_attr( (string) $row['name_en'] ) . '"></label></p>';
		echo '<p><label>نام روسی <input class="regular-text" name="lr_name_ru" value="' . esc_attr( (string) $row['name_ru'] ) . '"></label></p>';
		$levels = explode( ',', (string) $row['degree_levels'] );
		echo '<p>مقاطع ';
		foreach ( self::degrees() as $value => $label ) {
			echo '<label><input type="checkbox" name="lr_degrees[]" value="' . esc_attr( $value ) . '" ' . checked( in_array( $value, $levels, true ), true, false ) . '> ' . esc_html( $label ) . '</label> ';
		}
		echo '</p><p>زبان‌ها ';
		$langs = explode( ',', (string) $row['languages'] );
		foreach ( array(
			'ru' => 'روسی',
			'en' => 'انگلیسی',
		) as $value => $label ) {
			echo '<label><input type="checkbox" name="lr_langs[]" value="' . esc_attr( $value ) . '" ' . checked( in_array( $value, $langs, true ), true, false ) . '> ' . esc_html( $label ) . '</label> ';
		}
		echo '</p><p><label>مدت پیش‌فرض (سال) <input type="number" step="0.1" name="lr_duration" value="' . esc_attr( (string) $row['default_duration_years'] ) . '"></label></p>';
	}

	/**
	 * City details, including living cost.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function city_box( \WP_Post $post ): void {
		$row = Store::row_for_post( 'cities', $post->ID );
		self::nonce();
		if ( ! $row ) {
			echo '<p>ردیف شهر بعد از اولین ذخیره ساخته می‌شود.</p>';
			return;
		}
		echo '<input type="hidden" name="lr_city_managed" value="1">';
		echo '<p><label>نام انگلیسی <input class="regular-text" name="lr_name_en" value="' . esc_attr( (string) $row['name_en'] ) . '"></label></p>';
		echo '<p><label>نام روسی <input class="regular-text" name="lr_name_ru" value="' . esc_attr( (string) $row['name_ru'] ) . '"></label></p>';
		echo '<p><label>موضوع فدرال <input class="regular-text" name="lr_federal" value="' . esc_attr( (string) $row['federal_subject'] ) . '"></label></p>';
		echo '<p><label>جمعیت <input type="number" name="lr_population" value="' . esc_attr( (string) $row['population'] ) . '"></label></p>';
		echo '<p><label>هزینه زندگی از <input type="number" step="0.01" name="lr_living_min" value="' . esc_attr( (string) $row['living_cost_min'] ) . '"></label> ';
		echo '<label>تا <input type="number" step="0.01" name="lr_living_max" value="' . esc_attr( (string) $row['living_cost_max'] ) . '"></label> ';
		echo '<label>ارز <input name="lr_currency" maxlength="3" value="' . esc_attr( (string) $row['currency'] ) . '"></label></p>';
		echo '<p><label>اقلیم <input class="large-text" name="lr_climate" value="' . esc_attr( (string) $row['climate_summary'] ) . '"></label></p>';
		echo '<p><label>منبع <input class="regular-text" name="lr_source" value="' . esc_attr( (string) $row['source'] ) . '"></label></p>';
	}

	/**
	 * SEO title and description. Capability lr_edit_seo.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function seo_box( \WP_Post $post ): void {
		self::nonce();
		echo '<input type="hidden" name="lr_seo_present" value="1">';
		echo '<p><label>عنوان سئو<br><input class="widefat" name="lr_seo_title" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_seo_title', true ) ) . '"></label></p>';
		echo '<p><label>توضیح سئو<br><textarea class="widefat" rows="4" name="lr_seo_description">' . esc_textarea( (string) get_post_meta( $post->ID, '_lr_seo_description', true ) ) . '</textarea></label></p>';
	}

	/**
	 * Persist meta after the shadow row exists.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['lr_catalog_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_catalog_nonce'] ) ), 'lr_catalog_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'lr_university' === $post->post_type && ! empty( $_POST['lr_uni_managed'] ) && current_user_can( 'lr_manage_university_data' ) ) {
			self::save_university( $post_id );
		}
		if ( 'lr_field' === $post->post_type && ! empty( $_POST['lr_field_managed'] ) && current_user_can( 'lr_manage_university_data' ) ) {
			self::save_field( $post_id );
		}
		if ( 'lr_city' === $post->post_type && ! empty( $_POST['lr_city_managed'] ) && current_user_can( 'lr_manage_university_data' ) ) {
			self::save_city( $post_id );
		}
		if ( ! empty( $_POST['lr_seo_present'] ) && current_user_can( 'lr_edit_seo' ) ) {
			update_post_meta( $post_id, '_lr_seo_title', sanitize_text_field( wp_unslash( $_POST['lr_seo_title'] ?? '' ) ) );
			update_post_meta( $post_id, '_lr_seo_description', sanitize_textarea_field( wp_unslash( $_POST['lr_seo_description'] ?? '' ) ) );
		}
	}

	/**
	 * Nonce shared by the boxes.
	 */
	private static function nonce(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		wp_nonce_field( 'lr_catalog_save', 'lr_catalog_nonce' );
	}

	/**
	 * Choice rows for a select, up to 300.
	 *
	 * @param string $suffix Table suffix.
	 * @return array<int, array<string, mixed>>
	 */
	private static function choice_rows( string $suffix ): array {
		$result = Repository::for( $suffix )->paginate(
			array(
				'page'     => 1,
				'per_page' => 100,
				'orderby'  => 'name_fa',
				'order'    => 'ASC',
			)
		);
		return $result['items'];
	}

	/**
	 * Latest dorm row.
	 *
	 * @param int $university_id University id.
	 * @return array<string, mixed>|null
	 */
	private static function dorm_row( int $university_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_dormitory_fees';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
				$university_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Ministry fields. Saved only by lr_manage_university_data.
	 *
	 * @param int                  $university_id University id.
	 * @param array<string, mixed> $row           University row.
	 */
	private static function approval_fields( int $university_id, array $row ): void {
		$stored = self::approvals( $university_id );
		echo '<fieldset><legend>تأیید وزارتخانه</legend>';
		foreach ( array(
			'health_ministry'  => 'وزارت بهداشت',
			'science_ministry' => 'وزارت علوم',
		) as $authority => $label ) {
			$current = $stored[ $authority ] ?? array();
			$status  = (string) ( $current['status'] ?? $row[ $authority . '_status' ] );
			echo '<p><strong>' . esc_html( $label ) . '</strong> <select name="lr_appr[' . esc_attr( $authority ) . '][status]">';
			foreach ( array(
				'unknown'      => 'نامشخص',
				'approved'     => 'تأیید',
				'conditional'  => 'مشروط',
				'not_approved' => 'تأیید نشده',
			) as $value => $text ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $status, $value, false ), esc_html( $text ) );
			}
			echo '</select> ';
			echo '<input placeholder="منبع" name="lr_appr[' . esc_attr( $authority ) . '][source]" value="' . esc_attr( (string) ( $current['source'] ?? '' ) ) . '"> ';
			echo '<input placeholder="نشانی منبع" class="regular-text" name="lr_appr[' . esc_attr( $authority ) . '][source_url]" value="' . esc_attr( (string) ( $current['source_url'] ?? '' ) ) . '"> ';
			$verified = isset( $current['last_verified_at'] ) ? (string) $current['last_verified_at'] : '';
			echo '<input type="datetime-local" name="lr_appr[' . esc_attr( $authority ) . '][verified]" value="' . esc_attr( $verified ? gmdate( 'Y-m-d\TH:i', strtotime( $verified . ' UTC' ) ) : '' ) . '"></p>';
		}
		echo '</fieldset>';
	}

	/**
	 * University-wide approval rows.
	 *
	 * @param int $university_id University id.
	 * @return array<string, array<string, mixed>>
	 */
	private static function approvals( int $university_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_university_approvals';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE university_id = %d AND field_id IS NULL AND academic_year = '' AND deleted_at IS NULL",
				$university_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$out   = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['authority'] ] = $row;
		}
		return $out;
	}

	/**
	 * Link to the unlimited program screen.
	 *
	 * @param int                             $university_id University id.
	 * @param array<int, array<string,mixed>> $fields        Unused. The screen loads fields itself.
	 */
	private static function program_table( int $university_id, array $fields ): void {
		unset( $fields );
		global $wpdb;
		$table = $wpdb->prefix . 'lr_university_fields';
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL", $university_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$url   = admin_url( 'admin.php?page=lr-programs&university=' . $university_id );
		echo '<h3>رشته‌های ارائه‌شده</h3>';
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html( sprintf( 'ویرایش %d رشته', $total ) ) . '</a></p>';
	}

	/**
	 * Ranking repeater.
	 *
	 * @param int $university_id University id.
	 */
	private static function ranking_table( int $university_id ): void {
		global $wpdb;
		$table     = $wpdb->prefix . 'lr_university_rankings';
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL ORDER BY year DESC LIMIT 20",
				$university_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$providers = Repository::for( 'ranking_providers' )->paginate(
			array(
				'page'     => 1,
				'per_page' => 50,
				'orderby'  => 'sort_order',
				'order'    => 'ASC',
			)
		);
		echo '<h3>رتبه‌بندی</h3><table class="widefat"><thead><tr><th>مرجع</th><th>محدوده</th><th>موضوع</th><th>رتبه</th><th>بازه</th><th>سال</th></tr></thead><tbody>';
		$list   = is_array( $rows ) ? $rows : array();
		$list[] = array();
		foreach ( $list as $index => $rank ) {
			echo '<tr><td><select name="lr_ranks[' . (int) $index . '][provider]"><option value="">—</option>';
			foreach ( $providers['items'] as $provider ) {
				printf(
					'<option value="%d" %s>%s</option>',
					(int) $provider['id'],
					selected( (int) ( $rank['ranking_provider_id'] ?? 0 ), (int) $provider['id'], false ),
					esc_html( $provider['name'] )
				);
			}
			echo '</select></td><td><select name="lr_ranks[' . (int) $index . '][scope]">';
			foreach ( array(
				'world'    => 'جهانی',
				'national' => 'ملی',
				'regional' => 'منطقه‌ای',
				'subject'  => 'رشته‌ای',
			) as $value => $label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( (string) ( $rank['scope'] ?? 'world' ), $value, false ), esc_html( $label ) );
			}
			echo '</select></td>';
			echo '<td><input name="lr_ranks[' . (int) $index . '][subject]" value="' . esc_attr( (string) ( $rank['subject'] ?? '' ) ) . '"></td>';
			echo '<td><input type="number" name="lr_ranks[' . (int) $index . '][rank]" value="' . esc_attr( (string) ( $rank['rank_value'] ?? '' ) ) . '"></td>';
			echo '<td><input name="lr_ranks[' . (int) $index . '][band]" value="' . esc_attr( (string) ( $rank['rank_band'] ?? '' ) ) . '"></td>';
			echo '<td><input type="number" name="lr_ranks[' . (int) $index . '][year]" value="' . esc_attr( (string) ( $rank['year'] ?? '' ) ) . '"></td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Save university columns, programs, rankings, and post meta.
	 *
	 * @param int $post_id Post id.
	 */
	private static function save_university( int $post_id ): void {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		$row = Store::row_for_post( 'universities', $post_id );
		if ( ! $row ) {
			return;
		}
		$id    = (int) $row['id'];
		$thumb = (int) get_post_thumbnail_id( $post_id );
		Store::update_row(
			'universities',
			$id,
			array(
				'name_en'            => sanitize_text_field( wp_unslash( $_POST['lr_name_en'] ?? '' ) ),
				'name_ru'            => sanitize_text_field( wp_unslash( $_POST['lr_name_ru'] ?? '' ) ),
				'short_name'         => sanitize_text_field( wp_unslash( $_POST['lr_short_name'] ?? '' ) ),
				'city_id'            => absint( $_POST['lr_city_id'] ?? 0 ) ? absint( $_POST['lr_city_id'] ) : null,
				'website'            => esc_url_raw( wp_unslash( $_POST['lr_website'] ?? '' ) ),
				'founded_year'       => absint( $_POST['lr_founded_year'] ?? 0 ) ? absint( $_POST['lr_founded_year'] ) : null,
				'address'            => sanitize_text_field( wp_unslash( $_POST['lr_address'] ?? '' ) ),
				'ownership'          => in_array( sanitize_key( wp_unslash( $_POST['lr_ownership'] ?? '' ) ), array( 'state', 'private' ), true ) ? sanitize_key( wp_unslash( $_POST['lr_ownership'] ?? '' ) ) : null,
				'teaching_languages' => self::posted_set( 'lr_teaching', array( 'ru', 'en' ) ),
				'has_dormitory'      => empty( $_POST['lr_flag_has_dormitory'] ) ? 0 : 1,
				'has_padfak'         => empty( $_POST['lr_flag_has_padfak'] ) ? 0 : 1,
				'has_direct_course'  => empty( $_POST['lr_flag_has_direct_course'] ) ? 0 : 1,
				'has_scholarship'    => empty( $_POST['lr_flag_has_scholarship'] ) ? 0 : 1,
				'is_featured'        => empty( $_POST['lr_flag_is_featured'] ) ? 0 : 1,
				'logo_id'            => $thumb ? $thumb : null,
			)
		);
		$dorm_min = self::posted_number( 'lr_dorm_min' );
		if ( null !== $dorm_min ) {
			$max = self::posted_number( 'lr_dorm_max' );
			Store::upsert_dorm( $id, $dorm_min, $max, strtoupper( sanitize_text_field( wp_unslash( $_POST['lr_dorm_currency'] ?? 'RUB' ) ) ) );
		}
		$approvals = self::posted_array( 'lr_appr' );
		foreach ( array( 'health_ministry', 'science_ministry' ) as $authority ) {
			if ( empty( $approvals[ $authority ] ) || ! is_array( $approvals[ $authority ] ) ) {
				continue;
			}
			$item   = $approvals[ $authority ];
			$status = sanitize_key( (string) ( $item['status'] ?? 'unknown' ) );
			if ( ! in_array( $status, array( 'approved', 'conditional', 'not_approved', 'unknown' ), true ) ) {
				$status = 'unknown';
			}
			$when = sanitize_text_field( (string) ( $item['verified'] ?? '' ) );
			$when = $when ? gmdate( 'Y-m-d H:i:s', strtotime( $when . ' UTC' ) ) : gmdate( 'Y-m-d H:i:s' );
			Store::upsert_approval(
				$id,
				$authority,
				$status,
				sanitize_text_field( (string) ( $item['source'] ?? '' ) ),
				esc_url_raw( (string) ( $item['source_url'] ?? '' ) ),
				$when
			);
		}
		update_post_meta( $post_id, '_lr_students_total', absint( $_POST['lr_students_total'] ?? 0 ) );
		update_post_meta( $post_id, '_lr_students_international', absint( $_POST['lr_students_international'] ?? 0 ) );
		update_post_meta( $post_id, '_lr_phone', sanitize_text_field( wp_unslash( $_POST['lr_phone'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_email', sanitize_email( wp_unslash( $_POST['lr_email'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_admissions_email', sanitize_email( wp_unslash( $_POST['lr_admissions_email'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_gallery', self::id_list( sanitize_text_field( wp_unslash( $_POST['lr_gallery'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_lr_faq_ids', self::id_list( sanitize_text_field( wp_unslash( $_POST['lr_faq_ids'] ?? '' ) ) ) );
		self::save_programs( $id );
		self::save_rankings( $id );
		Store::bump();
	}

	/**
	 * Upsert posted program rows. There is no row cap.
	 *
	 * @param int $university_id University id.
	 */
	private static function save_programs( int $university_id ): void {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		if ( ! isset( $_POST['lr_programs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$posted = self::posted_array( 'lr_programs' );
		foreach ( $posted as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$field_id = absint( $item['field_id'] ?? 0 );
			$degree   = sanitize_key( (string) ( $item['degree'] ?? '' ) );
			$existing = absint( $item['id'] ?? 0 );
			if ( ! $field_id || ! isset( self::degrees()[ $degree ] ) ) {
				if ( $existing ) {
					Repository::for( 'university_fields' )->delete( $existing );
				}
				continue;
			}
			$lang = sanitize_key( (string) ( $item['language'] ?? 'ru' ) );
			if ( ! isset( self::languages()[ $lang ] ) ) {
				$lang = 'ru';
			}
			$tuition = isset( $item['tuition'] ) && is_numeric( $item['tuition'] ) ? (float) $item['tuition'] : null;
			Store::upsert_program(
				$university_id,
				$field_id,
				$degree,
				$lang,
				isset( $item['duration'] ) && is_numeric( $item['duration'] ) ? (float) $item['duration'] : 0,
				$tuition,
				strtoupper( sanitize_text_field( (string) ( $item['currency'] ?? 'RUB' ) ) ),
				sanitize_text_field( (string) ( $item['year'] ?? '' ) ),
				true
			);
		}
	}

	/**
	 * Upsert displayed ranking rows.
	 *
	 * @param int $university_id University id.
	 */
	private static function save_rankings( int $university_id ): void {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		$posted = self::posted_array( 'lr_ranks' );
		$seen   = 0;
		foreach ( $posted as $item ) {
			if ( $seen >= 20 || ! is_array( $item ) ) {
				continue;
			}
			++$seen;
			$provider = absint( $item['provider'] ?? 0 );
			$year     = absint( $item['year'] ?? 0 );
			if ( ! $provider || $year < 1990 ) {
				continue;
			}
			$scope = sanitize_key( (string) ( $item['scope'] ?? 'world' ) );
			if ( ! in_array( $scope, array( 'world', 'national', 'regional', 'subject' ), true ) ) {
				$scope = 'world';
			}
			Store::upsert_ranking(
				$university_id,
				$provider,
				$scope,
				sanitize_text_field( (string) ( $item['subject'] ?? '' ) ),
				absint( $item['rank'] ?? 0 ),
				sanitize_text_field( (string) ( $item['band'] ?? '' ) ),
				$year,
				true
			);
		}
	}

	/**
	 * Save a field row.
	 *
	 * @param int $post_id Post id.
	 */
	private static function save_field( int $post_id ): void {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		$row = Store::row_for_post( 'fields', $post_id );
		if ( ! $row ) {
			return;
		}
		$duration = self::posted_number( 'lr_duration' );
		Store::update_row(
			'fields',
			(int) $row['id'],
			array(
				'name_en'                => sanitize_text_field( wp_unslash( $_POST['lr_name_en'] ?? '' ) ),
				'name_ru'                => sanitize_text_field( wp_unslash( $_POST['lr_name_ru'] ?? '' ) ),
				'degree_levels'          => self::posted_set( 'lr_degrees', array_keys( self::degrees() ) ),
				'languages'              => self::posted_set( 'lr_langs', array( 'ru', 'en' ) ),
				'default_duration_years' => $duration,
			)
		);
	}

	/**
	 * Save a city row.
	 *
	 * @param int $post_id Post id.
	 */
	private static function save_city( int $post_id ): void {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		$row = Store::row_for_post( 'cities', $post_id );
		if ( ! $row ) {
			return;
		}
		$currency = strtoupper( sanitize_text_field( wp_unslash( $_POST['lr_currency'] ?? 'RUB' ) ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			$currency = 'RUB';
		}
		Store::update_row(
			'cities',
			(int) $row['id'],
			array(
				'name_en'         => sanitize_text_field( wp_unslash( $_POST['lr_name_en'] ?? '' ) ),
				'name_ru'         => sanitize_text_field( wp_unslash( $_POST['lr_name_ru'] ?? '' ) ),
				'federal_subject' => sanitize_text_field( wp_unslash( $_POST['lr_federal'] ?? '' ) ),
				'population'      => absint( $_POST['lr_population'] ?? 0 ) ? absint( $_POST['lr_population'] ) : null,
				'living_cost_min' => self::posted_number( 'lr_living_min' ),
				'living_cost_max' => self::posted_number( 'lr_living_max' ),
				'currency'        => $currency,
				'climate_summary' => sanitize_text_field( wp_unslash( $_POST['lr_climate'] ?? '' ) ),
				'source'          => sanitize_text_field( wp_unslash( $_POST['lr_source'] ?? '' ) ),
			)
		);
	}

	/**
	 * Checkbox list limited to an allow-list.
	 *
	 * @param string   $key     Post key.
	 * @param string[] $allowed Allowed values.
	 */
	private static function posted_set( string $key, array $allowed ): string {
		$raw = self::posted_array( $key );
		$out = array();
		foreach ( $raw as $value ) {
			$value = sanitize_key( (string) $value );
			if ( in_array( $value, $allowed, true ) ) {
				$out[] = $value;
			}
		}
		return implode( ',', $out );
	}

	/**
	 * Numeric post field, or null when it is empty.
	 *
	 * @param string $key Field name.
	 */
	private static function posted_number( string $key ): ?float {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		if ( ! isset( $_POST[ $key ] ) ) {
			return null;
		}
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		return is_numeric( $raw ) ? (float) $raw : null;
	}

	/**
	 * Posted array, sanitized as text.
	 *
	 * @param string $key Field name.
	 * @return array<int|string, mixed>
	 */
	private static function posted_array( string $key ): array {
		check_admin_referer( 'lr_catalog_save', 'lr_catalog_nonce' );
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}
		$raw = map_deep( wp_unslash( $_POST[ $key ] ), 'sanitize_text_field' );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Comma-separated positive ids.
	 *
	 * @param string $raw Raw list.
	 */
	private static function id_list( string $raw ): string {
		$out = array();
		foreach ( explode( ',', $raw ) as $part ) {
			$id = absint( $part );
			if ( $id ) {
				$out[] = (string) $id;
			}
		}
		return implode( ',', $out );
	}
}
