<?php
/**
 * SEO health dashboard.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Seo;

use LifeRuss\Core\Admin\Chrome;
use LifeRuss\Core\Monitor\NotFound;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Indexable inventory, cluster coverage, and the checks that block indexing.
 */
class Health {

	/**
	 * Targets from the six-month plan. The bar fills against the top of the range.
	 *
	 * @return array<string, array{label: string, min: int, max: int}>
	 */
	public static function targets(): array {
		return array(
			'universities' => array(
				'label' => 'دانشگاه',
				'min'   => 30,
				'max'   => 50,
			),
			'fields'       => array(
				'label' => 'رشته',
				'min'   => 15,
				'max'   => 25,
			),
			'cities'       => array(
				'label' => 'شهر',
				'min'   => 10,
				'max'   => 15,
			),
			'mothers'      => array(
				'label' => 'صفحهٔ مادر',
				'min'   => 8,
				'max'   => 12,
			),
			'articles'     => array(
				'label' => 'مقاله',
				'min'   => 80,
				'max'   => 120,
			),
			'guides'       => array(
				'label' => 'راهنما و پرسش',
				'min'   => 20,
				'max'   => 30,
			),
		);
	}

	/**
	 * Admin screen.
	 */
	public static function screen(): void {
		if ( ! current_user_can( 'lr_edit_seo' ) ) {
			wp_die( esc_html__( 'مجوز ندارید.', 'liferuss-core' ) );
		}
		$counts = self::counts();
		$total  = array_sum( $counts );
		$goal   = 150;
		$pct    = min( 100, (int) round( ( $total / $goal ) * 100 ) );
		$circ   = 289;
		$dash   = (int) round( $circ * $pct / 100 );
		$ring   = '<div class="lr-ring' . ( $total >= $goal ? ' is-ok' : '' ) . '" role="img" aria-label="' . esc_attr( self::digits( $total ) . ' / ' . self::digits( $goal ) ) . '">';
		$ring  .= '<svg viewBox="0 0 120 120" aria-hidden="true"><circle class="lr-ring-track" cx="60" cy="60" r="46"></circle><circle class="lr-ring-value" cx="60" cy="60" r="46" stroke-dasharray="' . esc_attr( (string) $dash ) . ' ' . esc_attr( (string) $circ ) . '"></circle></svg>';
		$ring  .= '<div class="lr-ring-label"><strong>' . esc_html( self::digits( $total ) ) . '</strong><span>/ ' . esc_html( self::digits( $goal ) ) . '</span></div></div>';
		Chrome::open( 'سلامت سئو', 'تنظیمات', $ring );
		echo '<p class="lr-lead">هدف کل صفحات قابل ایندکس ۱۵۰ تا ۲۵۰ است.</p>';
		echo '<div class="lr-kpis">';
		foreach ( self::targets() as $key => $target ) {
			$count = (int) ( $counts[ $key ] ?? 0 );
			$width = $target['max'] > 0 ? min( 100, (int) round( ( $count / $target['max'] ) * 100 ) ) : 0;
			$state = 'is-bad';
			if ( $count >= (int) $target['min'] ) {
				$state = 'is-ok';
			} elseif ( $count > 0 ) {
				$state = 'is-warn';
			}
			echo '<article class="lr-kpi ' . esc_attr( $state ) . '">';
			echo '<span>' . esc_html( $target['label'] ) . '</span>';
			echo '<strong>' . esc_html( self::digits( $count ) ) . '</strong>';
			echo '<span class="lr-kpi-goal">از هدف ' . esc_html( self::digits( (string) $target['min'] . '–' . (string) $target['max'] ) ) . '</span>';
			echo '<div class="lr-bar" role="progressbar" aria-valuenow="' . esc_attr( (string) $count ) . '" aria-valuemin="0" aria-valuemax="' . esc_attr( (string) $target['max'] ) . '"><span style="width:' . esc_attr( (string) $width ) . '%"></span></div>';
			echo '</article>';
		}
		echo '</div>';
		self::clusters();
		self::issues();
		self::positions();
		Chrome::close();
	}

