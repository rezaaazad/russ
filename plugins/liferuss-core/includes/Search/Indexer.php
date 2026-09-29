<?php
/**
 * Keeps the local index and Meilisearch in step with saves, deletes, and imports.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks, cron, and the full reindex.
 */
class Indexer {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'save_post', array( self::class, 'on_save' ), 40, 2 );
		add_action( 'before_delete_post', array( self::class, 'on_delete' ) );
		add_action( 'liferuss_catalog_imported', array( self::class, 'on_import' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'init', array( self::class, 'maybe_bootstrap' ), 30 );
		add_filter( 'cron_schedules', array( self::class, 'schedule_every_fifteen' ) );
		add_action( 'lr_search_queue', array( self::class, 'process' ) );
		add_action( 'admin_post_lr_search_reindex', array( self::class, 'admin_reindex' ) );
	}

	/**
	 * Fifteen-minute cron interval.
	 *
	 * @param array<string, array<string, int|string>> $schedules Schedules.
	 * @return array<string, array<string, int|string>>
	 */
	public static function schedule_every_fifteen( array $schedules ): array {
		$schedules['lr_fifteen_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'Every fifteen minutes',
		);
		return $schedules;
	}

	/**
	 * Ensure the queue worker is scheduled.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'lr_search_queue' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'lr_fifteen_minutes', 'lr_search_queue' );
		}
	}

	/**
	 * Build the local index once after the tables exist.
	 */
	public static function maybe_bootstrap(): void {
		if ( '1' === (string) get_option( 'lr_search_local_ready', '' ) ) {
			return;
		}
		if ( ! self::table_ready() ) {
			return;
		}
		Documents::rebuild();
		update_option( 'lr_search_local_ready', '1', false );
		delete_transient( 'lr_search_typo_pool' );
		if ( Meili::configured() ) {
			self::queue_all();
			Meili::ensure();
		}
	}

	/**
	 * Index or remove one post. A Meili failure is queued.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function on_save( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || '' === Documents::kind( $post->post_type ) ) {
			return;
		}
		if ( ! self::table_ready() ) {
			return;
		}
		$doc = Documents::write( $post_id );
		delete_transient( 'lr_search_typo_pool' );
		if ( ! $doc ) {
			Documents::remove( $post->post_type, $post_id );
			self::push_delete( $post->post_type, $post_id );
			return;
		}
		if ( ! Meili::configured() ) {
			return;
		}
		if ( ! Meili::upsert( $doc ) ) {
			Queue::enqueue( $post->post_type, $post_id, 'upsert' );
		}
	}

	/**
	 * Drop a post from both indexes.
	 *
	 * @param int $post_id Post id.
	 */
	public static function on_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || '' === Documents::kind( $post->post_type ) || ! self::table_ready() ) {
			return;
		}
		Documents::remove( $post->post_type, $post_id );
		delete_transient( 'lr_search_typo_pool' );
		self::push_delete( $post->post_type, $post_id );
	}

	/**
	 * Queue every row of an imported catalog type.
	 *
	 * @param string $type Import type.
	 */
	public static function on_import( string $type ): void {
		$map = array(
			'universities' => 'lr_university',
			'fields'       => 'lr_field',
			'cities'       => 'lr_city',
		);
		if ( ! isset( $map[ $type ] ) || ! self::table_ready() ) {
			return;
		}
		self::queue_type( $map[ $type ] );
	}

	/**
	 * Apply a batch. Stops early when Meilisearch is down.
	 */
	public static function process(): void {
		if ( ! self::table_ready() ) {
			return;
		}
		$want_meili = Meili::configured();
		if ( $want_meili && ! Meili::ensure() ) {
			return;
		}
		foreach ( Queue::pending( 40 ) as $row ) {
			$post_type = (string) $row['object_type'];
			$post_id   = (int) $row['object_id'];
			$id        = (int) $row['id'];
			if ( 'delete' === $row['action'] ) {
				Documents::remove( $post_type, $post_id );
				if ( $want_meili && ! Meili::delete( Documents::key( $post_type, $post_id ) ) ) {
					Queue::failed( $id );
					return;
				}
				Queue::done( $id );
				continue;
			}
			$doc = Documents::write( $post_id );
			if ( ! $doc ) {
				Documents::remove( $post_type, $post_id );
				if ( $want_meili ) {
					Meili::delete( Documents::key( $post_type, $post_id ) );
				}
				Queue::done( $id );
				continue;
			}
			if ( $want_meili && ! Meili::upsert( $doc ) ) {
				Queue::failed( $id );
				return;
			}
			Queue::done( $id );
		}
	}

	/**
	 * Rebuild local documents and queue a Meili push.
	 *
	 * @return int Documents written.
	 */
	public static function reindex(): int {
		if ( ! self::table_ready() ) {
			return 0;
		}
		$count = Documents::rebuild();
		delete_transient( 'lr_search_typo_pool' );
		update_option( 'lr_search_local_ready', '1', false );
		if ( Meili::configured() ) {
			Meili::ensure();
			self::queue_all();
			self::process();
		}
		return $count;
	}

	/**
	 * Admin button. Local rebuild, then the queue.
	 */
	public static function admin_reindex(): void {
		if ( ! current_user_can( 'lr_manage_settings' ) ) {
			wp_die( esc_html__( 'اجازه ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'lr_search_reindex' );
		$count = self::reindex();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'lr-settings',
					'tab'     => 'search',
					'reindex' => $count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Queue every searchable post.
	 */
	private static function queue_all(): void {
		foreach ( array_keys( Documents::types() ) as $post_type ) {
			self::queue_type( $post_type );
		}
	}

	/**
	 * Queue published posts of one type.
	 *
	 * @param string $post_type Post type.
	 */
	private static function queue_type( string $post_type ): void {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = %s", $post_type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $ids as $id ) {
			Queue::enqueue( $post_type, (int) $id, 'upsert' );
		}
	}

	/**
	 * Delete from Meili, or queue the delete.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post id.
	 */
	private static function push_delete( string $post_type, int $post_id ): void {
		if ( ! Meili::configured() ) {
			return;
		}
		$key = Documents::key( $post_type, $post_id );
		if ( '' === $key || ! Meili::delete( $key ) ) {
			Queue::enqueue( $post_type, $post_id, 'delete' );
		}
	}

	/**
	 * Whether dbDelta has created the docs table.
	 */
	private static function table_ready(): bool {
		global $wpdb;
		$name  = $wpdb->prefix . 'lr_search_docs';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) );
		return $found === $name;
	}
}
