<?php
/**
 * Template Name: دانشگاه‌ها
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'uni_page_eye' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'uni_page_h1' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'uni_page_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<section class="section">
	<div class="container uni-page-grid">
		<?php foreach ( liferuss_universities() as $uni ) : ?>
			<article class="uni-card">
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
			</article>
		<?php endforeach; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
