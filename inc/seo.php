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
	} elseif ( is_page_template( 'templates/path.php' ) || is_singular( 'lr_scholarship' ) ) {
		$custom = (string) get_post_meta( get_queried_object_id(), '_lr_seo_title', true );
		if ( $custom ) {
			$parts['title'] = $custom;
		}
	} elseif ( is_post_type_archive( 'lr_scholarship' ) ) {
		$parts['title'] = liferuss_t( 'nav_scholarships' );
	} elseif ( is_post_type_archive( 'lr_guide' ) || is_tax( 'lr_guide_cat' ) ) {
		$parts['title'] = is_tax() ? single_term_title( '', false ) : liferuss_t( 'nav_guide' );
	} elseif ( is_home() ) {
		$parts['title'] = liferuss_t( 'blog_title' );
	} elseif ( is_category() || is_tag() ) {
		$parts['title'] = single_term_title( '', false );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'liferuss_document_title' );

/**
 * Rank Math is printing the front-end head on this request.
 *
 * The plugin constant alone is not enough: until registration is skipped or
 * the site is connected, Rank Math does not boot its head, and our tags stay.
 */
function liferuss_rank_math_active() {
	if ( ! defined( 'RANK_MATH_VERSION' ) || ! function_exists( 'rank_math' ) ) {
		return false;
	}
	$plugin = rank_math();
	return is_object( $plugin ) && ! empty( $plugin->head );
}

/**
 * Global switch: index RU/EN/AR even before a translation is marked complete.
 */
function liferuss_index_incomplete() {
	if ( ! class_exists( '\LifeRuss\Core\Settings\Settings' ) ) {
		return false;
	}
	$languages = \LifeRuss\Core\Settings\Settings::get( 'languages' );
	return '1' === (string) ( $languages['index_incomplete'] ?? '0' );
}

/**
 * Whether a language version of this post may be indexed.
 *
 * Persian is always complete. Other languages need the per-post flag.
 *
 * @param string $lang    Language code.
 * @param int    $post_id Post id. Zero uses the queried object.
 */
function liferuss_translation_complete( $lang, $post_id = 0 ) {
	if ( 'fa' === $lang || '' === (string) $lang ) {
		return true;
	}
	if ( liferuss_index_incomplete() ) {
		return true;
	}
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( function_exists( 'pll_get_post_language' ) && $post_id ) {
		$target = $post_id;
		$own    = (string) pll_get_post_language( $post_id, 'slug' );
		if ( $own && $own !== $lang && function_exists( 'pll_get_post' ) ) {
			$target = (int) pll_get_post( $post_id, $lang );
		}
		return $target && '1' === (string) get_post_meta( $target, '_lr_translation_complete', true );
	}
	$ready = array_filter( array_map( 'sanitize_key', explode( ',', (string) get_post_meta( $post_id, '_lr_lang_ready', true ) ) ) );
	return in_array( $lang, $ready, true );
}

/**
 * Languages that should receive hreflang for the current view.
 *
 * @return string[]
 */
function liferuss_complete_langs() {
	$langs   = array( 'fa' );
	$post_id = (int) get_queried_object_id();
	foreach ( array_keys( liferuss_languages() ) as $code ) {
		if ( 'fa' !== $code && liferuss_translation_complete( $code, $post_id ) ) {
			$langs[] = $code;
		}
	}
	return $langs;
}

/**
 * noindex for filtered archives, paginated archives, explicit flags, and incomplete translations.
 */
function liferuss_should_noindex() {
	if ( get_query_var( 'lr_find' ) || get_query_var( 'lr_account' ) || get_query_var( 'lr_pay' ) ) {
		return true;
	}
	$learn = (string) get_query_var( 'lr_learn' );
	if ( 'placement' === $learn || 'certificate' === $learn ) {
		return true;
	}
	if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
		$data = \LifeRuss\Core\Compare\Set::current();
		if ( empty( $data['indexable'] ) ) {
			return true;
		}
	}
	if ( function_exists( 'liferuss_catalog_is_filtered' ) && liferuss_catalog_is_filtered() ) {
		return true;
	}
	if ( is_paged() && ( is_archive() || is_home() || is_search() ) ) {
		return true;
	}
	if ( is_singular() && '1' === (string) get_post_meta( get_queried_object_id(), '_lr_noindex', true ) ) {
		return true;
	}
	$lang = liferuss_current_lang();
	if ( 'fa' !== $lang && ! liferuss_translation_complete( $lang, get_queried_object_id() ) ) {
		return true;
	}
	return false;
}

