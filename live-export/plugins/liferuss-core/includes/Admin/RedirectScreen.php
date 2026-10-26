<?php
/**
 * Redirect list, editor, and CSV import.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\Redirects\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO redirect screen.
 */
class RedirectScreen {

	/**
	 * Admin post handlers are submitted to admin-post.php.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_save_redirect', array( self::class, 'save' ) );
		add_action( 'admin_post_lr_import_redirects', array( self::class, 'import' ) );
	}

	/**
	 * List and forms.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_view_redirects' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		$edit = isset( $_GET['id'] ) ? Store::find( absint( $_GET['id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		Chrome::open( 'ریدایرکت‌ها', 'تنظیمات' );
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>ذخیره شد.</p></div>';
		}
		if ( current_user_can( 'lr_manage_redirects' ) ) {
			self::form( $edit );
			self::import_form();
		}
		echo '<table class="widefat striped"><thead><tr><th>مبدأ</th><th>مقصد</th><th>کد</th><th>تطابق</th><th>بازدید</th><th>منبع</th><th></th></tr></thead><tbody>';
		foreach ( Store::all() as $row ) {
			$link = admin_url( 'admin.php?page=lr-redirects&id=' . (int) $row['id'] );
			echo '<tr><td><code>' . esc_html( (string) $row['source_path'] ) . '</code></td>';
			echo '<td><code>' . esc_html( (string) $row['target_url'] ) . '</code></td>';
			echo '<td>' . (int) $row['status_code'] . '</td>';
			echo '<td>' . esc_html( (string) $row['match_type'] ) . '</td>';
			echo '<td>' . (int) $row['hits'] . '</td>';
			echo '<td>' . esc_html( (string) $row['origin'] ) . '</td>';
			echo '<td><a href="' . esc_url( $link ) . '">ویرایش</a></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Add or edit.
	 *
	 * @param array<string, mixed>|null $row Row.
	 */
	private static function form( ?array $row ): void {
		$row = $row ? $row : array();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_save_redirect', 'lr_redirect_nonce' );
		echo '<input type="hidden" name="action" value="lr_save_redirect">';
		echo '<input type="hidden" name="id" value="' . (int) ( $row['id'] ?? 0 ) . '">';
		echo '<table class="form-table"><tr><th>مبدأ</th><td><input class="regular-text" name="source_path" value="' . esc_attr( (string) ( $row['source_path'] ?? '' ) ) . '" required></td></tr>';
		echo '<tr><th>مقصد</th><td><input class="regular-text" name="target_url" value="' . esc_attr( (string) ( $row['target_url'] ?? '' ) ) . '"></td></tr>';
		echo '<tr><th>کد</th><td><select name="status_code">';
		foreach ( array( 301, 302, 410 ) as $code ) {
			echo '<option value="' . (int) $code . '" ' . selected( (int) ( $row['status_code'] ?? 301 ), $code, false ) . '>' . (int) $code . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr><th>تطابق</th><td><select name="match_type">';
		foreach ( array(
			'exact'  => 'دقیق',
			'prefix' => 'پیشوند',
			'regex'  => 'عبارت',
		) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( (string) ( $row['match_type'] ?? 'exact' ), $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr>';
		$active = ! isset( $row['is_active'] ) || ! empty( $row['is_active'] );
		echo '<tr><th>فعال</th><td><label><input type="checkbox" name="is_active" value="1" ' . checked( $active, true, false ) . '> بله</label></td></tr>';
		echo '</table><p><button class="button button-primary">ذخیره</button></p></form>';
	}

	/**
	 * CSV import form.
	 */
	private static function import_form(): void {
		echo '<h2>ورود CSV</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
		wp_nonce_field( 'lr_import_redirects', 'lr_import_nonce' );
		echo '<input type="hidden" name="action" value="lr_import_redirects">';
		echo '<input type="file" name="lr_redirect_csv" accept=".csv,text/csv"> ';
		echo '<button class="button">ورود</button>';
		echo '<p class="description">ستون‌ها: source_path, target_url, status_code, match_type</p></form>';
	}

	/**
	 * Save one row.
	 */
	public static function save(): void {
		if ( ! current_user_can( 'lr_manage_redirects' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_save_redirect', 'lr_redirect_nonce' );
		$match  = isset( $_POST['match_type'] ) ? sanitize_key( wp_unslash( $_POST['match_type'] ) ) : 'exact';
		$source = isset( $_POST['source_path'] ) ? sanitize_text_field( wp_unslash( $_POST['source_path'] ) ) : '';
		if ( 'regex' === $match && false === @preg_match( $source, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			wp_die( esc_html__( 'عبارت منظم نامعتبر است.', 'liferuss-core' ) );
		}
		Store::upsert(
			array(
				'source_path' => $source,
				'target_url'  => isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '',
				'status_code' => isset( $_POST['status_code'] ) ? absint( $_POST['status_code'] ) : 301,
				'match_type'  => $match,
				'is_active'   => empty( $_POST['is_active'] ) ? 0 : 1,
				'origin'      => 'manual',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=lr-redirects&updated=1' ) );
		exit;
	}

	/**
	 * Import a CSV file.
	 */
	public static function import(): void {
		if ( ! current_user_can( 'lr_manage_redirects' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_import_redirects', 'lr_import_nonce' );
		$tmp = isset( $_FILES['lr_redirect_csv']['tmp_name'] ) ? sanitize_text_field( $_FILES['lr_redirect_csv']['tmp_name'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_safe_redirect( admin_url( 'admin.php?page=lr-redirects' ) );
			exit;
		}
		$handle = fopen( $tmp, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			wp_safe_redirect( admin_url( 'admin.php?page=lr-redirects' ) );
			exit;
		}
		$header = fgetcsv( $handle );
		$index  = array();
		if ( is_array( $header ) ) {
			foreach ( $header as $i => $name ) {
				$index[ sanitize_key( (string) $name ) ] = (int) $i;
			}
		}
		while ( ( $line = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$cell = static function ( string $key ) use ( $index, $line ): string {
				if ( ! isset( $index[ $key ], $line[ $index[ $key ] ] ) ) {
					return '';
				}
				return (string) $line[ $index[ $key ] ];
			};
			if ( '' === $cell( 'source_path' ) ) {
				continue;
			}
			Store::upsert(
				array(
					'source_path' => $cell( 'source_path' ),
					'target_url'  => $cell( 'target_url' ),
					'status_code' => absint( $cell( 'status_code' ) ),
					'match_type'  => sanitize_key( $cell( 'match_type' ) ),
					'is_active'   => 1,
					'origin'      => 'import',
				)
			);
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		wp_safe_redirect( admin_url( 'admin.php?page=lr-redirects&updated=1' ) );
		exit;
	}
}
