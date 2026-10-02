<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="section stories-section" id="stories">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'testimonials_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'testimonials', liferuss_opt( 'testimonials_title' ) ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'testimonials_subtitle' ) ); ?></p>
		</header>
		<div class="stories-grid">
			<?php foreach ( liferuss_testimonials() as $story ) : ?>
				<article class="story-card">
					<figure class="story-avatar">
						<?php
						$story_name = (string) ( $story['name'] ?? '' );
						$bits       = preg_split( '/\s+/u', trim( $story_name ) );
						$initials   = '';
						if ( is_array( $bits ) ) {
							foreach ( array_slice( $bits, 0, 2 ) as $bit ) {
								$initials .= mb_substr( $bit, 0, 1 );
							}
						}
						?>
						<span class="story-fallback" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
						<?php
						liferuss_the_image(
							array(
								'id'       => $story['image_id'] ?? 0,
								'fallback' => $story['image'] ?? '',
								'alt'      => $story['name'],
								'width'    => 72,
								'height'   => 72,
								'size'     => 'thumbnail',
								'sizes'    => '72px',
							)
						);
						?>
					</figure>
					<?php echo liferuss_stars( $story['rating'] ?? 5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<blockquote>
						<p><?php echo esc_html( $story['quote'] ); ?></p>
					</blockquote>
					<p class="story-name"><?php echo esc_html( $story['name'] ); ?></p>
					<p class="story-meta"><?php echo esc_html( $story['meta'] ); ?></p>
				</article>
			<?php endforeach; ?>
			<?php if ( ! liferuss_home_section_enabled( 'stats' ) ) : ?>
			<aside class="stat-card">
				<p class="screen-reader-text"><?php echo esc_html( liferuss_t( 'stories_aria' ) ); ?></p>
				<div class="ru-flag" aria-hidden="true"><span></span><span></span><span></span></div>
				<p class="stat-plus"><?php echo esc_html( liferuss_opt( 'stat_value' ) ); ?></p>
				<p><?php echo esc_html( liferuss_opt( 'stat_text' ) ); ?></p>
			</aside>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
