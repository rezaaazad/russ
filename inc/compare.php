<?php
/**
 * Comparison and search templates, titles, and the compare tray.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the comparison or search template.
 *
 * @param string $template Current template.
 * @return string
 */
function liferuss_front_template( $template ) {
	if ( get_query_var( 'lr_compare' ) ) {
		$custom = locate_template( 'templates/compare.php' );
		return $custom ? $custom : $template;
	}
	if ( get_query_var( 'lr_find' ) ) {
		$custom = locate_template( 'templates/find.php' );
		return $custom ? $custom : $template;
	}
	if ( get_query_var( 'lr_account' ) ) {
		$custom = locate_template( 'templates/account.php' );
		return $custom ? $custom : $template;
	}
	if ( get_query_var( 'lr_pay' ) ) {
		$custom = locate_template( 'templates/pay.php' );
		return $custom ? $custom : $template;
	}
	if ( get_query_var( 'lr_learn' ) || is_singular( array( 'lr_course', 'lr_lesson' ) ) ) {
		$custom = locate_template( 'templates/language.php' );
		return $custom ? $custom : $template;
	}
	return $template;
}
add_filter( 'template_include', 'liferuss_front_template' );

/**
 * Document title for comparison and search.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function liferuss_front_title( $parts ) {
	if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
		$data = \LifeRuss\Core\Compare\Set::current();
		if ( ! empty( $data['title'] ) ) {
			$parts['title'] = (string) $data['title'];
		}
	}
	if ( get_query_var( 'lr_find' ) ) {
		$parts['title'] = 'جستجو';
	}
	if ( get_query_var( 'lr_account' ) ) {
		$parts['title'] = 'حساب من';
	}
	if ( get_query_var( 'lr_pay' ) ) {
		$parts['title'] = 'پرداخت';
	}
	$learn = (string) get_query_var( 'lr_learn' );
	if ( 'index' === $learn ) {
		$parts['title'] = 'آموزش زبان روسی';
	} elseif ( 'placement' === $learn ) {
		$parts['title'] = 'تعیین سطح زبان روسی';
	} elseif ( 'certificate' === $learn ) {
		$parts['title'] = 'گواهی دوره';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'liferuss_front_title' );

/**
 * Fixed tray that links to the shareable comparison URL.
 */
function liferuss_compare_bar() {
	echo '<div class="lr-compare-bar" id="lr-compare-bar" hidden>';
	echo '<p class="lr-compare-bar-text" id="lr-compare-bar-text"></p>';
	echo '<a class="btn btn-gold" id="lr-compare-open" href="' . esc_url( liferuss_url( '/compare/' ) ) . '">مشاهده مقایسه</a>';
	echo '</div>';
}

/**
 * Header search combobox.
 */
function liferuss_header_search() {
	$action = liferuss_url( '/search/' );
	echo '<form class="lr-finder" role="search" method="get" action="' . esc_url( $action ) . '">';
	echo '<label class="screen-reader-text" for="lr-finder-input">جستجو</label>';
	echo '<input id="lr-finder-input" type="search" name="q" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="lr-finder-list" aria-activedescendant="" autocomplete="off" placeholder="جستجوی دانشگاه، رشته، شهر">';
	echo '<ul id="lr-finder-list" class="lr-finder-list" role="listbox" hidden></ul>';
	echo '</form>';
}

/**
 * ItemList and FAQ schema for a comparison.
 *
 * @param array<string, mixed> $data Comparison payload.
 */
function liferuss_compare_schema( $data ) {
	$list = array();
	$pos  = 1;
	foreach ( (array) ( $data['columns'] ?? array() ) as $column ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'item'     => array(
				'@type' => 'CollegeOrUniversity',
				'name'  => (string) ( $column['name_fa'] ?? '' ),
				'url'   => (string) get_permalink( (int) ( $column['post_id'] ?? 0 ) ),
			),
		);
		++$pos;
	}
	$graph = array(
		array(
			'@type'           => 'ItemList',
			'name'            => (string) ( $data['title'] ?? '' ),
			'itemListElement' => $list,
		),
	);
	if ( ! empty( $data['faq'] ) && is_array( $data['faq'] ) ) {
		$entities = array();
		foreach ( $data['faq'] as $faq ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => (string) $faq['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => (string) $faq['a'],
				),
			);
		}
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>' . "\n";
}
