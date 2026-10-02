<?php
/**
 * Single university.
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
$post_id = get_the_ID();
$row     = liferuss_catalog_ready() ? \LifeRuss\Core\Catalog\Query::university( (string) get_post_field( 'post_name', $post_id ) ) : null;
$faqs    = liferuss_catalog_faqs( $post_id );
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( $row && ! empty( $row['city']['name_fa'] ) ? $row['city']['name_fa'] : liferuss_brand() ); ?></p>
		<h1><?php the_title(); ?></h1>
		<?php if ( $row ) : ?>
			<p class="lr-uni-actions">
				<button type="button" class="btn btn-ghost btn-ghost-light lr-compare-add" data-slug="<?php echo esc_attr( (string) $row['slug'] ); ?>" data-name="<?php echo esc_attr( (string) $row['name_fa'] ); ?>" aria-pressed="false">مقایسه</button>
				<?php if ( is_user_logged_in() ) : ?>
					<form method="post" action="<?php echo esc_url( liferuss_url( '/account/saved/' ) ); ?>">
						<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
						<input type="hidden" name="lr_account_action" value="save_uni">
						<input type="hidden" name="slug" value="<?php echo esc_attr( (string) $row['slug'] ); ?>">
						<button class="btn btn-ghost btn-ghost-light" type="submit">ذخیره در حساب</button>
					</form>
				<?php else : ?>
					<a class="btn btn-ghost btn-ghost-light" href="<?php echo esc_url( liferuss_url( '/account/' ) ); ?>">ذخیره در حساب</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container lr-single">
		<?php if ( ! $row ) : ?>
			<p>این دانشگاه هنوز منتشر نشده است.</p>
		<?php else : ?>
			<ul class="lr-facts">
				<?php if ( ! empty( $row['founded_year'] ) ) : ?><li>تأسیس <?php echo esc_html( liferuss_local_digits( (string) $row['founded_year'] ) ); ?></li><?php endif; ?>
				<?php if ( 'state' === $row['ownership'] ) : ?><li>دولتی</li><?php elseif ( 'private' === $row['ownership'] ) : ?><li>خصوصی</li><?php endif; ?>
				<?php if ( (int) get_post_meta( $post_id, '_lr_students_total', true ) ) : ?><li><?php echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_lr_students_total', true ) ) ); ?> دانشجو</li><?php endif; ?>
				<?php $usd = liferuss_catalog_usd( $row['min_tuition_usd'], true ); if ( $usd ) : ?><li>شهریه از <?php echo esc_html( $usd ); ?></li><?php endif; ?>
				<?php if ( ! empty( $row['best_world_rank'] ) ) : ?><li>بهترین رتبه جهانی <?php echo esc_html( liferuss_local_digits( (string) $row['best_world_rank'] ) ); ?></li><?php endif; ?>
				<?php if ( 'approved' === $row['health_ministry_status'] ) : ?><li>تأیید وزارت بهداشت</li><?php endif; ?>
				<?php if ( 'approved' === $row['science_ministry_status'] ) : ?><li>تأیید وزارت علوم</li><?php endif; ?>
				<?php if ( ! empty( $row['has_dormitory'] ) ) : ?><li>خوابگاه</li><?php endif; ?>
			</ul>
			<?php if ( get_the_content() ) : ?>
				<div class="prose"><?php the_content(); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $row['rankings'] ) ) : ?>
				<h2>رتبه‌بندی</h2>
				<?php liferuss_table_scroll_open(); ?>
				<table class="lr-table">
					<thead><tr><th>مرجع</th><th>محدوده</th><th>رتبه</th><th>سال</th></tr></thead>
					<tbody>
					<?php foreach ( $row['rankings'] as $rank ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $rank['provider_name'] ); ?></td>
							<td><?php echo esc_html( (string) $rank['scope'] ); ?></td>
							<td><?php echo esc_html( (string) ( $rank['rank_value'] ? $rank['rank_value'] : $rank['rank_band'] ) ); ?></td>
							<td><?php echo esc_html( (string) $rank['year'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php liferuss_table_scroll_close(); ?>
			<?php endif; ?>
			<?php if ( ! empty( $row['programs'] ) ) : ?>
				<h2>رشته‌ها و شهریه</h2>
				<?php liferuss_table_scroll_open(); ?>
				<table class="lr-table">
					<thead><tr><th>رشته</th><th>مقطع</th><th>زبان</th><th>مدت</th><th>شهریه</th><th>دلار</th></tr></thead>
					<tbody>
					<?php foreach ( $row['programs'] as $program ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $program['field_name'] ); ?></td>
							<td><?php echo esc_html( liferuss_catalog_degree( (string) $program['degree'] ) ); ?></td>
							<td><?php echo esc_html( liferuss_catalog_lang( (string) $program['language'] ) ); ?></td>
							<td><?php echo esc_html( (string) $program['duration_years'] ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $program['tuition'] ) . ' ' . (string) $program['currency'] ); ?></td>
							<td><?php echo esc_html( liferuss_catalog_usd( $program['amount_usd'], true ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php liferuss_table_scroll_close(); ?>
			<?php endif; ?>
			<?php if ( $row && class_exists( '\LifeRuss\Core\Scholarships\Store' ) ) : ?>
				<?php $scholarships = \LifeRuss\Core\Scholarships\Store::for_university( (int) $row['id'] ); ?>
				<?php if ( $scholarships ) : ?>
					<h2>بورسیه‌ها</h2>
					<ul class="lr-facts">
						<?php foreach ( $scholarships as $scholarship ) : ?>
							<li><a href="<?php echo esc_url( (string) get_permalink( (int) $scholarship['post_id'] ) ); ?>"><?php echo esc_html( get_the_title( (int) $scholarship['post_id'] ) ); ?></a>
								<?php if ( '' !== (string) $scholarship['deadline'] ) : ?>
									— <?php echo esc_html( (string) $scholarship['deadline'] ); ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( ! empty( $row['city'] ) ) : ?>
				<h2>شهر</h2>
				<p><a href="<?php echo esc_url( get_permalink( (int) $row['city']['post_id'] ) ); ?>"><?php echo esc_html( (string) $row['city']['name_fa'] ); ?></a>
					<?php
					if ( ! empty( $row['city']['living_cost_min'] ) ) {
						echo ' — هزینه زندگی ماهانه ' . esc_html( number_format_i18n( (float) $row['city']['living_cost_min'] ) );
						if ( ! empty( $row['city']['living_cost_max'] ) ) {
							echo ' تا ' . esc_html( number_format_i18n( (float) $row['city']['living_cost_max'] ) );
						}
						echo ' ' . esc_html( (string) $row['city']['currency'] );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
			$gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_lr_gallery', true ) ) ) );
			if ( $gallery ) :
				echo '<h2>گالری</h2><div class="lr-gallery">';
				foreach ( $gallery as $id ) {
					echo wp_get_attachment_image( $id, 'medium_large' );
				}
				echo '</div>';
			endif;
			if ( $faqs ) :
				echo '<h2>پرسش‌های متداول</h2>';
				foreach ( $faqs as $faq ) {
					echo '<details class="lr-faq"><summary>' . esc_html( $faq['q'] ) . '</summary><p>' . esc_html( $faq['a'] ) . '</p></details>';
				}
			endif;
			$related = \LifeRuss\Core\Catalog\Query::related( (int) $row['city_id'], (int) $row['id'] );
			if ( $related ) {
				echo '<h2>دانشگاه‌های مرتبط</h2>';
				liferuss_catalog_cards( array_slice( $related, 0, 4 ) );
			}
			$GLOBALS['liferuss_consult_university'] = $row['name_fa'];
			get_template_part( 'template-parts/consult-form' );
			?>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
