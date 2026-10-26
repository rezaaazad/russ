<?php
/**
 * Service payment and receipt. Not a shop checkout.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$row = \LifeRuss\Core\Payments\Checkout::by_token( (string) get_query_var( 'lr_pay' ) );
get_header();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow">پرداخت خدمات</p>
		<h1><?php echo $row && 'paid' === $row['status'] ? 'رسید پرداخت' : 'پرداخت'; ?></h1>
	</div>
</header>
<section class="section">
	<div class="container">
		<?php if ( ! $row ) : ?>
			<p class="lr-empty">این لینک پرداخت معتبر نیست.</p>
		<?php else : ?>
			<?php $label = \LifeRuss\Core\Payments\Checkout::statuses()[ (string) $row['status'] ] ?? ''; ?>
			<article class="lr-card">
				<div class="lr-card-body">
					<p><?php echo esc_html( (string) $row['description'] ); ?></p>
					<p><strong><?php echo esc_html( number_format_i18n( (int) $row['amount_toman'] ) ); ?> تومان</strong></p>
					<p>وضعیت: <?php echo esc_html( $label ); ?></p>
					<?php if ( 'paid' === $row['status'] ) : ?>
						<p>شماره مرجع: <?php echo esc_html( (string) $row['ref_id'] ); ?></p>
						<p class="lr-meta">زمان پرداخت: <?php echo \LifeRuss\Core\CRM\Jalali::html( (string) $row['paid_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup. ?></p>
					<?php elseif ( 'pending' === $row['status'] ) : ?>
						<?php if ( ! empty( $row['expires_at'] ) ) : ?>
							<p class="lr-meta">مهلت: <?php echo \LifeRuss\Core\CRM\Jalali::html( (string) $row['expires_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup. ?></p>
						<?php endif; ?>
						<form method="post" action="<?php echo esc_url( \LifeRuss\Core\Payments\Checkout::url( $row ) ); ?>">
							<?php wp_nonce_field( 'lr_pay_' . $row['token'], 'lr_pay_nonce' ); ?>
							<button class="btn btn-gold" type="submit">پرداخت با زرین‌پال</button>
						</form>
					<?php endif; ?>
				</div>
			</article>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