/**
 * Drop drafts, noindex rows, and incomplete translations from sitemaps.
 *
 * @param int $post_id Post id.
 */
function liferuss_post_excluded_from_sitemap( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return true;
	}
	if ( '1' === (string) get_post_meta( $post_id, '_lr_noindex', true ) ) {
		return true;
	}
	if ( function_exists( 'pll_get_post_language' ) ) {
		$lang = (string) pll_get_post_language( $post_id, 'slug' );
		if ( $lang && 'fa' !== $lang && ! liferuss_translation_complete( $lang, $post_id ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Title override from our SEO fields. Empty means leave the default.
 */
function liferuss_seo_title() {
	if ( is_front_page() ) {
		return (string) liferuss_opt( 'seo_title', '' );
	}
	if ( is_page_template( 'templates/freight.php' ) && liferuss_opt( 'freight_seo_title' ) ) {
		return (string) liferuss_opt( 'freight_seo_title' );
	}
	if ( is_page_template( 'templates/trade.php' ) && liferuss_opt( 'trade_seo_title' ) ) {
		return (string) liferuss_opt( 'trade_seo_title' );
	}
	if ( is_singular() ) {
		$custom = (string) get_post_meta( get_queried_object_id(), '_lr_seo_title', true );
		if ( $custom ) {
			return $custom;
		}
	}
	return '';
}

/**
 * Description used by our tags and by Rank Math.
 */
function liferuss_seo_description() {
	if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
		$data = \LifeRuss\Core\Compare\Set::current();
		if ( ! empty( $data['description'] ) ) {
			return (string) $data['description'];
		}
	}
	if ( get_query_var( 'lr_find' ) ) {
		return 'جستجو در دانشگاه‌ها، رشته‌ها، شهرها، بورسیه‌ها، دانستنی‌ها و مجله.';
	}
	if ( get_query_var( 'lr_account' ) ) {
		return 'پیگیری درخواست، مدارک و گفتگو با مشاور.';
	}
	if ( 'index' === (string) get_query_var( 'lr_learn' ) ) {
		return 'دوره‌های زبان روسی از A1 تا B2، با درس، تمرین و تعیین سطح.';
	}
	$desc = (string) liferuss_opt( 'seo_description' );
	if ( is_page_template( 'templates/freight.php' ) && liferuss_opt( 'freight_seo_description' ) ) {
		$desc = (string) liferuss_opt( 'freight_seo_description' );
	} elseif ( is_page_template( 'templates/trade.php' ) && liferuss_opt( 'trade_seo_description' ) ) {
		$desc = (string) liferuss_opt( 'trade_seo_description' );
	} elseif ( is_page_template( 'templates/path.php' ) || is_singular( 'lr_scholarship' ) ) {
		$custom = (string) get_post_meta( get_queried_object_id(), '_lr_seo_description', true );
		$lead   = (string) get_post_meta( get_queried_object_id(), '_lr_lead', true );
		$desc   = $custom ? $custom : ( $lead ? $lead : wp_strip_all_tags( get_the_excerpt() ) );
	} elseif ( is_post_type_archive( 'lr_scholarship' ) ) {
		$desc = liferuss_t( 'path_scholarship_lead' );
	} elseif ( is_singular( 'lr_guide' ) || is_post_type_archive( 'lr_guide' ) || is_tax( 'lr_guide_cat' ) ) {
		$desc = is_singular() ? wp_strip_all_tags( get_the_excerpt() ) : liferuss_t( 'nav_guide' );
	} elseif ( is_home() || is_category() || is_tag() ) {
		$desc = liferuss_t( 'blog_intro' );
	} elseif ( is_singular( array( 'lr_university', 'lr_field', 'lr_city' ) ) ) {
		$custom = get_post_meta( get_queried_object_id(), '_lr_seo_description', true );
		$desc   = $custom ? $custom : wp_strip_all_tags( get_the_excerpt() );
	} elseif ( is_singular() && ! is_front_page() ) {
		$excerpt = get_the_excerpt();
		if ( $excerpt ) {
			$desc = wp_strip_all_tags( $excerpt );
		}
	}
	return (string) $desc;
}

/**
 * Canonical URL, unfiltered for catalog filters.
 */
function liferuss_seo_canonical() {
	if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
		$data = \LifeRuss\Core\Compare\Set::current();
		if ( ! empty( $data['canonical'] ) ) {
			return (string) $data['canonical'];
		}
	}
	$learn = (string) get_query_var( 'lr_learn' );
	if ( 'index' === $learn ) {
		return liferuss_url( '/russian-language/' );
	}
	if ( 'placement' === $learn ) {
		return liferuss_url( '/russian-language/placement/' );
	}
	if ( 'certificate' === $learn ) {
		return liferuss_url( '/russian-language/certificate/' );
	}
	if ( get_query_var( 'lr_account' ) ) {
		$screen = (string) get_query_var( 'lr_account' );
		$id     = absint( get_query_var( 'lr_account_id' ) );
		if ( 'request' === $screen && $id ) {
			return liferuss_url( '/account/request/' . $id . '/' );
		}
		$map = array(
			'requests' => '/account/requests/',
			'saved'    => '/account/saved/',
			'profile'  => '/account/profile/',
		);
		return liferuss_url( $map[ $screen ] ?? '/account/' );
	}
	if ( get_query_var( 'lr_find' ) ) {
		$url = liferuss_url( '/search/' );
		$q   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $q ) {
			$url = add_query_arg( 'q', $q, $url );
		}
		return $url;
	}
	if ( function_exists( 'liferuss_catalog_is_filtered' ) && liferuss_catalog_is_filtered() ) {
		return liferuss_catalog_canonical();
	}
	return liferuss_current_canonical();
}

