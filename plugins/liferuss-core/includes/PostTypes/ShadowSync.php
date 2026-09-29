<?php
/**
 * Keeps the 1:1 shadow row in sync with university, field, and city posts.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\PostTypes;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT plus lr_* shadow row.
 */
class ShadowSync {

	/**
	 * Post type => table suffix.
	 *
	 * @var array<string, string>
	 */
	private const MAP = array(
		'lr_university' => 'universities',
		'lr_field'      => 'fields',
		'lr_city'       => 'cities',
	);

	/**
	 * Public URL base for slug redirects.
	 *
	 * @var array<string, string>
	 */
	private const BASES = array(
		'lr_university' => 'universities',
		'lr_field'      => 'fields',
		'lr_city'       => 'cities',
	);

	/**
	 * Register sync hooks.
	 */
	public static function hooks(): void {
		add_action( 'save_post', array( self::class, 'on_save' ), 20, 2 );
		add_action( 'trashed_post', array( self::class, 'on_trash' ) );
		add_action( 'untrashed_post', array( self::class, 'on_untrash' ) );
		add_filter( 'pre_delete_post', array( self::class, 'guard_delete' ), 10, 3 );
		add_action( 'before_delete_post', array( self::class, 'on_before_delete' ), 10, 2 );
		add_action( 'post_updated', array( self::class, 'on_slug_change' ), 10, 3 );
	}

