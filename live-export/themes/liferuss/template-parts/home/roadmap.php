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
<section class="section roadmap-section" id="roadmap">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'roadmap_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'roadmap', liferuss_opt( 'roadmap_title' ) ) ); ?></h2>
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
