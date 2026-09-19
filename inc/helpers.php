<?php
/**
 * Icons, URLs, and small front-end helpers.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme image URI.
 *
 * @param string $file Relative path inside assets/images.
 * @return string
 */
function liferuss_img( $file ) {
	return trailingslashit( LIFERUSS_URI ) . 'assets/images/' . ltrim( $file, '/' );
}

/**
 * Bundled WebP variants for a theme JPEG/PNG (relative to assets/images).
 *
 * @param string $file Relative image path.
 * @return array<int, array{file:string,w:int}>
 */
function liferuss_bundled_webp_sources( $file ) {
	$file = ltrim( (string) $file, '/' );
	if ( '' === $file ) {
		return array();
	}

	static $known = array(
		'st-basil.jpg'             => array(
			array( 'file' => 'st-basil-800.webp', 'w' => 800 ),
			array( 'file' => 'st-basil.webp', 'w' => 933 ),
		),
		'hero-student.jpg'         => array(
			array( 'file' => 'hero-student-400.webp', 'w' => 400 ),
			array( 'file' => 'hero-student.webp', 'w' => 640 ),
		),
		'consult-student.jpg'      => array(
			array( 'file' => 'consult-student.webp', 'w' => 720 ),
		),
		'universities/bauman.jpg'  => array(
			array( 'file' => 'universities/bauman.webp', 'w' => 720 ),
		),
		'universities/hse.jpg'     => array(
			array( 'file' => 'universities/hse.webp', 'w' => 524 ),
		),
		'universities/msu.jpg'     => array(
			array( 'file' => 'universities/msu.webp', 'w' => 720 ),
		),
		'universities/rudn.jpg'    => array(
			array( 'file' => 'universities/rudn.webp', 'w' => 720 ),
		),
		'universities/sechenov.jpg' => array(
			array( 'file' => 'universities/sechenov.webp', 'w' => 446 ),
		),
		'universities/spbu.jpg'    => array(
			array( 'file' => 'universities/spbu.webp', 'w' => 524 ),
		),
		'students/student-1.jpg'   => array(
			array( 'file' => 'students/student-1.webp', 'w' => 160 ),
		),
		'students/student-2.jpg'   => array(
			array( 'file' => 'students/student-2.webp', 'w' => 160 ),
		),
		'students/student-3.jpg'   => array(
			array( 'file' => 'students/student-3.webp', 'w' => 160 ),
		),
	);

	if ( isset( $known[ $file ] ) ) {
		$candidates = $known[ $file ];
	} else {
		$sibling = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
		$candidates = ( $sibling && $sibling !== $file ) ? array( array( 'file' => $sibling, 'w' => 0 ) ) : array();
	}

	$dir    = LIFERUSS_DIR . '/assets/images/';
	$sources = array();
	foreach ( $candidates as $candidate ) {
		if ( ! empty( $candidate['file'] ) && is_readable( $dir . $candidate['file'] ) ) {
			$sources[] = $candidate;
		}
	}
	return $sources;
}

/**
 * Build a srcset string from bundled WebP variants.
 *
 * @param array<int, array{file:string,w:int}> $sources Sources.
 * @return string
 */
function liferuss_webp_srcset( $sources ) {
	$parts = array();
	foreach ( $sources as $source ) {
		$url = liferuss_img( $source['file'] );
		if ( ! empty( $source['w'] ) ) {
			$parts[] = $url . ' ' . (int) $source['w'] . 'w';
		} else {
			$parts[] = $url;
		}
	}
	return implode( ', ', $parts );
}

/**
 * Preload an LCP image (attachment or bundled WebP).
 *
 * @param int    $id       Attachment ID.
 * @param string $fallback Bundled fallback path.
 * @param string $sizes    imagesizes attribute.
 */
function liferuss_print_image_preload( $id, $fallback = '', $sizes = '100vw' ) {
	$id = absint( $id );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'liferuss-wide' );
		if ( $url ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $url ) . '" fetchpriority="high">' . "\n";
		}
		return;
	}

	$sources = liferuss_bundled_webp_sources( $fallback );
	if ( $sources ) {
		$href   = liferuss_img( $sources[0]['file'] );
		$srcset = liferuss_webp_srcset( $sources );
		printf(
			'<link rel="preload" as="image" type="image/webp" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
			esc_url( $href ),
			esc_attr( $srcset ),
			esc_attr( $sizes )
		);
		return;
	}

	if ( $fallback ) {
		echo '<link rel="preload" as="image" href="' . esc_url( liferuss_img( $fallback ) ) . '" fetchpriority="high">' . "\n";
	}
}

