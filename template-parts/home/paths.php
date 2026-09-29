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
<section class="section path-home" id="study-paths">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_study' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'paths', liferuss_t( 'path_home_title' ) ) ); ?></h2>
		</header>
		<div class="path-children">
			<?php foreach ( array_merge( liferuss_path_links(), liferuss_service_home_links() ) as $link ) : ?>
				<a class="path-child" href="<?php echo esc_url( liferuss_url( $link['url'] ) ); ?>">
					<strong><?php echo esc_html( $link['title'] ); ?></strong>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
