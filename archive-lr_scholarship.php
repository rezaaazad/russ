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
		<?php if ( have_posts() || ! empty( $_GET ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<form class="lr-filters lr-compare-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'lr_scholarship' ) ); ?>">
			<label>
				<span>مقطع</span>
				<select name="degree">
					<option value="">همه</option>
					<?php foreach ( \LifeRuss\Core\Scholarships\Store::degrees() as $key => $label ) : ?>
						<?php if ( '' === $key ) { continue; } ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $_GET['degree'] ) ? sanitize_key( wp_unslash( (string) $_GET['degree'] ) ) : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span>پوشش</span>
				<select name="coverage">
					<option value="">همه</option>
					<?php foreach ( \LifeRuss\Core\Scholarships\Store::coverage_types() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $_GET['coverage'] ) ? sanitize_key( wp_unslash( (string) $_GET['coverage'] ) ) : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span>زبان</span>
				<select name="language">
					<option value="">همه</option>
					<?php foreach ( array( 'ru' => 'روسی', 'en' => 'انگلیسی', 'ru_en' => 'روسی و انگلیسی' ) as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $_GET['language'] ) ? sanitize_key( wp_unslash( (string) $_GET['language'] ) ) : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span>دانشگاه</span>
				<select name="university">
					<option value="0">همه</option>
					<?php foreach ( \LifeRuss\Core\Scholarships\Store::filter_universities() as $id => $name ) : ?>
						<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( isset( $_GET['university'] ) ? absint( $_GET['university'] ) : 0, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button class="btn btn-gold" type="submit">فیلتر</button>
		</form>
		<?php endif; ?>
		<div class="lr-cards">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<?php $fact = \LifeRuss\Core\Scholarships\Store::for_post( (int) get_the_ID() ); ?>
					<article class="lr-card">
						<div class="lr-card-body">
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( has_excerpt() ) : ?>
								<p class="lr-meta"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>
							<ul class="lr-facts">
								<?php if ( $fact && '' !== (string) $fact['field_name'] ) : ?>
									<li><?php echo esc_html( (string) $fact['field_name'] ); ?></li>
								<?php endif; ?>
								<?php if ( $fact && '' !== (string) $fact['deadline'] ) : ?>
									<li><?php echo esc_html( (string) $fact['deadline'] ); ?></li>
								<?php endif; ?>
								<?php if ( $fact && isset( \LifeRuss\Core\Scholarships\Store::coverage_types()[ (string) $fact['coverage_type'] ] ) ) : ?>
									<li><?php echo esc_html( \LifeRuss\Core\Scholarships\Store::coverage_types()[ (string) $fact['coverage_type'] ] . ( $fact['coverage_percent'] ? ' ' . (int) $fact['coverage_percent'] . '%' : '' ) ); ?></li>
								<?php endif; ?>
							</ul>
						</div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<?php liferuss_empty_catalog( liferuss_t( 'nav_scholarships' ) ); ?>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
