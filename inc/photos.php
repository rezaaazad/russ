<?php
/**
 * Owner photo catalog, responsive markup, and the demo-image swap.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Responsive derivatives of the six owner photographs.
 *
 * @return array<string, array<string, mixed>>
 */
function liferuss_photo_catalog() {
	$hero = array( 640, 1024, 1600, 1920 );
	return array(
		'saint-basil'         => array(
			'w'      => 1920,
			'h'      => 1440,
			'widths' => $hero,
			'pos'    => 'lr-pos-basil',
			'alt'    => 'کلیسای سنت باسیل و کرملین',
		),
		'moscow-night'        => array(
			'w'      => 1920,
			'h'      => 2560,
			'widths' => $hero,
			'pos'    => 'lr-pos-night',
			'alt'    => 'رودخانه مسکو هنگام غروب',
		),
		'saint-petersburg'    => array(
			'w'      => 1920,
			'h'      => 1440,
			'widths' => $hero,
			'pos'    => 'lr-pos-peter',
			'alt'    => 'گنبد سنت ایزاک بر فراز نوا',
		),
		'russian-couple'      => array(
			'w'      => 1920,
			'h'      => 2560,
			'widths' => $hero,
			'pos'    => 'lr-pos-couple',
			'alt'    => 'زوج با لباس سنتی روسی در میدان سرخ',
		),
		'borscht'             => array(
			'w'      => 1920,
			'h'      => 1440,
			'widths' => $hero,
			'pos'    => 'lr-pos-borscht',
			'alt'    => 'برش و نان پامپوشکا',
		),
		'matryoshka-samovar'  => array(
			'w'      => 1920,
			'h'      => 1440,
			'widths' => $hero,
			'pos'    => 'lr-pos-dolls',
			'alt'    => 'ماتریوشکا و سماور',
		),
		'saint-basil-og'      => array(
			'w'      => 1200,
			'h'      => 630,
			'widths' => array( 1200 ),
			'pos'    => 'lr-pos-basil',
			'alt'    => 'کلیسای سنت باسیل و کرملین',
		),
		'saint-basil-card'    => array(
			'w'      => 1600,
			'h'      => 900,
			'widths' => array( 640, 1024, 1600 ),
			'pos'    => 'lr-pos-basil',
			'alt'    => '',
		),
	);
}

/**
 * CSS scene token for an overlay.
 *
 * @param string $slug Catalog slug.
 * @return string
 */
