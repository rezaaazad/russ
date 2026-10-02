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

$icons = array(
	'intro'     => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V6l8-3 8 3v13"/><path d="M12 22V10"/><path d="M8 13h8"/></svg>',
	'tuition'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/></svg>',
	'dorm'      => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11 12 4l9 7"/><path d="M6 10v9h12v-9"/><path d="M10 19v-5h4v5"/></svg>',
	'admission' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>',
	'moh'       => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 9 4.2-1.8 7-4.8 7-9V6z"/><path d="M9 12h6M12 9v6"/></svg>',
	'city'      => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.2"/></svg>',
	'rank'      => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 4h8v3a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 3M16 6h3a3 3 0 0 1-3 3"/><path d="M12 11v3M9 20h6l-1-4h-4z"/></svg>',
	'faq'       => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.2 2.4c-.8.3-1.2.9-1.2 1.6V14"/><path d="M12 17h.01"/></svg>',
	'aid'       => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M5 8h14l-2 11H7z"/></svg>',
);

/**
 * Echo a fact line for this card, falling back to the university profile.
 *
 * @param string $type Type.
 * @param int    $id   Id.
 * @param string $key  Fact key.
 * @param int    $uni  University id.
 */
$source = static function ( $type, $id, $key, $uni ) {
	if ( ! class_exists( '\LifeRuss\Core\Seo\Facts' ) ) {
		return;
	}
	$line = \LifeRuss\Core\Seo\Facts::line( (string) $type, (int) $id, (string) $key );
	if ( '' === $line && (int) $uni > 0 ) {
		$line = \LifeRuss\Core\Seo\Facts::line( 'university', (int) $uni );
	}
	if ( '' !== $line ) {
		echo '<p class="lr-fresh">' . esc_html( $line ) . '</p>';
	}
};
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
<article class="section lr-uni">
	<div class="container">
		<?php if ( ! $row ) : ?>
			<p>این دانشگاه هنوز منتشر نشده است.</p>
		<?php else : ?>
			<?php
			$uni_id   = (int) $row['id'];
			$programs = isset( $row['programs'] ) && is_array( $row['programs'] ) ? $row['programs'] : array();
			$picked   = null;
			$best     = -1;
			foreach ( $programs as $program ) {
				$start = class_exists( '\LifeRuss\Core\Seo\Facts' ) ? \LifeRuss\Core\Seo\Facts::year_start( (string) $program['academic_year'] ) : 0;
				if ( null === $picked || $start > $best || ( $start === $best && (float) $program['tuition'] < (float) $picked['tuition'] ) ) {
					$picked = $program;
					$best   = $start;
				}
			}
			$langs = array();
			foreach ( $programs as $program ) {
				$label = liferuss_catalog_lang( (string) $program['language'] );
				if ( '' !== $label ) {
					$langs[ $label ] = $label;
				}
			}
			$moh       = (string) $row['health_ministry_status'];
			$moh_label = 'approved' === $moh ? 'تأیید شده' : ( 'rejected' === $moh ? 'تأیید نشده' : 'ثبت نشده' );
			$moh_class = 'approved' === $moh ? 'is-ok' : ( 'rejected' === $moh ? 'is-bad' : '' );
			$rank      = ! empty( $row['best_world_rank'] ) ? liferuss_local_digits( (string) $row['best_world_rank'] ) : '';
			if ( '' === $rank && ! empty( $row['rankings'][0] ) ) {
				$rank = (string) ( $row['rankings'][0]['rank_value'] ? $row['rankings'][0]['rank_value'] : $row['rankings'][0]['rank_band'] );
				$rank = liferuss_local_digits( $rank );
			}
			$dorm_label = 'ثبت نشده';
			if ( ! empty( $row['dorm']['amount_min'] ) ) {
				$dorm_label = liferuss_catalog_amount( $row['dorm']['amount_min'], (string) $row['dorm']['currency'] );
			} elseif ( ! empty( $row['has_dormitory'] ) ) {
				$dorm_label = 'دارد';
			}
			$tuition_label = $picked ? liferuss_catalog_amount( $picked['tuition'], (string) $picked['currency'], $picked['amount_usd'] ?? null ) : '';
			$toc           = array(
				'intro'     => 'معرفی',
				'tuition'   => 'شهریه',
				'dorm'      => 'خوابگاه',
				'admission' => 'پذیرش',
				'moh'       => 'وزارت بهداشت',
				'city'      => 'شهر',
			);
			if ( ! empty( $row['rankings'] ) ) {
				$toc['rankings'] = 'رتبه';
			}
			if ( $faqs ) {
				$toc['faq'] = 'پرسش‌ها';
			}
			$toc['compare-similar'] = 'مقایسه';
			$toc['related-links']   = 'پیوندها';
			?>
			<ul class="lr-fact-row">
				<li>
					<span>شهر</span>
					<strong>
						<?php if ( ! empty( $row['city']['post_id'] ) ) : ?>
							<a href="<?php echo esc_url( get_permalink( (int) $row['city']['post_id'] ) ); ?>"><?php echo esc_html( (string) $row['city']['name_fa'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( ! empty( $row['city']['name_fa'] ) ? (string) $row['city']['name_fa'] : '—' ); ?>
						<?php endif; ?>
					</strong>
				</li>
				<li>
					<span>شهریه امسال</span>
					<strong><?php echo esc_html( '' !== $tuition_label ? $tuition_label : '—' ); ?></strong>
					<?php if ( $picked && ! empty( $picked['academic_year'] ) && class_exists( '\LifeRuss\Core\Seo\Facts' ) ) : ?>
						<small><?php echo esc_html( \LifeRuss\Core\Seo\Facts::display_year( (string) $picked['academic_year'] ) ); ?></small>
					<?php endif; ?>
				</li>
				<li>
					<span>وزارت بهداشت</span>
					<strong><span class="lr-badge <?php echo esc_attr( $moh_class ); ?>"><?php echo esc_html( $moh_label ); ?></span></strong>
				</li>
				<li>
					<span>رتبه</span>
					<strong><?php echo esc_html( '' !== $rank ? $rank : '—' ); ?></strong>
				</li>
				<li>
					<span>زبان</span>
					<strong><?php echo esc_html( $langs ? implode( '، ', $langs ) : '—' ); ?></strong>
				</li>
				<li>
					<span>خوابگاه</span>
					<strong><?php echo esc_html( '' !== $dorm_label ? $dorm_label : '—' ); ?></strong>
				</li>
			</ul>
			<div class="lr-uni-layout">
				<nav class="lr-uni-toc" aria-label="فهرست صفحه">
					<?php foreach ( $toc as $anchor => $label ) : ?>
						<a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>
				<div class="lr-uni-main">
					<section class="lr-uni-card" id="intro">
						<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['intro']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>معرفی</h2></header>
						<ul class="lr-facts">
							<?php if ( ! empty( $row['founded_year'] ) ) : ?><li>تأسیس <?php echo esc_html( liferuss_local_digits( (string) $row['founded_year'] ) ); ?></li><?php endif; ?>
							<?php if ( 'state' === $row['ownership'] ) : ?><li>دولتی</li><?php elseif ( 'private' === $row['ownership'] ) : ?><li>خصوصی</li><?php endif; ?>
							<?php if ( (int) get_post_meta( $post_id, '_lr_students_total', true ) ) : ?><li><?php echo esc_html( liferuss_local_digits( number_format( (int) get_post_meta( $post_id, '_lr_students_total', true ) ) ) ); ?> دانشجو</li><?php endif; ?>
							<?php if ( 'approved' === $row['science_ministry_status'] ) : ?><li>تأیید وزارت علوم</li><?php endif; ?>
						</ul>
						<?php if ( get_the_content() ) : ?>
							<div class="prose"><?php the_content(); ?></div>
						<?php endif; ?>
						<?php $source( 'university', $uni_id, 'profile', 0 ); ?>
					</section>
					<section class="lr-uni-card" id="tuition">
						<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['tuition']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>شهریه</h2></header>
						<?php if ( $programs ) : ?>
							<?php liferuss_table_scroll_open(); ?>
							<table class="lr-table">
								<thead><tr><th>رشته</th><th>مقطع</th><th>زبان</th><th>مدت</th><th>سال تحصیلی</th><th>شهریه</th></tr></thead>
								<tbody>
								<?php foreach ( $programs as $program ) : ?>
									<tr>
										<td>
											<?php
											$field_slug = (string) ( $program['field_slug'] ?? '' );
											$program_url = '' !== $field_slug ? home_url( '/universities/' . rawurlencode( (string) $row['slug'] ) . '/' . rawurlencode( $field_slug ) . '/' ) : '';
											if ( $program_url ) {
												echo '<a href="' . esc_url( $program_url ) . '">' . esc_html( (string) $program['field_name'] ) . '</a>';
											} else {
												echo esc_html( (string) $program['field_name'] );
											}
											?>
										</td>
										<td><?php echo esc_html( liferuss_catalog_degree( (string) $program['degree'] ) ); ?></td>
										<td><?php echo esc_html( liferuss_catalog_lang( (string) $program['language'] ) ); ?></td>
										<td><?php echo esc_html( liferuss_local_digits( (string) $program['duration_years'] ) ); ?> سال</td>
										<td><?php echo esc_html( class_exists( '\LifeRuss\Core\Seo\Facts' ) ? \LifeRuss\Core\Seo\Facts::display_year( (string) $program['academic_year'] ) : (string) $program['academic_year'] ); ?></td>
										<td><?php echo esc_html( liferuss_catalog_amount( $program['tuition'], (string) $program['currency'], $program['amount_usd'] ?? null ) ); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<?php liferuss_table_scroll_close(); ?>
						<?php else : ?>
							<p>شهریهٔ سال جاری هنوز ثبت نشده است.</p>
						<?php endif; ?>
						<?php
						$tuition_source = 0;
						foreach ( $programs as $program ) {
							if ( class_exists( '\LifeRuss\Core\Seo\Facts' ) && '' !== \LifeRuss\Core\Seo\Facts::line( 'program', (int) $program['id'], 'tuition' ) ) {
								$tuition_source = (int) $program['id'];
								break;
							}
						}
						if ( $tuition_source ) {
							$source( 'program', $tuition_source, 'tuition', $uni_id );
						} else {
							$source( 'university', $uni_id, 'profile', 0 );
						}
						?>
					</section>
					<section class="lr-uni-card" id="dorm">
						<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['dorm']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>خوابگاه</h2></header>
						<?php if ( ! empty( $row['dorm'] ) ) : ?>
							<p><?php echo esc_html( liferuss_catalog_amount( $row['dorm']['amount_min'], (string) $row['dorm']['currency'] ) ); ?>
								<?php if ( ! empty( $row['dorm']['academic_year'] ) && class_exists( '\LifeRuss\Core\Seo\Facts' ) ) : ?>
									— <?php echo esc_html( \LifeRuss\Core\Seo\Facts::display_year( (string) $row['dorm']['academic_year'] ) ); ?>
								<?php endif; ?>
							</p>
						<?php elseif ( ! empty( $row['has_dormitory'] ) ) : ?>
							<p>خوابگاه دارد. هزینهٔ سال جاری هنوز ثبت نشده است.</p>
						<?php else : ?>
							<p>خوابگاه برای این دانشگاه ثبت نشده است.</p>
						<?php endif; ?>
						<?php $source( 'university', $uni_id, 'dorm', $uni_id ); ?>
					</section>
					<section class="lr-uni-card" id="admission">
						<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['admission']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>شرایط پذیرش</h2></header>
						<?php if ( $programs ) : ?>
							<ul class="lr-admit">
								<?php foreach ( $programs as $program ) : ?>
									<li>
										<strong><?php echo esc_html( (string) $program['field_name'] ); ?></strong>
										<span><?php echo esc_html( liferuss_catalog_degree( (string) $program['degree'] ) . ' · ' . liferuss_catalog_lang( (string) $program['language'] ) . ' · ' . liferuss_local_digits( (string) $program['duration_years'] ) . ' سال' ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p>شرایط پذیرش این دانشگاه هنوز ثبت نشده است.</p>
						<?php endif; ?>
						<?php $source( 'university', $uni_id, 'admission', $uni_id ); ?>
					</section>
					<section class="lr-uni-card" id="moh">
						<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['moh']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>وضعیت وزارت بهداشت</h2></header>
						<p><span class="lr-badge <?php echo esc_attr( $moh_class ); ?>"><?php echo esc_html( $moh_label ); ?></span></p>
						<?php if ( 'approved' === $row['science_ministry_status'] ) : ?>
							<p>وزارت علوم هم این دانشگاه را تأیید کرده است.</p>
						<?php endif; ?>
						<?php $source( 'university', $uni_id, 'approval', $uni_id ); ?>
					</section>
					<?php if ( ! empty( $row['city'] ) ) : ?>
						<section class="lr-uni-card" id="city">
							<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['city']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>شهر</h2></header>
							<p><a href="<?php echo esc_url( get_permalink( (int) $row['city']['post_id'] ) ); ?>"><?php echo esc_html( (string) $row['city']['name_fa'] ); ?></a></p>
							<?php if ( ! empty( $row['city']['living_cost_min'] ) ) : ?>
								<p>هزینهٔ زندگی ماهانه <?php echo esc_html( liferuss_catalog_amount( $row['city']['living_cost_min'], (string) $row['city']['currency'] ) ); ?>
									<?php if ( ! empty( $row['city']['living_cost_max'] ) ) : ?>
										تا <?php echo esc_html( liferuss_catalog_amount( $row['city']['living_cost_max'], (string) $row['city']['currency'] ) ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
							<?php $source( 'city', (int) $row['city']['id'], 'profile', $uni_id ); ?>
						</section>
					<?php endif; ?>
					<?php if ( ! empty( $row['rankings'] ) ) : ?>
						<section class="lr-uni-card" id="rankings">
							<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['rank']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>رتبه‌بندی</h2></header>
							<?php liferuss_table_scroll_open(); ?>
							<table class="lr-table">
								<thead><tr><th>مرجع</th><th>محدوده</th><th>رتبه</th><th>سال</th></tr></thead>
								<tbody>
								<?php foreach ( $row['rankings'] as $rank_row ) : ?>
									<tr>
										<td><?php echo esc_html( (string) $rank_row['provider_name'] ); ?></td>
										<td><?php echo esc_html( (string) $rank_row['scope'] ); ?></td>
										<td><?php echo esc_html( liferuss_local_digits( (string) ( $rank_row['rank_value'] ? $rank_row['rank_value'] : $rank_row['rank_band'] ) ) ); ?></td>
										<td><?php echo esc_html( liferuss_local_digits( (string) $rank_row['year'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<?php liferuss_table_scroll_close(); ?>
							<?php $source( 'university', $uni_id, 'ranking', $uni_id ); ?>
						</section>
					<?php endif; ?>
					<?php if ( class_exists( '\LifeRuss\Core\Scholarships\Store' ) ) : ?>
						<?php $scholarships = \LifeRuss\Core\Scholarships\Store::for_university( $uni_id ); ?>
						<?php if ( $scholarships ) : ?>
							<section class="lr-uni-card" id="scholarships">
								<header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true"><?php echo $icons['aid']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><h2>بورسیه‌ها</h2></header>
								<ul class="lr-facts">
									<?php foreach ( $scholarships as $scholarship ) : ?>
										<li><a href="<?php echo esc_url( (string) get_permalink( (int) $scholarship['post_id'] ) ); ?>"><?php echo esc_html( get_the_title( (int) $scholarship['post_id'] ) ); ?></a>
											<?php if ( '' !== (string) $scholarship['deadline'] ) : ?>
												— <?php echo esc_html( liferuss_local_digits( (string) $scholarship['deadline'] ) ); ?>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>
					<?php endif; ?>
					<?php
					$gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_lr_gallery', true ) ) ) );
					if ( $gallery ) :
						echo '<section class="lr-uni-card" id="gallery"><header class="lr-uni-card-head"><h2>گالری</h2></header><div class="lr-gallery">';
						foreach ( $gallery as $id ) {
							echo wp_get_attachment_image( $id, 'medium_large' );
						}
						echo '</div></section>';
					endif;
					if ( $faqs ) :
						echo '<section class="lr-uni-card" id="faq"><header class="lr-uni-card-head"><span class="lr-uni-ico" aria-hidden="true">' . $icons['faq'] . '</span><h2>پرسش‌های متداول</h2></header><div class="lr-faq-acc">';
						foreach ( $faqs as $faq ) {
							echo '<details class="lr-faq"><summary>' . esc_html( $faq['q'] ) . '</summary><p>' . esc_html( $faq['a'] ) . '</p></details>';
						}
						echo '</div></section>';
					endif;
					\LifeRuss\Core\Seo\Links::similar( $row );
					echo '<section class="lr-uni-card" id="related-links"><header class="lr-uni-card-head"><h2>پیوندهای مرتبط</h2></header>';
					\LifeRuss\Core\Seo\Links::university( $row );
					$related = \LifeRuss\Core\Catalog\Query::related( (int) $row['city_id'], $uni_id );
					if ( $related ) {
						echo '<div class="lr-chip-group"><h3>دانشگاه‌های همین شهر</h3><ul>';
						foreach ( array_slice( $related, 0, 6 ) as $item ) {
							echo '<li><a class="lr-chip" href="' . esc_url( (string) get_permalink( (int) $item['post_id'] ) ) . '">' . esc_html( (string) $item['name_fa'] ) . '</a></li>';
						}
						echo '</ul></div>';
					}
					echo '</section>';
					$GLOBALS['liferuss_consult_university'] = $row['name_fa'];
					get_template_part( 'template-parts/consult-form' );
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
