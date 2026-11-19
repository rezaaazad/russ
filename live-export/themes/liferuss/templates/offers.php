<?php
/**
 * Template Name: پیشنهادها (همهٔ مسیرها)
 *
 * Complete listing behind the homepage «مشاهده همه» button: every offer card from the homepage
 * (option hv2_offers), a category filter, other hubs, and a consultation call to action.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$items = liferuss_enabled_items( 'hv2_offers' );
$more  = liferuss_t( 'hv2_more' );
$title = liferuss_opt( 'hv2_offers_title' );
$lead  = liferuss_opt( 'hv2_offers_subtitle' );

// Distinct categories (tags) in the order they first appear.
$tags = array();
foreach ( $items as $item ) {
	$tag = trim( (string) ( $item['tag'] ?? '' ) );
	if ( '' !== $tag && ! in_array( $tag, $tags, true ) ) {
		$tags[] = $tag;
	}
}

// Other hubs that are not already one of the cards above.
$used = array();
foreach ( $items as $item ) {
	$used[] = untrailingslashit( (string) ( $item['link'] ?? '' ) );
}
$others = array();
foreach ( liferuss_path_links() as $link ) {
	if ( in_array( untrailingslashit( $link['url'] ), $used, true ) ) {
		continue;
	}
	if ( in_array( $link['url'], array( '/scholarships/' ), true ) ) {
		continue; // Catalog is empty until verified data is imported.
	}
	$others[] = $link;
}
?>
<header class="hv2-subhero">
	<div class="container">
		<?php liferuss_breadcrumbs(); ?>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( $lead ) : ?>
			<p class="hv2-subhero__lead"><?php echo esc_html( $lead ); ?></p>
		<?php endif; ?>
		<?php if ( $items ) : ?>
			<p class="hv2-subhero__count"><strong><?php echo esc_html( liferuss_local_digits( (string) count( $items ) ) ); ?></strong> <?php echo esc_html( liferuss_t( 'hv2_hub_count' ) ); ?></p>
		<?php endif; ?>
	</div>
</header>

<?php if ( $items ) : ?>
<section class="hv2-section hv2-offers hv2-hub" id="offers" aria-label="<?php echo esc_attr( $title ); ?>">
	<div class="container">
		<?php if ( count( $tags ) > 1 ) : ?>
			<div class="hv2-chips" role="group" aria-label="<?php echo esc_attr( liferuss_t( 'hv2_hub_filter' ) ); ?>" data-hv2-filter>
				<button type="button" class="hv2-chip-btn is-on" data-tag="" aria-pressed="true"><?php echo esc_html( liferuss_t( 'hv2_hub_all' ) ); ?></button>
				<?php foreach ( $tags as $tag ) : ?>
					<button type="button" class="hv2-chip-btn" data-tag="<?php echo esc_attr( $tag ); ?>" aria-pressed="false"><?php echo esc_html( $tag ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="hv2-offers__grid hv2-offers__grid--even" data-hv2-grid>
			<?php foreach ( $items as $n => $item ) : ?>
				<a class="hv2-offer hv2-offer--<?php echo (int) ( $n + 1 ); ?>" data-tag="<?php echo esc_attr( (string) ( $item['tag'] ?? '' ) ); ?>" href="<?php echo esc_url( liferuss_hv2_href( $item['link'] ?? '' ) ); ?>">
					<span class="hv2-offer__media" aria-hidden="true">
						<?php
						liferuss_hv2_photo(
							$item['image_id'] ?? 0,
							(string) ( $item['photo'] ?? 'saint-basil' ),
							'',
							'(max-width: 640px) 92vw, (max-width: 1000px) 46vw, 380px',
							'hv2-offer__img'
						);
						?>
						<?php if ( ! empty( $item['tag'] ) ) : ?>
							<span class="hv2-offer__tag"><?php echo esc_html( $item['tag'] ); ?></span>
						<?php endif; ?>
					</span>
					<span class="hv2-offer__body">
						<strong class="hv2-offer__title"><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<span class="hv2-offer__text"><?php echo esc_html( $item['text'] ?? '' ); ?></span>
					</span>
					<span class="hv2-offer__bar">
						<span><?php echo esc_html( $more ); ?></span>
						<span class="hv2-go" aria-hidden="true"><?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $others ) : ?>
<section class="hv2-section hv2-others" aria-labelledby="hv2-others-title">
	<div class="container">
		<header class="hv2-head">
			<h2 id="hv2-others-title"><?php echo esc_html( liferuss_t( 'hv2_hub_more_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_t( 'hv2_hub_more_lead' ) ); ?></p>
		</header>
		<ul class="hv2-others__list">
			<?php foreach ( $others as $link ) : ?>
				<li>
					<a class="hv2-others__link" href="<?php echo esc_url( liferuss_url( $link['url'] ) ); ?>">
						<span><?php echo esc_html( $link['title'] ); ?></span>
						<span class="hv2-go" aria-hidden="true"><?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<section class="hv2-section hv2-pay hv2-hub-cta" aria-labelledby="hv2-hub-cta-title">
	<div class="container">
		<div class="hv2-pay__box">
			<h2 id="hv2-hub-cta-title"><?php echo esc_html( liferuss_t( 'hv2_hub_cta_title' ) ); ?></h2>
			<p class="hv2-pay__lead"><?php echo esc_html( liferuss_t( 'hv2_hub_cta_text' ) ); ?></p>
			<p class="hv2-pay__cta"><a class="btn btn-gold js-scroll-consult" href="#consultation"><?php echo esc_html( liferuss_t( 'drawer_consult' ) ); ?> <?php echo liferuss_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/consult-form' ); ?>

<?php
get_footer();
