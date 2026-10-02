<?php
/**
 * University × field program page.
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
$post_id = (int) get_the_ID();
$slug    = sanitize_title( (string) get_query_var( 'lr_program' ) );
$row     = class_exists( '\LifeRuss\Core\Seo\Routes' ) ? \LifeRuss\Core\Seo\Routes::program_row( $post_id, $slug ) : null;
$field   = $row && isset( $row['field'] ) && is_array( $row['field'] ) ? $row['field'] : array();
$uni     = $row && isset( $row['university'] ) && is_array( $row['university'] ) ? $row['university'] : array();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( (string) ( $uni['name_fa'] ?? '' ) ); ?></p>
		<h1><?php echo esc_html( (string) ( $field['name_fa'] ?? get_the_title() ) ); ?></h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container lr-single">
		<?php if ( ! $row ) : ?>
			<p>این ترکیب دانشگاه و رشته دادهٔ واقعی ندارد.</p>
		<?php else : ?>
			<?php \LifeRuss\Core\Seo\Links::freshness( 'program', (int) $row['id'] ); ?>
			<ul class="lr-facts">
				<li><?php echo esc_html( liferuss_catalog_degree( (string) $row['degree'] ) ); ?></li>
				<li><?php echo esc_html( liferuss_catalog_lang( (string) $row['language'] ) ); ?></li>
				<?php if ( '' !== (string) $row['academic_year'] ) : ?>
					<li>سال تحصیلی <?php echo esc_html( \LifeRuss\Core\Seo\Facts::display_year( (string) $row['academic_year'] ) ); ?></li>
				<?php endif; ?>
				<?php if ( is_numeric( $row['tuition'] ?? null ) ) : ?>
					<li>شهریه <?php echo esc_html( number_format_i18n( (float) $row['tuition'] ) . ' ' . (string) $row['currency'] ); ?></li>
				<?php endif; ?>
			</ul>
			<nav class="lr-auto-links" aria-label="پیوندهای رشته">
				<h2>در این صفحه</h2>
				<ul>
					<li><a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">صفحهٔ دانشگاه</a></li>
					<?php if ( ! empty( $field['post_id'] ) ) : ?>
						<li><a href="<?php echo esc_url( (string) get_permalink( (int) $field['post_id'] ) ); ?>">صفحهٔ رشته</a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( home_url( '/medicine/' ) ); ?>">پزشکی و دندانپزشکی روسیه</a></li>
				</ul>
			</nav>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
