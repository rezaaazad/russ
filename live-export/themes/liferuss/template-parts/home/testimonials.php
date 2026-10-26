<?php
/**
 * Testimonials from published lr_testimonial posts. Packaged sample quotes are not shown.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quotes = liferuss_home_stories( 'lr_testimonial', 8 );
$stat   = liferuss_public_stat();
if ( ! $quotes && ! $stat ) {
	return;
}
?>
<section class="section stories-section" id="stories">
	<div class="container">
		<header class="section-head">
			<?php if ( liferuss_opt( 'testimonials_eyebrow' ) ) : ?>
				<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'testimonials_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h2><?php echo esc_html( liferuss_home_heading( 'testimonials', liferuss_opt( 'testimonials_title' ) ) ); ?></h2>
		</header>
		<div class="lr-snap">
			<?php foreach ( $quotes as $quote ) : ?>
				<?php
				$context = trim( (string) get_post_meta( $quote->ID, '_lr_context', true ) );
				if ( '' === $context ) {
					$context = trim( (string) $quote->post_excerpt );
				}
				$rating = absint( get_post_meta( $quote->ID, '_lr_rating', true ) );
				$text   = trim( wp_strip_all_tags( $quote->post_content ) );
				$thumb  = get_the_post_thumbnail(
					$quote,
					'thumbnail',
					array(
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
				<article class="story-card lr-story">
					<?php if ( $thumb ) : ?>
						<figure class="story-avatar lr-story__avatar"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
					<?php endif; ?>
					<?php if ( $rating >= 1 && $rating <= 5 && function_exists( 'liferuss_stars' ) ) : ?>
						<?php echo liferuss_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<?php if ( '' !== $text ) : ?>
						<blockquote><p><?php echo esc_html( $text ); ?></p></blockquote>
					<?php endif; ?>
					<p class="story-name"><?php echo esc_html( get_the_title( $quote ) ); ?></p>
					<?php if ( '' !== $context ) : ?>
						<p class="story-meta"><?php echo esc_html( $context ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
			<?php if ( $stat ) : ?>
				<aside class="stat-card lr-story">
					<p class="stat-plus"><?php echo esc_html( $stat['value'] ); ?></p>
					<p><?php echo esc_html( $stat['text'] ); ?></p>
				</aside>
			<?php endif; ?>
		</div>
	</div>
</section>
