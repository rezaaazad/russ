<?php
/**
 * Homepage — لایف روس.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

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
			<h1><?php echo esc_html( liferuss_opt( 'hero_headline' ) ); ?></h1>
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
	<div class="hero-trust trust-bar" aria-label="<?php echo esc_attr( liferuss_t( 'trust_aria' ) ); ?>">
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

<?php if ( '1' === (string) liferuss_opt( 'services_enabled', '1' ) ) : ?>
<section class="section services-section" id="services">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'services_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'services_title' ) ); ?></h2>
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

<?php if ( liferuss_section_on( 'home_landings_enabled' ) ) : ?>
<section class="section home-landings-section" id="landings">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'home_landings_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'home_landings_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'home_landings_subtitle' ) ); ?></p>
		</header>
		<div class="home-landings-grid">
			<?php foreach ( liferuss_enabled_items( 'home_landings' ) as $card ) : ?>
				<article class="home-landing-card">
					<span class="icon-circle"><?php echo liferuss_icon( $card['icon'] ?? 'globe' ); ?></span>
					<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
					<a class="btn btn-gold" href="<?php echo esc_url( liferuss_cta_url( $card['link'] ?? '' ) ); ?>">
						<?php echo esc_html( $card['cta'] ?? '' ); ?>
						<?php echo liferuss_icon( 'arrow' ); ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section universities-section" id="universities">
	<div class="container">
		<header class="section-head section-head-split">
			<div>
				<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'universities_eyebrow' ) ); ?></p>
				<h2><?php echo esc_html( liferuss_opt( 'universities_title' ) ); ?></h2>
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

<section class="section majors-section" id="majors">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'majors_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'majors_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'majors_subtitle' ) ); ?></p>
		</header>
		<div class="majors-grid">
			<?php foreach ( liferuss_majors() as $major ) : ?>
				<?php $tag = ! empty( $major['link'] ) ? 'a' : 'article'; ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="major-card"<?php echo ! empty( $major['link'] ) ? ' href="' . esc_url( liferuss_cta_url( $major['link'] ) ) . '"' : ''; ?>>
					<span class="major-orb"><?php echo liferuss_icon( $major['icon'] ); ?></span>
					<h3><?php echo esc_html( $major['title'] ); ?></h3>
				</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section costs-section" id="costs">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'costs_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'costs_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'costs_subtitle' ) ); ?></p>
		</header>
		<div class="costs-grid">
			<?php foreach ( liferuss_costs() as $cost ) : ?>
				<article class="cost-card">
					<span class="icon-circle"><?php echo liferuss_icon( $cost['icon'] ); ?></span>
					<h3><?php echo esc_html( $cost['title'] ); ?></h3>
					<p class="cost-value"><?php echo esc_html( $cost['value'] ); ?></p>
					<p class="cost-note"><?php echo esc_html( $cost['note'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section roadmap-section" id="roadmap">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'roadmap_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'roadmap_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'roadmap_subtitle' ) ); ?></p>
		</header>
		<ol class="roadmap">
			<?php foreach ( liferuss_roadmap() as $step ) : ?>
				<li class="roadmap-step">
					<span class="step-num"><?php echo esc_html( $step['num'] ); ?></span>
					<h3><?php echo esc_html( $step['title'] ); ?></h3>
					<p><?php echo esc_html( $step['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section stories-section" id="stories">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'testimonials_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_opt( 'testimonials_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'testimonials_subtitle' ) ); ?></p>
		</header>
		<div class="stories-grid">
			<?php foreach ( liferuss_testimonials() as $story ) : ?>
				<article class="story-card">
					<figure class="story-avatar">
						<?php
						liferuss_the_image(
							array(
								'id'       => $story['image_id'] ?? 0,
								'fallback' => $story['image'] ?? '',
								'alt'      => $story['name'],
								'width'    => 72,
								'height'   => 72,
								'size'     => 'thumbnail',
								'sizes'    => '72px',
							)
						);
						?>
					</figure>
					<?php echo liferuss_stars( $story['rating'] ?? 5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<blockquote>
						<p><?php echo esc_html( $story['quote'] ); ?></p>
					</blockquote>
					<p class="story-name"><?php echo esc_html( $story['name'] ); ?></p>
					<p class="story-meta"><?php echo esc_html( $story['meta'] ); ?></p>
				</article>
			<?php endforeach; ?>
			<aside class="stat-card" aria-label="<?php echo esc_attr( liferuss_t( 'stories_aria' ) ); ?>">
				<div class="ru-flag" aria-hidden="true"><span></span><span></span><span></span></div>
				<p class="stat-plus"><?php echo esc_html( liferuss_opt( 'stat_value' ) ); ?></p>
				<p><?php echo esc_html( liferuss_opt( 'stat_text' ) ); ?></p>
			</aside>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
