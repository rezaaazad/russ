<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
				<?php foreach ( liferuss_universities() as $uni ) : ?>
					<?php
					$card_tag = ! empty( $uni['link'] ) ? 'a' : 'article';
					$href     = ! empty( $uni['link'] ) ? ' href="' . esc_url( $uni['link'] ) . '"' : '';
					?>
					<<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="uni-card"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<figure class="uni-photo">
							<?php
							liferuss_the_image(
								array(
									'id'       => $uni['image_id'] ?? 0,
									'fallback' => $uni['image'] ?? '',
									'alt'      => $uni['name'],
									'width'    => 640,
									'height'   => 400,
									'size'     => 'liferuss-card',
									'sizes'    => '(max-width: 640px) 92vw, (max-width: 1100px) 46vw, 280px',
								)
							);
							?>
						</figure>
						<div class="uni-body">
							<p class="uni-latin"><?php echo esc_html( $uni['latin'] ); ?></p>
							<h3><?php echo esc_html( $uni['name'] ); ?></h3>
							<p class="uni-meta">
								<span class="rank"><?php echo liferuss_icon( 'star' ); ?> <?php echo esc_html( $uni['rank'] ); ?></span>
								<span><?php echo esc_html( $uni['city'] ); ?></span>
							</p>
							<p><?php echo esc_html( $uni['focus'] ); ?></p>
						</div>
					</<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
