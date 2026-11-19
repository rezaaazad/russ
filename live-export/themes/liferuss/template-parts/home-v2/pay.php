<?php
/**
 * Homepage v2: dark installment band with three benefits.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! liferuss_hv2_on( 'hv2_pay_enabled' ) ) {
	return;
}
$items = (array) liferuss_opt( 'hv2_pay_items', array() );
$cta   = liferuss_opt( 'hv2_pay_cta' );
?>
<section class="hv2-section hv2-pay" id="installments" aria-labelledby="hv2-pay-title">
	<div class="container">
		<div class="hv2-pay__box">
			<h2 id="hv2-pay-title"><?php echo liferuss_hv2_mark( liferuss_opt( 'hv2_pay_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php if ( liferuss_opt( 'hv2_pay_subtitle' ) ) : ?>
				<p class="hv2-pay__lead"><?php echo esc_html( liferuss_opt( 'hv2_pay_subtitle' ) ); ?></p>
			<?php endif; ?>
			<ul class="hv2-pay__list">
				<?php foreach ( $items as $item ) : ?>
					<li>
						<span class="hv2-ico hv2-ico--gold" aria-hidden="true"><?php echo liferuss_hv2_icon( (string) ( $item['icon'] ?? 'wallet' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<span><?php echo esc_html( $item['text'] ?? '' ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $cta ) : ?>
				<p class="hv2-pay__cta"><a class="btn btn-gold js-scroll-consult" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( 'hv2_pay_cta_link', '#consultation' ) ) ); ?>"><?php echo esc_html( $cta ); ?> <?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</section>
