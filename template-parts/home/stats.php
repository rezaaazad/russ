<?php
/**
 * Standalone stats block. Off by default so the stat card stays inside stories.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="section" id="stats">
	<div class="container">
		<?php $heading = liferuss_home_heading( 'stats', '' ); ?>
		<?php if ( $heading ) : ?>
			<header class="section-head"><h2><?php echo esc_html( $heading ); ?></h2></header>
		<?php endif; ?>
		<aside class="stat-card">
			<p class="screen-reader-text"><?php echo esc_html( liferuss_t( 'stories_aria' ) ); ?></p>
			<div class="ru-flag" aria-hidden="true"><span></span><span></span><span></span></div>
			<p class="stat-plus"><?php echo esc_html( liferuss_opt( 'stat_value' ) ); ?></p>
			<p><?php echo esc_html( liferuss_opt( 'stat_text' ) ); ?></p>
		</aside>
	</div>
</section>