/**
 * Decorative homepage hero LCP image (real img, not CSS background).
 *
 * @param int    $id       Attachment ID.
 * @param string $fallback Bundled fallback path.
 */
function liferuss_the_hero_lcp( $id, $fallback = 'st-basil.jpg' ) {
	echo '<div class="hero-media" aria-hidden="true">';
	$id = absint( $id );
	if ( $id && function_exists( 'wp_attachment_is_image' ) && wp_attachment_is_image( $id ) ) {
		echo wp_get_attachment_image(
			$id,
			'liferuss-wide',
			false,
			array(
				'class'         => 'hero-lcp',
				'alt'           => '',
				'decoding'      => 'async',
				'fetchpriority' => 'high',
				'loading'       => 'eager',
			)
		);
	} else {
		liferuss_the_image(
			array(
				'fallback' => $fallback,
				'alt'      => '',
				'width'    => 800,
				'height'   => 1201,
				'class'    => 'hero-lcp',
				'lazy'     => false,
				'priority' => true,
				'sizes'    => '100vw',
			)
		);
	}
	echo '</div>';
}

/**
 * WhatsApp chat URL from a phone number.
 *
 * @param string $phone Raw phone.
 * @return string
 */
function liferuss_whatsapp_url( $phone ) {
	$digits = preg_replace( '/\D+/', '', $phone );
	return $digits ? 'https://wa.me/' . $digits : '';
}

/**
 * Normalize Telegram / Instagram to a URL.
 *
 * @param string $value   Handle or URL.
 * @param string $network telegram or instagram.
 * @return string
 */
function liferuss_social_url( $value, $network ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $value ) ) {
		return $value;
	}
	$handle = ltrim( $value, '@' );
	if ( 'telegram' === $network ) {
		return 'https://t.me/' . rawurlencode( $handle );
	}
	if ( 'instagram' === $network ) {
		return 'https://instagram.com/' . rawurlencode( $handle );
	}
	return $value;
}

/**
 * Default primary navigation items.
 *
 * @return array<int, array<string, string>>
 */
function liferuss_default_nav_items() {
	$blog = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : liferuss_url( '/blog/' );
	return array(
		array( 'title' => liferuss_t( 'nav_home' ), 'url' => liferuss_home() ),
		array( 'title' => liferuss_t( 'nav_about' ), 'url' => liferuss_url( '/about/' ) ),
		array( 'title' => liferuss_t( 'nav_universities' ), 'url' => liferuss_url( '/universities/' ) ),
		array( 'title' => liferuss_t( 'nav_services' ), 'url' => liferuss_url( '/services/' ) ),
		array( 'title' => liferuss_t( 'nav_costs' ), 'url' => liferuss_url( '/costs/' ) ),
		array( 'title' => liferuss_t( 'nav_blog' ), 'url' => $blog ),
		array( 'title' => liferuss_t( 'nav_freight' ), 'url' => liferuss_url( '/freight/' ) ),
		array( 'title' => liferuss_t( 'nav_trade' ), 'url' => liferuss_url( '/trade/' ) ),
		array( 'title' => liferuss_t( 'nav_contact' ), 'url' => liferuss_url( '/contact/' ) ),
	);
}

/**
 * Study-level choices for the consultation form.
 *
 * @return array<string, string>
 */
function liferuss_study_levels() {
	return array(
		''          => liferuss_t( 'level_placeholder' ),
		'diploma'   => liferuss_t( 'level_diploma' ),
		'bachelor'  => liferuss_t( 'level_bachelor' ),
		'master'    => liferuss_t( 'level_master' ),
		'phd'       => liferuss_t( 'level_phd' ),
		'medicine'  => liferuss_t( 'level_medicine' ),
		'dentistry' => liferuss_t( 'level_dentistry' ),
		'pharmacy'  => liferuss_t( 'level_pharmacy' ),
		'padfak'    => liferuss_t( 'level_padfak' ),
	);
}

/**
 * Star rating markup.
 *
 * @param int $rating 1-5.
 * @return string
 */
function liferuss_stars( $rating ) {
	$rating = max( 1, min( 5, (int) $rating ) );
	$html   = '<p class="stars" aria-label="' . esc_attr( (string) $rating ) . ' / 5">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$html .= '<span' . ( $i <= $rating ? ' class="is-on"' : '' ) . '>' . liferuss_icon( 'star' ) . '</span>';
	}
	return $html . '</p>';
}

