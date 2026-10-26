<?php
/**
 * Structured fields for study, immigration, and scholarship screens.
 *
 * The section builder is deferred. These are fixed slots a content manager fills in.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta boxes for path pages and scholarships.
 */
class Editor {

	/**
	 * Register boxes.
	 */
	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post_page', array( self::class, 'save_page' ) );
		add_action( 'save_post_lr_scholarship', array( self::class, 'save_scholarship' ) );
	}

	/**
	 * Boxes.
	 */
	public static function boxes(): void {
		add_meta_box( 'lr_path', 'بخش‌های مسیر', array( self::class, 'path_box' ), 'page', 'normal', 'high' );
		add_meta_box( 'lr_scholarship', 'جزئیات بورسیه', array( self::class, 'scholarship_box' ), 'lr_scholarship', 'normal', 'high' );
	}

	/**
	 * Path page fields. Saved only when this box is submitted.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function path_box( \WP_Post $post ): void {
		wp_nonce_field( 'lr_path_save', 'lr_path_nonce' );
		echo '<input type="hidden" name="lr_path_present" value="1">';
		self::text_row( 'بالای عنوان', 'lr_eyebrow', (string) get_post_meta( $post->ID, '_lr_eyebrow', true ) );
		echo '<p><label>مقدمه<br><textarea class="large-text" rows="3" name="lr_lead">' . esc_textarea( (string) get_post_meta( $post->ID, '_lr_lead', true ) ) . '</textarea></label></p>';
		$sections = json_decode( (string) get_post_meta( $post->ID, '_lr_sections', true ), true );
		$sections = is_array( $sections ) ? $sections : array();
		echo '<h3>بخش‌ها</h3>';
		for ( $i = 0; $i < 6; $i++ ) {
			$title = (string) ( $sections[ $i ]['title'] ?? '' );
			$body  = (string) ( $sections[ $i ]['body'] ?? '' );
			echo '<p><input class="widefat" name="lr_section_title[]" placeholder="عنوان بخش" value="' . esc_attr( $title ) . '"></p>';
			echo '<p><textarea class="widefat" rows="4" name="lr_section_body[]" placeholder="متن بخش">' . esc_textarea( $body ) . '</textarea></p>';
		}
		self::select_row(
			'داده زنده',
			'lr_catalog',
			(string) get_post_meta( $post->ID, '_lr_catalog', true ),
			array(
				''         => 'ندارد',
				'degree'   => 'دانشگاه‌ها بر اساس مقطع',
				'ranked'   => 'برترین رتبه‌ها',
				'padfak'   => 'پادفک',
				'direct'   => 'پذیرش مستقیم',
				'tuition'  => 'بازه شهریه',
				'children' => 'زیرصفحه‌ها',
			)
		);
		self::select_row(
			'مقطع کاتالوگ',
			'lr_degree',
			(string) get_post_meta( $post->ID, '_lr_degree', true ),
			array(
				''           => '—',
				'bachelor'   => 'کارشناسی',
				'master'     => 'ارشد',
				'phd'        => 'دکتری',
				'specialist' => 'تخصصی',
				'residency'  => 'رزیدنتی',
			)
		);
		self::text_row( 'نامک رشته', 'lr_field', (string) get_post_meta( $post->ID, '_lr_field', true ) );
		self::select_row(
			'فرم',
			'lr_form',
			(string) get_post_meta( $post->ID, '_lr_form', true ),
			array(
				'admission'   => 'پذیرش',
				'consult'     => 'مشاوره',
				'exchange'    => 'استعلام نرخ',
				'immigration' => 'مهاجرت',
				'freight'     => 'استعلام بار',
				'trade'       => 'درخواست تجارت',
				''            => 'بدون فرم',
			)
		);
		self::select_row(
			'نوع درخواست',
			'lr_program',
			(string) get_post_meta( $post->ID, '_lr_program', true ),
			array(
				'degree'        => 'مقطع',
				'padfak'        => 'پادفک',
				'direct_course' => 'پذیرش مستقیم',
				'scholarship'   => 'بورسیه',
			)
		);
		self::select_row(
			'اسکیما',
			'lr_schema',
			(string) get_post_meta( $post->ID, '_lr_schema', true ),
			array(
				''        => 'ندارد',
				'course'  => 'Course',
				'grant'   => 'MonetaryGrant',
				'service' => 'Service',
			)
		);
		self::links( $post->ID );
		self::seo( $post->ID );
	}

	/**
	 * Scholarship fields. Structured values live in lr_scholarships.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function scholarship_box( \WP_Post $post ): void {
		wp_nonce_field( 'lr_path_save', 'lr_path_nonce' );
		echo '<input type="hidden" name="lr_scholarship_present" value="1">';
		$row = \LifeRuss\Core\Scholarships\Store::for_post( $post->ID );
		if ( ! $row ) {
			$row = \LifeRuss\Core\Scholarships\Store::from_meta( $post->ID );
		}
		echo '<p><label>دانشگاه<br><select name="lr_university_id"><option value="0">—</option>';
		foreach ( \LifeRuss\Core\Scholarships\Store::universities() as $id => $name ) {
			echo '<option value="' . esc_attr( (string) $id ) . '" ' . selected( (int) ( $row['university_id'] ?? 0 ), (int) $id, false ) . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select></label></p>';
		self::text_row( 'رشته', 'lr_field_name', (string) ( $row['field_name'] ?? '' ) );
		echo '<p><label>مقطع<br><select name="lr_degree">';
		foreach ( \LifeRuss\Core\Scholarships\Store::degrees() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) ( $row['degree'] ?? '' ), $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>نوع پوشش<br><select name="lr_coverage_type">';
		foreach ( \LifeRuss\Core\Scholarships\Store::coverage_types() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) ( $row['coverage_type'] ?? '' ), $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
		self::text_row( 'درصد پوشش', 'lr_coverage_percent', (string) ( $row['coverage_percent'] ?? '' ) );
		self::text_row( 'سهمیه', 'lr_quota', (string) ( $row['quota'] ?? '' ) );
		self::text_row( 'مهلت', 'lr_deadline', (string) ( $row['deadline'] ?? get_post_meta( $post->ID, '_lr_deadline', true ) ) );
		echo '<p><label>زبان<br><select name="lr_language">';
		foreach ( array(
			''      => 'نامشخص',
			'ru'    => 'روسی',
			'en'    => 'انگلیسی',
			'ru_en' => 'روسی و انگلیسی',
		) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) ( $row['language'] ?? '' ), $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>شرایط<br><textarea class="large-text" rows="4" name="lr_eligibility">' . esc_textarea( (string) ( $row['requirements'] ?? get_post_meta( $post->ID, '_lr_eligibility', true ) ) ) . '</textarea></label></p>';
		self::text_row( 'منبع', 'lr_source', (string) ( $row['source'] ?? '' ) );
		self::text_row( 'نشانی منبع', 'lr_source_url', (string) get_post_meta( $post->ID, '_lr_source_url', true ) );
		self::links( $post->ID );
		self::seo( $post->ID );
	}

	/**
	 * Save a path page.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save_page( int $post_id ): void {
		if ( ! isset( $_POST['lr_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_path_nonce'] ) ), 'lr_path_save' ) ) {
			return;
		}
		if ( ! self::authorized( $post_id ) || empty( $_POST['lr_path_present'] ) ) {
			return;
		}
		update_post_meta( $post_id, '_lr_eyebrow', sanitize_text_field( wp_unslash( $_POST['lr_eyebrow'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_lead', sanitize_textarea_field( wp_unslash( $_POST['lr_lead'] ?? '' ) ) );
		$titles   = isset( $_POST['lr_section_title'] ) ? map_deep( wp_unslash( $_POST['lr_section_title'] ), 'sanitize_text_field' ) : array();
		$bodies   = isset( $_POST['lr_section_body'] ) ? map_deep( wp_unslash( $_POST['lr_section_body'] ), 'wp_kses_post' ) : array();
		$sections = array();
		if ( is_array( $titles ) ) {
			foreach ( $titles as $index => $title ) {
				$body = is_array( $bodies ) && isset( $bodies[ $index ] ) ? (string) $bodies[ $index ] : '';
				if ( '' === $title && '' === trim( wp_strip_all_tags( $body ) ) ) {
					continue;
				}
				$sections[] = array(
					'title' => (string) $title,
					'body'  => $body,
				);
			}
		}
		update_post_meta( $post_id, '_lr_sections', wp_json_encode( $sections, JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $post_id, '_lr_catalog', sanitize_key( wp_unslash( $_POST['lr_catalog'] ?? '' ) ) );
		$degree = sanitize_key( wp_unslash( $_POST['lr_degree'] ?? '' ) );
		update_post_meta( $post_id, '_lr_degree', in_array( $degree, array( 'bachelor', 'master', 'phd', 'specialist', 'residency' ), true ) ? $degree : '' );
		update_post_meta( $post_id, '_lr_field', sanitize_title( wp_unslash( $_POST['lr_field'] ?? '' ) ) );
		$form = sanitize_key( wp_unslash( $_POST['lr_form'] ?? '' ) );
		update_post_meta( $post_id, '_lr_form', in_array( $form, array( 'admission', 'consult', 'exchange', 'immigration', 'freight', 'trade' ), true ) ? $form : '' );
		$program = sanitize_key( wp_unslash( $_POST['lr_program'] ?? '' ) );
		update_post_meta( $post_id, '_lr_program', in_array( $program, array( 'degree', 'padfak', 'direct_course', 'scholarship' ), true ) ? $program : 'degree' );
		$schema = sanitize_key( wp_unslash( $_POST['lr_schema'] ?? '' ) );
		update_post_meta( $post_id, '_lr_schema', in_array( $schema, array( 'course', 'grant', 'service' ), true ) ? $schema : '' );
		self::save_links( $post_id );
		self::save_seo( $post_id );
	}

	/**
	 * Save a scholarship.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save_scholarship( int $post_id ): void {
		if ( ! isset( $_POST['lr_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_path_nonce'] ) ), 'lr_path_save' ) ) {
			return;
		}
		if ( ! self::authorized( $post_id ) || empty( $_POST['lr_scholarship_present'] ) ) {
			return;
		}
		$deadline = sanitize_text_field( wp_unslash( $_POST['lr_deadline'] ?? '' ) );
		$cover    = sanitize_key( wp_unslash( $_POST['lr_coverage_type'] ?? '' ) );
		$percent  = absint( $_POST['lr_coverage_percent'] ?? 0 );
		$needs    = sanitize_textarea_field( wp_unslash( $_POST['lr_eligibility'] ?? '' ) );
		$source   = sanitize_text_field( wp_unslash( $_POST['lr_source'] ?? '' ) );
		update_post_meta( $post_id, '_lr_deadline', $deadline );
		update_post_meta( $post_id, '_lr_coverage', $cover . ( $percent ? ' ' . $percent . '%' : '' ) );
		update_post_meta( $post_id, '_lr_eligibility', $needs );
		update_post_meta( $post_id, '_lr_source', $source );
		update_post_meta( $post_id, '_lr_source_url', esc_url_raw( wp_unslash( $_POST['lr_source_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_last_verified_at', gmdate( 'Y-m-d H:i:s' ) );
		\LifeRuss\Core\Scholarships\Store::upsert(
			$post_id,
			array(
				'university_id'    => absint( $_POST['lr_university_id'] ?? 0 ),
				'field_name'       => sanitize_text_field( wp_unslash( $_POST['lr_field_name'] ?? '' ) ),
				'degree'           => sanitize_key( wp_unslash( $_POST['lr_degree'] ?? '' ) ),
				'coverage_type'    => $cover,
				'coverage_percent' => $percent,
				'quota'            => absint( $_POST['lr_quota'] ?? 0 ),
				'deadline'         => $deadline,
				'language'         => sanitize_key( wp_unslash( $_POST['lr_language'] ?? '' ) ),
				'requirements'     => $needs,
				'source'           => $source,
			)
		);
		self::save_links( $post_id );
		self::save_seo( $post_id );
	}

	/**
	 * Nonce and edit capability.
	 *
	 * @param int $post_id Post id.
	 */
	private static function authorized( int $post_id ): bool {
		if ( ! isset( $_POST['lr_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_path_nonce'] ) ), 'lr_path_save' ) ) {
			return false;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * One text field.
	 *
	 * @param string $label Label.
	 * @param string $name  Input name.
	 * @param string $value Value.
	 */
	private static function text_row( string $label, string $name, string $value ): void {
		echo '<p><label>' . esc_html( $label ) . ' <input class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"></label></p>';
	}

	/**
	 * One select.
	 *
	 * @param string               $label   Label.
	 * @param string               $name    Input name.
	 * @param string               $current Current value.
	 * @param array<string,string> $options Options.
	 */
	private static function select_row( string $label, string $name, string $current, array $options ): void {
		echo '<p><label>' . esc_html( $label ) . ' <select name="' . esc_attr( $name ) . '">';
		foreach ( $options as $value => $text ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $text ) . '</option>';
		}
		echo '</select></label></p>';
	}

	/**
	 * Related content id lists.
	 *
	 * @param int $post_id Post id.
	 */
	private static function links( int $post_id ): void {
		self::text_row( 'سؤال‌ها (شناسه)', 'lr_faq_ids', (string) get_post_meta( $post_id, '_lr_faq_ids', true ) );
		self::text_row( 'نظرها (شناسه)', 'lr_testimonial_ids', (string) get_post_meta( $post_id, '_lr_testimonial_ids', true ) );
		self::text_row( 'دانستنی‌ها (شناسه)', 'lr_guide_ids', (string) get_post_meta( $post_id, '_lr_guide_ids', true ) );
		self::text_row( 'نوشته‌های مجله (شناسه)', 'lr_post_ids', (string) get_post_meta( $post_id, '_lr_post_ids', true ) );
	}

	/**
	 * SEO fields. Stored for anyone who can edit; the SEO role also has edit on these types.
	 *
	 * @param int $post_id Post id.
	 */
	private static function seo( int $post_id ): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			return;
		}
		echo '<input type="hidden" name="lr_seo_present" value="1">';
		self::text_row( 'عنوان سئو', 'lr_seo_title', (string) get_post_meta( $post_id, '_lr_seo_title', true ) );
		echo '<p><label>توضیح سئو<br><textarea class="large-text" rows="3" name="lr_seo_description">' . esc_textarea( (string) get_post_meta( $post_id, '_lr_seo_description', true ) ) . '</textarea></label></p>';
	}

	/**
	 * Persist link lists.
	 *
	 * @param int $post_id Post id.
	 */
	private static function save_links( int $post_id ): void {
		if ( ! isset( $_POST['lr_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_path_nonce'] ) ), 'lr_path_save' ) ) {
			return;
		}
		foreach ( array( 'lr_faq_ids', 'lr_testimonial_ids', 'lr_guide_ids', 'lr_post_ids' ) as $key ) {
			$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
			$ids = array();
			foreach ( explode( ',', $raw ) as $part ) {
				$id = absint( $part );
				if ( $id ) {
					$ids[] = (string) $id;
				}
			}
			update_post_meta( $post_id, '_' . $key, implode( ',', $ids ) );
		}
	}

	/**
	 * Persist SEO copy when the SEO box was shown.
	 *
	 * @param int $post_id Post id.
	 */
	private static function save_seo( int $post_id ): void {
		if ( ! isset( $_POST['lr_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_path_nonce'] ) ), 'lr_path_save' ) ) {
			return;
		}
		if ( empty( $_POST['lr_seo_present'] ) || ! current_user_can( 'lr_edit_seo' ) ) {
			return;
		}
		update_post_meta( $post_id, '_lr_seo_title', sanitize_text_field( wp_unslash( $_POST['lr_seo_title'] ?? '' ) ) );
		update_post_meta( $post_id, '_lr_seo_description', sanitize_textarea_field( wp_unslash( $_POST['lr_seo_description'] ?? '' ) ) );
	}
}
