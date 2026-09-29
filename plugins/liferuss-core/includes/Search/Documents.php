<?php
/**
 * Local search documents. This table is the MySQL fallback and the Meili source.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders

/**
 * One row per published post we search.
 */
class Documents {

	/**
	 * Post types included in site search.
	 *
	 * @return array<string, string> Post type => public type slug.
	 */
	public static function types(): array {
		return array(
			'lr_university'  => 'university',
			'lr_field'       => 'field',
			'lr_city'        => 'city',
			'lr_scholarship' => 'scholarship',
			'lr_guide'       => 'guide',
			'post'           => 'post',
		);
	}

	/**
	 * Public type slug for a post type.
	 *
	 * @param string $post_type Post type.
	 */
	public static function kind( string $post_type ): string {
		$types = self::types();
		return $types[ $post_type ] ?? '';
	}

	/**
	 * Document id stored in MySQL and Meilisearch.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post id.
	 */
	public static function key( string $post_type, int $post_id ): string {
		$kind = self::kind( $post_type );
		if ( '' === $kind ) {
			return '';
		}
		return $kind . '_' . $post_id;
	}

	/**
	 * Write the local row for a published post. Returns the Meili document.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>|null
	 */
	public static function write( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return null;
		}
		$doc = self::build( $post );
		if ( ! $doc ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'lr_search_docs';
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}` (doc_key, object_type, object_id, title, text_norm, aliases, excerpt, url, slug, city, city_slug, updated_at)
				VALUES (%s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE title = VALUES(title), text_norm = VALUES(text_norm), aliases = VALUES(aliases), excerpt = VALUES(excerpt), url = VALUES(url), slug = VALUES(slug), city = VALUES(city), city_slug = VALUES(city_slug), object_type = VALUES(object_type), object_id = VALUES(object_id), updated_at = VALUES(updated_at)",
				$doc['id'],
				$doc['type'],
				$post_id,
				$doc['title'],
				$doc['text'],
				$doc['aliases'],
				$doc['excerpt'],
				$doc['url'],
				$doc['slug'],
				$doc['city'],
				$doc['city_slug'],
				gmdate( 'Y-m-d H:i:s' )
			)
		);
		return $doc;
	}

	/**
	 * Remove the local row.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post id.
	 */
	public static function remove( string $post_type, int $post_id ): void {
		$key = self::key( $post_type, $post_id );
		if ( '' === $key ) {
			return;
		}
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'lr_search_docs', array( 'doc_key' => $key ), array( '%s' ) );
	}

	/**
	 * Rebuild every published document. Returns the number written.
	 */
	public static function rebuild(): int {
		global $wpdb;
		$types = array_keys( self::types() );
		$in    = "'" . implode( "','", array_map( 'esc_sql', $types ) ) . "'";
		$ids   = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$in})" );
		$count = 0;
		$seen  = array();
		foreach ( (array) $ids as $id ) {
			$doc = self::write( (int) $id );
			if ( $doc ) {
				$seen[] = $doc['id'];
				++$count;
			}
		}
		$table = $wpdb->prefix . 'lr_search_docs';
		if ( $seen ) {
			$placeholders = implode( ',', array_fill( 0, count( $seen ), '%s' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE doc_key NOT IN ({$placeholders})", $seen ) );
		} else {
			$wpdb->query( "DELETE FROM `{$table}`" );
		}
		return $count;
	}

	/**
	 * Meili payload for a post.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<string, mixed>|null
	 */
	private static function build( \WP_Post $post ): ?array {
		$kind = self::kind( $post->post_type );
		if ( '' === $kind ) {
			return null;
		}
		$title     = $post->post_title;
		$slug      = $post->post_name;
		$city      = '';
		$city_slug = '';
		$aliases   = Aliases::for_slug( $slug );
		$extra     = $post->post_excerpt . ' ' . wp_strip_all_tags( $post->post_content );

		if ( in_array( $post->post_type, array( 'lr_university', 'lr_field', 'lr_city' ), true ) ) {
			$row = self::catalog_row( $post );
			if ( ! $row ) {
				return null;
			}
			$title    = (string) $row['name_fa'];
			$slug     = (string) $row['slug'];
			$aliases .= ' ' . (string) ( $row['name_en'] ?? '' ) . ' ' . (string) ( $row['name_ru'] ?? '' ) . ' ' . (string) ( $row['short_name'] ?? '' ) . ' ' . $slug;
			$aliases .= ' ' . Aliases::for_slug( $slug );
			$extra    = $aliases;
			if ( 'lr_university' === $post->post_type ) {
				$city      = (string) ( $row['city_name'] ?? '' );
				$city_slug = (string) ( $row['city_slug'] ?? '' );
				$extra    .= ' ' . $city;
			}
		}

		$text = Text::index_text( $title . ' ' . $extra . ' ' . $aliases );
		if ( '' === $text ) {
			return null;
		}
		$excerpt = $city ? $city : wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 22, '…' );
		return array(
			'id'        => $kind . '_' . $post->ID,
			'type'      => $kind,
			'title'     => $title,
			'text'      => $text,
			'aliases'   => Text::index_text( $aliases . ' ' . $slug ),
			'excerpt'   => $excerpt,
			'url'       => (string) get_permalink( $post ),
			'slug'      => $slug,
			'city'      => $city,
			'city_slug' => $city_slug,
		);
	}

	/**
	 * Published catalog row joined to its city when the post is a university.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<string, mixed>|null
	 */
	private static function catalog_row( \WP_Post $post ): ?array {
		global $wpdb;
		$map   = array(
			'lr_university' => 'lr_universities',
			'lr_field'      => 'lr_fields',
			'lr_city'       => 'lr_cities',
		);
		$table = $wpdb->prefix . $map[ $post->post_type ];
		if ( 'lr_university' === $post->post_type ) {
			$cities = $wpdb->prefix . 'lr_cities';
			$row    = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT u.*, c.name_fa AS city_name, c.slug AS city_slug FROM `{$table}` u LEFT JOIN `{$cities}` c ON c.id = u.city_id WHERE u.post_id = %d AND u.deleted_at IS NULL AND u.status = 'published' LIMIT 1",
					$post->ID
				),
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE post_id = %d AND deleted_at IS NULL AND status = 'published' LIMIT 1",
					$post->ID
				),
				ARRAY_A
			);
		}
		return is_array( $row ) ? $row : null;
	}
}
