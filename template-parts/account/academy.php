<?php
/**
 * Student academy panel.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tabs = array(
	'courses'      => array( 'دوره‌های من', liferuss_url( '/account/academy/' ) ),
	'certificates' => array( 'گواهی‌ها', liferuss_url( '/account/academy/certificates/' ) ),
	'subscription' => array( 'اشتراک', liferuss_url( '/account/academy/subscription/' ) ),
	'bookings'     => array( 'رزروها', liferuss_url( '/account/academy/bookings/' ) ),
	'orders'       => array( 'سفارش‌ها', liferuss_url( '/account/academy/orders/' ) ),
);
?>
<nav class="lr-account-nav" aria-label="آکادمی">
	<?php foreach ( $tabs as $key => $link ) : ?>
		<a class="<?php echo $tab === $key ? 'is-current' : ''; ?>" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a>
	<?php endforeach; ?>
</nav>
<?php if ( 'certificates' === $tab ) : ?>
	<?php if ( empty( $academy['certificates'] ) ) : ?>
		<p class="academy-empty">هنوز گواهی صادر نشده است.</p>
	<?php else : ?>
		<ul class="academy-list">
			<?php foreach ( $academy['certificates'] as $cert ) : ?>
				<li><a href="<?php echo esc_url( liferuss_url( '/academy/certificate/' . $cert['code'] . '/' ) ); ?>"><?php echo esc_html( (string) $cert['jalali_date'] ); ?> — <?php echo esc_html( (string) $cert['code'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
<?php elseif ( 'subscription' === $tab ) : ?>
	<?php if ( empty( $academy['subscription'] ) ) : ?>
		<p class="academy-empty">اشتراک فعالی ندارید. <a href="<?php echo esc_url( liferuss_url( '/academy/plans/' ) ); ?>">طرح‌ها</a></p>
	<?php else : ?>
		<?php $sub = $academy['subscription']; ?>
		<p><?php echo esc_html( (string) $sub['plan_title'] ); ?> تا <?php echo wp_kses_post( \LifeRuss\Core\CRM\Jalali::html( (string) $sub['ends_at'] ) ); ?>.</p>
		<p>وضعیت: <?php echo esc_html( (string) $sub['status'] ); ?>. سه روز پس از پایان هنوز دسترسی دارید. تمدید از همان صفحهٔ طرح‌ها و با یک پرداخت تازه است.</p>
		<p><a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/academy/plans/' ) ); ?>">تمدید</a></p>
	<?php endif; ?>
<?php elseif ( 'bookings' === $tab ) : ?>
	<?php if ( empty( $academy['bookings'] ) ) : ?>
		<p class="academy-empty">رزروی ندارید.</p>
	<?php else : ?>
		<ul class="academy-list">
			<?php foreach ( $academy['bookings'] as $booking ) : ?>
				<li>
					<strong><?php echo esc_html( (string) $booking['title'] ); ?></strong>
					<span><?php echo esc_html( \LifeRuss\Core\CRM\Jalali::plain( (string) $booking['starts_at'] ) ); ?> — <?php echo esc_html( (string) $booking['status'] ); ?></span>
					<?php if ( 'confirmed' === $booking['status'] && ! empty( $booking['meeting_url'] ) ) : ?>
						<a href="<?php echo esc_url( (string) $booking['meeting_url'] ); ?>">لینک جلسه</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
<?php elseif ( 'orders' === $tab ) : ?>
	<?php if ( empty( $academy['orders'] ) ) : ?>
		<p class="academy-empty">سفارشی ثبت نشده است.</p>
	<?php else : ?>
		<ul class="academy-list">
			<?php foreach ( $academy['orders'] as $order ) : ?>
				<li><strong><?php echo esc_html( (string) $order['code'] ); ?></strong> <span><?php echo esc_html( (string) $order['status'] ); ?> — <?php echo esc_html( number_format_i18n( (int) $order['net'] ) ); ?> تومان</span></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
<?php else : ?>
	<?php if ( empty( $academy['courses'] ) ) : ?>
		<p class="academy-empty">هنوز دوره‌ای ندارید. <a href="<?php echo esc_url( liferuss_url( '/academy/' ) ); ?>">آکادمی</a></p>
	<?php else : ?>
		<div class="academy-grid">
			<?php foreach ( $academy['courses'] as $row ) : ?>
				<article class="academy-card">
					<h2><?php echo esc_html( (string) $row['course']['title'] ); ?></h2>
					<p><?php echo esc_html( (string) $row['percent'] ); ?>٪</p>
					<?php if ( ! empty( $row['next'] ) ) : ?>
						<a class="btn btn-navy" href="<?php echo esc_url( liferuss_url( '/academy/courses/' . $row['course']['slug'] . '/' . $row['next']['slug'] . '/' ) ); ?>">ادامه</a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>