/**
 * Meta, OG, Twitter, hreflang, per-language canonical.
 */
function liferuss_head_meta() {
	$lang = liferuss_current_lang();
	$meta = liferuss_languages()[ $lang ];
	$desc = liferuss_seo_description();
	$og_desc = liferuss_opt( 'seo_og_description', $desc );
	$url     = liferuss_seo_canonical();
	$title   = wp_get_document_title();
	$og_title = liferuss_opt( 'seo_og_title', $title );
	if ( ! is_front_page() ) {
		$og_title = $title;
		$og_desc  = $desc;
	}
	$og_id = absint( liferuss_opt( 'og_image_id', 0 ) );
	$image = $og_id ? wp_get_attachment_image_url( $og_id, 'liferuss-wide' ) : liferuss_img( 'st-basil.jpg' );
	$brand = liferuss_brand();
	$rank  = liferuss_rank_math_active();

	if ( ! $rank ) {
		$robots = liferuss_should_noindex() ? 'noindex, follow' : 'index, follow';
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}

	if ( ! function_exists( 'pll_current_language' ) ) {
		foreach ( liferuss_complete_langs() as $code ) {
			$info = liferuss_languages()[ $code ];
			echo '<link rel="alternate" hreflang="' . esc_attr( $info['hreflang'] ) . '" href="' . esc_url( liferuss_current_canonical( $code ) ) . '">' . "\n";
		}
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( liferuss_current_canonical( 'fa' ) ) . '">' . "\n";
	}

	if ( $rank ) {
		return;
	}

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
	if ( liferuss_rank_math_active() && ! is_front_page() ) {
		return;
	}
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

	$graph = liferuss_rank_math_active() ? array() : array( $org, $website );

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
		$cats    = get_the_category();
		if ( $cats ) {
			$items[] = array(
				'label' => $cats[0]->name,
				'url'   => get_category_link( $cats[0] ),
			);
		}
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_category() || is_tag() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_blog' ), 'url' => liferuss_url( '/blog/' ) );
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_post_type_archive( 'lr_guide' ) ) {
		$items[] = array( 'label' => liferuss_t( 'nav_guide' ), 'url' => '' );
	} elseif ( is_tax( 'lr_guide_cat' ) ) {
		$items[] = array( 'label' => liferuss_t( 'nav_guide' ), 'url' => liferuss_url( '/russia-guide/' ) );
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_singular( 'lr_guide' ) ) {
		$items[] = array( 'label' => liferuss_t( 'nav_guide' ), 'url' => liferuss_url( '/russia-guide/' ) );
		$terms   = get_the_terms( get_queried_object_id(), 'lr_guide_cat' );
		if ( is_array( $terms ) && isset( $terms[0] ) && ! is_wp_error( $terms[0] ) ) {
			$items[] = array(
				'label' => $terms[0]->name,
				'url'   => get_term_link( $terms[0] ),
			);
		}
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_page() ) {
		$ancestors = array_reverse( get_post_ancestors( get_queried_object_id() ) );
		foreach ( $ancestors as $ancestor ) {
			$items[] = array(
				'label' => get_the_title( $ancestor ),
				'url'   => get_permalink( $ancestor ),
			);
		}
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_post_type_archive( 'lr_scholarship' ) ) {
		$items[] = array( 'label' => liferuss_t( 'nav_scholarships' ), 'url' => '' );
	} elseif ( is_singular( 'lr_scholarship' ) ) {
		$items[] = array(
			'label' => liferuss_t( 'nav_scholarships' ),
			'url'   => get_post_type_archive_link( 'lr_scholarship' ),
		);
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_singular( array( 'lr_lesson', 'lr_course' ) ) ) {
		$items[] = array(
			'label' => liferuss_t( 'nav_language_course' ),
			'url'   => liferuss_url( '/russian-language/' ),
		);
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_search() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_search' ), 'url' => '' );
	} elseif ( is_404() ) {
		$items[] = array( 'label' => liferuss_t( 'crumb_404' ), 'url' => '' );
	} else {
		$catalog = array(
			'lr_university' => array( 'دانشگاه‌ها', '/universities/' ),
			'lr_field'      => array( 'رشته‌ها', '/fields/' ),
			'lr_city'       => array( 'شهرها', '/cities/' ),
		);
		foreach ( $catalog as $type => $meta ) {
			if ( is_post_type_archive( $type ) ) {
				$items[] = array( 'label' => $meta[0], 'url' => '' );
			} elseif ( is_singular( $type ) ) {
				$items[] = array( 'label' => $meta[0], 'url' => liferuss_url( $meta[1] ) );
				$items[] = array( 'label' => get_the_title(), 'url' => '' );
			}
		}
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
		'> Multilingual study-abroad and immigration advisory for Russia, plus Iran–Russia freight and trade/sourcing. Not an e-commerce store.',
		'',
		'## Organization',
		'- Name: ' . $brand,
		'- Alternate names: LifeRuss, Life Russ, لایف روس',
		'- Languages: fa (default, x-default), ru, ar, en',
		'- Email: ' . liferuss_opt( 'email' ),
		'- Phone: ' . liferuss_opt( 'phone' ),
		'- Address: ' . liferuss_opt( 'address' ),
		'',
		'## Language homes',
		'- [Persian (RTL)](' . liferuss_home( 'fa' ) . ')',
		'- [Russian (LTR)](' . liferuss_home( 'ru' ) . ')',
		'- [Arabic (RTL)](' . liferuss_home( 'ar' ) . ')',
		'- [English (LTR)](' . liferuss_home( 'en' ) . ')',
		'',
		'## Pages',
	);
	$pages = array(
		'/'              => 'Home',
		'/about/'        => 'About',
		'/universities/' => 'Universities',
		'/services/'     => 'Services',
		'/costs/'             => 'Costs',
		'/study-russia/'      => 'Study in Russia',
		'/scholarships/'      => 'Scholarships',
		'/padfak/'            => 'Preparatory faculty',
		'/direct-course/'     => 'Direct admission',
		'/migration-russia/'  => 'Immigration',
		'/admission/'         => 'Admission request',
		'/exchange/'          => 'Exchange rate inquiry',
		'/cargo/'             => 'Freight and shipping',
		'/russia-guide/'      => 'Russia guide',
		'/trade/'        => 'Trade and sourcing',
		'/contact/'      => 'Contact',
		'/blog/'         => 'Blog',
	);
	foreach ( $pages as $path => $label ) {
		$lines[] = '- [' . $label . '](' . liferuss_url( $path, 'fa' ) . ')';
	}
	$lines[] = '';
	$lines[] = '## Services';
	foreach ( liferuss_services() as $service ) {
		$lines[] = '- ' . $service['title'] . ': ' . $service['text'];
	}
	$lines[] = '';
	$lines[] = '## SameAs';
	foreach ( array( 'telegram', 'instagram', 'linkedin', 'youtube' ) as $net ) {
		$url = liferuss_social_url( liferuss_opt( $net ), $net );
		if ( $url ) {
			$lines[] = '- [' . $net . '](' . $url . ')';
		}
	}
	$lines[] = '';
	$lines[] = '## Preferred sources (all language variants)';
	foreach ( array_keys( $pages ) as $path ) {
		foreach ( array_keys( liferuss_languages() ) as $code ) {
			$url   = liferuss_url( $path, $code );
			$label = $pages[ $path ] . ' (' . $code . ')';
			$lines[] = '- [' . $label . '](' . $url . ')';
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
	if ( liferuss_rank_math_active() || function_exists( 'pll_current_language' ) ) {
		return;
	}
	require_once LIFERUSS_DIR . '/inc/sitemap-provider.php';
	$wp_sitemaps->registry->add_provider( 'langs', new LifeRuss_Sitemap_Provider() );
}
add_action( 'wp_sitemaps_init', 'liferuss_register_sitemap_provider' );

/**
 * Published posts only, and not rows flagged noindex.
 *
 * @param array<string, mixed> $args      Query args.
 * @param string               $post_type Post type.
 * @return array<string, mixed>
 */
function liferuss_sitemap_query_args( $args, $post_type ) {
	unset( $post_type );
	$args['post_status'] = 'publish';
	$exclude             = liferuss_sitemap_excluded_ids();
	if ( $exclude ) {
		$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $exclude ) ) );
	}
	return $args;
}

