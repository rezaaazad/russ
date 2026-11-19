<?php
/**
 * Homepage v2: hero. Copy on the start side, intro image or video on the other.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cta_link = liferuss_cta_url( liferuss_opt( 'hero_cta1_link', '#consultation' ) );
$cta_text = liferuss_opt( 'hv2_hero_cta_text', 'اولین قدم را بردارید' );
$video    = liferuss_hv2_video( liferuss_opt( 'hv2_hero_video', '' ) );
$eyebrow  = liferuss_opt( 'hero_eyebrow' );
$quote    = liferuss_opt( 'hero_quote' );
$alt      = liferuss_photo_alt( 'saint-basil' );
?>
<section class="hv2-hero" id="about" aria-labelledby="hv2-hero-title">
	<div class="container hv2-hero__grid">
		<div class="hv2-hero__copy">
			<?php if ( $eyebrow ) : ?>
				<p class="hv2-chip"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 id="hv2-hero-title"><?php echo esc_html( liferuss_home_heading( 'hero', liferuss_opt( 'hero_headline' ) ) ); ?></h1>
			<p class="hv2-hero__lead"><?php echo esc_html( liferuss_opt( 'hero_subheadline' ) ); ?></p>
			<div class="hv2-hero__actions">
				<a class="btn btn-gold hv2-btn-lg js-scroll-consult" href="<?php echo esc_url( $cta_link ); ?>">
					<?php echo esc_html( $cta_text ); ?>
					<?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		</div>
		<div class="hv2-hero__media">
			<figure class="hv2-media" data-hv2-media>
				<?php
				liferuss_hv2_photo(
					liferuss_opt( 'hv2_hero_image_id', 0 ),
					'saint-basil',
					$alt,
					'(max-width: 900px) 92vw, 560px',
					'hv2-media__img',
					true
				);
				?>
				<?php if ( $video ) : ?>
					<?php
					$label = liferuss_opt( 'hv2_hero_video_label', 'پخش ویدیوی معرفی' );
					if ( 'link' === $video['type'] ) :
						?>
						<a class="hv2-play" href="<?php echo esc_url( $video['src'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $label ); ?>">
							<?php echo liferuss_hv2_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php else : ?>
						<button class="hv2-play" type="button" data-hv2-play data-type="<?php echo esc_attr( $video['type'] ); ?>" data-src="<?php echo esc_url( $video['src'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
							<?php echo liferuss_hv2_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $quote && ! $video ) : ?>
					<figcaption class="hv2-media__cap">«<?php echo esc_html( $quote ); ?>»</figcaption>
				<?php endif; ?>
			</figure>
		</div>
	</div>
</section>
