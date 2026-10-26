<?php
/**
 * Completed work entered as lr_case. Hidden when none are published.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cases = liferuss_home_stories( 'lr_case', 8 );
if ( ! $cases ) {
	return;
}
?>
<section class="section lr-cases" id="cases">
	<div class="container">
		<header class="section-head">
			<h2><?php echo esc_html( liferuss_home_heading( 'cases', 'نمونه‌های انجام‌شده' ) ); ?></h2>
		</header>
		<div class="lr-snap">
			<?php foreach ( $cases as $case ) : ?>
				<?php
				$context = trim( (string) get_post_meta( $case->ID, '_lr_context', true ) );
				$result  = trim( (string) get_post_meta( $case->ID, '_lr_result', true ) );
				$thumb   = get_the_post_thumbnail(
					$case,
					'liferuss-card',
					array(
						'alt'      => $case->post_title,
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
				<article class="lr-story">
					<figure class="lr-story__media"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
					<h3><?php echo esc_html( get_the_title( $case ) ); ?></h3>
					<?php if ( '' !== $context ) : ?>
						<p class="lr-story__meta"><?php echo esc_html( $context ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $result ) : ?>
						<p><?php echo esc_html( $result ); ?></p>
					<?php elseif ( '' !== trim( wp_strip_all_tags( $case->post_content ) ) ) : ?>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $case->post_content ), 28, '…' ) ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
