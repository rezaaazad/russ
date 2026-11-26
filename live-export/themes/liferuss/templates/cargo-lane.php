<?php
/**
 * Template Name: باربری، کارگو و ارسال
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$goods         = liferuss_cargo_goods();
$upload_bytes  = liferuss_cargo_upload_bytes();
$upload_mb     = max( 1, (int) round( $upload_bytes / MB_IN_BYTES ) );
$configured_mb = max( 1, (int) round( liferuss_cargo_max_bytes() / MB_IN_BYTES ) );
$upload_cap    = liferuss_cargo_upload_capped();
$status     = isset( $_GET['consult'] ) ? sanitize_key( wp_unslash( $_GET['consult'] ) ) : '';
$message    = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
$services   = array(
	array(
		'file'  => 'cargo-freight.webp',
		'title' => 'کارگو و باربری تخصصی',
		'text'  => 'حمل محموله‌های تجاری، بارهای حجیم و خرده‌بار (FCL و LCL) با مسیر و شرایط حمل مناسب',
		'icon'  => 'cargo',
		'alt'   => 'کامیون و کانتینر در بندر هنگام غروب',
	),
	array(
		'file'  => 'samples.webp',
		'title' => 'ارسال نمونه کالا برای بازرگانان',
		'text'  => 'ارسال نمونه محصولات، نمونه‌های تجاری و کالاهای تبلیغاتی در بازار روسیه و ایران',
		'icon'  => 'box',
		'alt'   => 'جعبهٔ نمونهٔ کالا',
	),
	array(
		'file'  => 'personal-luggage.webp',
		'title' => 'حمل بار و وسایل شخصی',
		'text'  => 'ارسال لوازم شخصی، اثاثیه و وسایل زندگی با بسته‌بندی مناسب و تحویل ایمن',
		'icon'  => 'suitcase',
		'alt'   => 'چمدان و بار شخصی',
	),
	array(
		'file'  => 'documents-student.webp',
		'title' => 'ارسال مدارک و بسته‌های دانشجویی',
		'text'  => 'ارسال مدارک دانشگاهی، بسته‌های دانشجویی و اسناد مهم بین ایران و روسیه',
		'icon'  => 'docs',
		'alt'   => 'مدارک و بستهٔ دانشجویی',
	),
);
$bullets    = array(
	array( 'icon' => 'chat', 'label' => 'پشتیبانی در تمام مراحل' ),
	array( 'icon' => 'docs', 'label' => 'هماهنگی کامل با امور گمرکی' ),
	array( 'icon' => 'language', 'label' => 'شبکه لجستیکی در ایران و روسیه' ),
	array( 'icon' => 'shield', 'label' => 'ارسال ایمن و مطمئن' ),
);
$steps      = array(
	array( 'icon' => 'docs', 'label' => 'ثبت درخواست', 'text' => 'تکمیل فرم و ارسال اطلاعات اولیه بار' ),
	array( 'icon' => 'search', 'label' => 'بررسی نوع بار', 'text' => 'بررسی نوع، حجم و شرایط ارسال' ),
	array( 'icon' => 'chat', 'label' => 'اعلام روش و هزینه', 'text' => 'ارائه روش حمل و برآورد هزینه و زمان' ),
	array( 'icon' => 'cargo', 'label' => 'تحویل و ارسال', 'text' => 'بسته‌بندی، انجام تشریفات گمرکی و ارسال محموله' ),
	array( 'icon' => 'pin', 'label' => 'تحویل در مقصد', 'text' => 'هماهنگی و تحویل ایمن در شهر مقصد' ),
);
?>

<section class="lr-src-hero">
	<?php liferuss_cargo_picture( 'hero-cargo.webp', 'پرچم ایران و روسیه، برج میلاد، کامیون، لیفتراک، کانتینر، کشتی باری و کلیسای سنت باسیل', 1600, 700, true, '100vw' ); ?>
	<div class="lr-cgo-script">
		<img class="lr-cgo-script__img" src="<?php echo esc_url( LIFERUSS_URI . '/assets/img/cargo/calligraphy-mahmoole.webp' ); ?>" srcset="<?php echo esc_url( LIFERUSS_URI . '/assets/img/cargo/calligraphy-mahmoole-900.webp' ); ?> 900w, <?php echo esc_url( LIFERUSS_URI . '/assets/img/cargo/calligraphy-mahmoole.webp' ); ?> 1774w" sizes="(max-width: 599px) 46vw, 220px" width="1774" height="887" alt="از مدارک دانشجویی تا محموله تجاری" decoding="async" fetchpriority="high">
	</div>
	<div class="lr-src-hero__scrim">
		<div class="lr-src-hero__copy">
			<h1>باربری، کارگو و ارسال<br><span>بین ایران و روسیه</span></h1>
			<p class="lr-src-hero__sub">راهکار مطمئن لایف روس برای ارسال مدارک، بار شخصی، نمونه کالا و محموله‌های تجاری بین ایران و روسیه</p>
			<p class="lr-src-hero__lead"><?php echo esc_html( liferuss_cargo_hero_lead() ); ?></p>
			<ul class="lr-src-bullets">
				<?php foreach ( $bullets as $bullet ) : ?>
					<li>
						<span class="lr-src-ico" aria-hidden="true"><?php echo liferuss_icon( $bullet['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php echo esc_html( $bullet['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<section class="lr-src-services" aria-label="خدمات باربری">
	<div class="container lr-src-services__grid">
		<?php foreach ( $services as $card ) : ?>
			<a class="lr-src-card" href="#cargo-form">
				<?php liferuss_cargo_picture( $card['file'], $card['alt'], 800, 500, false, '(max-width: 600px) 92vw, (max-width: 1024px) 46vw, 280px' ); ?>
				<span class="lr-src-card__badge" aria-hidden="true"><?php echo liferuss_icon( $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php echo esc_html( $card['title'] ); ?></strong>
				<em><?php echo esc_html( $card['text'] ); ?></em>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="lr-src-steps" aria-labelledby="lr-cgo-steps-title">
	<div class="container">
		<h2 id="lr-cgo-steps-title">مراحل <span>همکاری</span></h2>
		<p class="lr-src-steps__lead">از ثبت درخواست تا تحویل در مقصد، در کنار شما هستیم</p>
		<ol>
			<?php foreach ( $steps as $index => $step ) : ?>
				<li>
					<span class="lr-src-step__ico" aria-hidden="true">
						<?php echo liferuss_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<b><?php echo esc_html( liferuss_local_digits( (string) ( $index + 1 ) ) ); ?></b>
					</span>
					<span><?php echo esc_html( $step['label'] ); ?></span>
					<em><?php echo esc_html( $step['text'] ); ?></em>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="lr-src-request" id="cargo-form">
	<span id="consultation"></span>
	<div class="container lr-src-request__grid">
		<div class="lr-src-form">
			<h2>فرم <span>درخواست ارسال بار</span></h2>
			<p class="lr-src-form__lead">با تکمیل فرم زیر، کارشناسان ما در سریع‌ترین زمان با شما تماس خواهند گرفت.</p>
			<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite"><?php echo $message ? esc_html( $message ) : ''; ?></div>
			<form class="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="liferuss_consult">
				<input type="hidden" name="consult_type" value="cargo">
				<input type="hidden" name="consult_lang" value="fa">
				<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
				<div class="hp" aria-hidden="true"><label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label></div>
				<?php if ( function_exists( 'liferuss_form_tracking_fields' ) ) { liferuss_form_tracking_fields(); } ?>
				<label>
					<span>نام و نام خانوادگی / نام شرکت</span>
					<input type="text" name="consult_name" required autocomplete="name" maxlength="150">
				</label>
				<label>
					<span>شماره تماس / واتساپ</span>
					<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" dir="ltr" maxlength="16">
				</label>
				<label>
					<span>مبدأ (شهر / کشور)</span>
					<input type="text" name="consult_origin" required maxlength="100">
				</label>
				<label>
					<span>مقصد (شهر / کشور)</span>
					<input type="text" name="consult_dest" required maxlength="100">
				</label>
				<label>
					<span>نوع کالا / محموله</span>
					<select name="consult_cargo_kind" required>
						<option value="">انتخاب کنید</option>
						<?php foreach ( $goods as $slug => $item ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $item['label'] ); ?></option>
						<?php endforeach; ?>
						<option value="other">سایر</option>
					</select>
				</label>
				<div class="lr-cgo-opts">
					<label>
						<span>وزن تقریبی (کیلوگرم) (اختیاری)</span>
						<input type="number" name="consult_weight" min="0" step="0.1" inputmode="decimal">
					</label>
					<label>
						<span>ابعاد تقریبی (سانتی‌متر) (اختیاری)</span>
						<input type="text" name="consult_dims" maxlength="80">
					</label>
					<label>
						<span>تعداد بسته‌ها (اختیاری)</span>
						<input type="number" name="consult_packages" min="1" step="1" inputmode="numeric">
					</label>
					<label>
						<span>ارزش تقریبی کالا (دلار) (اختیاری)</span>
						<input type="number" name="consult_value" min="0" step="1" inputmode="numeric">
					</label>
				</div>
				<label>
					<span>توضیحات تکمیلی (اختیاری)</span>
					<textarea name="consult_notes" rows="3"></textarea>
				</label>
				<div class="lr-src-file">
					<span id="lr-cgo-file-label">آپلود عکس یا فایل مربوط به بار</span>
					<div class="lr-src-file__row">
						<input id="lr-cgo-files" class="lr-src-file__input" type="file" name="consult_files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" data-lr-max-files="<?php echo esc_attr( (string) LIFERUSS_SOURCING_MAX_FILES ); ?>" data-lr-max-bytes="<?php echo esc_attr( (string) $upload_bytes ); ?>" data-lr-file-names="lr-cgo-file-names" aria-labelledby="lr-cgo-file-label" aria-describedby="lr-cgo-file-help lr-cgo-file-names">
						<label for="lr-cgo-files" class="lr-src-file__btn">انتخاب فایل</label>
					</div>
					<ul class="lr-src-file__names" id="lr-cgo-file-names" aria-live="polite"><li>فایلی انتخاب نشده</li></ul>
					<small id="lr-cgo-file-help">تا <?php echo esc_html( liferuss_local_digits( (string) LIFERUSS_SOURCING_MAX_FILES ) ); ?> فایل. هر فایل حداکثر <?php echo esc_html( liferuss_local_digits( (string) $configured_mb ) ); ?> مگابایت. فقط JPG، PNG، WEBP یا PDF.<?php echo $upload_cap ? ' سقف سرور کمتر است، پس همین سقف ' . liferuss_local_digits( (string) $upload_mb ) . ' مگابایت اعمال می‌شود.' : ''; ?></small>
				</div>
				<button class="btn btn-gold btn-block" type="submit">ارسال درخواست</button>
				<p class="lr-src-form__note"><?php echo esc_html( liferuss_cargo_form_note() ); ?></p>
			</form>
		</div>
		<aside class="lr-src-banner">
			<?php liferuss_cargo_picture( 'side-banner-cargo.webp', 'بندر و کشتی باری', 600, 900, false, '(max-width: 799px) 92vw, 250px' ); ?>
			<div class="lr-src-banner__copy">
				<strong>اتصال مطمئن</strong>
				<span>ایران به روسیه</span>
			</div>
		</aside>
	</div>
</section>

<section class="lr-src-cats" aria-labelledby="lr-cgo-goods-title">
	<div class="container">
		<h2 id="lr-cgo-goods-title">موارد <span>معمول ارسال</span></h2>
		<div class="lr-src-cats__grid">
			<?php foreach ( $goods as $slug => $item ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'kind', $slug, get_permalink() ) . '#cargo-form' ); ?>">
					<?php liferuss_cargo_picture( $item['file'], $item['alt'], 500, 400, false, '(max-width: 600px) 46vw, 240px' ); ?>
					<span><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="lr-cgo-alert"><span class="lr-cgo-alert__ico" aria-hidden="true"><?php echo liferuss_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span>اگر کالای مورد نظر شما در لیست بالا وجود ندارد، می‌توانید اطلاعات آن را برای بررسی ارسال کنید. پذیرش و آماده‌سازی ارسال کلیه کالاها منوط به قوانین حمل‌ونقل بین‌المللی و مقررات گمرکی ایران و روسیه است.</span></p>
	</div>
</section>

<section class="lr-src-cta">
	<?php liferuss_cargo_picture( 'cta-band-cargo.webp', '', 1600, 300, false, '100vw' ); ?>
	<div class="lr-src-cta__copy">
		<h2>حمل مطمئن، <span>فرصت‌های بزرگ‌تر</span></h2>
		<p class="lr-cgo-cta__kicker">مسیر درست برای کسب‌وکار جهانی</p>
		<p>
			<a class="btn btn-gold" href="#cargo-form">درخواست ارسال بار</a>
			<a class="btn lr-src-cta__line" href="<?php echo esc_url( liferuss_cargo_consult_url() ); ?>">مشاوره کارگو با لایف روس</a>
		</p>
	</div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
  var params = new URLSearchParams(window.location.search);
  var kind = params.get("kind");
  var select = document.querySelector("#cargo-form [name='consult_cargo_kind']");
  if (kind && select) {
    var safe = kind.replace(/[^a-z]/g, "");
    if (select.querySelector("option[value='" + safe + "']")) {
      select.value = safe;
    }
  }
  var picker = document.getElementById("lr-cgo-files");
  var names = document.getElementById("lr-cgo-file-names");
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
