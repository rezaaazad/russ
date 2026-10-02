<?php
/**
 * Automatic internal links for catalog and magazine pages.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * On-page link blocks and the orphan scan.
 */
class Links {

	/**
	 * Freshness line for a catalog row.
	 *
	 * @param string $entity_type Fact type.
	 * @param int    $entity_id   Row id.
	 */
	public static function freshness( string $entity_type, int $entity_id ): void {
		$line = Facts::line( $entity_type, $entity_id );
		if ( '' === $line ) {
			return;
		}
		echo '<p class="lr-fresh">' . esc_html( $line ) . '</p>';
	}

	/**
	 * University link blocks: fields, city, padfak, sections, similar schools, mother page.
	 *
	 * @param array<string, mixed> $row University query row.
	 */
	public static function university( array $row ): void {
		$slug     = (string) $row['slug'];
		$programs = isset( $row['programs'] ) && is_array( $row['programs'] ) ? $row['programs'] : array();
		usort(
			$programs,
			static function ( $left, $right ) {
				return self::field_rank( (array) $left ) <=> self::field_rank( (array) $right );
			}
		);
		echo '<nav class="lr-auto-links" aria-label="پیوندهای دانشگاه"><h2>در این دانشگاه</h2><ul>';
		echo '<li><a href="#tuition">شهریه سال جاری</a></li>';
		echo '<li><a href="#dorm">خوابگاه</a></li>';
		echo '<li><a href="#admission">شرایط پذیرش</a></li>';
		echo '<li><a href="#moh">وضعیت وزارت بهداشت</a></li>';
		foreach ( $programs as $program ) {
			$field_slug = (string) ( $program['field_slug'] ?? '' );
			if ( '' === $field_slug ) {
				continue;
			}
			$url = home_url( '/universities/' . rawurlencode( $slug ) . '/' . rawurlencode( $field_slug ) . '/' );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( (string) $program['field_name'] ) . '</a></li>';
		}
		if ( ! empty( $row['city']['post_id'] ) ) {
			echo '<li><a href="' . esc_url( (string) get_permalink( (int) $row['city']['post_id'] ) ) . '">' . esc_html( (string) $row['city']['name_fa'] ) . '</a></li>';
		}
		echo '<li><a href="' . esc_url( home_url( '/padfak/' ) ) . '">پادفک</a></li>';
		echo '<li><a href="' . esc_url( home_url( '/universities/' ) ) . '">دانشگاه‌های روسیه</a></li>';
		echo '</ul></nav>';
		self::similar( $row );
	}

	/**
	 * Same city or same field, nearest tuition.
	 *
	 * @param array<string, mixed> $row University.
	 */
	public static function similar( array $row ): void {
		global $wpdb;
		$unis     = $wpdb->prefix . 'lr_universities';
		$id       = (int) $row['id'];
		$city     = (int) ( $row['city_id'] ?? 0 );
		$tuition  = (float) ( $row['min_tuition_usd'] ?? 0 );
		$field_id = 0;
		if ( ! empty( $row['programs'][0]['field_id'] ) ) {
			$field_id = (int) $row['programs'][0]['field_id'];
		}
		$sql  = "SELECT id, post_id, name_fa, slug, min_tuition_usd FROM `{$unis}` WHERE id <> %d AND status = 'published' AND deleted_at IS NULL AND (city_id = %d";
		$args = array( $id, $city );
		if ( $field_id > 0 ) {
			$programs = $wpdb->prefix . 'lr_university_fields';
			$sql     .= " OR id IN (SELECT university_id FROM `{$programs}` WHERE field_id = %d AND status = 'active' AND deleted_at IS NULL)";
			$args[]   = $field_id;
		}
		$sql   .= ') ORDER BY ABS(IFNULL(min_tuition_usd, 0) - %f) ASC LIMIT 4';
		$args[] = $tuition;
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		echo '<section class="lr-similar" id="compare-similar"><h2>مقایسه با دانشگاه‌های مشابه</h2>';
		if ( ! $rows ) {
			echo '<p>دانشگاه منتشرشدهٔ نزدیکی برای مقایسه نیست.</p></section>';
			return;
		}
		echo '<ul>';
		foreach ( $rows as $item ) {
			echo '<li><a href="' . esc_url( (string) get_permalink( (int) $item['post_id'] ) ) . '">' . esc_html( (string) $item['name_fa'] ) . '</a></li>';
		}
		echo '</ul></section>';
	}

