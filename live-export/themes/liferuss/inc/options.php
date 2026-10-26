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

	if ( function_exists( 'liferuss_blank_default_contact' ) ) {
		$fa = liferuss_blank_default_contact( $fa, $key );
	}
	if ( function_exists( 'liferuss_contact_from_core' ) && '' === trim( (string) $fa ) ) {
		$core = liferuss_contact_from_core( $key );
		if ( '' !== $core ) {
			$fa = $core;
		}
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

	$slug = liferuss_photo_slug_from_file( (string) $args['fallback'] );
	if ( $slug ) {
		liferuss_photo_markup( $slug, $args );
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
 * Bundled brand file in assets/img.
 *
 * @param string $file File name.
 * @return string
 */
function liferuss_theme_img( $file ) {
	return trailingslashit( LIFERUSS_URI ) . 'assets/img/' . ltrim( (string) $file, '/' );
}

/**
 * Header wordmark. Layout box is 92×40 so the 64px bar does not grow.
 * h80 is 1x and h160 is 2x. A white plate keeps the navy artwork visible on the dark header.
 */
function liferuss_the_wordmark() {
	$alt = trim( liferuss_brand() . ' — LifeRuss' );
	$one = liferuss_theme_img( 'logo-header-h80.png' );
	$two = liferuss_theme_img( 'logo-header-h160.png' );
	echo '<span class="brand-plate">';
	printf(
		'<img class="brand-logo" src="%1$s" srcset="%1$s 1x, %2$s 2x" width="92" height="40" alt="%3$s" decoding="async">',
		esc_url( $one ),
		esc_url( $two ),
		esc_attr( $alt )
	);
	echo '</span>';
}

/**
 * Eagle mark for the footer. Decorative: the brand name sits beside it.
 */
function liferuss_the_emblem() {
	$one = liferuss_theme_img( 'emblem-h80.png' );
	$two = liferuss_theme_img( 'emblem-h160.png' );
	echo '<span class="brand-plate brand-plate--emblem">';
	printf(
		'<img class="brand-emblem" src="%1$s" srcset="%1$s 1x, %2$s 2x" width="32" height="37" alt="" decoding="async">',
		esc_url( $one ),
		esc_url( $two )
	);
	echo '</span>';
}

/**
 * Homepage logo loop. The clip has a white background, so it only sits on the white band under the hero.
 * The navy header and footer keep the static PNG. Reduced motion and Save-Data never request the mp4.
 */
function liferuss_the_logo_motion() {
	$alt    = trim( liferuss_brand() . ' — LifeRuss' );
	$still  = liferuss_theme_img( 'logo-header-h80.png' );
	$still2 = liferuss_theme_img( 'logo-header-h160.png' );
	$poster = liferuss_theme_img( 'logo-anim-poster.png' );
	$video  = liferuss_theme_img( 'logo-anim-white.mp4' );
	?>
	<div class="lr-logo-motion">
		<div class="lr-logo-motion__frame">
			<video class="lr-logo-motion__video" width="320" height="148" muted autoplay loop playsinline preload="metadata" poster="<?php echo esc_url( $poster ); ?>" data-src="<?php echo esc_url( $video ); ?>"></video>
			<img class="lr-logo-motion__still" src="<?php echo esc_url( $still ); ?>" srcset="<?php echo esc_url( $still ); ?> 1x, <?php echo esc_url( $still2 ); ?> 2x" width="184" height="80" alt="<?php echo esc_attr( $alt ); ?>" decoding="async">
		</div>
	</div>
	<script>
	(function () {
		var band = document.currentScript && document.currentScript.previousElementSibling;
		var video = band ? band.querySelector('.lr-logo-motion__video') : null;
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
		var type = conn && conn.effectiveType ? conn.effectiveType : '';
		var slow = conn && (conn.saveData || type === 'slow-2g' || type === '2g');
		if (!video || reduce || slow) {
			document.documentElement.classList.add('lr-logo-still');
			if (video) {
				video.removeAttribute('autoplay');
				video.preload = 'none';
				video.removeAttribute('data-src');
			}
			return;
		}
		var src = video.getAttribute('data-src');
		if (!src) {
			return;
		}
		var source = document.createElement('source');
		source.type = 'video/mp4';
		source.src = src;
		video.appendChild(source);
		video.load();
	}());
	</script>
	<?php
}

/**
 * Favicon, apple touch icon, and web manifest from the theme. Replaces the old round mark.
 */
function liferuss_site_icons() {
	if ( is_admin() ) {
		return;
	}
	$icons = array(
		array(
			'rel'   => 'icon',
			'href'  => 'favicon.ico',
			'sizes' => 'any',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-32.png',
			'sizes' => '32x32',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-16.png',
			'sizes' => '16x16',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-48.png',
			'sizes' => '48x48',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-192.png',
			'sizes' => '192x192',
		),
		array(
			'rel'   => 'icon',
			'type'  => 'image/png',
			'href'  => 'favicon-512.png',
			'sizes' => '512x512',
		),
		array(
			'rel'   => 'apple-touch-icon',
			'href'  => 'apple-touch-icon-180.png',
			'sizes' => '180x180',
		),
	);
	foreach ( $icons as $icon ) {
		$attrs = ' rel="' . esc_attr( $icon['rel'] ) . '" href="' . esc_url( liferuss_theme_img( $icon['href'] ) ) . '"';
		if ( ! empty( $icon['type'] ) ) {
			$attrs .= ' type="' . esc_attr( $icon['type'] ) . '"';
		}
		if ( ! empty( $icon['sizes'] ) ) {
			$attrs .= ' sizes="' . esc_attr( $icon['sizes'] ) . '"';
		}
		echo '<link' . $attrs . '>' . "\n";
	}
	echo '<link rel="manifest" href="' . esc_url( liferuss_theme_img( 'site.webmanifest' ) ) . '">' . "\n";
	echo '<meta name="theme-color" content="#0b2341">' . "\n";
}
add_action( 'wp_head', 'liferuss_site_icons', 1 );
remove_action( 'wp_head', 'wp_site_icon', 99 );

/**
 * Header mark at display size. Falls back to the full attachment when a crop is unavailable.
 *
 * @param int    $attachment_id Attachment.
 * @param string $alt           Accessible name.
 */
function liferuss_header_logo( $attachment_id, $alt ) {
	$attachment_id = absint( $attachment_id );
	$url           = function_exists( 'liferuss_resized_attachment_url' ) ? liferuss_resized_attachment_url( $attachment_id, 96, false ) : '';
	if ( ! $url ) {
		echo wp_get_attachment_image(
			$attachment_id,
			'thumbnail',
			false,
			array(
				'class' => 'custom-logo',
				'alt'   => $alt,
			)
		);
		return;
	}
	echo '<img class="custom-logo" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" width="84" height="84" decoding="async">';
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
 * Site icon from Theme Options favicon, else the WordPress site icon.
 *
 * @param string $url  Current URL.
 * @param int    $size Requested edge in pixels.
 * @return string
 */
function liferuss_filter_site_icon_url( $url, $size = 32 ) {
	unset( $url );
	$size = (int) $size;
	$file = 'favicon-32.png';
	if ( $size <= 16 ) {
		$file = 'favicon-16.png';
	} elseif ( $size <= 32 ) {
		$file = 'favicon-32.png';
	} elseif ( $size <= 48 ) {
		$file = 'favicon-48.png';
	} elseif ( $size <= 180 ) {
		$file = 'apple-touch-icon-180.png';
	} elseif ( $size <= 192 ) {
		$file = 'favicon-192.png';
	} else {
		$file = 'favicon-512.png';
	}
	return liferuss_theme_img( $file );
}
add_filter( 'get_site_icon_url', 'liferuss_filter_site_icon_url', 10, 2 );

/**
 * A small file for an attachment. Generated once and stored beside the original.
 *
 * Square crop is for site icons. The header logo keeps its aspect ratio.
 *
 * @param int  $attachment_id Attachment.
 * @param int  $size          Longest edge.
 * @param bool $crop          Crop to a square.
 * @return string
 */
function liferuss_resized_attachment_url( $attachment_id, $size, $crop = false ) {
	$attachment_id = absint( $attachment_id );
	$size          = (int) $size;
	if ( $attachment_id < 1 || $size < 1 ) {
		return '';
	}
	$size  = min( 512, max( 16, $size ) );
	$crop  = (bool) $crop;
	$key   = '_lr_img_' . $size . ( $crop ? 'c' : 'f' );
	$uploads = wp_get_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return '';
	}
	$stored = get_post_meta( $attachment_id, $key, true );
	if ( is_string( $stored ) && '' !== $stored ) {
		$abs = trailingslashit( $uploads['basedir'] ) . ltrim( $stored, '/' );
		if ( is_readable( $abs ) && filesize( $abs ) > 0 && filesize( $abs ) <= 200 * 1024 ) {
			return trailingslashit( $uploads['baseurl'] ) . ltrim( $stored, '/' );
		}
	}
	$file = get_attached_file( $attachment_id );
	if ( ! is_string( $file ) || ! is_readable( $file ) ) {
		return '';
	}
	if ( filesize( $file ) > 0 && filesize( $file ) <= 24 * 1024 ) {
		$original = wp_get_attachment_url( $attachment_id );
		return $original ? $original : '';
	}
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return '';
	}
	$resized = $editor->resize( $size, $size, $crop );
	if ( is_wp_error( $resized ) ) {
		return '';
	}
	$saved = $editor->save( trailingslashit( dirname( $file ) ) . 'lr-' . $attachment_id . '-' . $size . ( $crop ? 'c' : 'f' ) . '.png', 'image/png' );
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_readable( $saved['path'] ) ) {
		return '';
	}
	$basedir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
	$path    = wp_normalize_path( $saved['path'] );
	if ( 0 !== strpos( $path, $basedir ) ) {
		return '';
	}
	$rel = ltrim( substr( $path, strlen( $basedir ) ), '/' );
	update_post_meta( $attachment_id, $key, $rel );
	return trailingslashit( $uploads['baseurl'] ) . $rel;
}
