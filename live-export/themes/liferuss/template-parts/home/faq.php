<?php
/**
 * Homepage FAQ. Off by default; the JSON-LD questions stay in the head.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = array();
$query = new WP_Query(
	array(
		'post_type'              => 'lr_faq',
		'post_status'            => 'publish',
		'posts_per_page'         => 6,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	)
);
if ( $query->have_posts() ) {
	while ( $query->have_posts() ) {
		$query->the_post();
		$items[] = array(
			'q' => get_the_title(),
			'a' => wp_strip_all_tags( get_the_content() ),
		);
	}
	wp_reset_postdata();
}
if ( ! $items ) {
	$items = array(
		array( 'q' => liferuss_t( 'faq_q1' ), 'a' => liferuss_t( 'faq_a1' ) ),
		array( 'q' => liferuss_t( 'faq_q2' ), 'a' => liferuss_t( 'faq_a2' ) ),
		array( 'q' => liferuss_t( 'faq_q3' ), 'a' => liferuss_t( 'faq_a3' ) ),
	);
}
?>
<section class="section" id="faq">
	<div class="container">
		<header class="section-head">
			<h2><?php echo esc_html( liferuss_home_heading( 'faq', 'FAQ' ) ); ?></h2>
		</header>
		<div class="costs-grid">
			<?php foreach ( $items as $item ) : ?>
				<article class="cost-card">
					<h3><?php echo esc_html( $item['q'] ); ?></h3>
					<p><?php echo esc_html( $item['a'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
