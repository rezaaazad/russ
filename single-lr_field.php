<?php
/**
 * Single field.
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
$row = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::field( (string) get_post_field( 'post_name', get_the_ID() ) ) : null;
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow">رشته</p>
		<h1><?php the_title(); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container">
		<?php if ( get_the_content() ) : ?>
			<div class="prose"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php if ( $row && ! empty( $row['universities'] ) ) : ?>
			<h2>دانشگاه‌هایی که این رشته را دارند</h2>
			<table class="lr-table">
				<thead><tr><th>دانشگاه</th><th>شهر</th><th>شهریه (دلار)</th></tr></thead>
				<tbody>
				<?php foreach ( $row['universities'] as $uni ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( $uni['url'] ); ?>"><?php echo esc_html( $uni['name_fa'] ); ?></a></td>
						<td><?php echo esc_html( (string) $uni['city_name'] ); ?></td>
						<td>
							<?php
							$min = liferuss_catalog_usd( $uni['min_usd'] );
							$max = liferuss_catalog_usd( $uni['max_usd'] );
							echo esc_html( $min . ( $max && $max !== $min ? ' – ' . $max : '' ) );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php elseif ( ! $row ) : ?>
			<p>این رشته هنوز منتشر نشده است.</p>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
