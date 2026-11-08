<?php
/**
 * Theme options getter, image helpers, and color CSS.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merged options (saved + defaults).
 *
 * @return array
 */
function liferuss_options() {
	$saved    = get_option( 'liferuss_options', array() );
	$defaults = liferuss_default_options();
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return liferuss_array_merge_recursive( $defaults, $saved );
}

/**
 * Recursive replace: saved values win, missing keys keep defaults.
 *
 * @param array $defaults Defaults.
 * @param array $saved    Saved.
 * @return array
 */
function liferuss_array_merge_recursive( $defaults, $saved ) {
	foreach ( $defaults as $key => $value ) {
		if ( ! array_key_exists( $key, $saved ) ) {
			continue;
		}
		if ( is_array( $value ) && liferuss_is_list( $value ) && is_array( $saved[ $key ] ) ) {
			foreach ( $value as $i => $item ) {
				if ( isset( $saved[ $key ][ $i ] ) && is_array( $item ) && is_array( $saved[ $key ][ $i ] ) ) {
					$defaults[ $key ][ $i ] = array_merge( $item, $saved[ $key ][ $i ] );
				} elseif ( isset( $saved[ $key ][ $i ] ) ) {
					$defaults[ $key ][ $i ] = $saved[ $key ][ $i ];
				}
			}
			if ( count( $saved[ $key ] ) > count( $value ) ) {
				foreach ( array_slice( $saved[ $key ], count( $value ) ) as $extra ) {
					$defaults[ $key ][] = $extra;
				}
			}
		} elseif ( is_array( $value ) && is_array( $saved[ $key ] ) && ! liferuss_is_list( $value ) ) {
			$defaults[ $key ] = liferuss_array_merge_recursive( $value, $saved[ $key ] );
		} else {
			$defaults[ $key ] = $saved[ $key ];
		}
	}
	foreach ( $saved as $key => $value ) {
		if ( ! array_key_exists( $key, $defaults ) ) {
			$defaults[ $key ] = $value;
		}
	}
	return $defaults;
}

/**
 * Whether an array is a list (0..n).
 *
 * @param array $array Array.
 * @return bool
 */
function liferuss_is_list( $array ) {
	if ( function_exists( 'array_is_list' ) ) {
		return array_is_list( $array );
	}
	$i = 0;
	foreach ( $array as $k => $_ ) {
		if ( $k !== $i ) {
			return false;
		}
		++$i;
	}
	return true;
}

/**
 * Read one option key, overlaid with the active language when not Persian.
 *
 * Theme Options admin always reads the Persian tree. Front-end (and AJAX
 * with consult_lang) uses packaged + saved i18n overlays.
 *
 * @param string $key     Key.
 * @param mixed  $default Default.
 * @return mixed
 */
function liferuss_opt( $key, $default = '' ) {
	$opts = liferuss_options();
	$fa   = ( array_key_exists( $key, $opts ) && '' !== $opts[ $key ] && null !== $opts[ $key ] )
		? $opts[ $key ]
		: $default;

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $fa;
	}

	$lang = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	if ( 'fa' === $lang || ! function_exists( 'liferuss_lang_overlay' ) ) {
		return $fa;
	}

	$overlay = liferuss_lang_overlay( $lang );
	if ( ! array_key_exists( $key, $overlay ) ) {
		return $fa;
	}

	$translated = $overlay[ $key ];
	if ( is_array( $fa ) && is_array( $translated ) && function_exists( 'liferuss_is_list' ) && liferuss_is_list( $fa ) ) {
		return liferuss_merge_translated_list( $fa, $translated );
	}
	if ( '' !== $translated && null !== $translated ) {
		return $translated;
	}
	return $fa;
}

/**
 * Back-compat Customizer reader.
 *
 * @param string $key     Old theme_mod key.
 * @param mixed  $default Default.
 * @return mixed
 */
function liferuss_mod( $key, $default = '' ) {
	$map = array(
		'liferuss_phone'      => 'phone',
		'liferuss_phone_alt'  => 'phone_alt',
		'liferuss_email'      => 'email',
		'liferuss_whatsapp'   => 'whatsapp',
		'liferuss_telegram'   => 'telegram',
		'liferuss_instagram'  => 'instagram',
		'liferuss_address'    => 'address',
		'liferuss_hero_title' => 'hero_headline',
		'liferuss_hero_text'  => 'hero_subheadline',
	);
	if ( isset( $map[ $key ] ) ) {
		return liferuss_opt( $map[ $key ], $default );
	}
	$mod = get_theme_mod( $key, null );
	if ( null !== $mod && '' !== $mod ) {
		return $mod;
	}
	return liferuss_opt( $key, $default );
}

/**
 * Brand display name.
 *
 * @return string
 */
function liferuss_brand() {
	return liferuss_opt( 'brand_name', 'لایف روس' );
}

/**
 * Attachment URL or bundled fallback.
 *
 * @param int    $id       Attachment ID.
 * @param string $fallback Relative theme image.
 * @param string $size     Size.
 * @return string
 */
function liferuss_media_url( $id, $fallback = '', $size = 'liferuss-card' ) {
	$id = absint( $id );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, $size );
		if ( $url ) {
			return $url;
		}
	}
	return $fallback ? liferuss_img( $fallback ) : '';
}

