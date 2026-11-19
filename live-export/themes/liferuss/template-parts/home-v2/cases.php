<?php
/**
 * Homepage v2: successful cases. Real lr_case posts only; hidden when none.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cases = liferuss_home_stories( 'lr_case', 3 );
if ( ! $cases ) {
	return;
}
$btn      = liferuss_opt( 'hv2_cases_btn' );
$btn_link = liferuss_hv2_href( liferuss_opt( 'hv2_cases_btn_link', '' ) );
?>
<section class="section hv2-section hv2-cases lr-cases" id="cases" aria-labelledby="hv2-cases-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-cases-title"><?php echo esc_html( liferuss_home_heading( 'cases', liferuss_opt( 'hv2_cases_title' ) ) ); ?></h2>
			<?php if ( liferuss_opt( 'hv2_cases_subtitle' ) ) : ?>
				<p><?php echo esc_html( liferuss_opt( 'hv2_cases_subtitle' ) ); ?></p>
			<?php endif; ?>
		</header>
		<div class="hv2-grid3">
			<?php foreach ( $cases as $case ) : ?>
				<?php
				$context = trim( (string) get_post_meta( $case->ID, '_lr_context', true ) );
				$result  = trim( (string) get_post_meta( $case->ID, '_lr_result', true ) );
				if ( '' === $result ) {
					$result = wp_trim_words( wp_strip_all_tags( $case->post_content ), 28, '…' );
				}
				?>
				<article class="hv2-card hv2-case">
					<figure class="hv2-card__media">
						<?php if ( has_post_thumbnail( $case ) ) : ?>
							<?php
							echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$case,
								'liferuss-card',
								array(
									'alt'      => $case->post_title,
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => '(max-width: 640px) 92vw, (max-width: 1000px) 46vw, 360px',
								)
							);
							?>
						<?php else : ?>
							<?php liferuss_hv2_photo( 0, 'saint-petersburg', '', '(max-width: 640px) 92vw, 360px' ); ?>
						<?php endif; ?>
						<?php if ( '' !== $context ) : ?>
							<span class="hv2-pill"><?php echo esc_html( $context ); ?></span>
						<?php endif; ?>
					</figure>
					<div class="hv2-card__body">
						<h3><?php echo esc_html( get_the_title( $case ) ); ?></h3>
						<?php if ( '' !== $result ) : ?>
							<p><?php echo esc_html( $result ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $btn && $btn_link ) : ?>
			<p class="hv2-more-row"><a class="btn btn-ghost" href="<?php echo esc_url( $btn_link ); ?>"><?php echo esc_html( $btn ); ?> <?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
		<?php endif; ?>
	</div>
</section>
