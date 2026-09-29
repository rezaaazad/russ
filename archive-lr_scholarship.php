<?php
/**
 * Scholarship archive.
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
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_scholarships' ) ); ?></p>
		<h1><?php echo esc_html( liferuss_t( 'nav_scholarships' ) ); ?></h1>
		<p><?php echo esc_html( liferuss_t( 'path_scholarship_lead' ) ); ?></p>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<section class="section">
	<div class="container">
		<div class="lr-cards">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article class="lr-card">
						<div class="lr-card-body">
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( has_excerpt() ) : ?>
								<p class="lr-meta"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>
							<ul class="lr-facts">
								<?php
								$deadline = (string) get_post_meta( get_the_ID(), '_lr_deadline', true );
								$coverage = (string) get_post_meta( get_the_ID(), '_lr_coverage', true );
								?>
								<?php if ( $deadline ) : ?>
									<li><?php echo esc_html( $deadline ); ?></li>
								<?php endif; ?>
								<?php if ( $coverage ) : ?>
									<li><?php echo esc_html( $coverage ); ?></li>
								<?php endif; ?>
							</ul>
						</div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p class="lr-empty"><?php echo esc_html( liferuss_t( 'path_empty' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