/**
 * Print an image from attachment or bundled file.
 *
 * @param array $args Args.
 */
function liferuss_the_image( $args ) {
	$defaults = array(
		'id'       => 0,
		'fallback' => '',
		'alt'      => '',
		'width'    => 640,
		'height'   => 400,
		'size'     => 'liferuss-card',
		'lazy'     => true,
		'priority' => false,
		'class'    => '',
		'sizes'    => '',
	);
	$args     = array_merge( $defaults, $args );
	$id       = absint( $args['id'] );
	$attr     = array(
		'alt'      => $args['alt'],
		'class'    => $args['class'],
		'decoding' => 'async',
	);
	if ( $args['sizes'] ) {
		$attr['sizes'] = $args['sizes'];
	}
	if ( $args['priority'] ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	} elseif ( $args['lazy'] ) {
		$attr['loading'] = 'lazy';
	} else {
		$attr['loading'] = 'eager';
	}

	if ( $id && wp_attachment_is_image( $id ) ) {
		echo wp_get_attachment_image( $id, $args['size'], false, $attr );
		return;
	}

	$src = $args['fallback'] ? liferuss_img( $args['fallback'] ) : '';
	if ( ! $src ) {
		return;
	}

	$priority_attr = ! empty( $attr['fetchpriority'] ) ? ' fetchpriority="high"' : '';
	$sizes_attr    = $args['sizes'] ? ' sizes="' . esc_attr( $args['sizes'] ) . '"' : '';
	$webp          = liferuss_bundled_webp_sources( $args['fallback'] );

	if ( $webp ) {
		$srcset = liferuss_webp_srcset( $webp );
		printf(
			'<picture><source type="image/webp" srcset="%s"%s><img src="%s" alt="%s" width="%d" height="%d" class="%s" loading="%s" decoding="async"%s%s></picture>',
			esc_attr( $srcset ),
			$sizes_attr,
			esc_url( $src ),
			esc_attr( $args['alt'] ),
			(int) $args['width'],
			(int) $args['height'],
			esc_attr( $args['class'] ),
			esc_attr( $attr['loading'] ),
			$priority_attr,
			$sizes_attr
		);
		return;
	}

	printf(
		'<img src="%s" alt="%s" width="%d" height="%d" class="%s" loading="%s" decoding="async"%s%s>',
		esc_url( $src ),
		esc_attr( $args['alt'] ),
		(int) $args['width'],
		(int) $args['height'],
		esc_attr( $args['class'] ),
		esc_attr( $attr['loading'] ),
		$priority_attr,
		$sizes_attr
	);
}

/**
 * Enabled services only.
 *
 * @return array
 */
function liferuss_services() {
	$items = liferuss_opt( 'services', array() );
	$out   = array();
	foreach ( (array) $items as $item ) {
		if ( isset( $item['enabled'] ) && '0' === (string) $item['enabled'] ) {
			continue;
		}
		$out[] = $item;
	}
	return $out;
}

/**
 * Universities from options.
 *
 * @return array
 */
function liferuss_universities() {
	return array_values( (array) liferuss_opt( 'universities', array() ) );
}

/**
 * Majors from options.
 *
 * @return array
 */
function liferuss_majors() {
	return array_values( (array) liferuss_opt( 'majors', array() ) );
}

/**
 * Costs from options.
 *
 * @return array
 */
function liferuss_costs() {
	return array_values( (array) liferuss_opt( 'costs', array() ) );
}

/**
 * Roadmap from options.
 *
 * @return array
 */
function liferuss_roadmap() {
	return array_values( (array) liferuss_opt( 'roadmap', array() ) );
}

/**
 * Testimonials from options.
 *
 * @return array
 */
function liferuss_testimonials() {
	return array_values( (array) liferuss_opt( 'testimonials', array() ) );
}

/**
 * Output CSS custom properties from brand colors.
 */
function liferuss_color_css() {
	$navy = sanitize_hex_color( liferuss_opt( 'color_primary', '#0b2341' ) );
	$gold = sanitize_hex_color( liferuss_opt( 'color_secondary', '#e8b923' ) );
	if ( ! $navy ) {
		$navy = '#0b2341';
	}
	if ( ! $gold ) {
		$gold = '#e8b923';
	}
	echo '<style id="liferuss-colors">:root{--navy:' . esc_html( $navy ) . ';--gold:' . esc_html( $gold ) . ';}</style>' . "\n";
}
add_action( 'wp_head', 'liferuss_color_css', 2 );

/**
 * Site icon from Theme Options favicon.
 *
 * @param int $id Current.
 * @return int
 */
function liferuss_site_icon( $id ) {
	$fav = absint( liferuss_opt( 'favicon_id', 0 ) );
	return $fav ? $fav : $id;
}
add_filter( 'get_site_icon_url', function ( $url ) {
	$fav = absint( liferuss_opt( 'favicon_id', 0 ) );
	if ( $fav ) {
		$custom = wp_get_attachment_image_url( $fav, 'full' );
		if ( $custom ) {
			return $custom;
		}
	}
	return $url;
} );
