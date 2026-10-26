<?php
/**
 * Latest published posts. Hidden when the magazine has no posts.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$query = new WP_Query(
	array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => 4,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'ignore_sticky_posts'    => true,
	)
);

if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="section lr-articles" id="latest-blog">
	<div class="container">
		<header class="section-head section-head-split">
			<h2><?php echo esc_html( liferuss_home_heading( 'blog', liferuss_t( 'crumb_blog' ) ) ); ?></h2>
			<a class="text-link" href="<?php echo esc_url( liferuss_url( '/blog/' ) ); ?>"><?php echo esc_html( liferuss_t( 'crumb_blog' ) ); ?></a>
		</header>
		<div class="lr-snap">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<article class="lr-article">
					<a class="lr-article__link" href="<?php the_permalink(); ?>">
						<figure class="lr-article__media">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail(
									'liferuss-card',
									array(
										'alt'      => wp_strip_all_tags( get_the_title() ),
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
							}
							?>
						</figure>
						<h3><?php the_title(); ?></h3>
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</a>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
