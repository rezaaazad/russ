<?php
/**
 * Homepage v2: services (four cards, 2x2 on phones).
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = liferuss_enabled_items( 'hv2_services' );
if ( ! $items ) {
	return;
}
?>
<section class="section hv2-section hv2-services" id="services" aria-labelledby="hv2-services-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-services-title"><?php echo esc_html( liferuss_opt( 'hv2_services_title' ) ); ?></h2>
			<?php if ( liferuss_opt( 'hv2_services_subtitle' ) ) : ?>
				<p><?php echo esc_html( liferuss_opt( 'hv2_services_subtitle' ) ); ?></p>
			<?php endif; ?>
		</header>
		<div class="hv2-services__grid">
			<?php foreach ( $items as $item ) : ?>
				<a class="hv2-svc" href="<?php echo esc_url( liferuss_hv2_href( $item['link'] ?? '' ) ); ?>">
					<span class="hv2-svc__top">
						<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<span class="hv2-go" aria-hidden="true"><?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</span>
					<p><?php echo esc_html( $item['text'] ?? '' ); ?></p>
					<span class="hv2-ico" aria-hidden="true"><?php echo liferuss_hv2_icon( (string) ( $item['icon'] ?? 'cap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
