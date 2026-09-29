<?php
/**
 * City archive.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$page   = liferuss_catalog_paged();
$result = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::cities( $page ) : array( 'items' => array(), 'pages' => 1 );
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1>شهرها</h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<div class="section">
	<div class="container">
		<?php if ( empty( $result['items'] ) ) : ?>
			<?php liferuss_empty_catalog( 'شهری منتشر نشده است' ); ?>
		<?php else : ?>
		<div class="lr-cards">
			<?php foreach ( $result['items'] as $item ) : ?>
				<article class="lr-card"><div class="lr-card-body">
					<h2><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['name_fa'] ); ?></a></h2>
					<?php if ( ! empty( $item['living_cost_min'] ) ) : ?>
						<p class="lr-meta">هزینه زندگی از <?php echo esc_html( number_format_i18n( (float) $item['living_cost_min'] ) . ' ' . $item['currency'] ); ?></p>
					<?php endif; ?>
				</div></article>
			<?php endforeach; ?>
		</div>
		<?php liferuss_catalog_pager( $page, (int) $result['pages'] ); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
