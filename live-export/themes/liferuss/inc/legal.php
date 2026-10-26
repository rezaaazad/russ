<?php
/**
 * Privacy and terms pages. Seeded once; later edits in the editor are kept.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Link label for the active language. The page body stays Persian.
 *
 * @param string $which privacy|terms.
 * @return string
 */
function liferuss_legal_label( $which ) {
	$lang = function_exists( 'liferuss_current_lang' ) ? liferuss_current_lang() : 'fa';
	$map  = array(
		'fa' => array(
			'privacy' => 'حریم خصوصی',
			'terms'   => 'شرایط استفاده',
		),
		'en' => array(
			'privacy' => 'Privacy',
			'terms'   => 'Terms',
		),
		'ru' => array(
			'privacy' => 'Конфиденциальность',
			'terms'   => 'Условия',
		),
		'ar' => array(
			'privacy' => 'الخصوصية',
			'terms'   => 'الشروط',
		),
	);
	$pack = isset( $map[ $lang ] ) ? $map[ $lang ] : $map['fa'];
	return isset( $pack[ $which ] ) ? $pack[ $which ] : $map['fa']['privacy'];
}

/**
 * Consent line under a lead form.
 *
 * @param string $note Existing note.
 */
function liferuss_the_form_consent( $note ) {
	$note = trim( (string) $note );
	echo '<p class="form-note">';
	if ( '' !== $note ) {
		echo esc_html( $note ) . ' ';
	}
	echo '<a href="' . esc_url( liferuss_url( '/privacy-policy/' ) ) . '">' . esc_html( liferuss_legal_label( 'privacy' ) ) . '</a>';
	echo ' · ';
	echo '<a href="' . esc_url( liferuss_url( '/terms/' ) ) . '">' . esc_html( liferuss_legal_label( 'terms' ) ) . '</a>';
	echo '</p>';
}

/**
 * Privacy policy HTML. Counsel should review before this is treated as final.
 *
 * @return string
 */
function liferuss_privacy_content() {
	return '<p class="legal-review"><strong>برای بازبینی مالک.</strong> این متن پیش‌نویس کاربردی وب‌سایت است و جایگزین نظر مشاور حقوقی نیست. نام شخص حقوقی، نشانی ثبت، و مدت نگهداری را پیش از اتکا با وکیل خود قطعی کنید. پس از بازبینی می‌توانید همین برگه را در ویرایشگر وردپرس عوض کنید.</p>
<h2>این صفحه درباره چیست</h2>
<p>لایف روس (LifeRuss) از راه وب‌سایت liferuss.com درباره تحصیل، هماهنگی امور دانشجویی، و خدمات بازرگانی و ارسال بین ایران و روسیه اطلاع می‌دهد و درخواست مشاوره می‌گیرد. این صفحه می‌گوید چه داده‌ای از فرم‌ها جمع می‌شود، برای چه کاری استفاده می‌شود، و اینکه این داده‌ها فروخته نمی‌شوند.</p>
<h2>چه داده‌ای از فرم‌ها جمع می‌شود</h2>
<p>فرم مشاوره و فرم‌های مرتبط این موارد را می‌گیرند: نام، شماره تلفن، نوع درخواست، و زبان صفحه‌ای که فرم از آن فرستاده شده است. بسته به نوع درخواست، مقطع تحصیلی، متن پیام، یا جزئیات خدمت (مبدأ و مقصد، نوع کالا یا محموله، مقدار، توضیح) هم پرسیده می‌شود. اگر فرم امکان پیوست داشته باشد و فایلی بفرستید، همان فایل همراه درخواست ذخیره می‌شود. برای امنیت و جلوگیری از ارسال تکراری، نشانی IP و زمان ثبت هم کنار درخواست می‌ماند.</p>
<h2>این داده‌ها برای چیست</h2>
<p>هدف، پاسخ به همان درخواست، هماهنگی مشاوره یا خدمتی که خودتان خواسته‌اید، و نگهداری سابقه پیگیری است. داده‌ها برای فروش، اجاره، یا تبلیغات شخص ثالث استفاده نمی‌شوند.</p>
<h2>داده به چه کسی می‌رسد</h2>
<p>درخواست را کارکنان لایف روس که باید به آن رسیدگی کنند می‌بینند. میزبانی سایت برای نگهداری فنی لازم است. اگر در تنظیمات سایت ابزار تحلیل یا اسکریپت اضافه فعال شده باشد، همان ابزار طبق تنظیم خودِ سایت بارگذاری می‌شود؛ در حالت پیش‌فرض این متن فرض نمی‌کند چنین ابزاری روشن است. پیام‌رسان‌هایی مثل واتساپ یا تلگرام فقط وقتی در کارند که خودتان از لینک منتشرشده استفاده کنید و قواعد همان سرویس بر آن گفتگو حاکم است.</p>
<h2>چه مدت می‌ماند</h2>
<p>سابقه درخواست تا وقتی برای پیگیری همان کار لازم است می‌ماند، یا تا وقتی حذف آن را بخواهید و مانع قانونی برای حذف نباشد. فایل پیوست، پس از بسته شدن درخواست، طبق برنامهٔ فعلی سامانه حدود دوازده ماه بعد از بایگانی حذف می‌شود. این مدت را وکیل شما باید با تکلیف نگهداری مدارک تطبیق دهد.</p>
<h2>درخواست شما</h2>
<p>می‌توانید بخواهید بدانید چه درخواستی از شما ثبت شده، اشتباه آن اصلاح شود، یا حذف شود. راه تماس در پایین همین صفحه است. اگر شماره یا توضیح را ناقص بفرستید، ممکن است پاسخ به همان درخواست ممکن نباشد.</p>
<h2>تماس</h2>
<p>برای پرسش درباره داده‌ها از فرم تماس سایت استفاده کنید. اگر ایمیل پشتیبانی در تنظیمات سایت پر شده باشد، در پایین این صفحه هم نشان داده می‌شود. شماره تلفنی در این متن نوشته نشده است.</p>';
}