	/**
	 * Related entities chosen in the editor.
	 *
	 * @param int $post_id Article id.
	 */
	public static function related_box( int $post_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_seo_entity_links';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE post_id = %d ORDER BY id ASC", $post_id ), ARRAY_A );
		if ( ! $rows ) {
			return;
		}
		echo '<aside class="lr-related-entities"><h2>نهادهای مرتبط</h2><ul>';
		foreach ( $rows as $row ) {
			$url = self::entity_url( (string) $row['entity_type'], (int) $row['entity_id'] );
			if ( '' === $url ) {
				continue;
			}
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( (string) $row['label'] ) . '</a></li>';
		}
		echo '</ul></aside>';
	}

	/**
	 * Published catalog and article URLs with no inbound link.
	 *
	 * @return array<int, array{title: string, url: string}>
	 */
	public static function orphans(): array {
		$linked = self::linked_ids();
		$types  = array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship', 'lr_guide', 'post' );
		$out    = array();
		$posts  = get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $post_id ) {
			$post_id = (int) $post_id;
			if ( isset( $linked[ $post_id ] ) ) {
				continue;
			}
			if ( '1' === (string) get_post_meta( $post_id, '_lr_demo', true ) ) {
				continue;
			}
			$out[] = array(
				'title' => get_the_title( $post_id ),
				'url'   => (string) get_permalink( $post_id ),
			);
		}
		return $out;
	}

	/**
	 * Post ids that something else already points at.
	 *
	 * @return array<int, bool>
	 */
	private static function linked_ids(): array {
		global $wpdb;
		$found = array();
		$links = $wpdb->prefix . 'lr_seo_entity_links';
		foreach ( (array) $wpdb->get_results( "SELECT entity_type, entity_id FROM `{$links}`", ARRAY_A ) as $row ) {
			$post_id = self::entity_post( (string) $row['entity_type'], (int) $row['entity_id'] );
			if ( $post_id > 0 ) {
				$found[ $post_id ] = true;
			}
		}
		$unis     = $wpdb->get_results( "SELECT id, post_id, city_id FROM `{$wpdb->prefix}lr_universities` WHERE status = 'published' AND deleted_at IS NULL", ARRAY_A );
		$programs = $wpdb->get_results( "SELECT university_id, field_id FROM `{$wpdb->prefix}lr_university_fields` WHERE status = 'active' AND deleted_at IS NULL", ARRAY_A );
		$taught   = array();
		foreach ( (array) $programs as $program ) {
			$taught[ (int) $program['university_id'] ] = true;
			$field                                     = Repository::for( 'fields' )->find( (int) $program['field_id'] );
			if ( $field && ! empty( $field['post_id'] ) ) {
				$found[ (int) $field['post_id'] ] = true;
			}
		}
		foreach ( (array) $unis as $uni ) {
			if ( ! empty( $uni['city_id'] ) || ! empty( $taught[ (int) $uni['id'] ] ) ) {
				$found[ (int) $uni['post_id'] ] = true;
			}
			$city = ! empty( $uni['city_id'] ) ? Repository::for( 'cities' )->find( (int) $uni['city_id'] ) : null;
			if ( $city && ! empty( $city['post_id'] ) ) {
				$found[ (int) $city['post_id'] ] = true;
			}
		}
		return $found;
	}

	/**
	 * Public URL of a mapped entity.
	 *
	 * @param string $type Type.
	 * @param int    $id   Shadow or post id. Universities, fields, and cities use the shadow id.
	 */
	public static function entity_url( string $type, int $id ): string {
		$post_id = self::entity_post( $type, $id );
		return $post_id > 0 ? (string) get_permalink( $post_id ) : '';
	}

	/**
	 * Post id for a mapped entity.
	 *
	 * @param string $type Type.
	 * @param int    $id   Id.
	 */
	public static function entity_post( string $type, int $id ): int {
		if ( in_array( $type, array( 'post', 'page', 'lr_guide', 'lr_scholarship' ), true ) ) {
			return $id;
		}
		$map = array(
			'university' => 'universities',
			'field'      => 'fields',
			'city'       => 'cities',
		);
		if ( ! isset( $map[ $type ] ) ) {
			return 0;
		}
		$row = Repository::for( $map[ $type ] )->find( $id );
		return $row ? (int) $row['post_id'] : 0;
	}

	/**
	 * Medicine, then dentistry, then the rest.
	 *
	 * @param array<string, mixed> $program Program.
	 */
	private static function field_rank( array $program ): int {
		$text = (string) ( $program['field_name'] ?? '' ) . ' ' . (string) ( $program['field_slug'] ?? '' );
		if ( str_contains( $text, 'پزشک' ) || str_contains( $text, 'medicine' ) ) {
			return 0;
		}
		if ( str_contains( $text, 'دندان' ) || str_contains( $text, 'dent' ) ) {
			return 1;
		}
		return 2;
	}
}
