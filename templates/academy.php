<?php
/**
 * Academy landing, catalogue, course, lesson, plans, checkout, and certificate.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$context = class_exists( '\LifeRuss\Core\Academy\Front' ) ? \LifeRuss\Core\Academy\Front::context() : array();
$screen  = (string) ( $context['screen'] ?? 'landing' );
$error   = isset( $_GET['academy_error'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['academy_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

/**
 * Buy button.
 *
 * @param string $type Item type.
 * @param string $id   Item id or slug.
 * @param string $label Button label.
 */
$buy = static function ( $type, $id, $label ) {
	echo '<form method="post" action="' . esc_url( liferuss_url( '/academy/checkout/' ) ) . '">';
	wp_nonce_field( 'lr_academy', 'lr_academy_nonce' );
	echo '<input type="hidden" name="lr_academy_action" value="checkout">';
	echo '<input type="hidden" name="item_type" value="' . esc_attr( $type ) . '">';
	echo '<input type="hidden" name="item_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<input type="hidden" name="redirect_to" value="' . esc_url( liferuss_url( '/academy/checkout/' ) ) . '">';
	echo '<button class="btn btn-gold" type="submit">' . esc_html( $label ) . '</button></form>';
};
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_academy' ) ); ?></p>
		<h1>
			<?php
			if ( 'course' === $screen && ! empty( $context['course'] ) ) {
				echo esc_html( (string) $context['course']['title'] );
			} elseif ( 'lesson' === $screen && ! empty( $context['lesson'] ) ) {
				echo esc_html( (string) $context['lesson']['title'] );
			} elseif ( 'plans' === $screen ) {
				echo 'طرح‌های اشتراک';
			} elseif ( 'checkout' === $screen ) {
				echo 'پرداخت';
			} elseif ( 'certificate' === $screen ) {
				echo 'گواهی آکادمی';
			} else {
				echo 'آکادمی لایف‌روس';
			}
			?>
		</h1>
	</div>
