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
		$groups = array(
			'رشته‌ها'     => array(),
			'شهر و پادفک' => array(),
			'خوشه'        => array(
				array(
					'url'   => home_url( '/universities/' ),
					'label' => 'دانشگاه‌های روسیه',
				),
				array(
					'url'   => home_url( '/padfak/' ),
					'label' => 'پادفک',
				),
				array(
					'url'   => home_url( '/study-russia/' ),
					'label' => 'تحصیل در روسیه',
				),
			),
		);
		foreach ( $programs as $program ) {
			$field_slug = (string) ( $program['field_slug'] ?? '' );
			if ( '' === $field_slug ) {
				continue;
			}
			$groups['رشته‌ها'][] = array(
				'url'   => home_url( '/universities/' . rawurlencode( $slug ) . '/' . rawurlencode( $field_slug ) . '/' ),
				'label' => (string) $program['field_name'],
			);
		}
		if ( ! empty( $row['city']['post_id'] ) ) {
			$groups['شهر و پادفک'][] = array(
				'url'   => (string) get_permalink( (int) $row['city']['post_id'] ),
				'label' => (string) $row['city']['name_fa'],
			);
		}
		$groups['شهر و پادفک'][] = array(
			'url'   => home_url( '/padfak/' ),
			'label' => 'پادفک',
		);
		echo '<nav class="lr-chip-groups" aria-label="پیوندهای دانشگاه">';
		foreach ( $groups as $label => $links ) {
			if ( ! $links ) {
				continue;
			}
			echo '<div class="lr-chip-group"><h3>' . esc_html( $label ) . '</h3><ul>';
			foreach ( $links as $link ) {
				echo '<li><a class="lr-chip" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
			}
			echo '</ul></div>';
		}
		echo '</nav>';
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
		$cities = $wpdb->prefix . 'lr_cities';
		$progs  = $wpdb->prefix . 'lr_university_fields';
		$sql    = "SELECT u.id, u.post_id, u.name_fa, u.slug, u.min_tuition_usd, c.name_fa AS city_name, fee.tuition, fee.currency
			FROM `{$unis}` u
			LEFT JOIN `{$cities}` c ON c.id = u.city_id AND c.deleted_at IS NULL
			LEFT JOIN (
				SELECT university_id, MIN(tuition) AS tuition,
					SUBSTRING_INDEX(GROUP_CONCAT(currency ORDER BY tuition ASC SEPARATOR ','), ',', 1) AS currency
				FROM `{$progs}`
				WHERE status = 'active' AND deleted_at IS NULL AND tuition IS NOT NULL
				GROUP BY university_id
			) fee ON fee.university_id = u.id
			WHERE u.id <> %d AND u.status = 'published' AND u.deleted_at IS NULL AND (u.city_id = %d";
		$args   = array( $id, $city );
		if ( $field_id > 0 ) {
			$sql   .= " OR u.id IN (SELECT university_id FROM `{$progs}` WHERE field_id = %d AND status = 'active' AND deleted_at IS NULL)";
			$args[] = $field_id;
		}
		$sql   .= ') ORDER BY ABS(IFNULL(u.min_tuition_usd, 0) - %f) ASC LIMIT 4';
		$args[] = $tuition;
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		echo '<section class="lr-uni-card lr-similar" id="compare-similar"><header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5M4 19h16"/><path d="M8 15l3-4 3 2 4-6"/></svg></span><h2>مقایسه با دانشگاه‌های مشابه</h2></header>';
		if ( ! $rows ) {
			echo '<p>دانشگاه منتشرشدهٔ نزدیکی برای مقایسه نیست.</p>';
			self::freshness( 'university', $id );
			echo '</section>';
			return;
		}
		echo '<div class="table-scroll"><table class="lr-table"><thead><tr><th>دانشگاه</th><th>شهر</th><th>شهریه</th></tr></thead><tbody>';
		foreach ( $rows as $item ) {
			$money = self::money( $item['tuition'] ?? null, (string) ( $item['currency'] ?? 'RUB' ), $item['min_tuition_usd'] ?? null );
			echo '<tr><td><a href="' . esc_url( (string) get_permalink( (int) $item['post_id'] ) ) . '">' . esc_html( (string) $item['name_fa'] ) . '</a></td>';
			echo '<td>' . esc_html( (string) ( $item['city_name'] ? $item['city_name'] : '—' ) ) . '</td>';
			echo '<td>' . esc_html( '' !== $money ? $money : '—' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		self::freshness( 'university', $id );
		echo '</section>';
	}

	/**
	 * Persian amount with a currency name and a dollar equivalent.
	 *
	 * @param mixed  $amount   Native amount.
	 * @param string $currency Currency code.
	 * @param mixed  $usd      Optional dollar amount.
	 */
	private static function money( $amount, string $currency, $usd ): string {
		if ( function_exists( 'liferuss_catalog_amount' ) ) {
			return liferuss_catalog_amount( $amount, $currency, $usd );
		}
		return '';
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
	 * @return array<int, array{id: int, title: string, url: string}>
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
				'id'    => $post_id,
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
