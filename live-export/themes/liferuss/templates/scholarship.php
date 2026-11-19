<?php
/**
 * Template Name: بورسیه روسیه
 *
 * Replaces the empty /scholarships/ catalog index. The page id is kept when
 * that page already exists; this file is also the archive template.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$status  = isset( $_GET['consult'] ) ? sanitize_key( wp_unslash( $_GET['consult'] ) ) : '';
$message = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
$preset  = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';
$consult = liferuss_scholarship_consult_url();
$stats   = liferuss_scholarship_stats();
$cards   = array(
	array(
		'file'  => liferuss_scholarship_consult_file(),
		'title' => 'مشاوره تخصصی',
		'text'  => 'بررسی شرایط شما و راهنمایی برای انتخاب مسیر مناسب تحصیل در روسیه.',
		'link'  => $consult,
		'label' => 'دریافت مشاوره',
		'icon'  => 'chat',
		'alt'   => 'مشاور در دفتر با لپ‌تاپ',
	),
	array(
		'file'  => 'card-subjects.webp',
		'title' => 'رشته‌های متنوع',
		'text'  => 'پزشکی، دندانپزشکی، مهندسی و رشته‌های دیگر در مقاطع مختلف.',
		'link'  => '#lr-sch-fields',
		'label' => 'مشاهده رشته‌ها',
		'icon'  => 'book',
		'alt'   => 'دسته‌کتاب',
	),
	array(
		'file'  => 'card-costs.webp',
		'title' => 'پوشش هزینه‌ها',
		'text'  => 'بسته به نوع بورسیه، بخشی از هزینه‌ها ممکن است پوشش داده شود.',
		'link'  => liferuss_url( '/costs/' ),
		'label' => 'اطلاعات بیشتر',
		'icon'  => 'wallet',
		'alt'   => 'کلاه فارغ‌التحصیلی روی سکه',
	),
	array(
		'file'  => 'card-universities.webp',
		'title' => 'دانشگاه‌های معتبر روسیه',
		'text'  => 'آشنایی با دانشگاه‌های روسیه و مسیر پذیرش در هر کدام.',
		'link'  => liferuss_url( '/universities/' ),
		'label' => 'مشاهده دانشگاه‌ها',
		'icon'  => 'building',
		'alt'   => 'ساختمان اصلی دانشگاه مسکو',
	),
);
$bullets = array(
	array( 'icon' => 'handshake', 'text' => 'همکاری رسمی در ایران و روسیه' ),
	array( 'icon' => 'chat', 'text' => 'مشاوره تخصصی و راهنمایی اختصاصی' ),
	array( 'icon' => 'shield', 'text' => 'پشتیبانی کامل از پذیرش تا اقامت' ),
	array( 'icon' => 'cap', 'text' => 'فرصت تحصیل در دانشگاه‌های روسیه' ),
);
$steps = array(
	array( 'icon' => 'docs', 'title' => 'ثبت درخواست', 'text' => 'تکمیل فرم و ارسال مدارک اولیه' ),
	array( 'icon' => 'search', 'title' => 'بررسی و ارزیابی', 'text' => 'بررسی شرایط و مدارک شما' ),
	array( 'icon' => 'stamp', 'title' => 'دریافت پذیرش', 'text' => 'پیگیری پذیرش از دانشگاه' ),
	array( 'icon' => 'passport', 'title' => 'اخذ ویزا', 'text' => 'راهنمایی برای مراحل ویزا' ),
	array( 'icon' => 'plane', 'title' => 'آمادگی و سفر', 'text' => 'آمادگی برای سفر و شروع تحصیل' ),
);
$tiles = array(
	array( 'file' => 'field-medicine.webp', 'label' => 'پزشکی', 'field' => 'medicine', 'alt' => 'گوشی پزشکی' ),
	array( 'file' => 'field-dentistry.webp', 'label' => 'دندانپزشکی', 'field' => 'dentistry', 'alt' => 'ابزار دندانپزشکی' ),
	array( 'file' => 'field-engineering.webp', 'label' => 'مهندسی', 'field' => 'engineering', 'alt' => 'تأسیسات صنعتی' ),
	array( 'file' => 'field-graduate.webp', 'label' => 'کارشناسی ارشد و دکتری', 'field' => 'other', 'alt' => 'کلاه فارغ‌التحصیلی و دیپلم' ),
);
?>
<article class="lr-sch">
	<header class="lr-sch-hero">
		<img class="lr-sch-hero__photo" src="<?php echo esc_url( liferuss_scholarship_img( liferuss_scholarship_hero_file() ) ); ?>" width="1600" height="700" alt="دانشجوی دختر ایرانی با کلاه و ردای فارغ‌التحصیلی، پرچم ایران و روسیه و ساختمان دانشگاه دولتی مسکو" decoding="async" fetchpriority="high">
		<div class="lr-sch-hero__scrim" aria-hidden="true"></div>
		<div class="lr-sch-hero__copy">
			<h1>بورسیه <span>روسیه</span></h1>
			<p class="lr-sch-hero__sub">تحصیل رایگان و فرصت‌های طلایی در دانشگاه‌های روسیه</p>
			<p><?php echo esc_html( liferuss_scholarship_hero_lead() ); ?></p>
			<ul class="lr-sch-bullets">
				<?php foreach ( $bullets as $bullet ) : ?>
					<li>
						<span class="lr-sch-ico" aria-hidden="true"><?php echo liferuss_icon( $bullet['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo esc_html( $bullet['text'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</header>

	<section class="lr-sch-cards" aria-label="مسیر بورسیه">
		<div class="lr-sch-cards__grid">
			<?php foreach ( $cards as $card ) : ?>
				<a class="lr-sch-card" href="<?php echo esc_url( $card['link'] ); ?>">
					<img src="<?php echo esc_url( liferuss_scholarship_img( $card['file'] ) ); ?>" width="800" height="500" alt="<?php echo esc_attr( $card['alt'] ); ?>" loading="lazy" decoding="async">
					<span class="lr-sch-card__body">
						<span class="lr-sch-ico" aria-hidden="true"><?php echo liferuss_icon( $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $card['title'] ); ?></strong>
						<em><?php echo esc_html( $card['text'] ); ?></em>
						<span class="lr-sch-card__more"><?php echo esc_html( $card['label'] ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="lr-sch-steps" aria-labelledby="lr-sch-steps-title">
		<div class="lr-sch-wrap">
			<h2 id="lr-sch-steps-title">مراحل دریافت <span>بورسیه</span></h2>
			<ol>
				<?php foreach ( $steps as $index => $step ) : ?>
					<li>
						<span class="lr-sch-steps__n"><?php echo esc_html( liferuss_local_digits( (string) ( $index + 1 ) ) ); ?></span>
						<span class="lr-sch-ico" aria-hidden="true"><?php echo liferuss_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $step['title'] ); ?></strong>
						<em><?php echo esc_html( $step['text'] ); ?></em>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="lr-sch-request" id="scholarship-form">
		<span id="consultation"></span>
		<div class="lr-sch-wrap lr-sch-request__grid">
			<div class="lr-sch-form">
				<h2>فرم <span>درخواست بورسیه</span></h2>
				<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite"><?php echo $message ? esc_html( $message ) : ''; ?></div>
				<form class="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
					<input type="hidden" name="action" value="liferuss_consult">
					<input type="hidden" name="consult_type" value="scholarship">
					<input type="hidden" name="consult_lang" value="fa">
					<input type="hidden" name="crm_goal" value="بورسیه روسیه">
					<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
					<div class="hp" aria-hidden="true"><label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label></div>
					<?php if ( function_exists( 'liferuss_form_tracking_fields' ) ) { liferuss_form_tracking_fields(); } ?>
					<div class="lr-sch-fields">
						<label>
							<span>نام و نام خانوادگی</span>
							<input type="text" name="consult_name" required autocomplete="name" maxlength="150" placeholder="مثال: علی محمدی">
						</label>
						<label>
							<span>شماره تماس</span>
							<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" dir="ltr" maxlength="16" placeholder="شماره موبایل">
						</label>
						<label>
							<span>ایمیل</span>
							<input type="email" name="consult_email" autocomplete="email" dir="ltr" maxlength="100" placeholder="example@email.com">
						</label>
						<label>
							<span>مقطع تحصیلی موردنظر</span>
							<select name="consult_degree">
								<option value="">انتخاب کنید</option>
								<?php foreach ( liferuss_scholarship_degrees() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span>رشته مورد علاقه</span>
							<select name="consult_field">
								<option value="">انتخاب کنید</option>
								<?php foreach ( liferuss_scholarship_fields() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $preset, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span>مدرک زبان (در صورت وجود)</span>
							<select name="consult_lang_cert">
								<option value="">انتخاب کنید</option>
								<?php foreach ( liferuss_scholarship_languages() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="lr-sch-fields__wide">
							<span>توضیحات تکمیلی</span>
							<textarea name="consult_notes" rows="3" placeholder="هر توضیح یا سؤالی دارید اینجا بنویسید."></textarea>
						</label>
					</div>
					<div class="lr-sch-submit">
						<button class="btn btn-gold" type="submit">ارسال درخواست</button>
						<small>با ارسال فرم، <a href="<?php echo esc_url( liferuss_url( '/privacy-policy/' ) ); ?>">سیاست حریم خصوصی</a> را می‌پذیرید.</small>
					</div>
				</form>
			</div>
			<aside class="lr-sch-banner">
				<img src="<?php echo esc_url( liferuss_scholarship_img( 'side-banner-kremlin.webp' ) ); ?>" width="600" height="900" alt="سن‌باسیل و کرملین در غروب" loading="lazy" decoding="async">
				<div class="lr-sch-banner__copy">
					<?php liferuss_the_wordmark(); ?>
					<strong>تحصیل در روسیه</strong>
					<span>با لایف روس</span>
				</div>
			</aside>
		</div>
	</section>

	<section class="lr-sch-fields-band" id="lr-sch-fields" aria-labelledby="lr-sch-fields-title">
		<div class="lr-sch-wrap">
			<h2 id="lr-sch-fields-title">محبوب‌ترین رشته‌های بورسیه در <span>روسیه</span></h2>
			<div class="lr-sch-tiles">
				<?php foreach ( $tiles as $tile ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'field', $tile['field'], liferuss_url( '/scholarships/' ) ) . '#scholarship-form' ); ?>">
						<img src="<?php echo esc_url( liferuss_scholarship_img( $tile['file'] ) ); ?>" width="500" height="400" alt="<?php echo esc_attr( $tile['alt'] ); ?>" loading="lazy" decoding="async">
						<strong><?php echo esc_html( $tile['label'] ); ?></strong>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( is_array( $stats ) ) : ?>
		<section class="lr-sch-stats" aria-label="آمار">
			<ul>
				<?php foreach ( $stats as $stat ) : ?>
					<li>
						<span class="lr-sch-ico" aria-hidden="true"><?php echo 'steps' === $stat['icon'] && function_exists( 'liferuss_hv2_icon' ) ? liferuss_hv2_icon( 'steps' ) : liferuss_icon( $stat['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $stat['value'] ); ?></strong>
						<em><?php echo esc_html( $stat['label'] ); ?></em>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<section class="lr-sch-cta">
		<img src="<?php echo esc_url( liferuss_scholarship_img( 'cta-band-scholarship.webp' ) ); ?>" width="1600" height="300" alt="" loading="lazy" decoding="async">
		<div class="lr-sch-cta__copy">
			<h2>با <span>LifeRuss</span> آینده تحصیلی خود را در روسیه بسازید.</h2>
			<a class="btn btn-gold" href="#scholarship-form">درخواست بورسیه روسیه</a>
			<a class="btn lr-sch-cta__line" href="<?php echo esc_url( $consult ); ?>">مشاوره رایگان</a>
		</div>
	</section>
</article>
<?php
get_footer();
