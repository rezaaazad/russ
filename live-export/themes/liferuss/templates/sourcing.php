<?php
/**
 * Template Name: بازرگانی و سورسینگ
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categories = liferuss_sourcing_categories();
$countries  = liferuss_sourcing_countries();
$upload_mb  = max( 1, (int) round( liferuss_sourcing_upload_bytes() / MB_IN_BYTES ) );
$upload_cap = liferuss_sourcing_upload_capped();
$status     = isset( $_GET['consult'] ) ? sanitize_key( wp_unslash( $_GET['consult'] ) ) : '';
$message    = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
$services   = array(
	array( 'file' => 'sourcing-b2b.webp', 'title' => 'سورسینگ اختصاصی برای شرکت‌ها و بازرگانان', 'icon' => 'handshake', 'alt' => 'میز مذاکرهٔ بازرگانی' ),
	array( 'file' => 'intl-supply.webp', 'title' => 'تأمین کالا از روسیه و کشورهای حوزه اوراسیا', 'icon' => 'language', 'alt' => 'تأمین کالا از روسیه و کشورهای حوزه اوراسیا' ),
	array( 'file' => 'buy-from-russia.webp', 'title' => 'خرید و تأمین کالا از روسیه', 'icon' => 'cart', 'alt' => 'کالای روسی برای خرید' ),
	array( 'file' => 'export-iran.webp', 'title' => 'صادرات و تأمین محصولات ایرانی', 'icon' => 'cargo', 'alt' => 'صادرات محصولات ایرانی' ),
);
$bullets    = array(
	array( 'icon' => 'shield', 'label' => 'تأمین مطمئن' ),
	array( 'icon' => 'language', 'label' => 'شبکه تأمین در روسیه و کشورهای حوزه اوراسیا' ),
	array( 'icon' => 'star', 'label' => 'راهکارهای اختصاصی' ),
	array( 'icon' => 'steps', 'label' => 'رشد پایدار تجارت' ),
);
$steps      = array(
	array( 'icon' => 'docs', 'label' => 'ثبت درخواست' ),
	array( 'icon' => 'search', 'label' => 'بررسی نیاز' ),
	array( 'icon' => 'users', 'label' => 'یافتن تأمین‌کننده' ),
	array( 'icon' => 'chat', 'label' => 'ارائه قیمت و شرایط' ),
	array( 'icon' => 'cargo', 'label' => 'هماهنگی خرید و تجارت' ),
);
?>

<section class="lr-src-hero">
	<?php liferuss_sourcing_picture( 'hero-trade.webp', 'پرچم ایران و روسیه، برج آزادی، کامیون، کانتینر، کشتی و کلیسای سنت باسیل', 1600, 700, true, '100vw' ); ?>
	<div class="lr-src-hero__scrim">
		<div class="lr-src-hero__copy">
			<h1>تجارت، تأمین و <span>سورسینگ</span> کالا</h1>
			<p class="lr-src-hero__sub">شبکه تأمین در روسیه و کشورهای حوزه اوراسیا</p>
			<p class="lr-src-hero__lead"><?php echo esc_html( liferuss_sourcing_hero_lead() ); ?></p>
			<ul class="lr-src-bullets">
				<?php foreach ( $bullets as $bullet ) : ?>
					<li>
						<?php
						$lr_src_icon = liferuss_icon( $bullet['icon'] );
						if ( '' === $lr_src_icon && function_exists( 'liferuss_hv2_icon' ) ) {
							$lr_src_icon = liferuss_hv2_icon( $bullet['icon'] );
						}
						?>
						<span class="lr-src-ico" aria-hidden="true"><?php echo $lr_src_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php echo esc_html( $bullet['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<section class="lr-src-services" aria-label="خدمات تأمین">
	<div class="container lr-src-services__grid">
		<?php foreach ( $services as $card ) : ?>
			<a class="lr-src-card" href="#sourcing-form">
				<?php liferuss_sourcing_picture( $card['file'], $card['alt'], 800, 500, false, '(max-width: 600px) 92vw, (max-width: 1024px) 46vw, 280px' ); ?>
				<span class="lr-src-card__badge" aria-hidden="true"><?php echo liferuss_icon( $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php echo esc_html( $card['title'] ); ?></strong>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="lr-src-steps" aria-labelledby="lr-src-steps-title">
	<div class="container">
		<h2 id="lr-src-steps-title">مراحل <span>همکاری</span></h2>
		<ol>
			<?php foreach ( $steps as $index => $step ) : ?>
				<li>
					<span class="lr-src-step__ico" aria-hidden="true">
						<?php echo liferuss_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<b><?php echo esc_html( liferuss_local_digits( (string) ( $index + 1 ) ) ); ?></b>
					</span>
					<span><?php echo esc_html( $step['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="lr-src-request" id="sourcing-form">
	<span id="consultation"></span>
	<div class="container lr-src-request__grid">
		<div class="lr-src-form">
			<h2>فرم <span>درخواست تأمین کالا</span></h2>
			<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite"><?php echo $message ? esc_html( $message ) : ''; ?></div>
			<form class="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="liferuss_consult">
				<input type="hidden" name="consult_type" value="sourcing">
				<input type="hidden" name="consult_lang" value="fa">
				<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
				<div class="hp" aria-hidden="true"><label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label></div>
				<?php if ( function_exists( 'liferuss_form_tracking_fields' ) ) { liferuss_form_tracking_fields(); } ?>
				<label>
					<span>نام و نام خانوادگی / نام شرکت</span>
					<input type="text" name="consult_name" required autocomplete="name" maxlength="150">
				</label>
				<label>
					<span>شماره تماس و واتساپ</span>
					<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" dir="ltr" maxlength="16">
				</label>
				<label>
					<span>نام کالا</span>
					<input type="text" name="consult_product" required maxlength="150">
				</label>
				<label>
					<span>دسته‌بندی کالا</span>
					<select name="consult_category" required>
						<option value="">انتخاب کنید</option>
						<?php foreach ( $categories as $slug => $cat ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $cat['label'] ); ?></option>
						<?php endforeach; ?>
						<option value="other">سایر</option>
					</select>
				</label>
				<label>
					<span>کشور موردنظر برای خرید</span>
					<select name="consult_buy_country" required>
						<option value="">انتخاب کنید</option>
						<?php foreach ( $countries as $country ) : ?>
							<option value="<?php echo esc_attr( $country ); ?>"><?php echo esc_html( $country ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span>کشور مقصد</span>
					<select name="consult_dest_country" required>
						<option value="">انتخاب کنید</option>
						<?php foreach ( $countries as $country ) : ?>
							<option value="<?php echo esc_attr( $country ); ?>"><?php echo esc_html( $country ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span>حجم یا تعداد موردنیاز</span>
					<input type="text" name="consult_volume" required maxlength="100">
				</label>
				<label>
					<span>مشخصات فنی یا برند</span>
					<input type="text" name="consult_specs" maxlength="200">
				</label>
				<label>
					<span>توضیحات تکمیلی</span>
					<textarea name="consult_notes" rows="3"></textarea>
				</label>
				<div class="lr-src-file">
					<span id="lr-src-file-label">آپلود عکس یا فایل محصول</span>
					<div class="lr-src-file__row">
						<input id="lr-src-files" class="lr-src-file__input" type="file" name="consult_files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" data-lr-max-files="<?php echo esc_attr( (string) LIFERUSS_SOURCING_MAX_FILES ); ?>" data-lr-max-bytes="<?php echo esc_attr( (string) liferuss_sourcing_upload_bytes() ); ?>" aria-labelledby="lr-src-file-label" aria-describedby="lr-src-file-help lr-src-file-names">
						<label for="lr-src-files" class="lr-src-file__btn">انتخاب فایل</label>
					</div>
					<ul class="lr-src-file__names" id="lr-src-file-names" aria-live="polite"><li>فایلی انتخاب نشده</li></ul>
					<small id="lr-src-file-help">تا <?php echo esc_html( liferuss_local_digits( (string) LIFERUSS_SOURCING_MAX_FILES ) ); ?> فایل، هر کدام تا <?php echo esc_html( liferuss_local_digits( (string) $upload_mb ) ); ?> مگابایت. فقط JPG، PNG، WEBP یا PDF.<?php echo $upload_cap ? ' سقف سرور از ۲۰ مگابایت کمتر است، پس همین سقف اعمال می‌شود.' : ''; ?></small>
				</div>
				<button class="btn btn-gold btn-block" type="submit">ارسال درخواست</button>
				<p class="lr-src-form__note"><?php echo esc_html( liferuss_sourcing_form_note() ); ?></p>
			</form>
		</div>
		<aside class="lr-src-banner">
			<?php liferuss_sourcing_picture( 'side-banner-port.webp', 'بندر و کشتی باری', 600, 900, false, '(max-width: 799px) 92vw, 250px' ); ?>
			<div class="lr-src-banner__copy">
				<strong>تجارت بدون مرز</strong>
				<span>فرصت‌های بیشتر</span>
			</div>
		</aside>
	</div>
</section>

<section class="lr-src-cats" aria-labelledby="lr-src-cats-title">
	<div class="container">
		<h2 id="lr-src-cats-title">دسته‌بندی <span>محصولات</span></h2>
		<div class="lr-src-cats__grid">
			<?php foreach ( $categories as $slug => $cat ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'cat', $slug, get_permalink() ) . '#sourcing-form' ); ?>">
					<?php liferuss_sourcing_picture( $cat['file'], $cat['alt'], 500, 400, false, '(max-width: 600px) 46vw, 240px' ); ?>
					<span><?php echo esc_html( $cat['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="lr-src-note"><a href="#sourcing-form">کالای موردنظر شما در فهرست نیست؟</a> مشخصات آن را برای ما ارسال کنید.</p>
	</div>
</section>

<section class="lr-src-cta">
	<?php liferuss_sourcing_picture( 'cta-band.webp', '', 1600, 300, false, '100vw' ); ?>
	<div class="lr-src-cta__copy">
		<h2>با هم <span>بازارهای جدید</span> می‌سازیم</h2>
		<p>
			<a class="btn btn-gold" href="#sourcing-form">درخواست تأمین کالا</a>
			<a class="btn lr-src-cta__line" href="<?php echo esc_url( liferuss_sourcing_consult_url() ); ?>">مشاوره با بخش بازرگانی</a>
		</p>
	</div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
  var params = new URLSearchParams(window.location.search);
  var cat = params.get("cat");
  var select = document.querySelector("#sourcing-form [name='consult_category']");
  if (cat && select && select.querySelector("option[value='" + cat.replace(/[^a-z]/g, "") + "']")) {
    select.value = cat.replace(/[^a-z]/g, "");
  }
  var picker = document.getElementById("lr-src-files");
  var names = document.getElementById("lr-src-file-names");
  if (!picker || !names) {
    return;
  }
  picker.addEventListener("change", function () {
    names.replaceChildren();
    var files = picker.files || [];
    if (!files.length) {
      var empty = document.createElement("li");
      empty.textContent = "فایلی انتخاب نشده";
      names.appendChild(empty);
      return;
    }
    Array.prototype.forEach.call(files, function (file) {
      var item = document.createElement("li");
      item.textContent = file.name;
      names.appendChild(item);
    });
  });
});
</script>

<?php
get_footer();