function liferuss_photo_scene_key( $slug ) {
	$map = array(
		'saint-basil'        => 'basil',
		'moscow-night'       => 'night',
		'saint-petersburg'   => 'peter',
		'russian-couple'     => 'couple',
		'borscht'            => 'borscht',
		'matryoshka-samovar' => 'dolls',
		'saint-basil-og'     => 'basil',
		'saint-basil-card'   => 'basil',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : 'basil';
}

/**
 * Catalog slug for a theme-relative path or derivative filename.
 *
 * @param string $file Path or filename.
 * @return string
 */
function liferuss_photo_slug_from_file( $file ) {
	$file = (string) $file;
	$path = wp_parse_url( $file, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		$path = $file;
	}
	$base = pathinfo( $path, PATHINFO_FILENAME );
	if ( isset( liferuss_photo_catalog()[ $base ] ) ) {
		return $base;
	}
	if ( preg_match( '/^(saint-basil-card|saint-basil-og|saint-basil|moscow-night|saint-petersburg|russian-couple|borscht|matryoshka-samovar)(?:-\d+)?$/', $base, $match ) ) {
		return $match[1];
	}
	return '';
}

/**
 * Logical theme path understood by the image helper.
 *
 * @param string $slug Catalog slug.
 * @return string
 */
function liferuss_photo_file( $slug ) {
	return $slug . '.jpg';
}

/**
 * WebP or JPEG variants that exist on disk.
 *
 * @param string $slug Catalog slug.
 * @param string $ext  webp or jpg.
 * @return array<int, array{file:string,w:int}>
 */
function liferuss_photo_variants( $slug, $ext ) {
	$catalog = liferuss_photo_catalog();
	if ( ! isset( $catalog[ $slug ] ) ) {
		return array();
	}
	$dir      = LIFERUSS_DIR . '/assets/images/';
	$variants = array();
	foreach ( $catalog[ $slug ]['widths'] as $width ) {
		$rel = $slug . '-' . (int) $width . '.' . $ext;
		if ( is_readable( $dir . $rel ) ) {
			$variants[] = array(
				'file' => $rel,
				'w'    => (int) $width,
			);
		}
	}
	return $variants;
}

/**
 * Build a width-descriptor srcset.
 *
 * @param array<int, array{file:string,w:int}> $variants Variants.
 * @return string
 */
function liferuss_photo_srcset( $variants ) {
	$parts = array();
	foreach ( $variants as $variant ) {
		$parts[] = liferuss_img( $variant['file'] ) . ' ' . (int) $variant['w'] . 'w';
	}
	return implode( ', ', $parts );
}

/**
 * Print or return a responsive picture.
 *
 * @param string               $slug Catalog slug.
 * @param array<string, mixed> $args  Markup args.
 * @return string
 */
function liferuss_photo_markup( $slug, $args = array() ) {
	$catalog = liferuss_photo_catalog();
	if ( ! isset( $catalog[ $slug ] ) ) {
		return '';
	}
	$photo   = $catalog[ $slug ];
	$webp    = liferuss_photo_variants( $slug, 'webp' );
	$jpg     = liferuss_photo_variants( $slug, 'jpg' );
	if ( ! $jpg ) {
		return '';
	}
	$defaults = array(
		'alt'      => (string) $photo['alt'],
		'class'    => '',
		'sizes'    => '100vw',
		'priority' => false,
		'lazy'     => true,
		'return'   => false,
	);
	$args     = array_merge( $defaults, $args );
	$src      = liferuss_img( $jpg[0]['file'] );
	foreach ( $jpg as $variant ) {
		if ( 1024 === (int) $variant['w'] || 1200 === (int) $variant['w'] ) {
			$src = liferuss_img( $variant['file'] );
			break;
		}
	}
	$class   = trim( (string) $args['class'] . ' ' . $photo['pos'] );
	$loading = ! empty( $args['priority'] ) ? 'eager' : ( ! empty( $args['lazy'] ) ? 'lazy' : 'eager' );
	$fetch   = ! empty( $args['priority'] ) ? ' fetchpriority="high"' : '';
	$sizes   = (string) $args['sizes'];
	$img     = sprintf(
		'<img src="%s" srcset="%s" sizes="%s" alt="%s" width="%d" height="%d" class="%s" loading="%s" decoding="async"%s>',
		esc_url( $src ),
		esc_attr( liferuss_photo_srcset( $jpg ) ),
		esc_attr( $sizes ),
		esc_attr( (string) $args['alt'] ),
		(int) $photo['w'],
		(int) $photo['h'],
		esc_attr( $class ),
		esc_attr( $loading ),
		$fetch
	);
	if ( $webp ) {
		$html = sprintf(
			'<picture><source type="image/webp" srcset="%s" sizes="%s">%s</picture>',
			esc_attr( liferuss_photo_srcset( $webp ) ),
			esc_attr( $sizes ),
			$img
		);
	} else {
		$html = $img;
	}
	if ( empty( $args['return'] ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return '';
	}
	return $html;
}

/**
 * Full-bleed hero media and the navy scrim.
 *
 * @param string $slug     Catalog slug.
 * @param bool   $priority Preload this as the LCP image.
 * @param bool   $return   Return instead of echoing.
 * @return string
 */
function liferuss_photo_hero_html( $slug, $priority = true, $return = false ) {
	$image = liferuss_photo_markup(
		$slug,
		array(
			'alt'      => '',
			'class'    => 'hero-lcp',
			'sizes'    => '100vw',
			'priority' => $priority,
			'lazy'     => ! $priority,
			'return'   => true,
		)
	);
	if ( '' === $image ) {
		return '';
	}
	$html = '<div class="hero-media" aria-hidden="true">' . $image . '</div><div class="hero-overlay" aria-hidden="true"></div>';
	if ( $return ) {
		return $html;
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return '';
}

/**
 * Preload the scene hero. The href is a mid-size WebP; imagesrcset lists every width.
 *
 * @param string $slug  Catalog slug.
 * @param string $sizes imagesizes value.
 */
function liferuss_print_photo_preload( $slug, $sizes = '100vw' ) {
	$sources = liferuss_photo_variants( $slug, 'webp' );
	if ( ! $sources ) {
		return;
	}
	$href = liferuss_img( $sources[0]['file'] );
	foreach ( $sources as $source ) {
		if ( (int) $source['w'] >= 1024 ) {
			$href = liferuss_img( $source['file'] );
			break;
		}
	}
	printf(
		'<link rel="preload" as="image" type="image/webp" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
		esc_url( $href ),
		esc_attr( liferuss_photo_srcset( $sources ) ),
		esc_attr( $sizes )
	);
}

/**
 * Default social image, 1200×630.
 *
 * @return string
 */
function liferuss_og_image_url() {
	return liferuss_img( 'saint-basil-og-1200.jpg' );
}

/**
 * Hero photograph for the current request.
 *
 * @return string Catalog slug.
 */
function liferuss_photo_scene() {
	static $scene = null;
	if ( null !== $scene ) {
		return $scene;
	}
	$scene = liferuss_resolve_photo_scene();
	return $scene;
}

/**
 * Pick the hero photo from the template, query var, or page slug.
 *
 * @return string
 */
function liferuss_resolve_photo_scene() {
	$study = array( 'study-russia', 'admission', 'universities', 'padfak', 'direct-course', 'fields', 'cities', 'costs', 'scholarships', 'compare' );
	$night = array( 'migration-russia', 'exchange', 'cargo', 'trade', 'freight' );
	$own   = array(
		'about'     => 'russian-couple',
		'services'  => 'saint-petersburg',
		'contact'   => 'moscow-night',
		'account'   => 'matryoshka-samovar',
		'pay'       => 'borscht',
		'find'      => 'saint-petersburg',
		'language'  => 'matryoshka-samovar',
		'blog'      => 'saint-basil',
	);

	if ( get_query_var( 'lr_academy' ) ) {
		return 'matryoshka-samovar';
	}
	if ( is_front_page() ) {
		return 'saint-basil';
	}
	if ( is_page_template( array( 'templates/freight.php', 'templates/trade.php' ) ) ) {
		return 'moscow-night';
	}
	if ( is_post_type_archive( 'lr_guide' ) || is_singular( 'lr_guide' ) || is_tax( 'lr_guide_cat' ) ) {
		return 'matryoshka-samovar';
	}
	if ( get_query_var( 'lr_learn' ) || is_singular( array( 'lr_course', 'lr_lesson' ) ) ) {
		return 'matryoshka-samovar';
	}
	if ( get_query_var( 'lr_compare' ) ) {
		return 'saint-petersburg';
	}
	if ( get_query_var( 'lr_find' ) ) {
		return 'saint-petersburg';
	}
	if ( get_query_var( 'lr_account' ) ) {
		return 'matryoshka-samovar';
	}
	if ( get_query_var( 'lr_pay' ) ) {
		return 'borscht';
	}
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() || is_search() ) {
		return 'saint-basil';
	}
	if ( is_post_type_archive( array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship' ) ) || is_singular( array( 'lr_university', 'lr_field', 'lr_city', 'lr_scholarship' ) ) ) {
		return 'saint-petersburg';
	}

	$slugs = array();
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$slugs[] = (string) $post->post_name;
			foreach ( get_post_ancestors( $post ) as $ancestor_id ) {
				$slugs[] = (string) get_post_field( 'post_name', $ancestor_id );
			}
		}
	}
	$page_slug = isset( $slugs[0] ) ? $slugs[0] : '';
	if ( isset( $own[ $page_slug ] ) ) {
		return $own[ $page_slug ];
	}
	if ( in_array( $page_slug, $night, true ) ) {
		return 'moscow-night';
	}
	if ( in_array( $page_slug, $study, true ) ) {
		return 'saint-petersburg';
	}
	foreach ( array_slice( $slugs, 1 ) as $ancestor ) {
		if ( in_array( $ancestor, $night, true ) ) {
			return 'moscow-night';
		}
		if ( in_array( $ancestor, $study, true ) ) {
			return 'saint-petersburg';
		}
	}

	$rotation = array( 'saint-basil', 'moscow-night', 'saint-petersburg', 'russian-couple', 'borscht', 'matryoshka-samovar' );
	$index    = abs( (int) crc32( '' !== $page_slug ? $page_slug : 'page' ) ) % 6;
	return $rotation[ $index ];
}

/**
 * Aside photograph that is not the page hero.
 *
 * The homepage side card is already the couple, so the form uses St Petersburg.
 *
 * @return string
 */
function liferuss_photo_aside() {
	if ( is_front_page() ) {
		return 'saint-petersburg';
	}
	$map   = array(
		'saint-petersburg'   => 'russian-couple',
		'moscow-night'       => 'saint-basil',
		'matryoshka-samovar' => 'borscht',
		'russian-couple'     => 'saint-petersburg',
		'saint-basil'        => 'moscow-night',
		'borscht'            => 'matryoshka-samovar',
	);
	$scene = liferuss_photo_scene();
	return isset( $map[ $scene ] ) ? $map[ $scene ] : 'russian-couple';
}

/**
 * Drop a cover photo into the first plain page heroes.
 *
 * @param string $html Full document.
 * @return string
 */
function liferuss_inject_page_hero_photo( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'class="page-hero"' ) ) {
		return $html;
	}
	$slug  = liferuss_photo_scene();
	$key   = liferuss_photo_scene_key( $slug );
	$count = 0;
	$out   = preg_replace_callback(
		'/<header class="page-hero">/',
		static function ( $match ) use ( $slug, $key, &$count ) {
			unset( $match );
			++$count;
			$media = liferuss_photo_hero_html( $slug, 1 === $count, true );
			return '<header class="page-hero page-hero-photo scene-' . esc_attr( $key ) . '">' . $media;
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Food and culture strip for the Russia guide.
 */
function liferuss_the_guide_culture() {
	echo '<section class="section guide-culture">';
	echo '<div class="container guide-culture-grid">';
	echo '<figure>';
	liferuss_photo_markup(
		'borscht',
		array(
			'sizes' => '(max-width: 800px) 92vw, 640px',
			'lazy'  => true,
		)
	);
	echo '</figure>';
	echo '<div><p class="eyebrow">فرهنگ و غذا</p><h2>سفرهٔ روسی</h2>';
	echo '<p>برش و نان پامپوشکا بخشی از غذای روزمره است. در راهنمای زندگی، عادت‌های خانه، شهر و سفره کنار مسیر دانشجویی می‌آید.</p>';
	echo '</div></div></section>';
}

/**
 * Darkened St Basil card when a course has no thumbnail.
 *
 * @param string $title Course title.
 */
function liferuss_the_course_fallback( $title ) {
	liferuss_photo_markup(
		'saint-basil-card',
		array(
			'alt'   => '',
			'sizes' => '(max-width: 640px) 92vw, (max-width: 1100px) 46vw, 360px',
			'lazy'  => true,
			'class' => 'academy-card-bg',
		)
	);
	echo '<span class="academy-thumb-title" aria-hidden="true">' . esc_html( $title ) . '</span>';
}

/**
 * Featured image, or the 1200×630 St Basil crop when a post has none.
 *
 * @param string $context card or single.
 */
function liferuss_the_entry_image( $context = 'card' ) {
	if ( has_post_thumbnail() ) {
		$size = 'single' === $context ? 'liferuss-wide' : 'liferuss-card';
		the_post_thumbnail(
			$size,
			array(
				'alt'      => wp_strip_all_tags( get_the_title() ),
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		return;
	}
	$sizes = 'single' === $context ? '(max-width: 760px) 92vw, 760px' : '(max-width: 640px) 92vw, 360px';
	liferuss_photo_markup(
		'saint-basil-og',
		array(
			'alt'   => wp_strip_all_tags( get_the_title() ),
			'sizes' => $sizes,
			'lazy'  => true,
		)
	);
}

/**
 * Old bundled demo files, and the owner photo that replaces each one.
 *
 * Only these theme-relative names are rewritten. Uploads the owner added
 * himself are left untouched.
 *
 * @return array<string, string> Relative path => catalog slug.
 */
function liferuss_demo_image_map() {
	return array(
		'st-basil.jpg'              => 'saint-basil',
		'st-basil.webp'             => 'saint-basil',
		'st-basil-800.webp'         => 'saint-basil',
		'hero-student.jpg'          => 'russian-couple',
		'hero-student.webp'         => 'russian-couple',
		'hero-student-400.webp'     => 'russian-couple',
		'consult-student.jpg'       => 'saint-petersburg',
		'consult-student.webp'      => 'saint-petersburg',
		'universities/msu.jpg'      => 'saint-basil',
		'universities/msu.webp'     => 'saint-basil',
		'universities/spbu.jpg'     => 'saint-petersburg',
		'universities/spbu.webp'    => 'saint-petersburg',
		'universities/hse.jpg'      => 'moscow-night',
		'universities/hse.webp'     => 'moscow-night',
		'universities/sechenov.jpg' => 'matryoshka-samovar',
		'universities/sechenov.webp' => 'matryoshka-samovar',
		'universities/rudn.jpg'     => 'borscht',
		'universities/rudn.webp'    => 'borscht',
		'universities/bauman.jpg'   => 'russian-couple',
		'universities/bauman.webp'  => 'russian-couple',
		'students/student-1.jpg'    => 'russian-couple',
		'students/student-1.webp'   => 'russian-couple',
		'students/student-2.jpg'    => 'russian-couple',
		'students/student-2.webp'   => 'russian-couple',
		'students/student-3.jpg'    => 'russian-couple',
		'students/student-3.webp'   => 'russian-couple',
	);
}

/**
 * Import each owner photo into the media library once.
 *
 * @return array<string, int>
 */
function liferuss_import_brand_photos() {
	$stored = get_option( 'lr_brand_photos', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	if ( ! function_exists( 'media_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$files = array(
		'saint-basil'        => 'saint-basil-1600.jpg',
		'moscow-night'       => 'moscow-night-1600.jpg',
		'saint-petersburg'   => 'saint-petersburg-1600.jpg',
		'russian-couple'     => 'russian-couple-1600.jpg',
		'borscht'            => 'borscht-1600.jpg',
		'matryoshka-samovar' => 'matryoshka-samovar-1600.jpg',
		'saint-basil-og'     => 'saint-basil-og-1200.jpg',
	);
	foreach ( $files as $slug => $file ) {
		$existing = isset( $stored[ $slug ] ) ? absint( $stored[ $slug ] ) : 0;
		if ( $existing && wp_attachment_is_image( $existing ) && get_post_meta( $existing, '_lr_brand_photo', true ) === $slug ) {
			continue;
		}
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_lr_brand_photo',
				'meta_value'     => $slug,
			)
		);
		if ( $found ) {
			$stored[ $slug ] = (int) $found[0];
			continue;
		}
		$src = LIFERUSS_DIR . '/assets/images/' . $file;
		if ( ! is_readable( $src ) ) {
			continue;
		}
		$tmp = wp_tempnam( $file );
		if ( ! $tmp || ! copy( $src, $tmp ) ) {
			continue;
		}
		$photo = liferuss_photo_catalog()[ $slug ];
		$id    = media_handle_sideload(
			array(
				'name'     => 'liferuss-' . $slug . '.jpg',
				'tmp_name' => $tmp,
			),
			0,
			(string) $photo['alt']
		);
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			continue;
		}
		update_post_meta( (int) $id, '_lr_brand_photo', $slug );
		$stored[ $slug ] = (int) $id;
	}
	update_option( 'lr_brand_photos', $stored, false );
	return $stored;
}

/**
 * Attachment URL for an imported owner photo.
 *
 * @param string               $slug Catalog slug.
 * @param array<string, int>   $ids  Imported IDs.
 * @return string
 */
function liferuss_brand_attachment_url( $slug, $ids ) {
	$id = isset( $ids[ $slug ] ) ? absint( $ids[ $slug ] ) : 0;
	if ( $id ) {
		$url = wp_get_attachment_url( $id );
		if ( $url ) {
			return $url;
		}
	}
	$width = 'saint-basil-og' === $slug ? 1200 : 1600;
	return liferuss_img( $slug . '-' . $width . '.jpg' );
}

/**
 * Replace one string when it is, or contains, a bundled demo image.
 *
 * Exact theme-relative values stay theme-relative so srcset still works.
 * Longer HTML gets the media-library URL.
 *
 * @param string             $value Text.
 * @param array<string, int> $ids   Imported IDs.
 * @return string
 */
function liferuss_rewrite_demo_image_string( $value, $ids ) {
	$map  = liferuss_demo_image_map();
	$trim = ltrim( $value, '/' );
	if ( isset( $map[ $trim ] ) ) {
		return liferuss_photo_file( $map[ $trim ] );
	}
	if ( false === strpos( $value, 'liferuss/assets/images/' ) ) {
		return $value;
	}
	foreach ( $map as $old => $slug ) {
		$replacement = liferuss_brand_attachment_url( $slug, $ids );
		$pattern     = '#(?:https?:)?//[^"\'\s>]+/themes/liferuss/assets/images/' . preg_quote( $old, '#' ) . '#';
		$value       = (string) preg_replace( $pattern, $replacement, $value );
	}
	return $value;
}

/**
 * Walk options, content, and meta. Attachment IDs the owner set are not values we match.
 *
 * @param mixed              $value Any option shape.
 * @param array<string, int> $ids   Imported IDs.
 * @return mixed
 */
function liferuss_rewrite_demo_images_deep( $value, $ids ) {
	if ( is_string( $value ) ) {
		return liferuss_rewrite_demo_image_string( $value, $ids );
	}
	if ( ! is_array( $value ) ) {
		return $value;
	}
	foreach ( $value as $key => $item ) {
		$value[ $key ] = liferuss_rewrite_demo_images_deep( $item, $ids );
	}
	return $value;
}

/**
 * Point saved cargo and trade heroes at the night photograph when they still use a demo file.
 *
 * @param array<string, mixed> $opts Saved theme options.
 * @return array<string, mixed>
 */
function liferuss_retarget_scene_options( $opts ) {
	$demo = array_keys( liferuss_demo_image_map() );
	foreach ( liferuss_photo_catalog() as $slug => $photo ) {
		unset( $photo );
		$demo[] = liferuss_photo_file( $slug );
	}
	$force = array(
		'freight_hero_image' => 'moscow-night.jpg',
		'trade_hero_image'   => 'moscow-night.jpg',
	);
	foreach ( $force as $key => $file ) {
		if ( isset( $opts[ $key ] ) && is_string( $opts[ $key ] ) && in_array( ltrim( $opts[ $key ], '/' ), $demo, true ) ) {
			$opts[ $key ] = $file;
		}
	}
	return $opts;
}

/**
 * Swap bundled demo URLs in stored content. Owner uploads are not in that path.
 *
 * @param array<string, int> $ids Imported attachment IDs.
 */
function liferuss_swap_bundled_demo_images( $ids ) {
	$options = array( 'liferuss_options', 'theme_mods_liferuss' );
	foreach ( $options as $name ) {
		$stored = get_option( $name, null );
		if ( null === $stored ) {
			continue;
		}
		$next = liferuss_rewrite_demo_images_deep( $stored, $ids );
		if ( 'liferuss_options' === $name && is_array( $next ) ) {
			$next = liferuss_retarget_scene_options( $next );
		}
		if ( $next !== $stored ) {
			update_option( $name, $next );
		}
	}

	global $wpdb;
	$like = '%' . $wpdb->esc_like( '/themes/liferuss/assets/images/' ) . '%';
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s OR post_excerpt LIKE %s", $like, $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $post_ids as $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post ) {
			continue;
		}
		$next_content = liferuss_rewrite_demo_image_string( (string) $post->post_content, $ids );
		$next_excerpt = liferuss_rewrite_demo_image_string( (string) $post->post_excerpt, $ids );
		if ( $next_content === $post->post_content && $next_excerpt === $post->post_excerpt ) {
			continue;
		}
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->posts,
			array(
				'post_content' => $next_content,
				'post_excerpt' => $next_excerpt,
			),
			array( 'ID' => (int) $post_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( (int) $post_id );
	}

	$meta_rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $meta_rows as $row ) {
		$next = liferuss_rewrite_demo_image_string( (string) $row->meta_value, $ids );
		if ( $next === $row->meta_value ) {
			continue;
		}
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->postmeta,
			array( 'meta_value' => $next ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			array( 'meta_id' => (int) $row->meta_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}

/**
 * Import the owner photos and rewrite leftover demo references once per theme version.
 */
function liferuss_install_brand_photos() {
	if ( get_option( 'lr_brand_photos_ready' ) === LIFERUSS_VERSION ) {
		return;
	}
	if ( get_transient( 'lr_brand_photos_lock' ) ) {
		return;
	}
	set_transient( 'lr_brand_photos_lock', 1, 2 * MINUTE_IN_SECONDS );
	$ids = liferuss_import_brand_photos();
	$ok  = true;
	foreach ( array( 'saint-basil', 'moscow-night', 'saint-petersburg', 'russian-couple', 'borscht', 'matryoshka-samovar', 'saint-basil-og' ) as $slug ) {
		if ( empty( $ids[ $slug ] ) ) {
			$ok = false;
		}
	}
	liferuss_swap_bundled_demo_images( $ids );
	if ( $ok ) {
		update_option( 'lr_brand_photos_ready', LIFERUSS_VERSION, false );
	}
	delete_transient( 'lr_brand_photos_lock' );
}
