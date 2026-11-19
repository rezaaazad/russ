<?php
/**
 * Homepage v2: offers. Seven photo cards, rows of 2 / 3 / 2 on desktop.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = liferuss_enabled_items( 'hv2_offers' );
if ( ! $items ) {
	return;
}
$more     = liferuss_t( 'hv2_more' );
$btn      = liferuss_opt( 'hv2_offers_btn' );
$btn_link = liferuss_hv2_href( liferuss_opt( 'hv2_offers_btn_link', '/immigration/' ) );
?>
<section class="section hv2-section hv2-offers" id="offers" aria-labelledby="hv2-offers-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-offers-title"><?php echo esc_html( liferuss_opt( 'hv2_offers_title' ) ); ?></h2>
			<?php if ( liferuss_opt( 'hv2_offers_subtitle' ) ) : ?>
				<p><?php echo esc_html( liferuss_opt( 'hv2_offers_subtitle' ) ); ?></p>
			<?php endif; ?>
		</header>
		<div class="hv2-offers__grid">
			<?php foreach ( $items as $n => $item ) : ?>
				<a class="hv2-offer hv2-offer--<?php echo (int) ( $n + 1 ); ?>" href="<?php echo esc_url( liferuss_hv2_href( $item['link'] ?? '' ) ); ?>">
					<span class="hv2-offer__media" aria-hidden="true">
						<?php
						liferuss_hv2_photo(
							$item['image_id'] ?? 0,
							(string) ( $item['photo'] ?? 'saint-basil' ),
							'',
							'(max-width: 640px) 92vw, (max-width: 1000px) 46vw, 560px',
							'hv2-offer__img'
						);
						?>
						<?php if ( ! empty( $item['tag'] ) ) : ?>
							<span class="hv2-offer__tag"><?php echo esc_html( $item['tag'] ); ?></span>
						<?php endif; ?>
					</span>
					<span class="hv2-offer__body">
						<strong class="hv2-offer__title"><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<span class="hv2-offer__text"><?php echo esc_html( $item['text'] ?? '' ); ?></span>
					</span>
					<span class="hv2-offer__bar">
						<span><?php echo esc_html( $more ); ?></span>
						<span class="hv2-go" aria-hidden="true"><?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ( $btn && $btn_link ) : ?>
			<p class="hv2-more-row"><a class="btn btn-ghost" href="<?php echo esc_url( $btn_link ); ?>"><?php echo esc_html( $btn ); ?> <?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
		<?php endif; ?>
	</div>
</section>
