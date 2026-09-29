<?php
/**
 * Client account: login, requests, files, messages, saved universities, profile.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$screen  = (string) get_query_var( 'lr_account' );
$lead_id = absint( get_query_var( 'lr_account_id' ) );
$notice  = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$notices = array(
	'sent'  => 'کد ورود ارسال شد. تا ده دقیقه معتبر است.',
	'bad'   => 'این درخواست انجام نشد. اطلاعات را دوباره بررسی کنید.',
	'in'    => 'وارد حساب شدید. درخواست‌های مرتبط با شماره یا ایمیل تأییدشده اینجاست.',
	'saved' => 'ذخیره شد.',
);
$labels  = class_exists( '\LifeRuss\Core\Account\Portal' ) ? \LifeRuss\Core\Account\Portal::statuses() : array();
$user_id = get_current_user_id();
$links   = array(
	'dashboard' => array( 'حساب', liferuss_url( '/account/' ) ),
	'requests'  => array( 'درخواست‌ها', liferuss_url( '/account/requests/' ) ),
	'saved'     => array( 'دانشگاه‌ها', liferuss_url( '/account/saved/' ) ),
	'profile'   => array( 'پروفایل', liferuss_url( '/account/profile/' ) ),
);
$current = 'request' === $screen ? 'requests' : $screen;
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1>حساب من</h1>
	</div>
</header>
<article class="section">
	<div class="container lr-account">
		<?php if ( isset( $notices[ $notice ] ) ) : ?>
			<p class="lr-notice" role="status"><?php echo esc_html( $notices[ $notice ] ); ?></p>
		<?php endif; ?>

		<?php if ( ! is_user_logged_in() ) : ?>
			<h2>ورود با کد یک‌بارمصرف</h2>
			<form class="lr-account-form" method="post" action="<?php echo esc_url( liferuss_url( '/account/' ) ); ?>">
				<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
				<input type="hidden" name="lr_account_action" value="otp">
				<label>
					<span>روش</span>
					<select name="channel">
						<option value="phone">شماره موبایل</option>
						<option value="email">ایمیل</option>
					</select>
				</label>
				<label>
					<span>شماره یا ایمیل</span>
					<input type="text" name="target" required autocomplete="username">
				</label>
				<button class="btn btn-gold" type="submit">دریافت کد</button>
			</form>
			<?php if ( 'sent' === $notice ) : ?>
				<form class="lr-account-form" method="post" action="<?php echo esc_url( liferuss_url( '/account/' ) ); ?>">
					<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
					<input type="hidden" name="lr_account_action" value="verify">
					<label>
						<span>کد شش‌رقمی</span>
						<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required>
					</label>
					<button class="btn btn-gold" type="submit">ورود</button>
				</form>
			<?php endif; ?>
		<?php else : ?>
			<nav class="lr-account-nav" aria-label="حساب">
				<?php foreach ( $links as $key => $link ) : ?>
					<a class="<?php echo $current === $key ? 'is-current' : ''; ?>" href="<?php echo esc_url( $link[1] ); ?>" <?php echo $current === $key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $link[0] ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( wp_logout_url( liferuss_url( '/account/' ) ) ); ?>">خروج</a>
			</nav>

			<?php if ( 'requests' === $screen || 'dashboard' === $screen ) : ?>
				<?php $leads = \LifeRuss\Core\Account\Portal::leads( $user_id ); ?>
				<?php if ( 'dashboard' === $screen ) : ?>
					<p><?php echo esc_html( (string) count( $leads ) ); ?> درخواست به این حساب وصل است.</p>
				<?php endif; ?>
				<?php if ( ! $leads ) : ?>
					<p class="lr-empty">درخواستی با این شماره یا ایمیل ثبت نشده است.</p>
				<?php else : ?>
					<ul class="lr-account-list">
						<?php foreach ( $leads as $lead ) : ?>
							<?php
							$status = $labels[ (string) $lead['status'] ] ?? 'به‌روزرسانی';
							$url    = liferuss_url( '/account/request/' . (int) $lead['id'] . '/' );
							?>
							<li>
								<a href="<?php echo esc_url( $url ); ?>"><strong><?php echo esc_html( (string) $lead['lead_code'] ); ?></strong></a>
								<span><?php echo esc_html( (string) $lead['name'] ); ?> — <?php echo esc_html( $status ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php elseif ( 'request' === $screen ) : ?>
				<?php
				$lead = null;
				foreach ( \LifeRuss\Core\Account\Portal::leads( $user_id ) as $row ) {
					if ( (int) $row['id'] === $lead_id ) {
						$lead = $row;
						break;
					}
				}
				?>
				<?php if ( ! $lead ) : ?>
					<p class="lr-empty">این درخواست به حساب شما وصل نیست.</p>
				<?php else : ?>
					<h2><?php echo esc_html( (string) $lead['lead_code'] ); ?></h2>
					<p><?php echo esc_html( $labels[ (string) $lead['status'] ] ?? 'به‌روزرسانی' ); ?></p>
					<h3>روند وضعیت</h3>
					<ol class="lr-timeline">
						<?php foreach ( \LifeRuss\Core\Account\Portal::timeline( $lead_id ) as $step ) : ?>
							<li>
								<strong><?php echo esc_html( $step['label'] ); ?></strong>
								<?php echo \LifeRuss\Core\CRM\Jalali::html( $step['at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup. ?>
								<?php if ( $step['note'] ) : ?>
									<span><?php echo esc_html( $step['note'] ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
					<h3>صورتحساب‌ها</h3>
					<ul class="lr-account-list">
						<?php foreach ( \LifeRuss\Core\Payments\Checkout::for_lead( $lead_id ) as $invoice ) : ?>
							<?php $pay_label = \LifeRuss\Core\Payments\Checkout::statuses()[ (string) $invoice['status'] ] ?? ''; ?>
							<li>
								<a href="<?php echo esc_url( \LifeRuss\Core\Payments\Checkout::url( $invoice ) ); ?>"><?php echo esc_html( number_format_i18n( (int) $invoice['amount_toman'] ) . ' تومان' ); ?></a>
								<span><?php echo esc_html( $pay_label . ' — ' . (string) $invoice['description'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<h3>مدارک</h3>
					<ul class="lr-account-list">
						<?php foreach ( \LifeRuss\Core\Account\Portal::files( $lead_id ) as $file ) : ?>
							<?php $file_url = wp_nonce_url( admin_url( 'admin-post.php?action=lr_lead_file&file=' . (int) $file['id'] ), 'lr_lead_file' ); ?>
							<li><a href="<?php echo esc_url( $file_url ); ?>"><?php echo esc_html( (string) $file['original_name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
					<form class="lr-account-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( liferuss_url( '/account/request/' . $lead_id . '/' ) ); ?>">
						<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
						<input type="hidden" name="lr_account_action" value="upload">
						<input type="hidden" name="lead_id" value="<?php echo esc_attr( (string) $lead_id ); ?>">
						<label>
							<span>فایل JPG، PNG، WEBP یا PDF تا ۱۰ مگابایت</span>
							<input type="file" name="client_file" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
						</label>
						<button class="btn btn-gold" type="submit">بارگذاری</button>
					</form>
					<h3>گفتگو با مشاور</h3>
					<div class="lr-thread">
						<?php foreach ( \LifeRuss\Core\Account\Portal::messages( $lead_id ) as $message ) : ?>
							<article>
								<p><?php echo esc_html( (string) $message['body'] ); ?></p>
								<p class="lr-meta"><?php echo (int) $message['author_id'] === $user_id ? 'شما' : 'مشاور'; ?> — <?php echo \LifeRuss\Core\CRM\Jalali::html( (string) $message['created_at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup. ?></p>
							</article>
						<?php endforeach; ?>
					</div>
					<form class="lr-account-form" method="post" action="<?php echo esc_url( liferuss_url( '/account/request/' . $lead_id . '/' ) ); ?>">
						<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
						<input type="hidden" name="lr_account_action" value="message">
						<input type="hidden" name="lead_id" value="<?php echo esc_attr( (string) $lead_id ); ?>">
						<label>
							<span>پیام</span>
							<textarea name="body" rows="4" required></textarea>
						</label>
						<button class="btn btn-gold" type="submit">ارسال</button>
					</form>
				<?php endif; ?>
			<?php elseif ( 'saved' === $screen ) : ?>
				<h2>دانشگاه‌های ذخیره‌شده</h2>
				<?php
				$saved = \LifeRuss\Core\Account\Portal::saved( $user_id );
				if ( ! $saved ) {
					echo '<p class="lr-empty">هنوز دانشگاهی ذخیره نشده است.</p>';
				} else {
					echo '<ul class="lr-account-list">';
					foreach ( $saved as $uni_slug ) {
						$found = get_posts(
							array(
								'name'           => $uni_slug,
								'post_type'      => 'lr_university',
								'post_status'    => 'publish',
								'posts_per_page' => 1,
							)
						);
						$title = $found ? get_the_title( $found[0] ) : $uni_slug;
						$url   = $found ? get_permalink( $found[0] ) : liferuss_url( '/universities/' . $uni_slug . '/' );
						echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
					}
					echo '</ul>';
				}
				?>
				<h2>مقایسهٔ اخیر</h2>
				<div id="lr-account-compare"></div>
			<?php else : ?>
				<?php
				$user  = wp_get_current_user();
				$email = (string) $user->user_email;
				$phone = (string) get_user_meta( $user_id, 'lr_phone', true );
				if ( str_ends_with( $email, '@clients.liferuss.invalid' ) ) {
					$email = '';
				}
				?>
				<form class="lr-account-form" method="post" action="<?php echo esc_url( liferuss_url( '/account/profile/' ) ); ?>">
					<?php wp_nonce_field( 'lr_account', 'lr_account_nonce' ); ?>
					<input type="hidden" name="lr_account_action" value="profile">
					<label>
						<span>نام</span>
						<input type="text" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required>
					</label>
					<?php if ( $phone ) : ?>
						<p>موبایل تأییدشده: <?php echo esc_html( $phone ); ?></p>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<p>ایمیل تأییدشده: <?php echo esc_html( $email ); ?></p>
					<?php endif; ?>
					<button class="btn btn-gold" type="submit">ذخیره نام</button>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