/**
 * Terms HTML. Counsel should review before this is treated as final.
 *
 * @return string
 */
function liferuss_terms_content() {
	return '<p class="legal-review"><strong>برای بازبینی مالک.</strong> این متن پیش‌نویس قواعد استفاده از وب‌سایت است و جایگزین قرارداد جداگانه یا نظر مشاور حقوقی نیست. مرجع رسیدگی به اختلاف و مشخصات شخص حقوقی را وکیل شما باید اضافه کند. پس از بازبینی می‌توانید همین برگه را در ویرایشگر وردپرس عوض کنید.</p>
<h2>موضوع سایت</h2>
<p>لایف روس (LifeRuss) در liferuss.com درباره تحصیل در روسیه، هماهنگی امور مرتبط با دانشجو، و معرفی خدمات بازرگانی و ارسال بین ایران و روسیه اطلاع‌رسانی می‌کند. لایف روس دانشگاه، اداره مهاجرت، یا مرجع صدور ویزا نیست.</p>
<h2>فرم درخواست</h2>
<p>فرستادن فرم یعنی می‌خواهید درباره همان موضوع با شما تماس گرفته شود. این کار به‌تنهایی قرارداد پرداخت‌شده، پذیرش دانشگاه، ویزا، بورسیه، اقامت، یا نتیجه تجاری نمی‌سازد. هر هزینه یا تعهد اجرایی فقط وقتی معتبر است که جداگانه و روشن توافق شود.</p>
<h2>اطلاعاتی که می‌فرستید</h2>
<p>باید نام، شماره تماس، و توضیح درخواست درست باشد. لایف روس بر اساس همان اطلاعات پاسخ می‌دهد و مسئولیتی برای تصمیم دانشگاه، سفارت، گمرک، بانک، یا طرف معامله ندارد. نتیجه آن مراجع از پیش قابل تعهد نیست.</p>
<h2>محتوای سایت</h2>
<p>متن‌ها و راهنماهای سایت اطلاع عمومی هستند و با تغییر مقررات، شهریه، یا رویه اجرایی که از اختیار لایف روس خارج است ممکن است کهنه شوند. عدد یا وعده‌ای که در این متن نیامده، تعهد سایت نیست.</p>
<h2>مالکیت محتوا</h2>
<p>متن، نام LifeRuss، و نشان سایت متعلق به گرداننده همین وب‌سایت است، مگر جایی که خلاف آن نوشته شده باشد. استفاده تجاری از آن‌ها بدون اجازه مجاز نیست.</p>
<h2>استفاده مجاز</h2>
<p>فرم‌ها برای درخواست واقعی هستند. ارسال انبوه، اطلاعات ساختگی، یا فایل نامرتبط مجاز نیست و می‌تواند بدون پاسخ بماند.</p>
<h2>خدمات بیرونی</h2>
<p>اگر لینک پیام‌رسان یا سایت دیگری منتشر شود، با ترک liferuss.com قواعد همان سرویس اعمال می‌شود.</p>
<h2>تغییر این شروط</h2>
<p>متن منتشرشده در همین برگه، قواعد فعلی استفاده از وب‌سایت است. ادامه استفاده از سایت پس از به‌روزرسانی یعنی همان متن جاری ملاک است. قرارداد جداگانه‌ای که برای یک کار مشخص امضا شود بر همان کار مقدم است.</p>';
}

/**
 * Create the two pages if they are missing. Empty pages receive the draft text.
 */
function liferuss_maybe_seed_legal() {
	if ( ! function_exists( 'liferuss_ensure_page' ) ) {
		return;
	}
	$pages = array(
		'privacy-policy' => array(
			'title'   => 'حریم خصوصی',
			'content' => liferuss_privacy_content(),
		),
		'terms'          => array(
			'title'   => 'شرایط استفاده',
			'content' => liferuss_terms_content(),
		),
	);
	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug );
		if ( $existing instanceof WP_Post ) {
			update_post_meta( $existing->ID, '_wp_page_template', 'templates/legal.php' );
			if ( '' === trim( wp_strip_all_tags( $existing->post_content ) ) ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $page['content'],
					)
				);
			}
			continue;
		}
		liferuss_ensure_page( $page['title'], $slug, $page['content'], 'templates/legal.php' );
	}
}
add_action( 'init', 'liferuss_maybe_seed_legal', 32 );

/**
 * Contact line that only names an address the operator has actually saved.
 */
function liferuss_legal_contact_line() {
	$email = function_exists( 'liferuss_public_contact' ) ? liferuss_public_contact( 'email' ) : '';
	echo '<p class="legal-contact">';
	echo '<a href="' . esc_url( liferuss_url( '/contact/' ) ) . '#consultation">' . esc_html( 'فرم تماس' ) . '</a>';
	if ( is_email( $email ) ) {
		echo ' · <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}
	echo '</p>';
}
