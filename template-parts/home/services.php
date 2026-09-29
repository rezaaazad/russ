<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '1' === (string) liferuss_opt( 'services_enabled', '1' ) ) :
	?>
<section class="section services-section" id="services">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'services_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'services', liferuss_opt( 'services_title' ) ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'services_subtitle' ) ); ?></p>
		</header>
		<div class="services-grid">
			<?php foreach ( liferuss_services() as $service ) : ?>
				<article class="service-card">
					<?php if ( ! empty( $service['image_id'] ) ) : ?>
						<?php echo wp_get_attachment_image( (int) $service['image_id'], 'thumbnail', false, array( 'class' => 'service-photo', 'alt' => $service['title'] ) ); ?>
					<?php else : ?>
						<span class="icon-circle"><?php echo liferuss_icon( $service['icon'] ); ?></span>
					<?php endif; ?>
					<h3><?php echo esc_html( $service['title'] ); ?></h3>
					<p><?php echo esc_html( $service['text'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
