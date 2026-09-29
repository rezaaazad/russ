<?php
/**
 * Study-path icon grid. Service cards live in the services section only.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tiles = array(
	array( 'title' => liferuss_t( 'nav_study' ), 'url' => '/study-russia/', 'icon' => 'cap' ),
	array( 'title' => liferuss_t( 'nav_universities' ), 'url' => '/universities/', 'icon' => 'building' ),
	array( 'title' => liferuss_t( 'nav_podfak' ), 'url' => '/padfak/', 'icon' => 'book' ),
	array( 'title' => liferuss_t( 'nav_direct' ), 'url' => '/direct-course/', 'icon' => 'docs' ),
	array( 'title' => liferuss_t( 'nav_scholarships' ), 'url' => '/scholarships/', 'icon' => 'star' ),
	array( 'title' => liferuss_t( 'nav_immigration' ), 'url' => '/migration-russia/', 'icon' => 'passport' ),
	array( 'title' => liferuss_t( 'nav_language_course' ), 'url' => '/russian-language/', 'icon' => 'language' ),
	array( 'title' => liferuss_t( 'nav_costs' ), 'url' => '/costs/', 'icon' => 'wallet' ),
);
?>
<section class="section path-home" id="study-paths">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_study_menu' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'paths', liferuss_t( 'path_home_title' ) ) ); ?></h2>
		</header>
		<div class="path-grid">
			<?php foreach ( $tiles as $tile ) : ?>
				<a class="path-tile" href="<?php echo esc_url( liferuss_url( $tile['url'] ) ); ?>">
					<span class="icon-circle" aria-hidden="true"><?php echo liferuss_icon( $tile['icon'] ); ?></span>
					<strong><?php echo esc_html( $tile['title'] ); ?></strong>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
