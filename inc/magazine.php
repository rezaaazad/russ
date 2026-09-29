<?php
/**
 * Magazine permalinks, English slugs, reading aids, and article schema.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Put posts under /blog/ and move the three seeded titles to English slugs.
 */
function liferuss_magazine_base() {
	if ( '2' === (string) get_option( 'liferuss_blog_base' ) ) {
		return;
	}
	global $wp_rewrite;
	update_option( 'permalink_structure', '/blog/%postname%/' );
	update_option( 'category_base', 'blog/category' );
	update_option( 'tag_base', 'blog/tag' );
	if ( $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
		$wp_rewrite->set_category_base( 'blog/category' );
		$wp_rewrite->set_tag_base( 'blog/tag' );
	}
	flush_rewrite_rules( false );
	if ( '1' !== (string) get_option( 'liferuss_blog_base' ) ) {
		liferuss_magazine_terms();
		liferuss_magazine_slugs();
	}
	update_option( 'liferuss_blog_base', '2', false );
}
add_action( 'init', 'liferuss_magazine_base', 42 );

/**
 * Categories and one tag used by the magazine templates.
 */
function liferuss_magazine_terms() {
	$names = array(
		'study'    => 'تحصیل',
		'exchange' => 'صرافی',
		'cargo'    => 'کارگو',
		'trade'    => 'تجارت',
	);
	foreach ( $names as $slug => $name ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}
	if ( ! term_exists( 'russia', 'post_tag' ) ) {
		wp_insert_term( 'روسیه', 'post_tag', array( 'slug' => 'russia' ) );
	}
}

/**
 * English slugs for the seeded posts, with 301s from the old paths.
 */
function liferuss_magazine_slugs() {
	$map   = array(
		'پادفک چیست و چه کسانی به آن نیاز دارند؟' => 'what-is-padfak',
		'هزینه تحصیل پزشکی در روسیه در یک نگاه'  => 'medicine-tuition-russia',
		'مدارک لازم برای پذیرش و ویزای تحصیلی روسیه' => 'admission-visa-documents',
	);
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 20,
		)
	);
	$study = get_category_by_slug( 'study' );
	$tag   = get_term_by( 'slug', 'russia', 'post_tag' );
	foreach ( $posts as $post ) {
		if ( ! isset( $map[ $post->post_title ] ) ) {
			continue;
		}
		$slug = $map[ $post->post_title ];
		$old  = (string) $post->post_name;
		if ( $old !== $slug ) {
			if ( class_exists( '\LifeRuss\Core\Redirects\Store' ) && '' !== $old ) {
				\LifeRuss\Core\Redirects\Store::upsert(
					array(
						'source_path' => '/' . $old . '/',
						'target_url'  => '/blog/' . $slug . '/',
						'status_code' => 301,
						'match_type'  => 'exact',
						'origin'      => 'slug_change',
						'is_active'   => 1,
						'object_type' => 'post',
						'object_id'   => $post->ID,
					)
				);
			}
			wp_update_post(
				array(
					'ID'        => $post->ID,
					'post_name' => $slug,
				)
			);
		}
		if ( false === strpos( (string) $post->post_content, '<h2' ) ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => '<h2>خلاصه</h2>' . $post->post_content . '<h2>قدم بعدی</h2><p>فرم پایین همین صفحه درخواست را برای مشاور ثبت می‌کند.</p>',
				)
			);
		}
		if ( $study ) {
			wp_set_post_categories( $post->ID, array( (int) $study->term_id ) );
		}
		if ( $tag && ! is_wp_error( $tag ) ) {
			wp_set_object_terms( $post->ID, array( (int) $tag->term_id ), 'post_tag', false );
		}
	}
}

/**
 * Minutes for a Persian article. About 900 characters is one minute.
 *
 * @param int $post_id Post id.
 */
function liferuss_reading_minutes( $post_id ) {
	$text = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
	$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	return max( 1, (int) ceil( $len / 900 ) );
}

/**
 * Form type for the first matching category.
 *
 * @param int $post_id Post id.
 */
function liferuss_magazine_form_type( $post_id ) {
	$map = array(
		'exchange'    => 'exchange',
		'cargo'       => 'freight',
		'freight'     => 'freight',
		'trade'       => 'trade',
		'immigration' => 'immigration',
		'study'       => 'admission',
	);
	$cats = get_the_category( $post_id );
	if ( ! is_array( $cats ) ) {
		return 'consult';
	}
	foreach ( $cats as $cat ) {
		if ( isset( $map[ $cat->slug ] ) ) {
			return $map[ $cat->slug ];
		}
	}
	return 'consult';
}

/**
 * Add stable ids to headings and return the outline.
 *
 * @param string $content Content.
 * @return string
 */
function liferuss_magazine_headings( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$items = array();
	$count = 0;
	$content = preg_replace_callback(
		'/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/u',
		static function ( $match ) use ( &$items, &$count ) {
			++$count;
			$id      = 'section-' . $count;
			$items[] = array(
				'level' => (int) $match[1],
				'id'    => $id,
				'text'  => wp_strip_all_tags( $match[3] ),
			);
			return '<h' . $match[1] . ' id="' . esc_attr( $id ) . '">' . $match[3] . '</h' . $match[1] . '>';
		},
		$content
	);
	$GLOBALS['liferuss_toc'] = $items;
	return $content;
}
add_filter( 'the_content', 'liferuss_magazine_headings', 8 );

