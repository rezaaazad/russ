<?php
/**
 * SEO, Open Graph, JSON-LD, breadcrumbs, llms.txt, multilingual sitemap.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end language attributes (lang + dir).
 *
 * @param string $output Existing.
 * @return string
 */
function liferuss_language_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}
	$lang = liferuss_current_lang();
	$meta = liferuss_languages()[ $lang ];
	return 'lang="' . esc_attr( $meta['html_lang'] ) . '" dir="' . esc_attr( $meta['dir'] ) . '"';
}
add_filter( 'language_attributes', 'liferuss_language_attributes' );

/**
 * Absolute URL of the current view in a language.
 *
 * @param string|null $lang Language.
 * @return string
 */
function liferuss_current_canonical( $lang = null ) {
	$lang = $lang ? $lang : liferuss_current_lang();
	return liferuss_url( liferuss_current_path(), $lang );
}

/**
 * Document title on the front page (per language).
 *
 * @param array $parts Parts.
 * @return array
 */
function liferuss_document_title( $parts ) {
	if ( is_front_page() ) {
		$parts['title']   = liferuss_opt( 'seo_title', liferuss_brand() );
		$parts['site']    = '';
		$parts['tagline'] = '';
	} elseif ( is_page_template( 'templates/freight.php' ) && liferuss_opt( 'freight_seo_title' ) ) {
		$parts['title']   = liferuss_opt( 'freight_seo_title' );
		$parts['site']    = '';
		$parts['tagline'] = '';
	} elseif ( is_page_template( 'templates/trade.php' ) && liferuss_opt( 'trade_seo_title' ) ) {
		$parts['title']   = liferuss_opt( 'trade_seo_title' );
		$parts['site']    = '';
		$parts['tagline'] = '';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'liferuss_document_title' );

/**
 * Meta, OG, Twitter, hreflang, per-language canonical.
 */
function liferuss_head_meta() {
	$lang = liferuss_current_lang();
	$meta = liferuss_languages()[ $lang ];
	$desc = liferuss_opt( 'seo_description' );
	if ( is_page_template( 'templates/freight.php' ) && liferuss_opt( 'freight_seo_description' ) ) {
		$desc = liferuss_opt( 'freight_seo_description' );
	} elseif ( is_page_template( 'templates/trade.php' ) && liferuss_opt( 'trade_seo_description' ) ) {
		$desc = liferuss_opt( 'trade_seo_description' );
	} elseif ( is_singular() && ! is_front_page() ) {
		$excerpt = get_the_excerpt();
		if ( $excerpt ) {
			$desc = wp_strip_all_tags( $excerpt );
		}
	}
	$og_desc = liferuss_opt( 'seo_og_description', $desc );
	$url     = liferuss_current_canonical();
	$title   = wp_get_document_title();
	$og_title = liferuss_opt( 'seo_og_title', $title );
	if ( ! is_front_page() ) {
		$og_title = $title;
		$og_desc  = $desc;
	}
	$og_id = absint( liferuss_opt( 'og_image_id', 0 ) );
	$image = $og_id ? wp_get_attachment_image_url( $og_id, 'liferuss-wide' ) : liferuss_img( 'st-basil.jpg' );
	$brand = liferuss_brand();

	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="robots" content="index, follow">' . "\n";
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";

	foreach ( liferuss_languages() as $code => $info ) {
		echo '<link rel="alternate" hreflang="' . esc_attr( $info['hreflang'] ) . '" href="' . esc_url( liferuss_current_canonical( $code ) ) . '">' . "\n";
	}
	echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( liferuss_current_canonical( 'fa' ) ) . '">' . "\n";

	echo '<meta property="og:locale" content="' . esc_attr( $meta['og_locale'] ) . '">' . "\n";
	foreach ( liferuss_languages() as $code => $info ) {
		if ( $code === $lang ) {
			continue;
		}
		echo '<meta property="og:locale:alternate" content="' . esc_attr( $info['og_locale'] ) . '">' . "\n";
	}
	echo '<meta property="og:type" content="' . ( is_front_page() ? 'website' : 'article' ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $brand ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}
add_action( 'wp_head', 'liferuss_head_meta', 1 );

/**
 * Let WordPress skip its own canonical (we output a language-aware one).
 */
function liferuss_disable_core_canonical() {
	remove_action( 'wp_head', 'rel_canonical' );
}
add_action( 'wp', 'liferuss_disable_core_canonical' );

/**
 * JSON-LD graphs in the active language.
 */
function liferuss_json_ld() {
	$lang    = liferuss_current_lang();
	$meta    = liferuss_languages()[ $lang ];
	$brand   = liferuss_opt( 'org_name', liferuss_brand() );
	$legal   = liferuss_opt( 'org_legal', 'LifeRuss' );
	$org_url = liferuss_opt( 'org_url', home_url( '/' ) );
	$email   = liferuss_opt( 'email' );
	$phone   = liferuss_opt( 'phone' );
	$address = liferuss_opt( 'address' );
	$logo    = liferuss_media_url( liferuss_opt( 'logo_id' ), '', 'full' );
	if ( ! $logo ) {
		$logo = liferuss_img( 'st-basil.jpg' );
	}

	$same_as = array_values(
		array_filter(
			array(
				liferuss_social_url( liferuss_opt( 'telegram' ), 'telegram' ),
				liferuss_social_url( liferuss_opt( 'instagram' ), 'instagram' ),
				esc_url_raw( liferuss_opt( 'linkedin' ) ),
				esc_url_raw( liferuss_opt( 'youtube' ) ),
				liferuss_whatsapp_url( liferuss_opt( 'whatsapp' ) ),
			)
		)
	);

	$org = array(
		'@type'           => array( 'Organization', 'ProfessionalService', 'LocalBusiness' ),
		'@id'             => trailingslashit( $org_url ) . '#organization',
		'name'            => $brand,
		'alternateName'   => array( $legal, 'Life Russ', 'LifeRuss', 'لایف روس' ),
		'url'             => liferuss_home( $lang ),
		'logo'            => $logo,
		'image'           => $logo,
		'email'           => $email,
		'telephone'       => $phone,
		'description'     => liferuss_opt( 'seo_description' ),
		'sameAs'          => $same_as,
		'areaServed'      => array( 'IR', 'RU' ),
		'serviceType'     => liferuss_t( 'service_type' ),
		'priceRange'      => '$$',
		'inLanguage'      => $meta['html_lang'],
	);
	if ( $address ) {
		$org['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => $address,
			'addressCountry' => 'IR',
		);
	}

	$website = array(
		'@type'           => 'WebSite',
		'@id'             => trailingslashit( liferuss_home( $lang ) ) . '#website',
		'url'             => liferuss_home( $lang ),
		'name'            => $brand,
		'inLanguage'      => $meta['html_lang'],
		'publisher'       => array( '@id' => $org['@id'] ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => liferuss_url( '/', $lang ) . '?s={search_term_string}',
			'query-input' => 'required name=search_term_string',
		),
	);

	$graph = array( $org, $website );

	if ( is_front_page() ) {
		$offers = array();
		foreach ( liferuss_services() as $service ) {
			$offers[] = array(
				'@type'       => 'Service',
				'name'        => $service['title'],
				'description' => $service['text'],
				'provider'    => array( '@id' => $org['@id'] ),
				'areaServed'  => 'RU',
				'inLanguage'  => $meta['html_lang'],
			);
		}
		$graph[] = array(
			'@type'      => 'FAQPage',
			'inLanguage' => $meta['html_lang'],
			'mainEntity' => array(
				array(
					'@type'          => 'Question',
					'name'           => liferuss_t( 'faq_q1' ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => liferuss_t( 'faq_a1' ),
					),
				),
				array(
					'@type'          => 'Question',
					'name'           => liferuss_t( 'faq_q2' ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => liferuss_t( 'faq_a2' ),
					),
				),
				array(
					'@type'          => 'Question',
					'name'           => liferuss_t( 'faq_q3' ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => liferuss_t( 'faq_a3' ),
					),
				),
			),
		);
		$graph = array_merge( $graph, $offers );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'liferuss_json_ld', 30 );

/**
 * Breadcrumb trail for inner pages.
 */
function liferuss_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$items = array(
		array( 'label' => liferuss_t( 'crumb_home' ), 'url' => liferuss_home() ),
	);
	if ( is_home() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_blog' ), 'url' => '' );
	} elseif ( is_singular( 'post' ) ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_blog' ), 'url' => liferuss_url( '/blog/' ) );
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_page() ) {
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_search() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_search' ), 'url' => '' );
	} elseif ( is_404() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_404' ), 'url' => '' );
	}

	echo '<nav class="breadcrumbs" aria-label="' . esc_attr( liferuss_t( 'crumb_aria' ) ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		echo '<li>';
		if ( $item['url'] && $i !== $last ) {
			echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';

	$list = array();
	foreach ( $items as $i => $item ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['label'],
			'item'     => $item['url'] ? $item['url'] : liferuss_current_canonical(),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'inLanguage'      => liferuss_lang_meta( 'html_lang' ),
			'itemListElement' => $list,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>';
}

/**
 * Serve /llms.txt from the theme (also /en/llms.txt after prefix strip).
 */
function liferuss_llms_txt() {
	$path       = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	$normalized = untrailingslashit( (string) $path );
	if ( '/llms.txt' !== $normalized && 'llms.txt' !== ltrim( $normalized, '/' ) ) {
		return;
	}
	status_header( 200 );
	global $wp_query;
	if ( $wp_query ) {
		$wp_query->is_404 = false;
	}
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	$brand = liferuss_brand();
	$lines = array(
		'# ' . $brand . ' (LifeRuss)',
		'> Multilingual study-abroad and immigration advisory for Russia. Not an e-commerce store.',
		'',
		'Site (Persian, default, RTL): ' . liferuss_home( 'fa' ),
		'Site (Russian, LTR): ' . liferuss_home( 'ru' ),
		'Site (Arabic, RTL): ' . liferuss_home( 'ar' ),
		'Site (English, LTR): ' . liferuss_home( 'en' ),
		'Organization: ' . $brand,
		'Alternate names: LifeRuss, Life Russ, لایف روس',
		'Languages: fa (default, x-default), ru, ar, en',
		'Email: ' . liferuss_opt( 'email' ),
		'Phone: ' . liferuss_opt( 'phone' ),
		'Address: ' . liferuss_opt( 'address' ),
		'',
		'## Services',
	);
	foreach ( liferuss_services() as $service ) {
		$lines[] = '- ' . $service['title'] . ': ' . $service['text'];
	}
	$lines[] = '';
	$lines[] = '## SameAs';
	foreach ( array( 'telegram', 'instagram', 'linkedin', 'youtube' ) as $net ) {
		$url = liferuss_opt( $net );
		if ( $url ) {
			$lines[] = '- ' . $url;
		}
	}
	$lines[] = '';
	$lines[] = '## Preferred sources (all language variants)';
	foreach ( array( '/', '/about/', '/services/', '/contact/' ) as $p ) {
		foreach ( array_keys( liferuss_languages() ) as $code ) {
			$lines[] = '- ' . liferuss_url( $p, $code );
		}
	}
	echo implode( "\n", $lines );
	exit;
}
add_action( 'template_redirect', 'liferuss_llms_txt', 0 );

/**
 * Register a sitemap provider listing every language variant.
 *
 * @param WP_Sitemaps $wp_sitemaps Server.
 */
function liferuss_register_sitemap_provider( $wp_sitemaps ) {
	require_once LIFERUSS_DIR . '/inc/sitemap-provider.php';
	$wp_sitemaps->registry->add_provider( 'langs', new LifeRuss_Sitemap_Provider() );
}
add_action( 'wp_sitemaps_init', 'liferuss_register_sitemap_provider' );

/**
 * Also add hreflang-style alternates onto core page sitemap entries.
 *
 * @param array  $entry Entry.
 * @param object $post  Post.
 * @return array
 */
function liferuss_sitemap_page_entry( $entry, $post ) {
	if ( empty( $entry['loc'] ) ) {
		return $entry;
	}
	$path = (string) wp_parse_url( $entry['loc'], PHP_URL_PATH );
	if ( preg_match( '#^/(en|ru|ar)(/.*)?$#', $path, $match ) ) {
		$path = isset( $match[2] ) && '' !== $match[2] ? $match[2] : '/';
	}
	if ( ! $path ) {
		$path = '/';
	}
	$alternates = array();
	foreach ( liferuss_languages() as $code => $info ) {
		$alternates[] = array(
			'hreflang' => $info['hreflang'],
			'loc'      => liferuss_url( $path, $code ),
		);
	}
	$entry['alternates'] = $alternates;
	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'liferuss_sitemap_page_entry', 10, 2 );
