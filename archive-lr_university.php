<?php
/**
 * University archive with filters.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$filters = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::filters_from_request() : array();
$page    = liferuss_catalog_paged();
$result  = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::universities( $filters, $page ) : array( 'items' => array(), 'total' => 0, 'pages' => 1 );
$cities  = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::options( 'lr_cities' ) : array();
$fields  = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::options( 'lr_fields' ) : array();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1>دانشگاه‌های روسیه</h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<div class="section">
	<div class="container">
		<form class="lr-filters" id="lr-uni-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'lr_university' ) ); ?>">
			<label>شهر
				<select name="city">
					<option value="">همه</option>
					<?php foreach ( $cities as $city ) : ?>
						<option value="<?php echo esc_attr( $city['slug'] ); ?>" <?php selected( $filters['city'] ?? '', $city['slug'] ); ?>><?php echo esc_html( $city['name_fa'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>رشته
				<select name="field">
					<option value="">همه</option>
					<?php foreach ( $fields as $field ) : ?>
						<option value="<?php echo esc_attr( $field['slug'] ); ?>" <?php selected( $filters['field'] ?? '', $field['slug'] ); ?>><?php echo esc_html( $field['name_fa'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>مقطع
				<select name="degree">
					<option value="">همه</option>
					<?php foreach ( array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ) as $degree ) : ?>
						<option value="<?php echo esc_attr( $degree ); ?>" <?php selected( $filters['degree'] ?? '', $degree ); ?>><?php echo esc_html( liferuss_catalog_degree( $degree ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>زبان
				<select name="lang">
					<option value="">همه</option>
					<?php foreach ( array( 'ru', 'en', 'ru_en' ) as $lang ) : ?>
						<option value="<?php echo esc_attr( $lang ); ?>" <?php selected( $filters['lang'] ?? '', $lang ); ?>><?php echo esc_html( liferuss_catalog_lang( $lang ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>شهریه از (دلار)
				<input type="number" name="tuition_min" min="0" inputmode="numeric" value="<?php echo isset( $filters['tuition_min'] ) ? esc_attr( (string) $filters['tuition_min'] ) : ''; ?>">
			</label>
			<label>تا
				<input type="number" name="tuition_max" min="0" inputmode="numeric" value="<?php echo isset( $filters['tuition_max'] ) ? esc_attr( (string) $filters['tuition_max'] ) : ''; ?>">
			</label>
			<label>رتبه جهانی تا
				<input type="number" name="rank" min="1" inputmode="numeric" value="<?php echo isset( $filters['rank'] ) ? esc_attr( (string) $filters['rank'] ) : ''; ?>">
			</label>
			<label class="lr-check"><input type="checkbox" name="ministry" value="approved" <?php checked( ( $filters['ministry'] ?? '' ), 'approved' ); ?>> تأیید وزارتخانه</label>
			<button class="btn btn-gold" type="submit">اعمال فیلتر</button>
		</form>
		<?php if ( (int) $result['total'] > 0 ) : ?>
			<p class="lr-count"><?php echo esc_html( (string) (int) $result['total'] ); ?> دانشگاه</p>
		<?php endif; ?>
		<?php liferuss_catalog_cards( $result['items'] ); ?>
		<?php liferuss_catalog_pager( $page, (int) $result['pages'] ); ?>
	</div>
</div>
<?php
get_footer();