	/**
	 * Indexable pages in each bucket.
	 *
	 * @return array<string, int>
	 */
	public static function counts(): array {
		return array(
			'universities' => self::indexable_type( 'lr_university' ),
			'fields'       => self::indexable_type( 'lr_field' ),
			'cities'       => self::indexable_type( 'lr_city' ),
			'mothers'      => self::mothers(),
			'articles'     => self::indexable_type( 'post' ),
			'guides'       => self::indexable_type( 'lr_guide' ) + self::indexable_type( 'lr_faq' ),
		);
	}

	/**
	 * Published posts of one type that pass the quality gate.
	 *
	 * @param string $type Post type.
	 */
	private static function indexable_type( string $type ): int {
		$ids   = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$count = 0;
		foreach ( $ids as $id ) {
			if ( 'lr_faq' === $type || Quality::indexable_post( (int) $id ) ) {
				if ( 'lr_faq' === $type && '1' === (string) get_post_meta( (int) $id, '_lr_demo', true ) ) {
					continue;
				}
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Published mother pages for the six clusters.
	 */
	private static function mothers(): int {
		$slugs = array( 'universities', 'study-russia', 'fields', 'cities', 'scholarships', 'padfak', 'direct-course', 'russia-guide', 'immigration', 'blog', 'medicine', 'dentistry' );
		$count = 0;
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page instanceof \WP_Post && 'publish' === $page->post_status && '1' !== (string) get_post_meta( $page->ID, '_lr_demo', true ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Share of keywords whose target URL resolves.
	 */
	private static function clusters(): void {
		$labels = array(
			'universities' => 'دانشگاه‌های روسیه',
			'medicine'     => 'پزشکی و دندانپزشکی روسیه',
			'scholarship'  => 'بورسیه روسیه',
			'padfak'       => 'پادفک و کورس مستقیم',
			'guide'        => 'زندگی و دانستنی‌های روسیه',
			'immigration'  => 'مهاجرت و قوانین روسیه',
		);
		$totals = array();
		$ready  = array();
		foreach ( Keywords::all() as $row ) {
			$cluster = (string) $row['cluster'];
			if ( ! isset( $labels[ $cluster ] ) ) {
				continue;
			}
			$totals[ $cluster ] = (int) ( $totals[ $cluster ] ?? 0 ) + 1;
			if ( self::url_resolves( (string) $row['target_url'] ) ) {
				$ready[ $cluster ] = (int) ( $ready[ $cluster ] ?? 0 ) + 1;
			}
		}
		echo '<h2>پوشش خوشه‌ها</h2><div class="lr-scroll"><table class="widefat lr-table"><thead><tr><th>خوشه</th><th>کلیدواژه با نشانی موجود</th><th>پوشش</th></tr></thead><tbody>';
		foreach ( $labels as $key => $label ) {
			$total = (int) ( $totals[ $key ] ?? 0 );
			$done  = (int) ( $ready[ $key ] ?? 0 );
			$width = $total > 0 ? (int) round( ( $done / $total ) * 100 ) : 0;
			echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( self::digits( $done ) . ' از ' . self::digits( $total ) ) . '</td><td><div class="lr-bar"><span style="width:' . esc_attr( (string) $width ) . '%"></span></div></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Whether a site path currently has a public object.
	 *
	 * @param string $path Path beginning with /.
	 */
	private static function url_resolves( string $path ): bool {
		$path = '/' . ltrim( $path, '/' );
		if ( '/' === $path ) {
			return true;
		}
		$id = url_to_postid( home_url( $path ) );
		if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
			return true;
		}
		return (bool) get_page_by_path( trim( $path, '/' ) );
	}

	/**
	 * Orphans, stale facts, missing tags, broken links, and 404s.
	 */
	private static function issues(): void {
		$stale   = Facts::stale();
		$orphans = Links::orphans();
		$meta    = self::missing_meta();
		$schema  = self::missing_schema();
		$broken  = self::broken();
		$misses  = NotFound::all();
		$cards   = array(
			array(
				'label' => 'دادهٔ قدیمی',
				'count' => count( $stale ),
				'href'  => Chrome::url( 'lr-stale' ),
			),
			array(
				'label' => 'صفحهٔ یتیم',
				'count' => count( $orphans ),
				'href'  => '#lr-orphans',
			),
			array(
				'label' => 'متای ناقص',
				'count' => count( $meta ),
				'href'  => '#lr-missing-meta',
			),
			array(
				'label' => 'اسکیما ناقص',
				'count' => count( $schema ),
				'href'  => '#lr-missing-schema',
			),
			array(
				'label' => 'پیوند شکسته',
				'count' => count( $broken ),
				'href'  => '#lr-broken',
			),
			array(
				'label' => '۴۰۴',
				'count' => count( $misses ),
				'href'  => current_user_can( 'lr_manage_redirects' ) ? Chrome::url( 'lr-404' ) : '#lr-misses',
			),
		);
		echo '<h2>ایرادها</h2><div class="lr-kpis">';
		foreach ( $cards as $card ) {
			echo '<a class="lr-kpi lr-issue" href="' . esc_url( $card['href'] ) . '"><span>' . esc_html( $card['label'] ) . '</span><strong>' . esc_html( self::digits( (int) $card['count'] ) ) . '</strong></a>';
		}
		echo '</div>';
		self::issue_posts( 'lr-orphans', 'صفحات یتیم', $orphans, true );
		self::issue_posts( 'lr-missing-meta', 'متای ناقص', $meta, false );
		self::issue_posts( 'lr-missing-schema', 'اسکیما ناقص', $schema, false );
		echo '<section class="lr-issue-list" id="lr-broken"><h3>پیوند شکسته</h3>';
		if ( ! $broken ) {
			echo '<p>پیوند شکسته‌ای در نوشته‌های منتشرشده نیست.</p>';
		} else {
			echo '<ul class="lr-work">';
			foreach ( array_slice( $broken, 0, 12 ) as $path ) {
				echo '<li><span>' . esc_html( (string) $path ) . '</span></li>';
			}
			echo '</ul>';
		}
		echo '</section>';
		echo '<section class="lr-issue-list" id="lr-misses"><h3>۴۰۴های پایش</h3>';
		if ( ! $misses ) {
			echo '<p>۴۰۴ تازه‌ای ثبت نشده است.</p>';
		} else {
			echo '<ul class="lr-work">';
			foreach ( array_slice( $misses, 0, 12 ) as $row ) {
				echo '<li><span>' . esc_html( (string) $row['path'] ) . '</span><span>' . esc_html( self::digits( (int) $row['hits'] ) ) . '</span></li>';
			}
			echo '</ul>';
		}
		echo '</section>';
	}

	/**
	 * A short filtered list under an issue card.
	 *
	 * @param string                                                    $id      Anchor.
	 * @param string                                                    $title   Heading.
	 * @param array<int, array{title?: string, url?: string, id?: int}> $items Rows.
	 * @param bool                                                      $orphan  Whether to offer an add-link action.
	 */
	private static function issue_posts( string $id, string $title, array $items, bool $orphan ): void {
		echo '<section class="lr-issue-list" id="' . esc_attr( $id ) . '"><h3>' . esc_html( $title ) . '</h3>';
		if ( ! $items ) {
			echo '<p>موردی نیست.</p></section>';
			return;
		}
		echo '<ul class="lr-work">';
		foreach ( array_slice( $items, 0, 12 ) as $item ) {
			$edit = ! empty( $item['id'] ) ? (string) get_edit_post_link( (int) $item['id'], 'raw' ) : '';
			echo '<li><a href="' . esc_url( (string) ( $item['url'] ?? '#' ) ) . '">' . esc_html( (string) ( $item['title'] ?? '' ) ) . '</a><span class="lr-issue-actions">';
			if ( '' !== $edit ) {
				echo '<a class="button" href="' . esc_url( $edit ) . '">ویرایش</a>';
				if ( $orphan ) {
					echo '<a class="button" href="' . esc_url( $edit . '#lr_seo_quality' ) . '">افزودن لینک</a>';
				}
			}
			echo '</span></li>';
		}
		echo '</ul></section>';
	}

	/**
	 * Published gated posts without a title override.
	 *
	 * @return array<int, array{id: int, title: string, url: string}>
	 */
	private static function missing_meta(): array {
		$out = array();
		foreach ( Quality::types() as $type ) {
			$ids = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( '1' === (string) get_post_meta( $id, '_lr_demo', true ) ) {
					continue;
				}
				if ( '' === (string) get_post_meta( $id, '_lr_seo_title', true ) ) {
					$out[] = array(
						'id'    => $id,
						'title' => get_the_title( $id ),
						'url'   => (string) get_permalink( $id ),
					);
				}
			}
		}
		return $out;
	}

	/**
	 * Entities without an FAQ, which the schema and the gate both require.
	 *
	 * @return array<int, array{id: int, title: string, url: string}>
	 */
	private static function missing_schema(): array {
		$out = array();
		foreach ( array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship' ) as $type ) {
			$ids = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( '1' === (string) get_post_meta( $id, '_lr_demo', true ) ) {
					continue;
				}
				if ( '' === (string) get_post_meta( $id, '_lr_faq_ids', true ) ) {
					$out[] = array(
						'id'    => $id,
						'title' => get_the_title( $id ),
						'url'   => (string) get_permalink( $id ),
					);
				}
			}
		}
		return $out;
	}

	/**
	 * Content links whose path is already in the 404 monitor.
	 *
	 * @return string[]
	 */
	private static function broken(): array {
		$paths = array();
		foreach ( NotFound::all() as $row ) {
			$paths[ untrailingslashit( (string) $row['path'] ) ] = true;
		}
		if ( ! $paths ) {
			return array();
		}
		$found = array();
		$posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page', 'lr_guide', 'lr_university', 'lr_field', 'lr_city' ),
				'post_status'    => 'publish',
				'posts_per_page' => 80,
				'no_found_rows'  => true,
			)
		);
		foreach ( $posts as $post ) {
			if ( ! preg_match_all( '/href=["\']([^"\']+)["\']/', (string) $post->post_content, $matches ) ) {
				continue;
			}
			foreach ( $matches[1] as $href ) {
				$host = (string) wp_parse_url( $href, PHP_URL_HOST );
				$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
				if ( '' !== $host && $host !== $home ) {
					continue;
				}
				$path = untrailingslashit( (string) wp_parse_url( $href, PHP_URL_PATH ) );
				if ( isset( $paths[ $path ] ) ) {
					$found[] = $path;
				}
			}
		}
		return array_values( array_unique( $found ) );
	}

	/**
	 * Positions after Search Console is connected. Empty until then.
	 */
	private static function positions(): void {
		$settings = Keywords::settings();
		echo '<h2>رتبهٔ کلیدواژه‌ها</h2>';
		if ( empty( $settings['enabled'] ) || '' === (string) $settings['refresh_token'] ) {
			echo '<p>Search Console هنوز وصل نیست. کرون روزانه خاموش می‌ماند تا شناسه و توکن ذخیره شود.</p>';
			return;
		}
		echo '<div class="lr-scroll"><table class="widefat lr-table"><thead><tr><th>کلیدواژه</th><th>رتبه</th><th>کلیک</th></tr></thead><tbody>';
		foreach ( array_slice( Keywords::all(), 0, 20 ) as $row ) {
			$position = null === $row['current_position'] ? '—' : (string) $row['current_position'];
			$clicks   = null === $row['clicks'] ? '—' : self::digits( (int) $row['clicks'] );
			echo '<tr><td>' . esc_html( (string) $row['keyword'] ) . '</td><td>' . esc_html( $position ) . '</td><td>' . esc_html( $clicks ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Persian digits for a count.
	 *
	 * @param int|string $value Number.
	 */
	private static function digits( $value ): string {
		return \LifeRuss\Core\CRM\Jalali::fa_digits( (string) $value );
	}
}
