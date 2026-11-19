<?php
/**
 * Homepage v2: language-app banner. The button only appears when a URL exists.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! liferuss_hv2_on( 'hv2_app_enabled' ) ) {
	return;
}
$url = trim( (string) liferuss_opt( 'hv2_app_url', '' ) );
$btn = liferuss_opt( 'hv2_app_btn' );
?>
<section class="hv2-section hv2-app" id="app" aria-labelledby="hv2-app-title">
	<div class="container">
		<div class="hv2-app__box">
			<span class="hv2-ico" aria-hidden="true"><?php echo liferuss_hv2_icon( 'app' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div class="hv2-app__text">
				<h2 id="hv2-app-title"><?php echo esc_html( liferuss_opt( 'hv2_app_title' ) ); ?></h2>
				<?php if ( liferuss_opt( 'hv2_app_text' ) ) : ?>
					<p><?php echo esc_html( liferuss_opt( 'hv2_app_text' ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $url && $btn ) : ?>
				<a class="btn btn-navy hv2-app__btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo liferuss_hv2_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $btn ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
