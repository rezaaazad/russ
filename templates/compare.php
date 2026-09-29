<?php
/**
 * University comparison.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$data = class_exists( '\LifeRuss\Core\Compare\Set' ) ? \LifeRuss\Core\Compare\Set::current() : array( 'ok' => false, 'error' => 'مقایسه در دسترس نیست.' );
$slug = '';
if ( ! empty( $data['slugs'] ) && is_array( $data['slugs'] ) ) {
	$slug = implode( ',', $data['slugs'] );
}
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1><?php echo esc_html( (string) ( $data['title'] ?? 'مقایسه دانشگاه‌ها' ) ); ?></h1>
		<?php if ( is_user_logged_in() && $slug ) : ?>
			<form method="post" action="<?php echo esc_url( liferuss_url( '/account/saved/' ) ); ?>">
				<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
				<input type="hidden" name="lr_account_action" value="save_uni">
				<input type="hidden" name="slugs" value="<?php echo esc_attr( $slug ); ?>">
				<button class="btn btn-ghost" type="submit">ذخیره در حساب</button>
			</form>
		<?php endif; ?>
	</div>
</header>
<article class="section">
	<div class="container" id="lr-compare-root" data-slugs="<?php echo esc_attr( $slug ); ?>" data-names="<?php echo esc_attr( (string) ( $data['names'] ?? '' ) ); ?>">
		<?php
		$catalog_total = 0;
		if ( function_exists( 'liferuss_catalog_ready' ) && liferuss_catalog_ready() ) {
			$catalog_list  = \LifeRuss\Core\Catalog\Query::universities( array(), 1 );
			$catalog_total = (int) ( $catalog_list['total'] ?? 0 );
		}
		?>
		<?php if ( $catalog_total < 1 ) : ?>
			<?php liferuss_empty_catalog( 'دانشگاهی برای مقایسه منتشر نشده' ); ?>
		<?php elseif ( empty( $data['ok'] ) ) : ?>
			<div class="lr-empty-state">
				<h2>مقایسه دانشگاه</h2>
				<p><?php echo esc_html( (string) ( $data['error'] ?? 'دو تا چهار دانشگاه را انتخاب کنید.' ) ); ?></p>
				<p><a class="btn btn-gold" href="<?php echo esc_url( get_post_type_archive_link( 'lr_university' ) ); ?>">انتخاب از فهرست دانشگاه‌ها</a></p>
			</div>
		<?php else : ?>
			<?php if ( ! empty( $data['intro'] ) ) : ?>
				<div class="prose"><?php echo wp_kses_post( wpautop( (string) $data['intro'] ) ); ?></div>
			<?php endif; ?>
			<form class="lr-compare-filters" method="get" action="<?php echo esc_url( ! empty( $data['pretty'] ) && ! empty( $data['page']['slug'] ) ? liferuss_url( '/compare/' . $data['page']['slug'] . '/' ) : liferuss_url( '/compare/' ) ); ?>">
				<?php if ( empty( $data['pretty'] ) ) : ?>
					<input type="hidden" name="u" value="<?php echo esc_attr( $slug ); ?>">
				<?php endif; ?>
				<label>
					<span>رشته</span>
					<select name="field">
						<option value="">همه</option>
						<?php foreach ( (array) ( $data['fields'] ?? array() ) as $field_slug => $field_name ) : ?>
							<option value="<?php echo esc_attr( (string) $field_slug ); ?>" <?php selected( (string) ( $data['field'] ?? '' ), (string) $field_slug ); ?>><?php echo esc_html( (string) $field_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span>مقطع</span>
					<select name="degree">
						<option value="">همه</option>
						<?php foreach ( (array) ( $data['degrees'] ?? array() ) as $degree_slug => $degree_name ) : ?>
							<option value="<?php echo esc_attr( (string) $degree_slug ); ?>" <?php selected( (string) ( $data['degree'] ?? '' ), (string) $degree_slug ); ?>><?php echo esc_html( (string) $degree_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button class="btn btn-gold" type="submit">نمایش شهریه</button>
			</form>
			<div class="lr-compare-scroll">
				<table class="lr-compare">
					<thead>
						<tr>
							<th scope="col">مورد</th>
							<?php foreach ( (array) $data['columns'] as $column ) : ?>
								<th scope="col"><a href="<?php echo esc_url( (string) get_permalink( (int) $column['post_id'] ) ); ?>"><?php echo esc_html( (string) $column['name_fa'] ); ?></a></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( (array) $data['rows'] as $row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( (string) $row['label'] ); ?></th>
								<?php foreach ( (array) $row['cells'] as $cell ) : ?>
									<td><?php echo esc_html( (string) $cell ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ( ! empty( $data['faq'] ) ) : ?>
				<h2>پرسش‌های متداول</h2>
				<?php foreach ( $data['faq'] as $faq ) : ?>
					<details class="lr-faq"><summary><?php echo esc_html( (string) $faq['q'] ); ?></summary><p><?php echo esc_html( (string) $faq['a'] ); ?></p></details>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php
			$GLOBALS['liferuss_consult_university'] = (string) ( $data['names'] ?? '' );
			get_template_part( 'template-parts/consult-form' );
			liferuss_compare_schema( $data );
			?>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
