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
<section class="section costs-section" id="costs">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'costs_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'costs', liferuss_opt( 'costs_title' ) ) ); ?></h2>
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
