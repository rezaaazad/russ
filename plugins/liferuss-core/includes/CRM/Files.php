<?php
/**
 * Lead files stored outside the public uploads tree.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates, stores, and serves lead documents.
 */
class Files {

	/**
	 * Allowed MIME types.
	 *
	 * @var array<string, string>
	 */
	private const MIMES = array(
		'image/jpeg'      => 'jpg',
		'image/png'       => 'png',
		'image/webp'      => 'webp',
		'application/pdf' => 'pdf',
	);

	/**
	 * Register the download endpoint.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_lead_file', array( self::class, 'download' ) );
	}

	/**
	 * Private directory, created on demand.
	 */
	public static function dir(): string {
		$upload = wp_upload_dir();
		$base   = trailingslashit( dirname( $upload['basedir'] ) ) . 'liferuss-private';
		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
			file_put_contents( $base . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $base . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		return $base;
	}

	/**
	 * Store an uploaded file for a lead.
	 *
	 * @param int    $lead_id  Lead id.
	 * @param string $field    $_FILES key.
	 * @param string $doc_type ERD doc_type.
	 * @return int|\WP_Error File row id.
	 */
	public static function store_upload( int $lead_id, string $field, string $doc_type ) {
		if ( empty( $_FILES[ $field ] ) || ! isset( $_FILES[ $field ]['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return new \WP_Error( 'lr_file', __( 'فایلی ارسال نشده است.', 'liferuss-core' ) );
		}
		$error = isset( $_FILES[ $field ]['error'] ) ? (int) $_FILES[ $field ]['error'] : UPLOAD_ERR_NO_FILE; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			return 0;
		}
		if ( UPLOAD_ERR_OK !== $error ) {
			return new \WP_Error( 'lr_file', __( 'آپلود ناموفق بود.', 'liferuss-core' ) );
		}
		$tmp  = (string) $_FILES[ $field ]['tmp_name']; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$size = isset( $_FILES[ $field ]['size'] ) ? (int) $_FILES[ $field ]['size'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$name = isset( $_FILES[ $field ]['name'] ) ? sanitize_file_name( wp_unslash( (string) $_FILES[ $field ]['name'] ) ) : 'file'; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( $size < 1 || $size > 10 * MB_IN_BYTES ) {
			return new \WP_Error( 'lr_file', __( 'حجم فایل باید بین ۱ بایت و ۱۰ مگابایت باشد.', 'liferuss-core' ) );
		}
		$checked = self::inspect( $tmp, $name );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}
		return self::write( $lead_id, $tmp, $name, $checked, $size, $doc_type );
	}

	/**
	 * Copy an existing path (legacy attachment) into private storage.
	 *
	 * @param int    $lead_id  Lead id.
	 * @param string $path     Absolute source path.
	 * @param string $name     Original name.
	 * @param string $doc_type Doc type.
	 * @return int|\WP_Error
	 */
	public static function store_path( int $lead_id, string $path, string $name, string $doc_type ) {
		if ( ! is_readable( $path ) ) {
			return new \WP_Error( 'lr_file', __( 'فایل مبدأ خوانده نشد.', 'liferuss-core' ) );
		}
		$size    = (int) filesize( $path );
		$checked = self::inspect( $path, $name );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}
		return self::write( $lead_id, $path, sanitize_file_name( $name ), $checked, $size, $doc_type, true );
	}

	/**
	 * Stream a file to a user who can see the lead.
	 */
	public static function download(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'lr_lead_file' ) ) {
			wp_die( esc_html__( 'نشست منقضی شده است.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$id   = isset( $_GET['file'] ) ? absint( $_GET['file'] ) : 0;
		$file = Repository::for( 'lead_files' )->find( $id );
		if ( ! $file || ! empty( $file['purged_at'] ) ) {
			wp_die( esc_html__( 'فایل پیدا نشد.', 'liferuss-core' ), '', array( 'response' => 404 ) );
		}
		if ( ! LeadWriter::can_view( (int) $file['lead_id'] ) ) {
			wp_die( esc_html__( 'به این فایل دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$path = self::dir() . '/' . basename( (string) $file['stored_path'] );
		if ( ! is_readable( $path ) ) {
			wp_die( esc_html__( 'فایل روی دیسک نیست.', 'liferuss-core' ), '', array( 'response' => 404 ) );
		}
		nocache_headers();
		header( 'Content-Type: ' . (string) $file['mime_type'] );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( (string) $file['original_name'] ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Delete the bytes for a row and stamp purged_at.
	 *
	 * @param array<string, mixed> $file File row.
	 */
	public static function purge_row( array $file ): void {
		$path = self::dir() . '/' . basename( (string) ( $file['stored_path'] ?? '' ) );
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
		Repository::for( 'lead_files' )->update(
			(int) $file['id'],
			array(
				'purged_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Confirm MIME with finfo.
	 *
	 * @param string $path Temp or source path.
	 * @param string $name Original filename.
	 * @return string|\WP_Error MIME type.
	 */
	private static function inspect( string $path, string $name ) {
		$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
		$mime  = $finfo ? (string) finfo_file( $finfo, $path ) : '';
		if ( $finfo ) {
			finfo_close( $finfo );
		}
		if ( ! isset( self::MIMES[ $mime ] ) ) {
			return new \WP_Error( 'lr_file', __( 'فقط JPG، PNG، WEBP یا PDF پذیرفته می‌شود.', 'liferuss-core' ) );
		}
		$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( 'jpeg' === $ext ) {
			$ext = 'jpg';
		}
		if ( $ext && self::MIMES[ $mime ] !== $ext ) {
			return new \WP_Error( 'lr_file', __( 'پسوند فایل با نوع آن یکی نیست.', 'liferuss-core' ) );
		}
		return $mime;
	}

	/**
	 * Copy bytes and insert the catalogue row.
	 *
	 * @param int    $lead_id  Lead id.
	 * @param string $source   Source path.
	 * @param string $name     Original name.
	 * @param string $mime     MIME.
	 * @param int    $size     Bytes.
	 * @param string $doc_type Doc type.
	 * @param bool   $copy     Copy instead of move.
	 * @return int|\WP_Error
	 */
	private static function write( int $lead_id, string $source, string $name, string $mime, int $size, string $doc_type, bool $copy = false ) {
		$ext  = self::MIMES[ $mime ];
		$hash = hash_file( 'sha256', $source );
		$file = $lead_id . '-' . substr( (string) $hash, 0, 16 ) . '.' . $ext;
		$dest = self::dir() . '/' . $file;
		$ok   = $copy ? copy( $source, $dest ) : rename( $source, $dest ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $ok ) {
			return new \WP_Error( 'lr_file', __( 'ذخیرهٔ فایل ممکن نشد.', 'liferuss-core' ) );
		}
		$types = array( 'passport', 'diploma', 'transcript', 'photo', 'invoice', 'cargo_doc', 'trade_doc', 'contract', 'other' );
		if ( ! in_array( $doc_type, $types, true ) ) {
			$doc_type = 'other';
		}
		$id = Repository::for( 'lead_files' )->insert(
			array(
				'lead_id'       => $lead_id,
				'doc_type'      => $doc_type,
				'original_name' => $name ? $name : ( 'file.' . $ext ),
				'stored_path'   => $file,
				'mime_type'     => $mime,
				'file_size'     => $size,
				'checksum'      => $hash ? $hash : str_repeat( '0', 64 ),
				'uploaded_by'   => get_current_user_id() ? get_current_user_id() : null,
			)
		);
		return $id ? $id : new \WP_Error( 'lr_file', __( 'ردیف فایل ذخیره نشد.', 'liferuss-core' ) );
	}
}
