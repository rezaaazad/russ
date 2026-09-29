<?php
/**
 * Study-path, scholarship, and immigration front-end helpers.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public hubs added in theme 1.5.0.
 *
 * @return array<int, array{title: string, url: string}>
 */
function liferuss_path_links() {
	return array(
		array(
			'title' => liferuss_t( 'nav_study' ),
			'url'   => '/study/',
		),
		array(
			'title' => liferuss_t( 'nav_scholarships' ),
			'url'   => '/scholarships/',
		),
		array(
			'title' => liferuss_t( 'nav_podfak' ),
			'url'   => '/podfak/',
		),
		array(
			'title' => liferuss_t( 'nav_direct' ),
			'url'   => '/direct-admission/',
		),
		array(
			'title' => liferuss_t( 'nav_immigration' ),
			'url'   => '/immigration/',
		),
		array(
			'title' => liferuss_t( 'nav_admission' ),
			'url'   => '/admission/',
		),
		array(
			'title' => liferuss_t( 'nav_exchange' ),
			'url'   => '/exchange/',
		),
	);
}

/**
 * Append hub links to the assigned primary menu once.
 */
function liferuss_sync_path_nav() {
	if ( get_option( 'liferuss_path_nav' ) ) {
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
			$seen[] = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
		}
	}
	$position = is_array( $items ) ? count( $items ) : 0;
	foreach ( liferuss_path_links() as $link ) {
		$path = untrailingslashit( $link['url'] );
		if ( in_array( $path, $seen, true ) ) {
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
	update_option( 'liferuss_path_nav', '1', false );
}
add_action( 'init', 'liferuss_sync_path_nav', 50 );

/**
 * Footer hubs that are not already in the saved link list.
 *
 * @return array<int, array{title: string, url: string}>
 */
function liferuss_path_footer_extra() {
	$seen = array();
	foreach ( (array) liferuss_opt( 'footer_links', array() ) as $item ) {
		$seen[] = untrailingslashit( (string) ( $item['url'] ?? '' ) );
	}
	$extra = array();
	foreach ( liferuss_path_links() as $link ) {
		if ( in_array( untrailingslashit( $link['url'] ), $seen, true ) ) {
			continue;
		}
		$extra[] = $link;
	}
	return $extra;
}

/**
 * Comma-separated post ids stored in meta.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @return int[]
 */
function liferuss_path_ids( $post_id, $key ) {
	$ids = array();
	foreach ( explode( ',', (string) get_post_meta( $post_id, $key, true ) ) as $part ) {
		$id = absint( $part );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * Study level preset for the consult select.
 *
 * @param string $degree  Catalog degree.
 * @param string $field   Field slug.
 * @param string $program Program type.
 * @return string
 */
function liferuss_path_level( $degree, $field, $program ) {
	if ( 'padfak' === $program ) {
		return 'padfak';
	}
	if ( 'dentistry' === $field ) {
		return 'dentistry';
	}
	if ( 'pharmacy' === $field ) {
		return 'pharmacy';
	}
	if ( in_array( $field, array( 'general-medicine', 'medicine' ), true ) ) {
		return 'medicine';
	}
	if ( in_array( $degree, array( 'bachelor', 'master', 'phd' ), true ) ) {
		return $degree;
	}
	return '';
}

/**
 * Hand the shared form the request type for this screen.
 *
 * @param string $type        consult, admission, exchange, or immigration.
 * @param string $program     degree, padfak, direct_course, or scholarship.
 * @param string $degree      Catalog degree enum.
 * @param string $level       Study-level select value.
 * @param string $scholarship Scholarship title.
 */
function liferuss_path_form( $type, $program, $degree, $level, $scholarship ) {
	if ( in_array( $type, array( 'freight', 'trade' ), true ) ) {
		get_template_part( 'template-parts/landing-form', null, array( 'prefix' => $type ) );
		return;
	}
	$allowed = array( 'consult', 'admission', 'exchange', 'immigration' );
	if ( ! in_array( $type, $allowed, true ) ) {
		return;
	}
	$GLOBALS['liferuss_form'] = array(
		'type'        => $type,
		'program'     => $program,
		'degree'      => $degree,
		'level'       => $level,
		'scholarship' => $scholarship,
	);
	get_template_part( 'template-parts/consult-form' );
	unset( $GLOBALS['liferuss_form'] );
}

/**
 * Live catalog block for a path page.
 *
 * @param int $post_id Page id.
 */
function liferuss_path_live( $post_id ) {
	if ( ! function_exists( 'liferuss_catalog_ready' ) || ! liferuss_catalog_ready() ) {
		return;
	}
	$mode = (string) get_post_meta( $post_id, '_lr_catalog', true );
	if ( '' === $mode ) {
		return;
	}
	echo '<section class="section path-live"><div class="container">';
	if ( 'children' === $mode ) {
		$children = get_pages(
			array(
				'parent'      => $post_id,
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			)
		);
		echo '<div class="path-children">';
		if ( ! $children ) {
			echo '<p class="lr-empty">' . esc_html( liferuss_t( 'path_empty' ) ) . '</p>';
		}
		foreach ( $children as $child ) {
			echo '<a class="path-child" href="' . esc_url( get_permalink( $child ) ) . '">';
			echo '<strong>' . esc_html( get_the_title( $child ) ) . '</strong>';
			$lead = (string) get_post_meta( $child->ID, '_lr_lead', true );
			if ( $lead ) {
				echo '<span>' . esc_html( $lead ) . '</span>';
			}
			echo '</a>';
		}
		echo '</div></div></section>';
		return;
	}
	if ( 'padfak' === $mode || 'direct' === $mode ) {
		$type  = 'padfak' === $mode ? 'padfak' : 'direct_course';
		$items = \LifeRuss\Core\Catalog\Query::prep( $type );
		echo '<h2>' . esc_html( liferuss_t( 'path_prep_title' ) ) . '</h2>';
		if ( ! $items ) {
			echo '<p class="lr-empty">' . esc_html( liferuss_t( 'path_empty' ) ) . '</p></div></section>';
			return;
		}
		echo '<div class="lr-cards">';
		foreach ( $items as $item ) {
			echo '<article class="lr-card"><div class="lr-card-body">';
			echo '<h2><a href="' . esc_url( (string) $item['url'] ) . '">' . esc_html( (string) $item['name_fa'] ) . '</a></h2>';
			$bits = array();
			if ( ! empty( $item['city_name'] ) ) {
				$bits[] = (string) $item['city_name'];
			}
			if ( ! empty( $item['duration_months'] ) ) {
				$bits[] = (int) $item['duration_months'] . ' ' . liferuss_t( 'path_months' );
			}
			$usd = liferuss_catalog_usd( $item['usd'] ?? '' );
			if ( $usd ) {
				$bits[] = $usd;
			}
			if ( $bits ) {
				echo '<p class="lr-meta">' . esc_html( implode( ' · ', $bits ) ) . '</p>';
			}
			echo '</div></article>';
		}
		echo '</div></div></section>';
		return;
	}
	$degree  = (string) get_post_meta( $post_id, '_lr_degree', true );
	$field   = (string) get_post_meta( $post_id, '_lr_field', true );
	$filters = array();
	if ( $degree ) {
		$filters['degree'] = $degree;
	}
	if ( $field ) {
		$filters['field'] = $field;
	}
	$snap = \LifeRuss\Core\Catalog\Query::snapshot( $filters );
	echo '<h2>' . esc_html( liferuss_t( 'path_live_title' ) ) . '</h2>';
	echo '<ul class="lr-facts">';
	echo '<li>' . esc_html( liferuss_t( 'path_count' ) ) . ' ' . (int) $snap['total'] . '</li>';
	if ( $snap['min_usd'] > 0 ) {
		$range = liferuss_catalog_usd( $snap['min_usd'] );
		if ( $snap['max_usd'] > $snap['min_usd'] ) {
			$range .= ' – ' . liferuss_catalog_usd( $snap['max_usd'] );
		}
		echo '<li>' . esc_html( liferuss_t( 'path_tuition' ) ) . ' ' . esc_html( $range ) . '</li>';
	}
	echo '</ul>';
	if ( 'tuition' !== $mode ) {
		liferuss_catalog_cards( $snap['items'] );
	}
	$query = array();
	if ( $degree ) {
		$query['degree'] = $degree;
	}
	if ( $field ) {
		$query['field'] = $field;
	}
	$archive = add_query_arg( $query, liferuss_url( '/universities/' ) );
	echo '<p><a class="btn btn-gold" href="' . esc_url( $archive ) . '">' . esc_html( liferuss_t( 'path_all_unis' ) ) . '</a></p>';
	echo '</div></section>';
}

/**
 * Tuition range used on the costs page.
 */
function liferuss_path_tuition_block() {
	if ( ! function_exists( 'liferuss_catalog_ready' ) || ! liferuss_catalog_ready() ) {
		return;
	}
	$snap = \LifeRuss\Core\Catalog\Query::snapshot( array() );
	echo '<section class="section path-live"><div class="container">';
	echo '<h2>' . esc_html( liferuss_t( 'path_tuition' ) ) . '</h2>';
	echo '<ul class="lr-facts">';
	echo '<li>' . esc_html( liferuss_t( 'path_count' ) ) . ' ' . (int) $snap['total'] . '</li>';
	if ( $snap['min_usd'] > 0 ) {
		echo '<li>' . esc_html( liferuss_catalog_usd( $snap['min_usd'] ) ) . ' – ' . esc_html( liferuss_catalog_usd( $snap['max_usd'] ) ) . '</li>';
	}
	echo '</ul></div></section>';
}

/**
 * Testimonials linked from a page or scholarship.
 *
 * @param int $post_id Post id.
 */
function liferuss_path_quotes( $post_id ) {
	$quotes = array();
	foreach ( liferuss_path_ids( $post_id, '_lr_testimonial_ids' ) as $id ) {
		$post = get_post( $id );
		if ( ! $post || 'lr_testimonial' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}
		$quotes[] = $post;
	}
	if ( ! $quotes ) {
		return;
	}
	echo '<section class="section"><div class="container path-quotes">';
	echo '<h2>' . esc_html( liferuss_t( 'path_quotes' ) ) . '</h2>';
	foreach ( $quotes as $quote ) {
		echo '<blockquote><p>' . esc_html( wp_strip_all_tags( $quote->post_content ) ) . '</p><cite>' . esc_html( $quote->post_title ) . '</cite></blockquote>';
	}
	echo '</div></section>';
}

/**
 * Related guides and posts.
 *
 * @param int $post_id Post id.
 */
function liferuss_path_related( $post_id ) {
	$groups = array(
		'_lr_guide_ids' => liferuss_t( 'path_guides' ),
		'_lr_post_ids'  => liferuss_t( 'path_posts' ),
	);
	$printed = false;
	foreach ( $groups as $key => $label ) {
		$posts = array();
		foreach ( liferuss_path_ids( $post_id, $key ) as $id ) {
			$post = get_post( $id );
			if ( $post && 'publish' === $post->post_status ) {
				$posts[] = $post;
			}
		}
		if ( ! $posts ) {
			continue;
		}
		if ( ! $printed ) {
			echo '<section class="section"><div class="container path-related">';
			$printed = true;
		}
		echo '<h2>' . esc_html( $label ) . '</h2><ul class="footer-links">';
		foreach ( $posts as $post ) {
			echo '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
		}
		echo '</ul>';
	}
	if ( $printed ) {
		echo '</div></section>';
	}
}

/**
 * FAQ markup shared with the catalog.
 *
 * @param int $post_id Post id.
 */
function liferuss_path_faqs( $post_id ) {
	if ( ! function_exists( 'liferuss_catalog_faqs' ) ) {
		return;
	}
	$faqs = liferuss_catalog_faqs( $post_id );
	if ( ! $faqs ) {
		return;
	}
	echo '<section class="section"><div class="container lr-faq">';
	echo '<h2>' . esc_html( liferuss_t( 'path_faq' ) ) . '</h2>';
	foreach ( $faqs as $faq ) {
		echo '<details><summary>' . esc_html( $faq['q'] ) . '</summary><p>' . esc_html( $faq['a'] ) . '</p></details>';
	}
	echo '</div></section>';
}

/**
 * FAQPage, Course, and MonetaryGrant for path and scholarship screens.
 */
function liferuss_path_json_ld() {
	$graph   = array();
	$post_id = 0;
	if ( is_page_template( 'templates/path.php' ) ) {
		$post_id = (int) get_queried_object_id();
		$schema  = (string) get_post_meta( $post_id, '_lr_schema', true );
		$lead    = (string) get_post_meta( $post_id, '_lr_lead', true );
		if ( 'course' === $schema ) {
			$graph[] = array(
				'@type'       => 'Course',
				'name'        => get_the_title( $post_id ),
				'description' => $lead ? $lead : wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ),
				'url'         => get_permalink( $post_id ),
				'provider'    => array(
					'@type' => 'Organization',
					'name'  => liferuss_brand(),
					'url'   => liferuss_home(),
				),
			);
		}
		if ( 'grant' === $schema ) {
			$graph[] = liferuss_path_grant( $post_id );
		}
		if ( 'service' === $schema ) {
			$graph[] = liferuss_path_service( $post_id, $lead );
		}
	} elseif ( is_page_template( array( 'templates/freight.php', 'templates/trade.php' ) ) ) {
		$post_id = (int) get_queried_object_id();
		$graph[] = liferuss_path_service( $post_id, wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
	} elseif ( is_singular( 'lr_scholarship' ) ) {
		$post_id = (int) get_queried_object_id();
		$graph[] = liferuss_path_grant( $post_id );
	} elseif ( is_post_type_archive( 'lr_scholarship' ) ) {
		$list = array();
		$q    = new WP_Query(
			array(
				'post_type'      => 'lr_scholarship',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$position = 1;
		foreach ( $q->posts as $post ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'url'      => get_permalink( $post ),
				'name'     => get_the_title( $post ),
			);
			++$position;
		}
		wp_reset_postdata();
		if ( $list ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'itemListElement' => $list,
			);
		}
	}
	if ( $post_id && function_exists( 'liferuss_catalog_faqs' ) ) {
		$faqs = liferuss_catalog_faqs( $post_id );
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
add_action( 'wp_head', 'liferuss_path_json_ld', 22 );

/**
 * Scholarship-shaped schema. There is no scholarship table in the ERD.
 *
 * @param int $post_id Post id.
 * @return array<string, mixed>
 */
function liferuss_path_grant( $post_id ) {
	$source = (string) get_post_meta( $post_id, '_lr_source', true );
	$grant  = array(
		'@type'       => 'MonetaryGrant',
		'name'        => get_the_title( $post_id ),
		'description' => (string) get_post_meta( $post_id, '_lr_coverage', true ),
		'url'         => get_permalink( $post_id ),
		'funder'      => array(
			'@type' => 'Organization',
			'name'  => $source ? $source : liferuss_brand(),
		),
	);
	$deadline = (string) get_post_meta( $post_id, '_lr_deadline', true );
	if ( $deadline ) {
		$grant['disambiguatingDescription'] = $deadline;
	}
	return $grant;
}

/**
 * Service node for exchange, cargo, and trade screens.
 *
 * @param int    $post_id Post id.
 * @param string $lead    Description.
 * @return array<string, mixed>
 */
function liferuss_path_service( $post_id, $lead ) {
	return array(
		'@type'       => 'Service',
		'name'        => get_the_title( $post_id ),
		'description' => $lead ? $lead : wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ),
		'url'         => get_permalink( $post_id ),
		'provider'    => array(
			'@type' => 'Organization',
			'name'  => liferuss_brand(),
			'url'   => liferuss_home(),
		),
	);
}

/**
 * Published child pages of the current hub.
 */
function liferuss_hub_children() {
	$children = get_pages(
		array(
			'parent'      => get_queried_object_id(),
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
		)
	);
	if ( ! $children ) {
		return;
	}
	echo '<section class="section"><div class="container path-children">';
	foreach ( $children as $child ) {
		echo '<a class="path-child" href="' . esc_url( get_permalink( $child ) ) . '"><strong>' . esc_html( get_the_title( $child ) ) . '</strong></a>';
	}
	echo '</div></section>';
}
