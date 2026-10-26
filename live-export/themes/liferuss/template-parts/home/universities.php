<?php
/**
 * Homepage universities from the published catalog.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unis = liferuss_home_universities( 8 );
if ( count( $unis ) < 3 ) {
	return;
}
?>
<section class="section universities-section" id="universities">
	<div class="container">
		<header class="section-head section-head-split">
			<div>
				<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'universities_eyebrow' ) ); ?></p>
				<h2><?php echo esc_html( liferuss_home_heading( 'universities', liferuss_opt( 'universities_title' ) ) ); ?></h2>
				<p><?php echo esc_html( liferuss_opt( 'universities_subtitle' ) ); ?></p>
			</div>
			<div class="slider-controls">
				<a class="text-link" href="<?php echo esc_url( liferuss_url( '/universities/' ) ); ?>"><?php echo esc_html( liferuss_opt( 'universities_link_text' ) ); ?> <?php echo liferuss_icon( 'arrow' ); ?></a>
				<div class="slider-nav">
					<button type="button" class="slider-btn" data-slider-prev aria-label="<?php echo esc_attr( liferuss_t( 'slider_prev' ) ); ?>"><?php echo liferuss_icon( 'arrow' ); ?></button>
					<button type="button" class="slider-btn" data-slider-next aria-label="<?php echo esc_attr( liferuss_t( 'slider_next' ) ); ?>"><?php echo liferuss_icon( 'arrow' ); ?></button>
				</div>
			</div>
		</header>
		<div class="uni-slider" data-slider>
			<div class="uni-track" data-slider-track>
				<?php foreach ( $unis as $uni ) : ?>
					<a class="uni-card" href="<?php echo esc_url( (string) ( $uni['url'] ?? '' ) ); ?>">
						<figure class="uni-photo">
							<?php if ( ! empty( $uni['thumbnail'] ) ) : ?>
								<img src="<?php echo esc_url( (string) $uni['thumbnail'] ); ?>" alt="<?php echo esc_attr( (string) ( $uni['name'] ?? '' ) ); ?>" width="640" height="400" loading="lazy" decoding="async">
							<?php endif; ?>
						</figure>
						<div class="uni-body">
							<?php if ( ! empty( $uni['name_en'] ) ) : ?>
								<p class="uni-latin"><?php echo esc_html( (string) $uni['name_en'] ); ?></p>
							<?php endif; ?>
							<h3><?php echo esc_html( (string) ( $uni['name'] ?? '' ) ); ?></h3>
							<p class="uni-meta">
								<?php if ( ! empty( $uni['best_world_rank'] ) ) : ?>
									<span class="rank"><?php echo liferuss_icon( 'star' ); ?> <?php echo esc_html( (string) $uni['best_world_rank'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $uni['city'] ) ) : ?>
									<span><?php echo esc_html( (string) $uni['city'] ); ?></span>
								<?php endif; ?>
							</p>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
