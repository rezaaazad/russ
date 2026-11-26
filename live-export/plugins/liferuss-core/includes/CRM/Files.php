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

	public const SOURCING_MAX_FILES = 5;

	public const SOURCING_MAX_BYTES = 20971520;

	/**
	 * Cargo per-file ceiling. Change this one constant to 10485760 for 10 MB.
	 */
	public const CARGO_MAX_BYTES = 20971520;

	/**
	 * Per-file ceiling for sourcing: 20 MB, or the lower PHP upload ceiling.
	 */
	public static function sourcing_limit(): int {
		return self::capped_bytes( self::SOURCING_MAX_BYTES );
	}

	/**
	 * Per-file ceiling for cargo. Uses CARGO_MAX_BYTES, then the PHP ceiling.
	 */
	public static function cargo_limit(): int {
		return self::capped_bytes( self::CARGO_MAX_BYTES );
	}

	/**
	 * Configured cap, or less when PHP will not accept that size.
	 *
	 * @param int $configured Byte cap.
	 */
	private static function capped_bytes( int $configured ): int {
		$cap  = $configured;
		$up   = self::ini_bytes( ini_get( 'upload_max_filesize' ) );
		$post = self::ini_bytes( ini_get( 'post_max_size' ) );
		if ( $up > 0 ) {
			$cap = min( $cap, $up );
		}
		if ( $post > 0 ) {
			$cap = min( $cap, $post );
		}
		return $cap;
	}

	/**
	 * Bytes in an ini size.
	 *
	 * @param mixed $value Raw ini value.
	 */
	private static function ini_bytes( $value ): int {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return 0;
		}
		$unit = strtolower( substr( $value, -1 ) );
		$num  = (float) $value;
		if ( 'g' === $unit ) {
			return (int) ( $num * 1024 * 1024 * 1024 );
		}
		if ( 'm' === $unit ) {
			return (int) ( $num * 1024 * 1024 );
		}
		if ( 'k' === $unit ) {
			return (int) ( $num * 1024 );
		}
		return (int) $num;
	}

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
		$base = trailingslashit( dirname( ABSPATH ) ) . 'liferuss-private';
		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
			self::guard( $base );
		}
		$legacy = trailingslashit( dirname( wp_upload_dir()['basedir'] ) ) . 'liferuss-private';
		if ( is_dir( $legacy ) ) {
			self::guard( $legacy );
		}
		return $base;
	}

	/**
	 * Block directory listing and direct HTTP access where the server honors it.
	 *
	 * @param string $base Directory.
	 */
	private static function guard( string $base ): void {
		if ( ! is_file( $base . '/index.php' ) ) {
			file_put_contents( $base . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$rules = "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|cgi|pl)$\">\nRequire all denied\n</FilesMatch>\nphp_flag engine off\nRemoveHandler .php .phtml .phar\nRequire all denied\nDeny from all\n";
		if ( ! is_file( $base . '/.htaccess' ) || false === strpos( (string) file_get_contents( $base . '/.htaccess' ), 'php_flag engine off' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			file_put_contents( $base . '/.htaccess', $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * Store an uploaded file for a lead.
	 *
	 * @param int    $lead_id  Lead id.
	 * @param string $field    $_FILES key.
	 * @param string $doc_type ERD doc_type.
	 * @return int|\WP_Error File row id.
	 */
	public static function store_upload( int $lead_id, string $field, string $doc_type, int $max_bytes = 0, array $allow = array() ) {
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
		$limit = $max_bytes > 0 ? $max_bytes : 10 * MB_IN_BYTES;
		if ( $size < 1 || $size > $limit ) {
			return new \WP_Error( 'lr_file', __( 'حجم فایل از سقف مجاز بیشتر است.', 'liferuss-core' ) );
		}
		$checked = self::inspect( $tmp, $name );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}
		if ( $allow && ! isset( $allow[ $checked ] ) ) {
			return new \WP_Error( 'lr_file', __( 'فقط JPG، PNG یا PDF پذیرفته می‌شود.', 'liferuss-core' ) );
		}
		return self::write( $lead_id, $tmp, $name, $checked, $size, $doc_type );
	}

	/**
	 * Store up to several files from one multi-file input.
	 *
	 * @param int                  $lead_id   Lead id.
	 * @param string               $field     $_FILES key.
	 * @param string               $doc_type  Doc type.
	 * @param int                  $max_files File count.
	 * @param int                  $max_bytes Bytes per file.
	 * @param array<string, string> $allow    MIME to extension.
	 * @return true|\WP_Error
	 */
	public static function store_many( int $lead_id, string $field, string $doc_type, int $max_files, int $max_bytes, array $allow ) {
		if ( empty( $_FILES[ $field ] ) || ! isset( $_FILES[ $field ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return true;
		}
		$bag   = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$names = $bag['name'];
		if ( ! is_array( $names ) ) {
			$names        = array( $names );
			$bag['name']  = $names;
			$bag['type']  = array( $bag['type'] ?? '' );
			$bag['tmp_name'] = array( $bag['tmp_name'] ?? '' );
			$bag['error'] = array( $bag['error'] ?? UPLOAD_ERR_NO_FILE );
			$bag['size']  = array( $bag['size'] ?? 0 );
		}
		$present = 0;
		foreach ( $bag['error'] as $error ) {
			if ( UPLOAD_ERR_NO_FILE !== (int) $error ) {
				++$present;
			}
		}
		if ( 0 === $present ) {
			return true;
		}
		if ( $present > $max_files ) {
			return new \WP_Error( 'lr_file', __( 'حداکثر ۵ فایل می‌توانید بفرستید.', 'liferuss-core' ) );
		}
		foreach ( $bag['error'] as $index => $error ) {
			$error = (int) $error;
			if ( UPLOAD_ERR_NO_FILE === $error ) {
				continue;
			}
			if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
				return new \WP_Error( 'lr_file', __( 'حجم فایل از سقف سرور بیشتر است.', 'liferuss-core' ) );
			}
			if ( UPLOAD_ERR_OK !== $error ) {
				return new \WP_Error( 'lr_file', __( 'آپلود ناموفق بود.', 'liferuss-core' ) );
			}
			$tmp  = (string) ( $bag['tmp_name'][ $index ] ?? '' );
			$size = (int) ( $bag['size'][ $index ] ?? 0 );
			$name = sanitize_file_name( (string) ( $bag['name'][ $index ] ?? 'file' ) );
			if ( $size < 1 || $size > $max_bytes ) {
				return new \WP_Error( 'lr_file', __( 'هر فایل باید کوچک‌تر از سقف مجاز باشد.', 'liferuss-core' ) );
			}
			$checked = self::inspect( $tmp, $name );
			if ( is_wp_error( $checked ) ) {
				return $checked;
			}
			if ( $allow && ! isset( $allow[ $checked ] ) ) {
				return new \WP_Error( 'lr_file', __( 'فقط JPG، PNG، WEBP یا PDF پذیرفته می‌شود.', 'liferuss-core' ) );
			}
			$stored = self::write( $lead_id, $tmp, $name, $checked, $size, $doc_type );
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
		}
		return true;
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
		$owner = is_user_logged_in() && class_exists( '\LifeRuss\Core\Account\Portal' ) && \LifeRuss\Core\Account\Portal::owns( get_current_user_id(), (int) $file['lead_id'] );
		if ( ! $owner && ! LeadWriter::can_view( (int) $file['lead_id'] ) ) {
			wp_die( esc_html__( 'به این فایل دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$path = self::locate( (string) $file['stored_path'] );
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
		$path = self::locate( (string) ( $file['stored_path'] ?? '' ) );
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
	 * New files live outside the web root. Older rows may still sit under wp-content.
	 *
	 * @param string $stored Stored file name.
	 */
	private static function locate( string $stored ): string {
		$name = basename( $stored );
		$new  = self::dir() . '/' . $name;
		if ( is_file( $new ) ) {
			return $new;
		}
		$legacy = trailingslashit( dirname( wp_upload_dir()['basedir'] ) ) . 'liferuss-private/' . $name;
		return is_file( $legacy ) ? $legacy : $new;
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
		$file = bin2hex( random_bytes( 16 ) ) . '.' . $ext;
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
