<?php
/**
 * Replaces untouched placeholder hubs with the real Persian copy.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

use LifeRuss\Core\Redirects\Store;
use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade routine. Edited pages are left alone.
 */
class Copy {

	/**
	 * Hook the one-time upgrade.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe' ), 48 );
	}

	/**
	 * Run once after the seeders.
	 */
	public static function maybe(): void {
		if ( '1' === (string) get_option( 'lr_real_copy' ) ) {
			return;
		}
		self::apply();
		update_option( 'lr_real_copy', '1', false );
	}

	/**
	 * Publish real copy where the stored text is still the seed.
	 */
	public static function apply(): void {
		$ids = array();
		foreach ( Library::pages() as $page ) {
			$parent = 0;
			if ( '' !== $page['parent'] ) {
				$parent = isset( $ids[ $page['parent'] ] ) ? (int) $ids[ $page['parent'] ] : self::find_page( (string) $page['parent'], 0 );
			}
			$id = self::fill_page( $page, $parent );
			if ( $id > 0 ) {
				$ids[ $page['slug'] ] = $id;
			}
		}
		self::guide();
		self::scholarships();
		self::drop_sample_page();
		self::fix_admission_redirect();
	}

	/**
	 * Whether text is still seed copy.
	 *
	 * @param string $text Raw text.
	 */
	public static function is_placeholder( string $text ): bool {
		$text = wp_strip_all_tags( $text );
		foreach ( self::needles() as $needle ) {
			if ( str_contains( $text, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Seed phrases. Public pages must not keep these.
	 *
	 * @return string[]
	 */
	private static function needles(): array {
		return array(
			'(نمونه)',
			'متن نمونه',
			'این بخش نمونه',
			'بخش نمونه است',
			'نظر نمونه',
			'محتوای نمونه',
			'این متن نمونه',
			'دادهٔ نمونه',
			'داده‌ها نمونه',
			'راهنمای نمونه',
			'دانستنی نمونه',
			'این صفحه نمونه',
			'پیش‌نویس منتشر نشود',
			'سهمیه‌های نمونه',
		);
	}

	/**
	 * Write one page when it is still untouched, then publish it.
	 *
	 * @param array<string, mixed> $page      Definition.
	 * @param int                  $parent_id Parent id.
	 */
	private static function fill_page( array $page, int $parent_id ): int {
		$id = self::find_page( (string) $page['slug'], $parent_id );
		if ( $id < 1 && $parent_id > 0 ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'draft',
					'post_title'  => (string) $page['title'],
					'post_name'   => (string) $page['slug'],
					'post_parent' => $parent_id,
				)
			);
		}
		if ( $id < 1 ) {
			return 0;
		}
		$post = get_post( $id );
		if ( ! $post instanceof \WP_Post || ! self::untouched( $post ) ) {
			return $id;
		}
		$blob = (string) $page['title'] . (string) $page['lead'] . (string) $page['content'] . wp_json_encode( $page['sections'] );
		if ( self::is_placeholder( $blob ) ) {
			return $id;
		}
		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => (string) $page['title'],
				'post_content' => (string) $page['content'],
				'post_status'  => 'publish',
			)
		);
		update_post_meta( $id, '_wp_page_template', 'templates/path.php' );
		update_post_meta( $id, '_lr_eyebrow', (string) $page['eyebrow'] );
		update_post_meta( $id, '_lr_lead', (string) $page['lead'] );
		update_post_meta( $id, '_lr_sections', wp_json_encode( $page['sections'], JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $id, '_lr_testimonial_ids', '' );
		update_post_meta( $id, '_lr_demo', '0' );
		update_post_meta( $id, '_lr_faq_ids', self::faq_ids( (string) $page['slug'], $page['faqs'] ) );
		return $id;
	}

	/**
	 * True when the stored page is still the seed or an empty shell.
	 *
	 * @param \WP_Post $post Page.
	 */
	private static function untouched( \WP_Post $post ): bool {
		$lead     = (string) get_post_meta( $post->ID, '_lr_lead', true );
		$eyebrow  = (string) get_post_meta( $post->ID, '_lr_eyebrow', true );
		$sections = (string) get_post_meta( $post->ID, '_lr_sections', true );
		$blob     = $post->post_title . ' ' . $post->post_content . ' ' . $lead . ' ' . $eyebrow . ' ' . $sections;
		if ( self::is_placeholder( $blob ) || 'نمونه' === $eyebrow ) {
			return true;
		}
		$plain = trim( wp_strip_all_tags( $post->post_content ) );
		return '' === $plain && '' === $lead && '' === $eyebrow;
	}

	/**
	 * Real FAQ posts for one page. Reused by slug.
	 *
	 * @param string                            $slug Page slug.
	 * @param array<int, array<string, string>> $faqs Questions.
	 */
	private static function faq_ids( string $slug, array $faqs ): string {
		$ids = array();
		$i   = 1;
		foreach ( $faqs as $faq ) {
			$name  = 'copy-' . $slug . '-' . $i;
			$found = get_posts(
				array(
					'name'           => $name,
					'post_type'      => 'lr_faq',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( $found ) {
				$fid = (int) $found[0];
				wp_update_post(
					array(
						'ID'           => $fid,
						'post_title'   => $faq['q'],
						'post_content' => $faq['a'],
						'post_status'  => 'publish',
					)
				);
			} else {
				$fid = (int) wp_insert_post(
					array(
						'post_type'    => 'lr_faq',
						'post_status'  => 'publish',
						'post_title'   => $faq['q'],
						'post_name'    => $name,
						'post_content' => $faq['a'],
					)
				);
			}
			if ( $fid > 0 ) {
				$ids[] = $fid;
			}
			++$i;
		}
		return implode( ',', $ids );
	}

	/**
	 * Dorm guide and its category, when they are still the seed.
	 */
	private static function guide(): void {
		$term    = term_exists( 'life', 'lr_guide_cat' );
		$term_id = 0;
		if ( is_array( $term ) ) {
			$term_id = (int) $term['term_id'];
		} elseif ( is_numeric( $term ) ) {
			$term_id = (int) $term;
		}
		if ( $term_id > 0 ) {
			$name   = '';
			$object = get_term( $term_id, 'lr_guide_cat' );
			if ( $object instanceof \WP_Term ) {
				$name = $object->name;
			}
			if ( self::is_placeholder( $name ) || str_contains( $name, 'نمونه' ) ) {
				wp_update_term( $term_id, 'lr_guide_cat', array( 'name' => 'زندگی در روسیه' ) );
			}
		} else {
			$created = wp_insert_term( 'زندگی در روسیه', 'lr_guide_cat', array( 'slug' => 'life' ) );
			$term_id = is_array( $created ) ? (int) $created['term_id'] : 0;
		}
		$posts = get_posts(
			array(
				'name'           => 'student-dorms',
				'post_type'      => 'lr_guide',
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);
		$body  = '<p>خوابگاه دانشگاهی در روسیه معمولاً چندتخته است و هزینه و قوانینش را همان دانشگاه در نامهٔ پذیرش می‌نویسد. فاصله تا دانشکده، جنسیت ساختمان، و اینکه پادفک هم تخت دارد، سه سؤال اول‌اند.</p><h2>قبل از پرداخت</h2><p>تعهد کتبی تخت را بخواهید. اگر ظرفیت تمام شده باشد، گزینهٔ خصوصی را با فاصله و بودجه جدا بررسی می‌کنیم و عدد فرضی اعلام نمی‌کنیم.</p>';
		if ( ! $posts ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'    => 'lr_guide',
					'post_status'  => 'publish',
					'post_title'   => 'خوابگاه دانشجویی',
					'post_name'    => 'student-dorms',
					'post_excerpt' => 'تخت دانشگاهی، هزینه و فاصله را از نامهٔ همان دانشگاه بخوانید.',
					'post_content' => $body,
				)
			);
		} else {
			$post = $posts[0];
			$id   = (int) $post->ID;
			if ( ! self::is_placeholder( $post->post_title . ' ' . $post->post_content . ' ' . $post->post_excerpt ) ) {
				return;
			}
			wp_update_post(
				array(
					'ID'           => $id,
					'post_title'   => 'خوابگاه دانشجویی',
					'post_excerpt' => 'تخت دانشگاهی، هزینه و فاصله را از نامهٔ همان دانشگاه بخوانید.',
					'post_content' => $body,
					'post_status'  => 'publish',
				)
			);
		}
		if ( $id > 0 && ! empty( $term_id ) ) {
			wp_set_object_terms( $id, array( (int) $term_id ), 'lr_guide_cat' );
			update_post_meta( $id, '_lr_demo', '0' );
			update_post_meta( $id, '_lr_faq_ids', '' );
		}
	}

	/**
	 * Rewrite published or draft scholarship seeds without inventing a deadline.
	 */
	private static function scholarships(): void {
		$map = array(
			'government-quota' => array(
				'سهمیه دولتی روسیه',
				'سهمیهٔ دولتی برای تحصیل رایگان یا کم‌هزینه است و هر سال شرط، رشته و کشور را خودش اعلام می‌کند.',
				'<p>معدل، سن و رشته را با فراخوان همان سال می‌سنجیم. تا فراخوان منتشر نشده، تاریخ و ظرفیت نمی‌سازیم.</p>',
			),
			'university-grant' => array(
				'گرنت دانشگاه',
				'بعضی دانشگاه‌ها روی شهریه تخفیف می‌دهند. شرطش مال همان دانشگاه است، نه یک جدول ثابت.',
				'<p>تخفیف را فقط وقتی در نامه یا آیین‌نامه آمده باشد در پرونده می‌نویسیم.</p>',
			),
			'open-doors'       => array(
				'المپیاد درهای باز',
				'المپیاد Open Doors مسیر جداگانه‌ای برای پوشش شهریه است و رتبه و تقویم رسمی‌اش هر سال عوض می‌شود.',
				'<p>اگر در فراخوان همان سال رشتهٔ شما باشد، مدارک را جدا از پذیرش عادی جمع می‌کنیم.</p>',
			),
		);
		foreach ( $map as $slug => $copy ) {
			$posts = get_posts(
				array(
					'name'           => $slug,
					'post_type'      => 'lr_scholarship',
					'post_status'    => 'any',
					'posts_per_page' => 1,
				)
			);
			if ( ! $posts ) {
				continue;
			}
			$post = $posts[0];
			$blob = $post->post_title . ' ' . $post->post_content . ' ' . $post->post_excerpt;
			if ( ! self::is_placeholder( $blob ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_title'   => $copy[0],
					'post_excerpt' => $copy[1],
					'post_content' => $copy[2],
				)
			);
			update_post_meta( $post->ID, '_lr_faq_ids', '' );
			update_post_meta( $post->ID, '_lr_source', 'official-call' );
		}
	}

	/**
	 * Delete the default WordPress sample page when its text is still the default.
	 */
	private static function drop_sample_page(): void {
		foreach ( array( 'sample-page', 'برگه-نمونه' ) as $slug ) {
			$posts = get_posts(
				array(
					'name'           => $slug,
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => 1,
				)
			);
			if ( ! $posts ) {
				continue;
			}
			$post  = $posts[0];
			$plain = wp_strip_all_tags( $post->post_content );
			$known = str_contains( $plain, 'example page' ) || str_contains( $plain, 'برگه نمونه' ) || str_contains( $post->post_title, 'Sample' ) || str_contains( $post->post_title, 'نمونه' );
			if ( $known && strlen( $plain ) < 800 ) {
				wp_delete_post( $post->ID, true );
			}
		}
	}

	/**
	 * /admission/ must open the request page, not the magazine article.
	 */
	private static function fix_admission_redirect(): void {
		$repo = Repository::for( 'redirects' );
		$row  = $repo->find_by( 'source_hash', sha1( Store::path( '/admission/' ) ), true );
		if ( ! is_array( $row ) ) {
			return;
		}
		$target = (string) ( $row['target_url'] ?? '' );
		if ( str_contains( $target, 'admission-visa' ) || str_contains( $target, '/blog/' ) ) {
			$repo->update( (int) $row['id'], array( 'is_active' => 0 ) );
			Store::bust();
		}
	}

	/**
	 * Page id by slug and parent.
	 *
	 * @param string $slug      Slug.
	 * @param int    $parent_id Parent id.
	 */
	private static function find_page( string $slug, int $parent_id ): int {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'page',
				'post_status'    => 'any',
				'post_parent'    => $parent_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}
}
