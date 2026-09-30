<?php
/**
 * University, field, and city front-end helpers.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the public catalog classes are loaded.
 */
function liferuss_catalog_ready() {
	return class_exists( '\LifeRuss\Core\Catalog\Query' );
}

/**
 * Archive or single of the three catalog types.
 */
function liferuss_is_catalog() {
	return is_post_type_archive( array( 'lr_university', 'lr_field', 'lr_city' ) ) || is_singular( array( 'lr_university', 'lr_field', 'lr_city' ) );
}

/**
 * Filtered archive or page 2+, which should not be indexed.
 */
function liferuss_catalog_is_filtered() {
	if ( ! liferuss_catalog_ready() || ! is_post_type_archive( array( 'lr_university', 'lr_field', 'lr_city' ) ) ) {
		return false;
	}
	return \LifeRuss\Core\Catalog\Query::request_is_filtered();
}

/**
 * Canonical for catalog views. Filtered and paged archives point at page one.
 *
 * @return string
 */
function liferuss_catalog_canonical() {
	if ( ! liferuss_catalog_is_filtered() ) {
		return liferuss_current_canonical();
	}
	$type = get_query_var( 'post_type' );
	if ( is_array( $type ) ) {
		$type = (string) reset( $type );
	}
	$map = array(
		'lr_university' => '/universities/',
		'lr_field'      => '/fields/',
		'lr_city'       => '/cities/',
	);
	if ( isset( $map[ $type ] ) ) {
		return liferuss_url( $map[ $type ] );
	}
	return liferuss_current_canonical();
}

/**
 * Keep the main archive query cheap. Cards come from the catalog query.
 *
 * @param WP_Query $query Query.
 */
function liferuss_catalog_main_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( array( 'lr_university', 'lr_field', 'lr_city' ) ) ) {
		$query->set( 'posts_per_page', 1 );
		$query->set( 'no_found_rows', true );
		$query->set( 'fields', 'ids' );
	}
}
add_action( 'pre_get_posts', 'liferuss_catalog_main_query' );

/**
 * SEO title stored on the post.
 *
 * @param array $parts Title parts.
 * @return array
 */
