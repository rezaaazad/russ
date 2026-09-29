<?php
/**
 * Single city.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
if ( have_posts() ) {
	the_post();
}
$row = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::city( (string) get_post_field( 'post_name', get_the_ID() ) ) : null;
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow">شهر</p>
		<h1><?php the_title(); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container">
		<?php if ( $row ) : ?>
			<?php if ( ! empty( $row['living_cost_min'] ) ) : ?>
				<section class="lr-living">
					<h2>هزینه زندگی</h2>
					<p>ماهانه حدود <?php echo esc_html( number_format_i18n( (float) $row['living_cost_min'] ) ); ?>
						<?php if ( ! empty( $row['living_cost_max'] ) ) : ?>
							تا <?php echo esc_html( number_format_i18n( (float) $row['living_cost_max'] ) ); ?>
						<?php endif; ?>
						<?php echo esc_html( (string) $row['currency'] ); ?>
					</p>
					<?php if ( ! empty( $row['climate_summary'] ) ) : ?>
						<p><?php echo esc_html( (string) $row['climate_summary'] ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>
			<?php if ( get_the_content() ) : ?>
				<div class="prose"><?php the_content(); ?></div>
			<?php endif; ?>
			<h2>دانشگاه‌های این شهر</h2>
			<?php liferuss_catalog_cards( $row['universities'] ); ?>
		<?php else : ?>
			<p>این شهر هنوز منتشر نشده است.</p>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