/**
 * Table of contents collected for the current post.
 *
 * @return array<int, array{level: int, id: string, text: string}>
 */
function liferuss_toc_items() {
	if ( isset( $GLOBALS['liferuss_toc'] ) && is_array( $GLOBALS['liferuss_toc'] ) && $GLOBALS['liferuss_toc'] ) {
		return $GLOBALS['liferuss_toc'];
	}
	$post = get_post();
	if ( ! $post ) {
		return array();
	}
	$items = array();
	$count = 0;
	if ( preg_match_all( '/<h([23])(?:\s[^>]*)?>(.*?)<\/h\1>/u', (string) $post->post_content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			++$count;
			$items[] = array(
				'level' => (int) $match[1],
				'id'    => 'section-' . $count,
				'text'  => wp_strip_all_tags( $match[2] ),
			);
		}
	}
	return $items;
}

/**
 * Homepage links for cargo, trade, the guide, and the magazine.
 *
 * @return array<int, array{title: string, url: string}>
 */
function liferuss_service_home_links() {
	return array(
		array(
			'title' => liferuss_t( 'nav_cargo' ),
			'url'   => '/cargo/',
		),
		array(
			'title' => liferuss_t( 'nav_trade' ),
			'url'   => '/trade/',
		),
		array(
			'title' => liferuss_t( 'nav_guide' ),
			'url'   => '/russia-guide/',
		),
		array(
			'title' => liferuss_t( 'nav_blog' ),
			'url'   => '/blog/',
		),
		array(
			'title' => liferuss_t( 'nav_cities' ),
			'url'   => '/cities/',
		),
	);
}

/**
 * Related posts in the same category.
 *
 * @param int $post_id Post id.
 * @return \WP_Post[]
 */
function liferuss_related_posts( $post_id ) {
	$cats = wp_get_post_categories( $post_id );
	if ( ! $cats ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'category__in'   => $cats,
			'post__not_in'   => array( $post_id ),
		)
	);
}

/**
 * BlogPosting and Article schema.
 */
function liferuss_magazine_json_ld() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	$image   = get_the_post_thumbnail_url( $post_id, 'full' );
	$node    = array(
		'@type'            => 'BlogPosting',
		'headline'         => get_the_title( $post_id ),
		'datePublished'    => get_the_date( 'c', $post_id ),
		'dateModified'     => get_the_modified_date( 'c', $post_id ),
		'description'      => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'url'              => get_permalink( $post_id ),
		'inLanguage'       => liferuss_lang_meta( 'html_lang' ),
		'timeRequired'     => 'PT' . liferuss_reading_minutes( $post_id ) . 'M',
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => liferuss_brand(),
			'url'   => liferuss_home(),
		),
		'mainEntityOfPage' => get_permalink( $post_id ),
	);
	if ( $image ) {
		$node['image'] = $image;
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array( $node ),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>';
}
add_action( 'wp_head', 'liferuss_magazine_json_ld', 24 );

/**
 * Article schema for a guide.
 */
function liferuss_guide_json_ld() {
	if ( ! is_singular( 'lr_guide' ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@type'    => 'Article',
			'headline' => get_the_title( $post_id ),
			'url'      => get_permalink( $post_id ),
			'author'   => array(
				'@type' => 'Organization',
				'name'  => liferuss_brand(),
			),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>';
}
add_action( 'wp_head', 'liferuss_guide_json_ld', 24 );

/**
 * One-time menu updates for cargo, the guide, and the magazine.
 */
function liferuss_sync_service_nav() {
	if ( get_option( 'liferuss_service_nav' ) ) {
		return;
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		return;
	}
	$menu_id = (int) $locations['primary'];
	$items   = wp_get_nav_menu_items( $menu_id );
	$seen    = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			$path = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
			if ( '/freight' === $path ) {
				wp_update_nav_menu_item(
					$menu_id,
					(int) $item->ID,
					array(
						'menu-item-title'  => $item->title,
						'menu-item-url'    => liferuss_url( '/cargo/' ),
						'menu-item-status' => 'publish',
						'menu-item-type'   => 'custom',
					)
				);
				$path = '/cargo';
			}
			$seen[] = $path;
		}
	}
	$extra = array(
		array( 'title' => liferuss_t( 'nav_cargo' ), 'url' => '/cargo/' ),
		array( 'title' => liferuss_t( 'nav_guide' ), 'url' => '/russia-guide/' ),
		array( 'title' => liferuss_t( 'nav_blog' ), 'url' => '/blog/' ),
	);
	$position = is_array( $items ) ? count( $items ) : 0;
	foreach ( $extra as $link ) {
		if ( in_array( untrailingslashit( $link['url'] ), $seen, true ) ) {
			continue;
		}
		++$position;
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'    => $link['title'],
				'menu-item-url'      => liferuss_url( $link['url'] ),
				'menu-item-type'     => 'custom',
				'menu-item-status'   => 'publish',
				'menu-item-position' => $position,
			)
		);
	}
	update_option( 'liferuss_service_nav', '1', false );
}
add_action( 'init', 'liferuss_sync_service_nav', 55 );
