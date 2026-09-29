<?php
/**
 * Featured scholarships. Off by default.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$query = new WP_Query(
	array(
		'post_type'              => 'lr_scholarship',
		'post_status'            => 'publish',
		'posts_per_page'         => 4,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	)
);
?>
<section class="section" id="scholarships">
	<div class="container">
		<header class="section-head">
			<h2><?php echo esc_html( liferuss_home_heading( 'scholarships', 'بورسیه‌ها' ) ); ?></h2>
		</header>
		<div class="path-children">
			<?php
			if ( $query->have_posts() ) :
				while ( $query->have_posts() ) :
					$query->the_post();
					?>
					<a class="path-child" href="<?php the_permalink(); ?>"><strong><?php the_title(); ?></strong></a>
					<?php
				endwhile;
				wp_reset_postdata();
			endif;
			?>
			<a class="path-child" href="<?php echo esc_url( liferuss_url( '/scholarships/' ) ); ?>"><strong>بورسیه‌ها</strong></a>
		</div>
	</div>
</section>
