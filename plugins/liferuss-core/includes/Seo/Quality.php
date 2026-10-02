<?php
/**
 * Indexing gate for entities and articles.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * A page stays noindex until the checklist is complete. Demo rows never pass.
 */
class Quality {

	/**
	 * Minimum plain-text length for an entity.
	 */
	private const MIN_CHARS = 300;

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( self::class, 'box' ) );
		add_action( 'save_post', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
	}

	/**
	 * Types that use the checklist.
	 *
	 * @return string[]
	 */
	public static function types(): array {
		return array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship', 'lr_guide', 'post' );
	}

	/**
	 * Whether a post may be indexed.
	 *
	 * @param int $post_id Post id.
	 */
	public static function indexable_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}
		if ( ! in_array( $post->post_type, self::types(), true ) ) {
			return true;
		}
		foreach ( self::checks( $post_id ) as $check ) {
			if ( empty( $check['ok'] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Program URLs are indexable only with real data and a passing university.
	 *
	 * @param int $post_id University post.
	 * @param int $program_id Program row.
	 */
	public static function program_indexable( int $post_id, int $program_id ): bool {
		if ( ! self::indexable_post( $post_id ) ) {
			return false;
		}
		$fact = Facts::latest( 'program', $program_id );
		return (bool) $fact && '' !== (string) $fact['source_label'] && '' !== (string) $fact['last_verified_at'];
	}

	/**
	 * Checklist rows for the editor and the gate.
	 *
	 * @param int $post_id Post id.
	 * @return array<int, array{label: string, ok: bool}>
	 */
	public static function checks( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}
		$demo = '1' === (string) get_post_meta( $post_id, '_lr_demo', true );
		if ( in_array( $post->post_type, array( 'post', 'lr_guide' ), true ) ) {
			$author = get_the_author_meta( 'description', (int) $post->post_author );
			return array(
				array(
					'label' => 'ردیف نمونه نیست',
					'ok'    => ! $demo,
				),
				array(
					'label' => 'بیوگرافی نویسنده',
					'ok'    => '' !== trim( (string) $author ),
				),
				array(
					'label' => 'منابع',
					'ok'    => '' !== trim( (string) get_post_meta( $post_id, '_lr_sources', true ) ),
				),
				array(
					'label' => 'تاریخ بازبینی',
					'ok'    => '' !== (string) get_post_meta( $post_id, '_lr_reviewed_at', true ),
				),
			);
		}
		$entity = self::entity_for_post( $post );
		$fact   = $entity ? Facts::latest( $entity['type'], $entity['id'] ) : null;
		$text   = trim( wp_strip_all_tags( $post->post_content ) );
		$faqs   = function_exists( 'liferuss_catalog_faqs' ) ? liferuss_catalog_faqs( $post_id ) : array();
		return array(
			array(
				'label' => 'ردیف نمونه نیست',
				'ok'    => ! $demo,
			),
			array(
				'label' => 'منبع و تاریخ بررسی',
				'ok'    => $fact && '' !== (string) $fact['source_label'] && '' !== (string) $fact['last_verified_at'],
			),
			array(
				'label' => 'حداقل ' . (string) self::MIN_CHARS . ' نویسه متن',
				'ok'    => mb_strlen( $text ) >= self::MIN_CHARS,
			),
			array(
				'label' => 'دست‌کم ۳ پیوند ورودی',
				'ok'    => self::inlinks( $post, $entity ) >= 3,
			),
			array(
				'label' => 'دست‌کم یک پرسش متداول',
				'ok'    => count( $faqs ) >= 1,
			),
		);
	}

	/**
	 * Meta box.
	 */
	public static function box(): void {
		foreach ( self::types() as $type ) {
			add_meta_box( 'lr_seo_quality', 'چک‌لیست سئو', array( self::class, 'render' ), $type, 'normal', 'high' );
		}
	}

	/**
	 * Checklist markup.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'lr_seo_quality', 'lr_seo_quality_nonce' );
		echo '<ul class="lr-seo-check">';
		foreach ( self::checks( $post->ID ) as $check ) {
			$class = ! empty( $check['ok'] ) ? 'is-ok' : 'is-miss';
			$mark  = ! empty( $check['ok'] ) ? '✓' : '✗';
			echo '<li class="' . esc_attr( $class ) . '">' . esc_html( $mark . ' ' . $check['label'] ) . '</li>';
		}
		echo '</ul>';
		if ( in_array( $post->post_type, array( 'post', 'lr_guide' ), true ) ) {
			echo '<p><label>منابع<br><textarea name="lr_sources" rows="3" class="widefat">' . esc_textarea( (string) get_post_meta( $post->ID, '_lr_sources', true ) ) . '</textarea></label></p>';
			echo '<p><label>تاریخ بازبینی<br><input type="date" name="lr_reviewed_at" value="' . esc_attr( (string) get_post_meta( $post->ID, '_lr_reviewed_at', true ) ) . '"></label></p>';
			self::entity_picker( $post->ID );
			return;
		}
		$entity = self::entity_for_post( $post );
		$fact   = $entity ? Facts::latest( $entity['type'], $entity['id'] ) : null;
		echo '<p><label>برچسب منبع<br><input class="widefat" name="lr_source_label" value="' . esc_attr( $fact ? (string) $fact['source_label'] : '' ) . '"></label></p>';
		echo '<p><label>نشانی منبع<br><input class="widefat" type="url" name="lr_source_url" value="' . esc_attr( $fact ? (string) $fact['source_url'] : '' ) . '"></label></p>';
		echo '<p><label>سال تحصیلی<br><input name="lr_academic_year" value="' . esc_attr( $fact ? (string) $fact['academic_year'] : Facts::academic_year() ) . '"></label></p>';
	}

	/**
	 * Save sources, the review date, a fact version, and picked entities.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['lr_seo_quality_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_seo_quality_nonce'] ) ), 'lr_seo_quality' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::types(), true ) ) {
			return;
		}
		if ( isset( $_POST['lr_sources'] ) ) {
			update_post_meta( $post_id, '_lr_sources', sanitize_textarea_field( wp_unslash( $_POST['lr_sources'] ) ) );
		}
		if ( isset( $_POST['lr_reviewed_at'] ) ) {
			update_post_meta( $post_id, '_lr_reviewed_at', sanitize_text_field( wp_unslash( $_POST['lr_reviewed_at'] ) ) );
		}
		$entity = self::entity_for_post( $post );
		$label  = isset( $_POST['lr_source_label'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_source_label'] ) ) : '';
		if ( $entity && '' !== $label ) {
			$url  = isset( $_POST['lr_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['lr_source_url'] ) ) : '';
			$year = isset( $_POST['lr_academic_year'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_academic_year'] ) ) : Facts::academic_year();
			Facts::record( $entity['type'], $entity['id'], 'profile', wp_strip_all_tags( $post->post_title ), $year, $label, $url, current_time( 'mysql', true ), get_current_user_id() );
		}
		if ( isset( $_POST['lr_entities'] ) && is_array( $_POST['lr_entities'] ) ) {
			$picked = array_map( 'sanitize_text_field', wp_unslash( $_POST['lr_entities'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line via array_map.
			self::save_entities( $post_id, $picked );
		}
	}

	/**
	 * Styles for the checklist on the post editor.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, self::types(), true ) ) {
			return;
		}
		wp_enqueue_style( 'liferuss-core-admin', LIFERUSS_CORE_URL . 'assets/admin.css', array(), LIFERUSS_CORE_VERSION );
	}

	/**
	 * Shadow row for a catalog post.
	 *
	 * @param \WP_Post $post Post.
	 * @return array{type: string, id: int}|null
	 */
	private static function entity_for_post( \WP_Post $post ): ?array {
		$map = array(
			'lr_university'  => array( 'university', 'universities' ),
			'lr_field'       => array( 'field', 'fields' ),
			'lr_city'        => array( 'city', 'cities' ),
			'lr_scholarship' => array( 'scholarship', 'scholarships' ),
		);
		if ( ! isset( $map[ $post->post_type ] ) ) {
			return null;
		}
		$row = \LifeRuss\Core\Repositories\Repository::for( $map[ $post->post_type ][1] )->find_by( 'post_id', $post->ID );
		if ( ! $row ) {
			return null;
		}
		return array(
			'type' => $map[ $post->post_type ][0],
			'id'   => (int) $row['id'],
		);
	}

	/**
	 * Inbound links from the automatic graph and the entity map.
	 *
	 * @param \WP_Post                          $post   Post.
	 * @param array{type: string, id: int}|null $entity Entity.
	 */
	private static function inlinks( \WP_Post $post, ?array $entity ): int {
		global $wpdb;
		$count = 0;
		if ( $entity ) {
			$table  = $wpdb->prefix . 'lr_seo_entity_links';
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE entity_type = %s AND entity_id = %d", $entity['type'], $entity['id'] ) );
		}
		if ( 'lr_university' === $post->post_type && $entity ) {
			$uni = \LifeRuss\Core\Repositories\Repository::for( 'universities' )->find( $entity['id'] );
			if ( $uni && ! empty( $uni['city_id'] ) ) {
				++$count;
			}
			$programs = $wpdb->prefix . 'lr_university_fields';
			$count   += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT field_id) FROM `{$programs}` WHERE university_id = %d AND status = 'active' AND deleted_at IS NULL", $entity['id'] ) );
			++$count;
		}
		if ( 'lr_field' === $post->post_type && $entity ) {
			$programs = $wpdb->prefix . 'lr_university_fields';
			$count   += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT university_id) FROM `{$programs}` WHERE field_id = %d AND status = 'active' AND deleted_at IS NULL", $entity['id'] ) );
			++$count;
		}
		if ( 'lr_city' === $post->post_type && $entity ) {
			$unis   = $wpdb->prefix . 'lr_universities';
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$unis}` WHERE city_id = %d AND status = 'published' AND deleted_at IS NULL", $entity['id'] ) );
			++$count;
		}
		return $count;
	}

	/**
	 * Entity checkboxes. Values are type:id.
	 *
	 * @param int $post_id Post id.
	 */
	private static function entity_picker( int $post_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_entity_links';
		$have  = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT entity_type, entity_id FROM `{$table}` WHERE post_id = %d", $post_id ), ARRAY_A ) as $row ) {
			$have[ $row['entity_type'] . ':' . $row['entity_id'] ] = true;
		}
		echo '<fieldset><legend>نهادهای مرتبط</legend>';
		$sets = array(
			'university' => $wpdb->get_results( "SELECT id, name_fa AS label FROM `{$wpdb->prefix}lr_universities` WHERE deleted_at IS NULL ORDER BY name_fa ASC LIMIT 40", ARRAY_A ),
			'field'      => $wpdb->get_results( "SELECT id, name_fa AS label FROM `{$wpdb->prefix}lr_fields` WHERE deleted_at IS NULL ORDER BY name_fa ASC LIMIT 40", ARRAY_A ),
			'city'       => $wpdb->get_results( "SELECT id, name_fa AS label FROM `{$wpdb->prefix}lr_cities` WHERE deleted_at IS NULL ORDER BY name_fa ASC LIMIT 40", ARRAY_A ),
		);
		foreach ( $sets as $type => $rows ) {
			foreach ( (array) $rows as $row ) {
				$value = $type . ':' . (int) $row['id'];
				echo '<label style="display:block"><input type="checkbox" name="lr_entities[]" value="' . esc_attr( $value ) . '" ' . checked( isset( $have[ $value ] ), true, false ) . '> ' . esc_html( (string) $row['label'] ) . '</label>';
			}
		}
		echo '</fieldset>';
	}

	/**
	 * Replace the entity map for one article.
	 *
	 * @param int               $post_id Post id.
	 * @param array<int, mixed> $raw  Posted values.
	 */
	private static function save_entities( int $post_id, array $raw ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_entity_links';
		$wpdb->delete( $table, array( 'post_id' => $post_id ) );
		foreach ( $raw as $value ) {
			$parts = explode( ':', sanitize_text_field( (string) $value ) );
			if ( 2 !== count( $parts ) || ! in_array( $parts[0], array( 'university', 'field', 'city' ), true ) ) {
				continue;
			}
			$id  = absint( $parts[1] );
			$url = Links::entity_url( $parts[0], $id );
			if ( '' === $url ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'post_id'     => $post_id,
					'entity_type' => $parts[0],
					'entity_id'   => $id,
					'label'       => wp_strip_all_tags( get_the_title( Links::entity_post( $parts[0], $id ) ) ),
					'created_at'  => current_time( 'mysql', true ),
				)
			);
		}
	}
}
