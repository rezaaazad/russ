<?php
/**
 * Curated university comparisons.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Compare\Pages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List and edit indexable comparison pages.
 */
class CompareScreen {

	/**
	 * Admin post handlers.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_compare_save', array( self::class, 'save' ) );
		add_action( 'admin_post_lr_compare_delete', array( self::class, 'delete' ) );
	}

	/**
	 * Screen.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = $id ? Pages::by_id( $id ) : null;
		Chrome::open( 'مقایسه دانشگاه‌ها', 'تنظیمات' );
		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>ذخیره شد.</p></div>';
		}
		if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		self::form( $page );
		echo '<h2>صفحه‌های ذخیره‌شده</h2>';
		echo '<table class="widefat striped"><thead><tr><th>نامک</th><th>دانشگاه‌ها</th><th>ایندکس</th><th></th></tr></thead><tbody>';
		$rows = Pages::all();
		if ( ! $rows ) {
			echo '<tr><td colspan="4">هنوز صفحه‌ای نیست.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$edit = add_query_arg(
				array(
					'page' => 'lr-compare',
					'id'   => (int) $row['id'],
				),
				admin_url( 'admin.php' )
			);
			echo '<tr><td><a href="' . esc_url( $edit ) . '">' . esc_html( (string) $row['slug'] ) . '</a></td>';
			echo '<td>' . esc_html( (string) $row['uni_slugs'] ) . '</td>';
			echo '<td>' . ( ! empty( $row['is_indexable'] ) ? 'بله' : 'خیر' ) . '</td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'lr_compare_delete' );
			echo '<input type="hidden" name="action" value="lr_compare_delete">';
			echo '<input type="hidden" name="id" value="' . esc_attr( (string) $row['id'] ) . '">';
			submit_button( 'حذف', 'delete', 'submit', false );
			echo '</form></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Edit form.
	 *
	 * @param array<string, mixed>|null $page Page.
	 */
	private static function form( ?array $page ): void {
		$faq   = $page ? Pages::faq( (string) $page['faq'] ) : array();
		$faq[] = array(
			'q' => '',
			'a' => '',
		);
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_compare_save' );
		echo '<input type="hidden" name="action" value="lr_compare_save">';
		echo '<input type="hidden" name="id" value="' . esc_attr( (string) ( $page['id'] ?? 0 ) ) . '">';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th scope="row"><label for="lr_compare_slug">نامک</label></th><td>';
		echo '<input class="regular-text" id="lr_compare_slug" name="slug" value="' . esc_attr( (string) ( $page['slug'] ?? '' ) ) . '" placeholder="msu-vs-spbu">';
		echo '<p class="description">آدرس ایندکس‌شونده: /compare/نامک/</p></td></tr>';
		echo '<tr><th scope="row"><label for="lr_compare_unis">دانشگاه‌ها</label></th><td>';
		echo '<input class="regular-text" id="lr_compare_unis" name="uni_slugs" value="' . esc_attr( (string) ( $page['uni_slugs'] ?? '' ) ) . '" placeholder="msu,spbu">';
		echo '<p class="description">دو تا چهار نامک، با ویرگول.</p></td></tr>';
		echo '<tr><th scope="row"><label for="lr_compare_intro">مقدمه</label></th><td>';
		echo '<textarea class="large-text" rows="4" id="lr_compare_intro" name="intro">' . esc_textarea( (string) ( $page['intro'] ?? '' ) ) . '</textarea></td></tr>';
		echo '<tr><th scope="row">پرسش‌ها</th><td>';
		foreach ( $faq as $item ) {
			echo '<p><input class="regular-text" name="faq_q[]" value="' . esc_attr( $item['q'] ) . '" placeholder="پرسش"> ';
			echo '<input class="regular-text" name="faq_a[]" value="' . esc_attr( $item['a'] ) . '" placeholder="پاسخ"></p>';
		}
		echo '</td></tr>';
		echo '<tr><th scope="row">ایندکس</th><td><label><input type="checkbox" name="is_indexable" value="1" ' . checked( ! empty( $page['is_indexable'] ), true, false ) . '> این جفت در نتایج جستجو بیاید</label></td></tr>';
		echo '</tbody></table>';
		submit_button( $page ? 'به‌روزرسانی' : 'افزودن' );
		echo '</form>';
	}

	/**
	 * Save handler.
	 */
	public static function save(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_compare_save' );
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$slug  = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$unis  = isset( $_POST['uni_slugs'] ) ? sanitize_text_field( wp_unslash( $_POST['uni_slugs'] ) ) : '';
		$intro = isset( $_POST['intro'] ) ? wp_kses_post( wp_unslash( $_POST['intro'] ) ) : '';
		$slugs = array();
		$parts = preg_split( '/[,\s]+/', $unis );
		foreach ( is_array( $parts ) ? $parts : array() as $part ) {
			$part = sanitize_title( (string) $part );
			if ( $part && ! in_array( $part, $slugs, true ) ) {
				$slugs[] = $part;
			}
		}
		$back = admin_url( 'admin.php?page=lr-compare' );
		if ( count( $slugs ) < 2 || count( $slugs ) > 4 ) {
			wp_safe_redirect( add_query_arg( 'error', rawurlencode( 'دو تا چهار دانشگاه لازم است.' ), $back ) );
			exit;
		}
		if ( '' === $slug ) {
			$slug = implode( '-vs-', $slugs );
		}
		$other = Pages::by_slug( $slug );
		if ( $other && (int) $other['id'] !== $id ) {
			wp_safe_redirect( add_query_arg( 'error', rawurlencode( 'این نامک قبلاً استفاده شده است.' ), $back ) );
			exit;
		}
		$faq = array();
		$qs  = isset( $_POST['faq_q'] ) && is_array( $_POST['faq_q'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['faq_q'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$as  = isset( $_POST['faq_a'] ) && is_array( $_POST['faq_a'] ) ? array_map( 'sanitize_textarea_field', wp_unslash( $_POST['faq_a'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $qs as $index => $question ) {
			$question = (string) $question;
			$answer   = (string) ( $as[ $index ] ?? '' );
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			$faq[] = array(
				'q' => $question,
				'a' => $answer,
			);
			if ( count( $faq ) >= 10 ) {
				break;
			}
		}
		Pages::save(
			$id,
			array(
				'slug'         => $slug,
				'uni_slugs'    => implode( ',', $slugs ),
				'intro'        => $intro,
				'faq'          => wp_json_encode( $faq, JSON_UNESCAPED_UNICODE ),
				'is_indexable' => isset( $_POST['is_indexable'] ) ? 1 : 0,
			)
		);
		wp_safe_redirect( add_query_arg( 'saved', '1', $back ) );
		exit;
	}

	/**
	 * Delete handler.
	 */
	public static function delete(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_compare_delete' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id ) {
			Pages::delete( $id );
		}
		wp_safe_redirect( add_query_arg( 'saved', '1', admin_url( 'admin.php?page=lr-compare' ) ) );
		exit;
	}
}
