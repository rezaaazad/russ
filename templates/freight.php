<?php
/**
 * Template Name: باربری و ارسال
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php if ( liferuss_section_on( 'freight_hero_enabled' ) ) : ?>
<section class="landing-hero" id="freight-hero">
	<div class="container landing-hero-grid">
		<div class="landing-hero-copy">
			<?php if ( liferuss_opt( 'freight_hero_eyebrow' ) ) : ?>
				<p class="eyebrow hero-badge"><?php echo esc_html( liferuss_opt( 'freight_hero_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h1><?php echo liferuss_accent_headline( liferuss_opt( 'freight_hero_headline' ), liferuss_opt( 'freight_hero_accent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<p class="hero-lead landing-lead"><?php echo esc_html( liferuss_opt( 'freight_hero_lead' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-gold js-scroll-consult" href="<?php echo esc_url( liferuss_cta_url( liferuss_opt( 'freight_hero_cta_link' ) ) ); ?>">
					<?php echo esc_html( liferuss_opt( 'freight_hero_cta_text' ) ); ?>
					<?php echo liferuss_icon( 'arrow' ); ?>
				</a>
			</div>
		</div>
		<figure class="landing-hero-visual">
			<?php
			liferuss_the_image(
				array(
					'id'       => liferuss_opt( 'freight_hero_image_id' ),
					'fallback' => liferuss_opt( 'freight_hero_image', 'st-basil.jpg' ),
					'alt'      => liferuss_opt( 'freight_hero_headline' ),
					'width'    => 720,
					'height'   => 480,
					'size'     => 'liferuss-wide',
					'lazy'     => false,
					'priority' => true,
					'class'    => 'landing-hero-photo',
				)
			);
			?>
			<div class="landing-route" aria-hidden="true">
				<span class="ir-flag"><span></span><span></span><span></span></span>
				<span class="route-line"></span>
				<span class="route-label">IRAN — RUSSIA</span>
				<span class="route-line"></span>
				<span class="ru-flag"><span></span><span></span><span></span></span>
			</div>
		</figure>
	</div>
	<div class="landing-trust" aria-label="<?php echo esc_attr( liferuss_t( 'trust_aria' ) ); ?>">
		<div class="container trust-grid">
			<?php foreach ( (array) liferuss_opt( 'freight_trust', array() ) as $item ) : ?>
				<article class="trust-item">
					<span class="icon-circle"><?php echo liferuss_icon( $item['icon'] ?? 'shield' ); ?></span>
					<div>
						<strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<p><?php echo esc_html( $item['text'] ?? '' ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( liferuss_section_on( 'freight_services_enabled' ) ) : ?>
<section class="section landing-services" id="freight-services">
	<div class="container">
		<div class="landing-photo-grid">
			<?php foreach ( liferuss_enabled_items( 'freight_services' ) as $card ) : ?>
				<article class="landing-photo-card">
					<figure>
						<?php
						liferuss_the_image(
							array(
								'id'       => $card['image_id'] ?? 0,
								'fallback' => $card['image'] ?? '',
								'alt'      => $card['title'] ?? '',
								'width'    => 480,
								'height'   => 280,
								'size'     => 'liferuss-card',
							)
						);
						?>
					</figure>
					<div class="landing-photo-body">
						<span class="icon-circle"><?php echo liferuss_icon( $card['icon'] ?? 'box' ); ?></span>
						<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( liferuss_section_on( 'freight_process_enabled' ) ) : ?>
<section class="section landing-process" id="freight-process">
	<div class="container">
		<header class="section-head">
			<h2><?php echo esc_html( liferuss_opt( 'freight_process_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'freight_process_subtitle' ) ); ?></p>
		</header>
		<ol class="roadmap landing-roadmap">
			<?php foreach ( (array) liferuss_opt( 'freight_process', array() ) as $step ) : ?>
				<li class="roadmap-step">
					<span class="step-num"><?php echo esc_html( $step['num'] ?? '' ); ?></span>
					<h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $step['text'] ?? '' ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php endif; ?>

<?php
if ( liferuss_section_on( 'freight_form_enabled' ) ) {
	get_template_part( 'template-parts/landing-form', null, array( 'prefix' => 'freight' ) );
}
?>

<?php if ( liferuss_section_on( 'freight_items_enabled' ) ) : ?>
<section class="section landing-gallery" id="freight-items">
	<div class="container">
		<header class="section-head">
			<h2><?php echo esc_html( liferuss_opt( 'freight_items_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'freight_items_subtitle' ) ); ?></p>
		</header>
		<div class="landing-circles">
			<?php foreach ( (array) liferuss_opt( 'freight_items', array() ) as $item ) : ?>
				<figure class="landing-circle">
					<?php
					liferuss_the_image(
						array(
							'id'       => $item['image_id'] ?? 0,
							'fallback' => $item['image'] ?? '',
							'alt'      => $item['title'] ?? '',
							'width'    => 160,
							'height'   => 160,
							'size'     => 'thumbnail',
						)
					);
					?>
					<figcaption><?php echo esc_html( $item['title'] ?? '' ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( liferuss_opt( 'freight_items_notice' ) ) : ?>
			<p class="landing-notice">
				<?php echo liferuss_icon( 'info' ); ?>
				<span><?php echo esc_html( liferuss_opt( 'freight_items_notice' ) ); ?></span>
			</p>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php
if ( liferuss_section_on( 'freight_cta_enabled' ) ) {
	get_template_part( 'template-parts/landing-cta', null, array( 'prefix' => 'freight' ) );
}
?>

<?php
get_footer();
