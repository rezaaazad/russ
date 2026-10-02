<?php
/**
 * Field × city page. Served only when at least two universities match.
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
$field = class_exists( '\LifeRuss\Core\Repositories\Repository' ) ? \LifeRuss\Core\Repositories\Repository::for( 'fields' )->find_by( 'post_id', get_the_ID() ) : null;
$city  = class_exists( '\LifeRuss\Core\Repositories\Repository' ) ? \LifeRuss\Core\Repositories\Repository::for( 'cities' )->find_by( 'slug', sanitize_title( (string) get_query_var( 'lr_field_city' ) ) ) : null;
$rows  = ( $field && $city ) ? \LifeRuss\Core\Seo\Routes::pair_rows( (int) $field['id'], (int) $city['id'] ) : array();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( (string) ( $city['name_fa'] ?? '' ) ); ?></p>
		<h1><?php the_title(); ?> در <?php echo esc_html( (string) ( $city['name_fa'] ?? '' ) ); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container">
		<?php if ( $field ) : ?>
			<?php \LifeRuss\Core\Seo\Links::freshness( 'field', (int) $field['id'] ); ?>
		<?php endif; ?>
		<h2>دانشگاه‌ها</h2>
		<ul class="lr-facts">
			<?php foreach ( $rows as $row ) : ?>
				<li>
					<a href="<?php echo esc_url( home_url( '/universities/' . rawurlencode( (string) $row['slug'] ) . '/' . rawurlencode( (string) ( $field['slug'] ?? '' ) ) . '/' ) ); ?>"><?php echo esc_html( (string) $row['name_fa'] ); ?></a>
					<?php if ( is_numeric( $row['tuition'] ?? null ) ) : ?>
						— <?php echo esc_html( number_format_i18n( (float) $row['tuition'] ) . ' ' . (string) $row['currency'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p><a href="<?php echo esc_url( (string) get_permalink() ); ?>">همهٔ شهرهای این رشته</a></p>
	</div>
</article>
<?php
get_footer();
