<?php
/**
 * Homepage blocks that stay hidden until the owner fills them.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Announcement text. Empty means the bar is not rendered.
 *
 * @return string
 */
function liferuss_announce_text() {
	return trim( (string) liferuss_opt( 'announce_text', '' ) );
}

/**
 * Stable dismiss key for the current text.
 *
 * @return string
 */
function liferuss_announce_key() {
	return substr( md5( liferuss_announce_text() ), 0, 12 );
}

/**
 * Hide a dismissed bar before first paint.
 */
function liferuss_announce_boot() {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}
	if ( '' === liferuss_announce_text() ) {
		return;
	}
	$key = liferuss_announce_key();
	echo '<script>try{if(localStorage.getItem("lr-announce:' . esc_js( $key ) . '")==="1")document.documentElement.classList.add("lr-announce-off")}catch(e){}</script>' . "\n";
}
add_action( 'wp_head', 'liferuss_announce_boot', 1 );

/**
 * Gold bar above the homepage sections. The default sentence points at the consultation form.
 */
function liferuss_the_announcement() {
	$text = liferuss_announce_text();
	if ( '' === $text || ! is_front_page() ) {
		return;
	}
	$link = liferuss_cta_url( liferuss_opt( 'announce_link', '#consultation' ) );
	$key  = liferuss_announce_key();
	?>
	<div class="lr-announce" role="region" aria-label="<?php echo esc_attr( 'اطلاعیه' ); ?>" data-announce="<?php echo esc_attr( $key ); ?>">
		<a class="lr-announce__link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $text ); ?></a>
		<button class="lr-announce__close" type="button" aria-label="<?php echo esc_attr( 'بستن اطلاعیه' ); ?>">×</button>
	</div>
	<script>
	(function () {
		var bar = document.querySelector('.lr-announce');
		if (!bar) return;
		var button = bar.querySelector('.lr-announce__close');
		if (!button) return;
		button.addEventListener('click', function () {
			try { localStorage.setItem('lr-announce:' + bar.getAttribute('data-announce'), '1'); } catch (e) {}
			document.documentElement.classList.add('lr-announce-off');
		});
	}());
	</script>
	<?php
}

/**
 * License or trust rows the owner filled. Empty titles are dropped.
 *
 * @return array<int, array<string, string>>
 */
function liferuss_license_items() {
	$rows = array();
	foreach ( (array) liferuss_opt( 'licenses', array() ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$title = trim( (string) ( $item['title'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}
		$rows[] = array(
			'title'  => $title,
			'detail' => trim( (string) ( $item['detail'] ?? '' ) ),
			'url'    => trim( (string) ( $item['url'] ?? '' ) ),
		);
	}
	return $rows;
}

/**
 * Published stories, skipping seeded placeholder copy.
 *
 * @param string $type  Post type.
 * @param int    $limit Max rows.
 * @return \WP_Post[]
 */
function liferuss_home_stories( $type, $limit = 8 ) {
	if ( ! post_type_exists( $type ) ) {
		return array();
	}
	$query = new WP_Query(
		array(
			'post_type'           => $type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	$posts = array();
	foreach ( $query->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$blob = $post->post_title . ' ' . $post->post_content . ' ' . $post->post_excerpt;
		if ( function_exists( 'liferuss_is_placeholder_copy' ) && liferuss_is_placeholder_copy( $blob ) ) {
			continue;
		}
		if ( '' === trim( wp_strip_all_tags( $post->post_title ) ) ) {
			continue;
		}
		$posts[] = $post;
	}
	wp_reset_postdata();
	return $posts;
}

/**
 * A statistic the owner explicitly marked for publication.
 * The packaged +۵۰۰ value is never printed.
 *
 * @return array{value: string, text: string}|null
 */
function liferuss_public_stat() {
	if ( '1' !== (string) liferuss_opt( 'stat_publish', '0' ) ) {
		return null;
	}
	$value = trim( (string) liferuss_opt( 'stat_value' ) );
	$text  = trim( (string) liferuss_opt( 'stat_text' ) );
	if ( '' === $value || '' === $text ) {
		return null;
	}
	if ( in_array( $value, array( '+۵۰۰', '+500', '500', '۵۰۰' ), true ) ) {
		return null;
	}
	return array(
		'value' => $value,
		'text'  => $text,
	);
}