/**
 * Render an inline SVG icon by name.
 *
 * @param string $name Icon key.
 * @return string
 */
function liferuss_icon( $name ) {
	$icons = array(
		'cap'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 6l9 4.5L12 15 3 10.5z"/><path d="M7 12.2v4.3c0 .4 2.2 2 5 2s5-1.6 5-2v-4.3"/></svg>',
		'book'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21.5V5.5z"/><path d="M5 21.5A2.5 2.5 0 0 1 7.5 19H20"/></svg>',
		'passport'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="10" r="2.4"/><path d="M8.5 16.5h7"/></svg>',
		'home'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5 12 5l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-8.5z"/></svg>',
		'docs'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M14 4v5h5"/><path d="M9 13h6M9 16h6"/></svg>',
		'plane'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13.2 10.4 12 21 4.8c.5-.3 1 .4.6.9L14.2 14l-1.1 6.2-3.2-4.4L5.2 17 3 13.2z"/></svg>',
		'users'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.8 18.5c.7-2.6 2.7-4 5.2-4s4.5 1.4 5.2 4"/><circle cx="17" cy="9" r="2.3"/><path d="M16.2 14.6c2 .3 3.4 1.5 4 3.4"/></svg>',
		'shield'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5 19 6.2v6.1c0 4.2-2.8 7-7 8.7-4.2-1.7-7-4.5-7-8.7V6.2L12 3.5z"/><path d="m8.8 12 2.2 2.2 4.4-4.4"/></svg>',
		'chat'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H9l-4 3v-3H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z"/></svg>',
		'bolt'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3 5.8 13.6h5.1L10 21l8.4-12.2h-5.3L13 3z"/></svg>',
		'medicine'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6v4h4v6h-4v8H9v-8H5V7h4V3z"/></svg>',
		'dental'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5c1.2-.8 2.5-1.2 4-1.2s2.8.4 4 1.2c1.5 1 2.6 2.6 2.5 4.6-.2 3-1.6 5.2-2.5 8.2-.4 1.3-1.2 3.2-2.6 3.2-1 0-1.3-1.4-1.4-2.3-.2-1.3-.3-2.6-1-2.6s-.8 1.3-1 2.6c-.1.9-.4 2.3-1.4 2.3-1.4 0-2.2-1.9-2.6-3.2C5.1 14.3 3.7 12.1 3.5 9.1 3.4 7.1 4.5 5.5 8 4.5z"/></svg>',
		'pharma'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="3" width="8" height="5" rx="1"/><path d="M9 8h6l2 13H7L9 8z"/><path d="M9 14h6"/></svg>',
		'engineer'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 14h8l1 7H7l1-7z"/><path d="M12 3a5 5 0 0 1 5 5v2H7V8a5 5 0 0 1 5-5z"/><path d="M9 8V6m6 2V6"/></svg>',
		'art'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.8 14.5A8.5 8.5 0 1 1 19 8.2c0 1.4-1.1 2.3-2.4 2.3H14a2 2 0 0 0-2 2.2c.1 1.4-1 2.6-2.4 2.6H8.8"/><circle cx="8" cy="9" r="1"/><circle cx="12" cy="6.5" r="1"/><circle cx="16" cy="9" r="1"/></svg>',
		'language'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M3.8 12h16.4M12 3.5c2.4 2.4 3.7 5.3 3.7 8.5S14.4 18.1 12 20.5C9.6 18.1 8.3 15.2 8.3 12S9.6 5.9 12 3.5z"/></svg>',
		'wallet'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.2"/></svg>',
		'bed'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18V9h4a4 4 0 0 1 4 4h10v5"/><path d="M3 14h18"/><path d="M5 18v2M19 18v2"/></svg>',
		'tuition'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="12" rx="2"/><path d="M8 6V5a4 4 0 0 1 8 0v1"/><path d="M12 11v2"/></svg>',
		'stamp'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 14h8l1.5 6h-11L8 14z"/><circle cx="12" cy="8" r="3.5"/></svg>',
		'speech'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10a2 2 0 0 1 2 2v5H8l-4 3V7z"/><path d="M16 10h4a1 1 0 0 1 1 1v6l-3-2h-2"/></svg>',
		'star'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3.6 2.3 4.7 5.2.8-3.8 3.6.9 5.2L12 15.8 7.4 17.9l.9-5.2-3.8-3.6 5.2-.8L12 3.6z"/></svg>',
		'phone'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 3.8h3.1l1.2 3-2 1.4a12.2 12.2 0 0 0 6.3 6.3l1.4-2 3 1.2v3.1c0 .7-.6 1.4-1.3 1.4C9.8 18.2 5.8 14.2 5.8 5.1c0-.7.7-1.3 1.4-1.3z"/></svg>',
		'mail'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
		'pin'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.2"/></svg>',
		'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.2A7.8 7.8 0 0 0 5.4 16.3L4.2 20l3.8-1.2A7.8 7.8 0 1 0 12 4.2z"/><path d="M9.3 9.2c.2-.4.4-.4.6-.4h.5c.2 0 .4 0 .5.3.2.6.7 1.5.7 1.6s0 .3-.2.5l-.3.4c-.2.2-.3.3-.1.6.2.3.9 1.5 2 2.1 1 .6 1.2.4 1.5.4s.8-.3 1-.6.4-.5.6-.3l.7.4c.2.1.4.2.3.5-.1.6-.7 1.8-2.4 2.1-1.5.3-2.7-.2-3.6-.7-1.4-.8-2.4-2-2.8-2.6-.4-.6-.8-1.5-.8-2.3 0-.7.4-1.1.6-1.4z"/></svg>',
		'telegram'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 5.4 3.9 11.6c-1.1.4-1.1 1 .2 1.3l4.2 1.3 1.6 5c.2.6.1.8.8.8.4 0 .6-.2.8-.4l2.3-2.2 4.4 3.2c.8.5 1.4.2 1.6-.7l2.8-13.2c.3-1.1-.4-1.6-1.4-1.3z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.4"/><circle cx="16.6" cy="7.4" r=".8"/></svg>',
		'linkedin'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 10.5V17M8 7.5v.2M12 17v-4a2 2 0 0 1 4 0v4"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="m11 9 5 3-5 3V9z"/></svg>',
		'arrow'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H7m0 0 5-5M7 12l5 5"/></svg>',
		'menu'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
		'close'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6 18 18M18 6 6 18"/></svg>',
		'cargo'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="10" width="18" height="8" rx="1.5"/><path d="M6 10V8h12v2M7 18v2M17 18v2M8 6h8l1 4H7l1-4z"/></svg>',
		'box'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5v9L12 22 4 17.5v-9z"/><path d="M4 8.5 12 13l8-4.5M12 13v9"/></svg>',
		'suitcase'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7M3 12h18"/></svg>',
		'handshake' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 11.5 4.8 8.3a2 2 0 0 1 0-2.8L7 3.3l4.2 4.2M16 11.5l3.2-3.2a2 2 0 0 0 0-2.8L17 3.3l-3 3"/><path d="m8 12 2.2 2.2a2 2 0 0 0 2.8 0l.8-.8 2.2 2.2a2 2 0 0 1 0 2.8L14 20.2l-3.4-3.4L8 14.2 5.5 16.7"/><path d="m14 12 2 2"/></svg>',
		'cart'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h2.2l1.5 10h10.6L20 8H8"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg>',
		'globe'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M3.8 12h16.4M12 3.5c2.4 2.4 3.7 5.3 3.7 8.5S14.4 18.1 12 20.5C9.6 18.1 8.3 15.2 8.3 12S9.6 5.9 12 3.5z"/></svg>',
		'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.2 4.2"/></svg>',
		'info'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v6M12 8v.2"/></svg>',
	);

	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

/**
 * Fallback menu markup when no WP menu is assigned.
 */
function liferuss_fallback_menu() {
	echo '<ul class="nav-list">';
	foreach ( liferuss_default_nav_items() as $item ) {
		printf(
			'<li class="menu-item"><a href="%s">%s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['title'] )
		);
	}
	echo '</ul>';
}

/**
 * Resolve a CTA href (hash stays on current page).
 *
 * @param string $link Raw link.
 * @return string
 */
function liferuss_cta_url( $link ) {
	$link = trim( (string) $link );
	if ( '' === $link ) {
		return liferuss_home() . '#consultation';
	}
	if ( isset( $link[0] ) && '#' === $link[0] ) {
		return $link;
	}
	if ( preg_match( '#^https?://#i', $link ) ) {
		return liferuss_localize_url( $link );
	}
	if ( isset( $link[0] ) && '/' === $link[0] ) {
		return liferuss_url( $link );
	}
	return $link;
}
