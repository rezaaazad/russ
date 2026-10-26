<?php
/**
 * Trust and license row. Hidden until the owner fills a title.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = liferuss_license_items();
if ( ! $items ) {
	return;
}
?>
<section class="section lr-licenses" id="licenses" aria-label="<?php echo esc_attr( liferuss_home_heading( 'licenses', 'مجوزها و اعتماد' ) ); ?>">
	<div class="container">
		<h2 class="screen-reader-text"><?php echo esc_html( liferuss_home_heading( 'licenses', 'مجوزها و اعتماد' ) ); ?></h2>
		<div class="lr-snap">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$tag  = '' !== $item['url'] ? 'a' : 'article';
				$attr = '' !== $item['url'] ? ' href="' . esc_url( $item['url'] ) . '"' : '';
				?>
				<<?php echo $tag; ?> class="lr-license"<?php echo $attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<strong><?php echo esc_html( $item['title'] ); ?></strong>
					<?php if ( '' !== $item['detail'] ) : ?>
						<p><?php echo esc_html( $item['detail'] ); ?></p>
					<?php endif; ?>
				</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