function liferuss_catalog_title( $parts ) {
	if ( is_singular( array( 'lr_university', 'lr_field', 'lr_city' ) ) ) {
		$custom = get_post_meta( get_queried_object_id(), '_lr_seo_title', true );
		if ( $custom ) {
			$parts['title'] = $custom;
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', 'liferuss_catalog_title' );

/**
 * Catalog script on the university archive.
 */
function liferuss_catalog_assets() {
	if ( ! is_post_type_archive( 'lr_university' ) || ! liferuss_catalog_ready() ) {
		return;
	}
	wp_enqueue_script(
		'liferuss-catalog',
		LIFERUSS_URI . '/assets/js/catalog.js',
		array(),
		LIFERUSS_VERSION,
		true
	);
	wp_localize_script(
		'liferuss-catalog',
		'liferussCatalog',
		array(
			'endpoint' => rest_url( 'liferuss/v1/universities' ),
			'degrees'  => array(
				'bachelor'   => 'کارشناسی',
				'specialist' => 'تخصصی',
				'master'     => 'ارشد',
				'phd'        => 'دکتری',
				'residency'  => 'رزیدنتی',
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'liferuss_catalog_assets' );

/**
 * Current archive page from the main query.
 */
function liferuss_catalog_paged() {
	$paged = (int) get_query_var( 'paged' );
	return max( 1, $paged );
}

/**
 * Format a USD amount.
 *
 * @param mixed $amount Amount.
 * @return string
 */
function liferuss_catalog_usd( $amount, $with_stamp = false ) {
	if ( null === $amount || '' === (string) $amount || (float) $amount <= 0 ) {
		return '';
	}
	$text = '$' . number_format_i18n( (float) $amount, 0 );
	if ( $with_stamp && function_exists( 'liferuss_fx_stamp_text' ) ) {
		$stamp = liferuss_fx_stamp_text();
		if ( '' !== $stamp ) {
			$text .= ' (' . $stamp . ')';
		}
	}
	return $text;
}

/**
 * Plain Jalali stamp for a tuition USD figure.
 *
 * @return string
 */
function liferuss_fx_stamp_text() {
	if ( ! class_exists( '\LifeRuss\Core\Currency\Rates' ) ) {
		return '';
	}
	return \LifeRuss\Core\Currency\Rates::stamp_text();
}

/**
 * Rate lines and the Jalali stamp on the exchange inquiry page.
 */
function liferuss_fx_panel() {
	if ( ! class_exists( '\LifeRuss\Core\Currency\Rates' ) ) {
		return;
	}
	$lines = \LifeRuss\Core\Currency\Rates::pair_lines();
	$stamp = \LifeRuss\Core\Currency\Rates::stamp_html();
	if ( ! $lines && '' === $stamp ) {
		return;
	}
	echo '<div class="lr-fx">';
	foreach ( $lines as $line ) {
		echo '<p>' . esc_html( $line ) . '</p>';
	}
	if ( '' !== $stamp ) {
		echo '<p class="lr-meta">' . $stamp . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rates::stamp_html() returns escaped markup.
	}
	echo '</div>';
}

/**
 * Degree label.
 *
 * @param string $degree Degree key.
 * @return string
 */
function liferuss_catalog_degree( $degree ) {
	$labels = array(
		'bachelor'   => 'کارشناسی',
		'specialist' => 'تخصصی',
		'master'     => 'کارشناسی ارشد',
		'phd'        => 'دکتری',
		'residency'  => 'رزیدنتی',
	);
	return $labels[ $degree ] ?? $degree;
}

/**
 * Language label.
 *
 * @param string $lang Language key.
 * @return string
 */
function liferuss_catalog_lang( $lang ) {
	$labels = array(
		'ru'    => 'روسی',
		'en'    => 'انگلیسی',
		'ru_en' => 'روسی و انگلیسی',
	);
	return $labels[ $lang ] ?? $lang;
}

/**
 * Tidy empty catalog panel with a consultation link.
 *
 * @param string $title Heading.
 */
function liferuss_empty_catalog( $title ) {
	echo '<div class="lr-empty-state">';
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<p>هنوز موردی اینجا منتشر نشده است. برای انتخاب دانشگاه و مسیر، مشاوره رایگان بگیرید.</p>';
	echo '<a class="btn btn-gold" href="' . esc_url( liferuss_url( '/contact/' ) ) . '">درخواست مشاوره</a>';
	echo '</div>';
}

/**
 * Published universities for the homepage slider.
 *
 * @param int $limit Max cards.
 * @return array<int, array<string, mixed>>
 */
function liferuss_home_universities( $limit = 8 ) {
	if ( ! function_exists( 'liferuss_catalog_ready' ) || ! liferuss_catalog_ready() ) {
		return array();
	}
	$result = \LifeRuss\Core\Catalog\Query::universities( array(), 1 );
	$items  = isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : array();
	return array_slice( $items, 0, max( 1, (int) $limit ) );
}

/**
 * Card grid.
 *
 * @param array $items Cards.
 */
function liferuss_catalog_cards( $items ) {
	echo '<div class="lr-cards" id="lr-catalog-results">';
	if ( ! $items ) {
		liferuss_empty_catalog( 'موردی در این فهرست نیست' );
	}
	foreach ( $items as $item ) {
		$url  = $item['url'] ?? '';
		$name = $item['name'] ?? ( $item['name_fa'] ?? '' );
		echo '<article class="lr-card">';
		if ( ! empty( $item['thumbnail'] ) ) {
			echo '<a href="' . esc_url( $url ) . '"><img src="' . esc_url( $item['thumbnail'] ) . '" alt=""></a>';
		}
		echo '<div class="lr-card-body">';
		echo '<h2><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></h2>';
		if ( ! empty( $item['city'] ) ) {
			echo '<p class="lr-meta">' . esc_html( $item['city'] ) . '</p>';
		}
		$bits = array();
		$usd  = liferuss_catalog_usd( $item['min_tuition_usd'] ?? ( $item['min_usd'] ?? '' ) );
		if ( $usd ) {
			$bits[] = 'از ' . $usd;
		}
		if ( ! empty( $item['best_world_rank'] ) ) {
			$bits[] = 'رتبه ' . (int) $item['best_world_rank'];
		}
		if ( 'approved' === ( $item['health'] ?? '' ) || 'approved' === ( $item['science'] ?? '' ) ) {
			$bits[] = 'تأیید وزارتخانه';
		}
		if ( $bits ) {
			echo '<p class="lr-meta">' . esc_html( implode( ' · ', $bits ) ) . '</p>';
		}
		if ( ! empty( $item['slug'] ) ) {
			echo '<button type="button" class="btn btn-ghost lr-compare-add" data-slug="' . esc_attr( (string) $item['slug'] ) . '" data-name="' . esc_attr( (string) $name ) . '" aria-pressed="false">مقایسه</button>';
		}
		echo '</div></article>';
	}
	echo '</div>';
}

/**
 * Pagination that keeps the current filter query string.
 *
 * @param int $page  Current page.
 * @param int $pages Page count.
 */
function liferuss_catalog_pager( $page, $pages ) {
	if ( $pages < 2 ) {
		return;
	}
	echo '<nav class="lr-pager" aria-label="صفحه‌ها">';
	for ( $i = 1; $i <= $pages; $i++ ) {
		if ( $i > 8 && $i < $pages ) {
			continue;
		}
		$url = get_pagenum_link( $i );
		if ( $i === (int) $page ) {
			echo '<span aria-current="page">' . (int) $i . '</span>';
		} else {
			echo '<a href="' . esc_url( $url ) . '">' . (int) $i . '</a>';
		}
	}
	echo '</nav>';
}

/**
 * FAQ posts linked from a university.
 *
 * @param int $post_id Post id.
 * @return array<int, array{q: string, a: string}>
 */
function liferuss_catalog_faqs( $post_id ) {
	$raw  = (string) get_post_meta( $post_id, '_lr_faq_ids', true );
	$faqs = array();
	foreach ( explode( ',', $raw ) as $id ) {
		$faq = get_post( absint( $id ) );
		if ( ! $faq || 'lr_faq' !== $faq->post_type ) {
			continue;
		}
		if ( function_exists( 'liferuss_is_placeholder_copy' ) && liferuss_is_placeholder_copy( $faq->post_title . ' ' . $faq->post_content ) ) {
			continue;
		}
		$faqs[] = array(
			'q' => $faq->post_title,
			'a' => wp_strip_all_tags( $faq->post_content ),
		);
	}
	return $faqs;
}

/**
 * JSON-LD for catalog templates.
 */
function liferuss_catalog_json_ld() {
	if ( ! liferuss_catalog_ready() || ! liferuss_is_catalog() ) {
		return;
	}
	$graph = array();
	if ( is_singular( 'lr_university' ) ) {
		$row = \LifeRuss\Core\Catalog\Query::university( (string) get_post_field( 'post_name', get_queried_object_id() ) );
		if ( $row ) {
			$place = array(
				'@type'          => 'PostalAddress',
				'addressCountry' => 'RU',
			);
			if ( ! empty( $row['city']['name_fa'] ) ) {
				$place['addressLocality'] = $row['city']['name_fa'];
			}
			if ( ! empty( $row['address'] ) ) {
				$place['streetAddress'] = $row['address'];
			}
			$graph[] = array(
				'@type'   => 'CollegeOrUniversity',
				'name'    => $row['name_fa'],
				'url'     => get_permalink( (int) $row['post_id'] ),
				'address' => $place,
			);
			$faqs = liferuss_catalog_faqs( (int) $row['post_id'] );
			if ( $faqs ) {
				$entities = array();
				foreach ( $faqs as $faq ) {
					$entities[] = array(
						'@type'          => 'Question',
						'name'           => $faq['q'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $faq['a'],
						),
					);
				}
				$graph[] = array(
					'@type'      => 'FAQPage',
					'mainEntity' => $entities,
				);
			}
		}
	}
	if ( is_post_type_archive( 'lr_university' ) ) {
		$result = \LifeRuss\Core\Catalog\Query::universities( \LifeRuss\Core\Catalog\Query::filters_from_request(), liferuss_catalog_paged() );
		$list   = array();
		foreach ( $result['items'] as $index => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'url'      => $item['url'],
				'name'     => $item['name'],
			);
		}
		if ( $list ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'itemListElement' => $list,
			);
		}
	}
	if ( ! $graph ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>';
}
add_action( 'wp_head', 'liferuss_catalog_json_ld', 20 );