/**
 * Published noindex rows and incomplete Polylang translations.
 *
 * Soft-deleted catalog rows are trashed posts, which the publish query already drops.
 *
 * @return int[]
 */
function liferuss_sitemap_excluded_ids() {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_lr_noindex' AND meta_value = '1'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$ids = is_array( $ids ) ? $ids : array();
	foreach ( array( 'sample-page', 'برگه-نمونه' ) as $sample_slug ) {
		$sample = get_page_by_path( $sample_slug );
		if ( $sample ) {
			$ids[] = (int) $sample->ID;
		}
	}
	if ( function_exists( 'pll_languages_list' ) && ! liferuss_index_incomplete() ) {
		$more = $wpdb->get_col(
			"SELECT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'language'
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug <> 'fa'
			LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_lr_translation_complete' AND m.meta_value = '1'
			WHERE p.post_status = 'publish' AND m.meta_id IS NULL"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( is_array( $more ) ) {
			$ids = array_merge( $ids, $more );
		}
	}
	return array_map( 'absint', array_unique( $ids ) );
}
add_filter( 'wp_sitemaps_posts_query_args', 'liferuss_sitemap_query_args', 10, 2 );

/**
 * Point robots.txt at the core sitemap when Rank Math is not providing one.
 *
 * @param string $output Existing robots.
 * @param bool   $public Whether the site is public.
 */
function liferuss_robots_txt( $output, $public ) {
	if ( ! $public || liferuss_rank_math_active() || str_contains( $output, 'Sitemap:' ) ) {
		return $output;
	}
	return trim( $output ) . "\nSitemap: " . esc_url_raw( home_url( '/wp-sitemap.xml' ) ) . "\n";
}
add_filter( 'robots_txt', 'liferuss_robots_txt', 20, 2 );