	/**
	 * Create or update the shadow row.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function on_save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( self::MAP[ $post->post_type ] ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}
		self::upsert( $post, 'trash' === $post->post_status );
	}

	/**
	 * Soft-delete the shadow row when the post is trashed.
	 *
	 * @param int $post_id Post id.
	 */
	public static function on_trash( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post instanceof \WP_Post && isset( self::MAP[ $post->post_type ] ) ) {
			self::upsert( $post, true );
		}
	}

	/**
	 * Restore the shadow row.
	 *
	 * @param int $post_id Post id.
	 */
	public static function on_untrash( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post instanceof \WP_Post && isset( self::MAP[ $post->post_type ] ) ) {
			self::upsert( $post, false );
		}
	}

	/**
	 * Permanent deletion is Super Admin only, and blocked while children exist.
	 *
	 * @param mixed    $check  Short-circuit value.
	 * @param \WP_Post $post   Post.
	 * @param bool     $force  Whether this is a force delete.
	 * @return mixed
	 */
	public static function guard_delete( $check, $post, bool $force ) {
		if ( null !== $check || ! $force || ! $post instanceof \WP_Post ) {
			return $check;
		}
		if ( ! isset( self::MAP[ $post->post_type ] ) ) {
			return $check;
		}
		if ( ! current_user_can( 'lr_delete_permanently' ) ) {
			return false;
		}
		$repo = Repository::for( self::MAP[ $post->post_type ] );
		$row  = $repo->find_by( 'post_id', $post->ID, true );
		if ( $row && self::has_dependents( $post->post_type, (int) $row['id'] ) ) {
			return false;
		}
		return $check;
	}

	/**
	 * Remove the shadow row after a permanent delete is allowed.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function on_before_delete( int $post_id, $post ): void {
		unset( $post_id );
		if ( ! $post instanceof \WP_Post || ! isset( self::MAP[ $post->post_type ] ) ) {
			return;
		}
		$repo = Repository::for( self::MAP[ $post->post_type ] );
		$row  = $repo->find_by( 'post_id', $post->ID, true );
		if ( $row ) {
			$repo->hard_delete( (int) $row['id'] );
		}
	}

	/**
	 * Store a 301 when a public slug changes. The front-end redirect runner is later work.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $after   New post.
	 * @param \WP_Post $before  Previous post.
	 */
	public static function on_slug_change( int $post_id, \WP_Post $after, \WP_Post $before ): void {
		unset( $post_id );
		if ( ! isset( self::BASES[ $after->post_type ] ) ) {
			return;
		}
		if ( '' === $before->post_name || $before->post_name === $after->post_name ) {
			return;
		}
		$base   = self::BASES[ $after->post_type ];
		$source = '/' . $base . '/' . $before->post_name . '/';
		$target = '/' . $base . '/' . $after->post_name . '/';
		$hash   = sha1( $source );
		$repo   = Repository::for( 'redirects' );
		$found  = $repo->find_by( 'source_hash', $hash, true );
		$data   = array(
			'source_path' => $source,
			'source_hash' => $hash,
			'target_url'  => $target,
			'status_code' => 301,
			'match_type'  => 'exact',
			'origin'      => 'slug_change',
			'object_type' => str_replace( 'lr_', '', $after->post_type ),
			'object_id'   => $after->ID,
			'is_active'   => 1,
			'created_by'  => get_current_user_id() ? get_current_user_id() : null,
			'deleted_at'  => null,
		);
		if ( $found ) {
			$repo->update( (int) $found['id'], $data );
			return;
		}
		$repo->insert( $data );
	}

	/**
	 * Insert or update the shadow row.
	 *
	 * @param \WP_Post $post    Post.
	 * @param bool     $trashed Whether the row is soft-deleted.
	 */
	private static function upsert( \WP_Post $post, bool $trashed ): void {
		$repo   = Repository::for( self::MAP[ $post->post_type ] );
		$slug   = '' !== $post->post_name ? $post->post_name : 'item-' . $post->ID;
		$status = self::content_status( $post->post_status );
		$now    = gmdate( 'Y-m-d H:i:s' );
		$row    = $repo->find_by( 'post_id', $post->ID, true );
		$title  = '' !== $post->post_title ? $post->post_title : '(بدون عنوان)';

		if ( ! $row ) {
			$data = array(
				'post_id'    => $post->ID,
				'name_fa'    => $title,
				'name_en'    => '',
				'name_ru'    => '',
				'slug'       => self::unique_slug( $repo, $slug, 0 ),
				'status'     => $status,
				'deleted_at' => $trashed ? $now : null,
			);
			if ( 'cities' === self::MAP[ $post->post_type ] ) {
				$data['currency']         = 'RUB';
				$data['source']           = '';
				$data['last_verified_at'] = $now;
			}
			$repo->insert( $data );
			return;
		}

		$repo->update(
			(int) $row['id'],
			array(
				'name_fa'    => $title,
				'slug'       => self::unique_slug( $repo, $slug, (int) $row['id'] ),
				'status'     => $status,
				'deleted_at' => $trashed ? ( $row['deleted_at'] ? $row['deleted_at'] : $now ) : null,
			)
		);
	}

	/**
	 * Map a WordPress status onto the ERD content status.
	 *
	 * @param string $post_status Post status.
	 */
	private static function content_status( string $post_status ): string {
		$map = array(
			'publish'     => 'published',
			'draft'       => 'draft',
			'pending'     => 'review',
			'future'      => 'scheduled',
			'lr_archived' => 'archived',
		);
		return $map[ $post_status ] ?? 'draft';
	}

	/**
	 * Avoid colliding with another shadow slug.
	 *
	 * @param Repository $repo    Repository.
	 * @param string     $slug    Desired slug.
	 * @param int        $keep_id Row id that may already own the slug.
	 */
	private static function unique_slug( Repository $repo, string $slug, int $keep_id ): string {
		$found = $repo->find_by( 'slug', $slug, true );
		if ( ! $found || (int) $found['id'] === $keep_id ) {
			return $slug;
		}
		return $slug . '-' . ( $keep_id ? $keep_id : wp_rand( 1000, 9999 ) );
	}

	/**
	 * Whether other lr_* rows still point at this record.
	 *
	 * @param string $post_type Post type.
	 * @param int    $id        Shadow id.
	 */
	private static function has_dependents( string $post_type, int $id ): bool {
		$checks = array(
			'lr_university' => array(
				'university_fields'      => 'university_id',
				'tuition_fees'           => 'university_id',
				'dormitory_fees'         => 'university_id',
				'university_approvals'   => 'university_id',
				'university_rankings'    => 'university_id',
				'admission_requirements' => 'university_id',
				'intakes'                => 'university_id',
				'prep_programs'          => 'university_id',
				'admission_requests'     => 'university_id',
			),
			'lr_field'      => array(
				'university_fields'      => 'field_id',
				'tuition_fees'           => 'field_id',
				'university_approvals'   => 'field_id',
				'admission_requirements' => 'field_id',
				'intakes'                => 'field_id',
				'admission_requests'     => 'field_id',
			),
			'lr_city'       => array(
				'universities'       => 'city_id',
				'admission_requests' => 'preferred_city_id',
			),
		);
		if ( ! isset( $checks[ $post_type ] ) ) {
			return false;
		}
		foreach ( $checks[ $post_type ] as $suffix => $column ) {
			$exact = Repository::for( $suffix )->find_by( $column, $id );
			if ( $exact ) {
				return true;
			}
		}
		return false;
	}
}
