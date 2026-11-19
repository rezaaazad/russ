<?php
/**
 * Homepage v2: Instagram band. The button only appears when a URL exists.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! liferuss_hv2_on( 'hv2_ig_enabled' ) ) {
	return;
}
$url = liferuss_hv2_instagram_url();
$btn = liferuss_opt( 'hv2_ig_btn' );
?>
<section class="hv2-section hv2-ig" id="community" aria-labelledby="hv2-ig-title">
	<div class="container">
		<div class="hv2-ig__box">
			<div class="hv2-ig__copy">
				<span class="hv2-ico hv2-ico--gold" aria-hidden="true"><?php echo liferuss_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h2 id="hv2-ig-title"><?php echo liferuss_hv2_mark( liferuss_opt( 'hv2_ig_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php if ( liferuss_opt( 'hv2_ig_text' ) ) : ?>
					<p><?php echo esc_html( liferuss_opt( 'hv2_ig_text' ) ); ?></p>
				<?php endif; ?>
				<?php if ( $url && $btn ) : ?>
					<p class="hv2-ig__cta"><a class="btn btn-gold" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo liferuss_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $btn ); ?></a></p>
				<?php endif; ?>
			</div>
			<div class="hv2-ig__collage" aria-hidden="true">
				<?php
				foreach ( array( 'moscow-night', 'saint-basil', 'saint-petersburg' ) as $i => $slug ) {
					echo '<span class="hv2-ig__tile hv2-ig__tile--' . (int) ( $i + 1 ) . '">';
					liferuss_hv2_photo( 0, $slug, '', '(max-width: 1000px) 0px, 220px', 'hv2-ig__img' );
					echo '</span>';
				}
				?>
			</div>
		</div>
	</div>
</section>
