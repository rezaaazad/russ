<?php
/**
 * Signed, expiring playback. Paid lessons never print a file URL.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Course\Store as CourseStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The lesson page iframes /academy/play/{id}/. That URL checks access, then talks to the provider.
 * ArvanCloud VOD is the default. The provider URL is minted only inside this response.
 */
class Playback {

	/**
	 * Signed URL for one video.
	 *
	 * @param int $video_id Video id.
	 * @param int $user_id  User id. Zero is a public preview.
	 */
	public static function url( int $video_id, int $user_id ): string {
		$ttl = max( 60, (int) Settings::get()['playback_ttl'] );
		$exp = time() + $ttl;
		$sig = self::sign( $video_id, $exp, $user_id );
		return home_url( '/academy/play/' . $video_id . '/?exp=' . $exp . '&uid=' . $user_id . '&sig=' . $sig );
	}

	/**
	 * Whether the signature is still valid.
	 *
	 * @param int    $video_id Video id.
	 * @param int    $exp      Expiry.
	 * @param int    $user_id  User id.
	 * @param string $sig      Signature.
	 */
	public static function valid( int $video_id, int $exp, int $user_id, string $sig ): bool {
		if ( $exp < time() || '' === $sig ) {
			return false;
		}
		return hash_equals( self::sign( $video_id, $exp, $user_id ), $sig );
	}

	/**
	 * HTML player for a video the visitor is allowed to open.
	 *
	 * @param array<string, mixed> $video Video row.
	 * @param bool                 $paid  True when this is paid content.
	 */
	public static function render( array $video, bool $paid ): void {
		$provider = (string) $video['provider'];
		$external = (string) $video['external_id'];
		if ( 'upload' === $provider ) {
			self::stream( $external );
			return;
		}
		$src = self::provider_src( $provider, $external, $paid );
		if ( '' === $src ) {
			echo '<p class="lr-notice">پخش این ویدیو هنوز پیکربندی نشده است.</p>';
			return;
		}
		echo '<iframe class="lr-embed" src="' . esc_url( $src ) . '" title="ویدیو" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
	}

	/**
	 * Public intro embed. A bare file path is not used.
	 *
	 * @param string $url Provider URL.
	 */
	public static function intro( string $url ): string {
		return CourseStore::video_embed( $url );
	}

	/**
	 * First published video on a lesson.
	 *
	 * @param int $lesson_id Lesson id.
	 * @return array<string, mixed>|null
	 */
	public static function video_for( int $lesson_id ): ?array {
		foreach ( Db::sorted( 'lesson_videos', 'lesson_id', $lesson_id ) as $video ) {
			if ( 'published' === $video['status'] ) {
				return $video;
			}
		}
		return null;
	}

	/**
	 * HMAC shared by the lesson page and the player.
	 *
	 * @param int $video_id Video id.
	 * @param int $exp      Expiry.
	 * @param int $user_id  User id.
	 */
	private static function sign( int $video_id, int $exp, int $user_id ): string {
		return hash_hmac( 'sha256', $video_id . '|' . $exp . '|' . $user_id, wp_salt( 'auth' ) );
	}

	/**
	 * Provider URL after the access check. Upload never returns a URL.
	 *
	 * @param string $provider Provider key.
	 * @param string $external External id.
	 * @param bool   $paid     Paid content.
	 */
	private static function provider_src( string $provider, string $external, bool $paid ): string {
		unset( $paid );
		if ( '' === $external || str_contains( $external, '/' ) || str_contains( $external, '\\' ) ) {
			return '';
		}
		$exp = time() + max( 60, (int) Settings::get()['playback_ttl'] );
		if ( 'arvan_vod' === $provider ) {
			$secret = (string) Settings::get()['arvan_secret'];
			if ( '' === $secret ) {
				return '';
			}
			$token = hash_hmac( 'sha256', $external . '|' . $exp, $secret );
			return 'https://player.arvancloud.ir/' . rawurlencode( $external ) . '?expires=' . $exp . '&token=' . $token;
		}
		if ( 'bunny' === $provider ) {
			$secret = (string) Settings::get()['arvan_secret'];
			$token  = '' === $secret ? '' : hash_hmac( 'sha256', $external . $exp, $secret );
			return 'https://iframe.mediadelivery.net/embed/' . rawurlencode( $external ) . '?token=' . $token . '&expires=' . $exp;
		}
		if ( 'youtube' === $provider && preg_match( '/^[A-Za-z0-9_-]{6,}$/', $external ) ) {
			return 'https://www.youtube.com/embed/' . rawurlencode( $external );
		}
		if ( 'aparat' === $provider && preg_match( '/^[A-Za-z0-9_-]+$/', $external ) ) {
			return 'https://www.aparat.com/video/video/embed/videohash/' . rawurlencode( $external ) . '/vt/frame';
		}
		return '';
	}

	/**
	 * Stream an uploaded file from the private directory. The path is never sent to the browser.
	 *
	 * @param string $external Relative file name.
	 */
	private static function stream( string $external ): void {
		$external = basename( $external );
		$path     = self::private_dir() . '/' . $external;
		if ( '' === $external || ! is_readable( $path ) ) {
			status_header( 404 );
			echo esc_html__( 'فایل ویدیو پیدا نشد.', 'liferuss-core' );
			return;
		}
		$mime = wp_check_filetype( $path );
		header( 'Content-Type: ' . ( $mime['type'] ? $mime['type'] : 'video/mp4' ) );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: private, no-store' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	}

	/**
	 * Private academy directory, outside the web root when possible.
	 */
	public static function private_dir(): string {
		$base = dirname( ABSPATH ) . '/liferuss-private/academy';
		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
		}
		return $base;
	}
}
