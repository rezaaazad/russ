<?php
/**
 * Homepage v2: testimonials. Real lr_testimonial posts only; hidden when none.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quotes = liferuss_home_stories( 'lr_testimonial', 3 );
if ( ! $quotes ) {
	return;
}
?>
<section class="section hv2-section hv2-tm" id="stories" aria-labelledby="hv2-tm-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-tm-title"><?php echo esc_html( liferuss_home_heading( 'testimonials', liferuss_opt( 'hv2_tm_title' ) ) ); ?></h2>
			<?php if ( liferuss_opt( 'hv2_tm_subtitle' ) ) : ?>
				<p><?php echo esc_html( liferuss_opt( 'hv2_tm_subtitle' ) ); ?></p>
			<?php endif; ?>
		</header>
		<div class="hv2-grid3">
			<?php foreach ( $quotes as $quote ) : ?>
				<?php
				$context = trim( (string) get_post_meta( $quote->ID, '_lr_context', true ) );
				if ( '' === $context ) {
					$context = trim( (string) $quote->post_excerpt );
				}
				$rating = absint( get_post_meta( $quote->ID, '_lr_rating', true ) );
				$text   = trim( wp_strip_all_tags( $quote->post_content ) );
				?>
				<article class="hv2-card hv2-quote">
					<?php if ( $rating >= 1 && $rating <= 5 ) : ?>
						<?php echo liferuss_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<?php if ( '' !== $text ) : ?>
						<blockquote><p><?php echo esc_html( $text ); ?></p></blockquote>
					<?php endif; ?>
					<div class="hv2-quote__who">
						<span class="hv2-quote__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( trim( wp_strip_all_tags( get_the_title( $quote ) ) ), 0, 1 ) ); ?></span>
						<span class="hv2-quote__name">
							<strong><?php echo esc_html( get_the_title( $quote ) ); ?></strong>
							<?php if ( '' !== $context ) : ?>
								<small><?php echo esc_html( $context ); ?></small>
							<?php endif; ?>
						</span>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