</header>
<article class="section academy">
	<div class="container">
		<?php if ( $error ) : ?>
			<p class="lr-notice" role="status"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $context['missing'] ) ) : ?>
			<p class="academy-empty">این صفحه پیدا نشد.</p>
		<?php elseif ( 'landing' === $screen || 'courses' === $screen || 'category' === $screen ) : ?>
			<?php if ( 'landing' === $screen ) : ?>
				<p class="academy-lead">دوره‌های روسی، پادفک، زندگی در روسیه و کلاس‌های آنلاین. قیمت‌ها به تومان است و پرداخت با زرین‌پال انجام می‌شود.</p>
				<div class="academy-cats">
					<?php foreach ( (array) ( $context['categories'] ?? array() ) as $category ) : ?>
						<a href="<?php echo esc_url( liferuss_url( '/academy/category/' . $category['slug'] . '/' ) ); ?>"><?php echo esc_html( (string) $category['title'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php $list = 'landing' === $screen ? (array) ( $context['featured'] ?? array() ) : (array) ( $context['courses'] ?? array() ); ?>
			<?php if ( ! $list ) : ?>
				<div class="academy-empty"><h2>به‌زودی</h2><p>دوره‌های این بخش در حال آماده‌سازی هستند.</p></div>
			<?php else : ?>
				<div class="academy-grid">
					<?php foreach ( $list as $course ) : ?>
						<a class="academy-card" href="<?php echo esc_url( liferuss_url( '/academy/courses/' . $course['slug'] . '/' ) ); ?>">
							<h2><?php echo esc_html( (string) $course['title'] ); ?></h2>
							<p><?php echo ! empty( $course['is_free'] ) ? 'رایگان' : esc_html( number_format_i18n( (int) \LifeRuss\Core\Academy\Catalog::price( $course ) ) . ' تومان' ); ?></p>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( 'landing' === $screen && ! empty( $context['classes'] ) ) : ?>
				<h2>کلاس‌های پیشِ رو</h2>
				<ul class="academy-list">
					<?php foreach ( $context['classes'] as $session ) : ?>
						<li>
							<strong><?php echo esc_html( (string) $session['title'] ); ?></strong>
							<span><?php echo esc_html( \LifeRuss\Core\CRM\Jalali::plain( (string) $session['starts_at'] ) ); ?></span>
							<?php $buy( 'private' === $session['kind'] ? 'private_class' : 'group_class', (string) $session['id'], 'رزرو' ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php elseif ( 'course' === $screen && ! empty( $context['course'] ) ) : ?>
			<?php $course = $context['course']; ?>
			<p class="academy-lead"><?php echo esc_html( wp_strip_all_tags( (string) $course['description'] ) ); ?></p>
			<?php if ( ! empty( $course['intro_video'] ) ) : ?>
				<div class="academy-player"><?php echo \LifeRuss\Core\Academy\Playback::intro( (string) $course['intro_video'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<p>
				<?php if ( ! empty( $course['is_free'] ) ) : ?>
					<?php $buy( 'course', (string) $course['id'], 'شروع رایگان' ); ?>
				<?php else : ?>
					<?php $buy( 'course', (string) $course['id'], 'خرید دوره' ); ?>
				<?php endif; ?>
			</p>
			<?php if ( empty( $context['lessons'] ) ) : ?>
				<div class="academy-empty"><h2>به‌زودی</h2><p>درس‌های این دوره به‌زودی منتشر می‌شوند.</p></div>
			<?php else : ?>
				<ol class="academy-list">
					<?php foreach ( $context['lessons'] as $lesson ) : ?>
						<li><a href="<?php echo esc_url( liferuss_url( '/academy/courses/' . $course['slug'] . '/' . $lesson['slug'] . '/' ) ); ?>"><?php echo esc_html( (string) $lesson['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		<?php elseif ( 'lesson' === $screen && ! empty( $context['lesson'] ) ) : ?>
			<?php if ( empty( $context['watch'] ) ) : ?>
				<div class="academy-empty"><h2>این درس قفل است</h2><p>برای دیدن آن دوره را بخرید یا اشتراک فعال داشته باشید.</p></div>
			<?php else : ?>
				<?php if ( ! empty( $context['video'] ) ) : ?>
					<div class="academy-player"><iframe src="<?php echo esc_url( \LifeRuss\Core\Academy\Playback::url( (int) $context['video']['id'], get_current_user_id() ) ); ?>" title="پخش درس" allowfullscreen></iframe></div>
				<?php endif; ?>
				<div class="academy-copy"><?php echo wp_kses_post( wpautop( (string) $context['lesson']['content'] ) ); ?></div>
				<form method="post">
					<?php wp_nonce_field( 'lr_academy', 'lr_academy_nonce' ); ?>
					<input type="hidden" name="lr_academy_action" value="done">
					<button class="btn btn-navy" type="submit">این درس را تمام کردم</button>
				</form>
				<?php if ( ! empty( $context['premium'] ) ) : ?>
					<?php if ( ! empty( $context['files'] ) ) : ?>
						<h2>جزوه‌ها</h2>
						<ul>
							<?php foreach ( $context['files'] as $file ) : ?>
								<?php if ( 'published' !== $file['status'] ) { continue; } ?>
								<li><a href="<?php echo esc_url( liferuss_url( '/academy/download/' . (int) $file['id'] . '/' ) ); ?>"><?php echo esc_html( (string) $file['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php foreach ( (array) $context['quizzes'] as $quiz ) : ?>
						<h2><?php echo esc_html( (string) $quiz['title'] ); ?></h2>
						<form method="post" class="lr-quiz">
							<?php wp_nonce_field( 'lr_academy', 'lr_academy_nonce' ); ?>
							<input type="hidden" name="lr_academy_action" value="quiz">
							<input type="hidden" name="quiz_id" value="<?php echo esc_attr( (string) $quiz['id'] ); ?>">
							<?php foreach ( \LifeRuss\Core\Academy\Quizzes::questions( (int) $quiz['id'] ) as $index => $question ) : ?>
								<fieldset>
									<legend><?php echo esc_html( (string) $question['prompt'] ); ?></legend>
									<?php if ( 'choice' === $question['type'] && ! empty( $question['choices'] ) ) : ?>
										<?php foreach ( $question['choices'] as $choice_index => $choice ) : ?>
											<label><input type="radio" name="choice[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( (string) $choice_index ); ?>"> <?php echo esc_html( (string) $choice ); ?></label>
										<?php endforeach; ?>
									<?php elseif ( 'fill' === $question['type'] ) : ?>
										<input type="text" name="fill[<?php echo esc_attr( (string) $index ); ?>]">
									<?php endif; ?>
								</fieldset>
							<?php endforeach; ?>
							<button class="btn btn-gold" type="submit">ثبت آزمون</button>
						</form>
					<?php endforeach; ?>
					<h2>پشتیبانی دوره</h2>
					<form method="post">
						<?php wp_nonce_field( 'lr_academy', 'lr_academy_nonce' ); ?>
						<input type="hidden" name="lr_academy_action" value="support">
						<textarea name="message" required placeholder="سؤال خود را بنویسید"></textarea>
						<button class="btn btn-navy" type="submit">ارسال به مدرس</button>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		<?php elseif ( 'plans' === $screen ) : ?>
			<?php if ( empty( $context['plans'] ) ) : ?>
				<div class="academy-empty"><h2>به‌زودی</h2><p>طرح‌های اشتراک به‌زودی اعلام می‌شوند.</p></div>
			<?php else : ?>
				<div class="academy-grid">
					<?php foreach ( $context['plans'] as $plan ) : ?>
						<article class="academy-card">
							<h2><?php echo esc_html( (string) $plan['title'] ); ?></h2>
							<p><?php echo esc_html( number_format_i18n( (int) $plan['price'] ) ); ?> تومان / <?php echo 'year' === $plan['billing_interval'] ? 'سال' : 'ماه'; ?></p>
							<?php $buy( 'plan', (string) $plan['id'], 'خرید اشتراک' ); ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php elseif ( 'checkout' === $screen ) : ?>
			<p>اگر کد تخفیف دارید، آن را همراه دکمهٔ خرید در صفحهٔ دوره وارد کنید. پرداخت به تومان و از زرین‌پال است.</p>
			<form method="post" class="lr-account-form">
				<?php wp_nonce_field( 'lr_academy', 'lr_academy_nonce' ); ?>
				<input type="hidden" name="lr_academy_action" value="checkout">
				<label><span>نوع</span>
					<select name="item_type">
						<option value="course">دوره</option>
						<option value="plan">اشتراک</option>
						<option value="bundle">بسته</option>
					</select>
				</label>
				<label><span>شناسه یا نامک بسته</span><input name="item_id" required></label>
				<label><span>کد تخفیف</span><input name="coupon"></label>
				<button class="btn btn-gold" type="submit">ادامهٔ پرداخت</button>
			</form>
		<?php elseif ( 'certificate' === $screen && ! empty( $context['certificate'] ) ) : ?>
			<?php
			$url = liferuss_url( '/academy/certificate/' . $context['certificate']['code'] . '/' );
			?>
			<div class="academy-certificate">
				<p>این گواهی معتبر است.</p>
				<h2><?php echo esc_html( (string) ( $context['student']['display_name'] ?? '' ) ); ?></h2>
				<p><?php echo esc_html( (string) ( $context['course']['title'] ?? '' ) ); ?></p>
				<p>تاریخ: <?php echo esc_html( (string) $context['certificate']['jalali_date'] ); ?></p>
				<p>کد: <bdi><?php echo esc_html( (string) $context['certificate']['code'] ); ?></bdi></p>
				<?php echo \LifeRuss\Core\Academy\Qr::svg( $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p><a class="btn btn-navy" href="<?php echo esc_url( add_query_arg( 'pdf', '1', $url ) ); ?>">دانلود PDF</a></p>
			</div>
		<?php elseif ( 'instructor' === $screen && ! empty( $context['instructor'] ) ) : ?>
			<h2><?php echo esc_html( (string) $context['instructor']['name'] ); ?></h2>
			<p><?php echo esc_html( (string) $context['instructor']['bio'] ); ?></p>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
