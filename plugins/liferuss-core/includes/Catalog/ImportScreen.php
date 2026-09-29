<?php
/**
 * Admin CSV import and export screen.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upload, dry-run, and download for catalog CSV files.
 */
class ImportScreen {

	/**
	 * Render the screen. Capability is enforced by the menu.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'اجازهٔ این کار را ندارید.', 'liferuss-core' ) );
		}
		$report = null;
		if ( isset( $_POST['lr_import_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_import_nonce'] ) ), 'lr_catalog_import' ) ) {
			$report = self::handle_upload();
		}
		if ( isset( $_GET['lr_export'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'lr_catalog_export' ) ) {
			Importer::export( sanitize_key( wp_unslash( $_GET['lr_export'] ) ) );
		}
		echo '<div class="wrap"><h1>ورود و خروج CSV</h1>';
		echo '<p>کلید یکتا اسلاگ است. وضعیت پیش‌فرض پیش‌نویس است. معادل دلار شهریه از نرخ دستی تنظیمات حساب می‌شود.</p>';
		if ( is_array( $report ) ) {
			echo '<div class="notice notice-info"><p>';
			echo esc_html( sprintf( 'ردیف %d، ساخته‌شده %d، به‌روز %d، ردشده %d.', (int) $report['rows'], (int) $report['created'], (int) $report['updated'], (int) $report['skipped'] ) );
			if ( ! empty( $report['dry_run'] ) ) {
				echo ' ' . esc_html__( 'این یک اجرای آزمایشی بود و چیزی نوشته نشد.', 'liferuss-core' );
			}
			echo '</p>';
			if ( ! empty( $report['errors'] ) && is_array( $report['errors'] ) ) {
				echo '<ul>';
				foreach ( array_slice( $report['errors'], 0, 30 ) as $error ) {
					echo '<li>' . esc_html( (string) $error ) . '</li>';
				}
				echo '</ul>';
			}
			echo '</div>';
		}
		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'lr_catalog_import', 'lr_import_nonce' );
		echo '<p><label>نوع <select name="lr_import_type">';
		foreach ( Importer::types() as $type ) {
			echo '<option value="' . esc_attr( $type ) . '">' . esc_html( $type ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><input type="file" name="lr_csv" accept=".csv,text/csv" required></p>';
		echo '<p><label><input type="checkbox" name="lr_dry_run" value="1" checked> اجرای آزمایشی (بدون نوشتن)</label></p>';
		submit_button( 'اجرای ورود' );
		echo '</form><h2>خروجی</h2><ul>';
		foreach ( Importer::types() as $type ) {
			$url = wp_nonce_url( admin_url( 'admin.php?page=lr-uni-csv&lr_export=' . $type ), 'lr_catalog_export' );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $type ) . '</a></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Validate the upload and run the importer.
	 *
	 * @return array<string, mixed>
	 */
	private static function handle_upload(): array {
		check_admin_referer( 'lr_catalog_import', 'lr_import_nonce' );
		$type = isset( $_POST['lr_import_type'] ) ? sanitize_key( wp_unslash( $_POST['lr_import_type'] ) ) : '';
		$dry  = ! empty( $_POST['lr_dry_run'] );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filesystem path from PHP, checked with is_uploaded_file().
		$tmp = isset( $_FILES['lr_csv']['tmp_name'] ) ? $_FILES['lr_csv']['tmp_name'] : '';
		if ( ! is_string( $tmp ) || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			return array(
				'rows'    => 0,
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
				'dry_run' => $dry,
				'errors'  => array( 'فایلی دریافت نشد.' ),
			);
		}
		$size = isset( $_FILES['lr_csv']['size'] ) ? (int) $_FILES['lr_csv']['size'] : 0;
		if ( $size > 5 * MB_IN_BYTES ) {
			return array(
				'rows'    => 0,
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
				'dry_run' => $dry,
				'errors'  => array( 'حجم فایل بیشتر از ۵ مگابایت است.' ),
			);
		}
		return Importer::run( $type, $tmp, $dry );
	}
}
