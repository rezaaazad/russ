<?php
/**
 * Homepage v2: latest three articles. Hidden when the magazine is empty.
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
		'posts_per_page'         => 3,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'ignore_sticky_posts'    => true,
	)
);
if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	return;
}
$rotation = array( 'saint-petersburg', 'moscow-night', 'saint-basil' );
$n        = 0;
$btn      = liferuss_opt( 'hv2_blog_btn' );
?>
<section class="section hv2-section hv2-blog" id="latest-blog" aria-labelledby="hv2-blog-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-blog-title"><?php echo esc_html( liferuss_home_heading( 'blog', liferuss_opt( 'hv2_blog_title' ) ) ); ?></h2>
		</header>
		<div class="hv2-grid3">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<article class="hv2-card hv2-post">
					<a class="hv2-card__link" href="<?php the_permalink(); ?>">
						<figure class="hv2-card__media">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail(
									'liferuss-card',
									array(
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 640px) 92vw, (max-width: 1000px) 46vw, 360px',
									)
								);
							} else {
								liferuss_hv2_photo( 0, $rotation[ $n % count( $rotation ) ], '', '(max-width: 640px) 92vw, 360px' );
							}
							?>
						</figure>
						<div class="hv2-card__body">
							<time class="hv2-card__meta" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<h3><?php the_title(); ?></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
						</div>
					</a>
				</article>
				<?php
				++$n;
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php if ( $btn ) : ?>
			<p class="hv2-more-row"><a class="btn btn-ghost" href="<?php echo esc_url( liferuss_url( '/blog/' ) ); ?>"><?php echo esc_html( $btn ); ?> <?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
		<?php endif; ?>
	</div>
</section>
