<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero_bg_id = absint( liferuss_opt( 'hero_bg_id' ) );
if ( ! $hero_bg_id ) {
	$hero_bg_id = absint( liferuss_opt( 'hero_image_id' ) );
}
?>

<section class="hero" id="about">
	<?php liferuss_the_hero_lcp( $hero_bg_id, 'st-basil.jpg' ); ?>
	<div class="hero-overlay" aria-hidden="true"></div>
	<div class="container hero-grid">
		<div class="hero-copy">
			<?php if ( liferuss_opt( 'hero_eyebrow' ) ) : ?>
				<p class="eyebrow hero-badge"><?php echo esc_html( liferuss_opt( 'hero_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h1><?php echo esc_html( liferuss_home_heading( 'hero', liferuss_opt( 'hero_headline' ) ) ); ?></h1>
			<p class="hero-lead"><?php echo esc_html( liferuss_opt( 'hero_subheadline' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-gold js-scroll-consult" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( 'hero_cta1_link' ) ) ); ?>">
					<?php echo esc_html( liferuss_opt( 'hero_cta1_text' ) ); ?>
					<?php echo liferuss_icon( 'arrow' ); ?>
				</a>
				<a class="btn btn-ghost btn-ghost-light" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( 'hero_cta2_link' ) ) ); ?>">
					<?php echo esc_html( liferuss_opt( 'hero_cta2_text' ) ); ?>
				</a>
			</div>
		</div>
		<div class="hero-visual">
			<figure class="hero-student">
				<?php
				liferuss_the_image(
					array(
						'id'       => liferuss_opt( 'hero_student_id' ),
						'fallback' => 'hero-student.jpg',
						'alt'      => liferuss_t( 'hero_alt_student' ),
						'width'    => 400,
						'height'   => 535,
						'size'     => 'medium_large',
						'lazy'     => true,
						'priority' => false,
						'sizes'    => '(max-width: 640px) 1px, (max-width: 860px) 200px, 320px',
					)
				);
				?>
			</figure>
			<?php if ( liferuss_opt( 'hero_quote' ) ) : ?>
				<blockquote class="hero-quote">
					<p>«<?php echo esc_html( liferuss_opt( 'hero_quote' ) ); ?>»</p>
					<cite><?php echo esc_html( liferuss_opt( 'hero_quote_cite' ) ); ?></cite>
				</blockquote>
			<?php endif; ?>
		</div>
	</div>
	<div class="hero-trust trust-bar">
		<p class="screen-reader-text"><?php echo esc_html( liferuss_t( 'trust_aria' ) ); ?></p>
		<div class="container trust-grid">
			<?php foreach ( (array) liferuss_opt( 'trust', array() ) as $item ) : ?>
				<article class="trust-item">
					<span class="icon-circle"><?php echo liferuss_icon( $item['icon'] ); ?></span>
					<div>
						<strong><?php echo esc_html( $item['title'] ); ?></strong>
						<p><?php echo esc_html( $item['text'] ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
