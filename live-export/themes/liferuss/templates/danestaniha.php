<?php
/**
 * Template Name: دانستنی‌های روسیه
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$status  = isset( $_GET['consult'] ) ? sanitize_key( wp_unslash( $_GET['consult'] ) ) : '';
$message = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
$cards   = array(
	array(
		'file'  => 'card-souvenirs.webp',
		'title' => 'سوغات روسیه',
		'text'  => 'با معروف‌ترین سوغات و یادگاری‌های روسی از صنایع دستی تا خوراکی‌ها آشنا شوید.',
		'slug'  => 'russia-souvenirs',
		'icon'  => 'box',
		'alt'   => 'ماتریوشکا و سماور، سوغات روسیه',
	),
	array(
		'file'  => 'card-cities.webp',
		'title' => 'شهرهای مهم روسیه',
		'text'  => 'با معرفی شهرهای بزرگ و جاذبه‌های مانند مسکو، سن‌پترزبورگ و کازان آشنا شوید.',
		'slug'  => 'russia-cities',
		'icon'  => 'pin',
		'alt'   => 'کاخ و کلیسای سن‌پترزبورگ کنار کانال',
	),
	array(
		'file'  => 'card-food.webp',
		'title' => 'غذاهای روسیه',
		'text'  => 'با غذاهای سنتی و محبوب، از بورش، پلمنی و پیروژکی تا خوراکی‌های محلی و نوشیدنی‌های رایج آشنا شوید.',
		'slug'  => 'russia-food',
		'icon'  => 'cart',
		'alt'   => 'سوپ بورش و پیروژکی',
	),
	array(
		'file'  => 'card-culture.webp',
		'title' => 'فرهنگ و آداب روسیه',
		'text'  => 'با فرهنگ، سبک زندگی، آداب و رسوم مردم، هنر و جشن‌های تاریخی آشنا شوید.',
		'slug'  => 'russia-culture',
		'icon'  => 'users',
		'alt'   => 'زوج روسی با لباس سنتی',
	),
);
$bullets = array(
	array( 'icon' => 'cap', 'text' => 'راهنمای تحصیل و فرصت‌ها' ),
	array( 'icon' => 'home', 'text' => 'مسیر زندگی در روسیه' ),
	array( 'icon' => 'building', 'text' => 'معرفی شهرها و جاذبه‌ها' ),
	array( 'icon' => 'docs', 'text' => 'اطلاعات کاربردی و تصویری' ),
);
$steps = array(
	array( 'icon' => 'search', 'title' => 'انتخاب موضوع', 'text' => 'موضوع مورد علاقه خود را از میان دسته‌بندی‌ها انتخاب کنید.' ),
	array( 'icon' => 'book', 'title' => 'مطالعه مطالب', 'text' => 'مقالات و راهنماهای جامع و به‌روز را بخوانید.' ),
	array( 'icon' => 'bolt', 'title' => 'کسب آگاهی کاربردی', 'text' => 'با نکات واقعی و تجربیات دیگران آشنا شوید.' ),
	array( 'icon' => 'plane', 'title' => 'برنامه‌ریزی گام به گام', 'text' => 'مسیر تحصیل، سفر یا مهاجرت خود را با مشورت ما طراحی کنید.' ),
	array( 'icon' => 'star', 'title' => 'شروع و موفقیت', 'text' => 'با آمادگی بیشتر، تجربه‌ای مطمئن در روسیه بسازید.' ),
);
$tiles = array(
	array( 'file' => 'tile-food.webp', 'title' => 'غذاها و خوراکی‌ها', 'slug' => 'russia-food', 'icon' => 'cart', 'alt' => 'بلینی با خاویار' ),
	array( 'file' => 'tile-culture.webp', 'title' => 'فرهنگ و مردم روسیه', 'slug' => 'russia-culture', 'icon' => 'users', 'alt' => 'زوج روسی کنار سماور و بلینی' ),
	array( 'file' => 'tile-sights.webp', 'title' => 'جاذبه‌های گردشگری', 'slug' => 'russia-attractions', 'icon' => 'building', 'alt' => 'کلیسای سنت‌باسیل در میدان سرخ' ),
	array( 'file' => 'tile-weather.webp', 'title' => 'آب و هوا', 'slug' => 'russia-weather', 'icon' => 'globe', 'alt' => 'جنگل برفی روسیه' ),
	array( 'file' => 'tile-transport.webp', 'title' => 'حمل‌ونقل در روسیه', 'slug' => 'russia-transport', 'icon' => 'plane', 'alt' => 'قطار مسافری در ایستگاه' ),
);
?>
<article class="lr-dn">
	<header class="lr-dn-hero">
		<img class="lr-dn-hero__photo" src="<?php echo esc_url( liferuss_danestaniha_img( liferuss_danestaniha_hero_file() ) ); ?>" width="1600" height="700" alt="گردشگر با کلاه و کوله‌پشتی رو به کلیسای سنت‌باسیل و کرملین، پرچم روسیه و عروسک‌های ماتریوشکا" decoding="async" fetchpriority="high">
		<div class="lr-dn-hero__scrim" aria-hidden="true"></div>
		<div class="lr-dn-hero__copy">
			<h1>دانستنی‌های <span>روسیه</span></h1>
			<p class="lr-dn-hero__sub">فرهنگ، شهرها، غذاها، سبک زندگی و نکات مهمی که قبل از سفر، تحصیل یا مهاجرت به روسیه باید بدانید.</p>
			<p>با مرور منابع و مطالب، از آداب و رسوم مردم روسیه و زبان روسی تا جاذبه‌های زندگی، حمل‌ونقل، امنیت و تجربه‌های واقعی زندگی در روسیه با شما همراه هستیم.</p>
			<ul class="lr-dn-bullets">
				<?php foreach ( $bullets as $bullet ) : ?>
					<li>
						<span class="lr-dn-ico" aria-hidden="true"><?php echo liferuss_icon( $bullet['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo esc_html( $bullet['text'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</header>

	<section class="lr-dn-cards" aria-label="موضوع‌های دانستنی">
		<div class="lr-dn-cards__grid">
			<?php foreach ( $cards as $card ) : ?>
				<a class="lr-dn-card" href="<?php echo esc_url( liferuss_danestaniha_topic_url( $card['slug'] ) ); ?>" aria-label="<?php echo esc_attr( 'مطالعه بیشتر دربارهٔ ' . $card['title'] ); ?>">
					<img src="<?php echo esc_url( liferuss_danestaniha_img( $card['file'] ) ); ?>" width="800" height="500" alt="<?php echo esc_attr( $card['alt'] ); ?>" loading="lazy" decoding="async">
					<span class="lr-dn-card__badge" aria-hidden="true"><?php echo liferuss_icon( $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<strong><?php echo esc_html( $card['title'] ); ?></strong>
					<em><?php echo esc_html( $card['text'] ); ?></em>
					<span class="lr-dn-card__more">مطالعه بیشتر</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="lr-dn-steps" aria-labelledby="lr-dn-steps-title">
		<div class="lr-dn-wrap">
			<h2 id="lr-dn-steps-title">مسیر <span>آشنایی با روسیه</span></h2>
			<p>برای یک شروع آسان و انتخاب آگاهانه، این ۵ مرحله را با ما همراه باشید.</p>
			<ol>
				<?php foreach ( $steps as $index => $step ) : ?>
					<li>
						<span class="lr-dn-steps__n"><?php echo esc_html( liferuss_local_digits( (string) ( $index + 1 ) ) ); ?></span>
						<span class="lr-dn-ico" aria-hidden="true"><?php echo liferuss_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $step['title'] ); ?></strong>
						<em><?php echo esc_html( $step['text'] ); ?></em>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="lr-dn-request" id="danestaniha-form">
		<span id="consultation"></span>
		<div class="lr-dn-wrap lr-dn-request__grid">
			<div class="lr-dn-form">
				<h2>فرم <span>دریافت مشاوره</span></h2>
				<p class="lr-dn-form__lead">اگر برای تحصیل، زندگی، سفر یا کار در روسیه سؤال دارید، فرم زیر را تکمیل کنید تا کارشناسان ما با شما تماس بگیرند.</p>
				<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite"><?php echo $message ? esc_html( $message ) : ''; ?></div>
				<form class="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
					<input type="hidden" name="action" value="liferuss_consult">
					<input type="hidden" name="consult_type" value="danestaniha">
					<input type="hidden" name="consult_lang" value="fa">
					<input type="hidden" name="crm_goal" value="دانستنی‌های روسیه">
					<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
					<div class="hp" aria-hidden="true"><label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label></div>
					<?php if ( function_exists( 'liferuss_form_tracking_fields' ) ) { liferuss_form_tracking_fields(); } ?>
					<div class="lr-dn-fields">
						<label>
							<span>نام و نام خانوادگی</span>
							<input type="text" name="consult_name" required autocomplete="name" maxlength="150" placeholder="مثال: علی محمدی">
						</label>
						<label>
							<span>نوع درخواست</span>
							<select name="consult_guide_kind" required>
								<option value="">انتخاب کنید</option>
								<?php foreach ( liferuss_danestaniha_kinds() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span>شماره تماس</span>
							<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" dir="ltr" maxlength="16" placeholder="شماره موبایل">
						</label>
						<label>
							<span>موضوع مشاوره</span>
							<select name="consult_guide_topic" required>
								<option value="">انتخاب کنید</option>
								<?php foreach ( liferuss_danestaniha_topics() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span>ایمیل (اختیاری)</span>
							<input type="email" name="consult_email" autocomplete="email" dir="ltr" maxlength="100" placeholder="example@email.com">
						</label>
						<label class="lr-dn-fields__wide">
							<span>توضیحات بیشتر (اختیاری)</span>
							<textarea name="consult_notes" rows="3" placeholder="در صورت نیاز توضیحات خود را وارد کنید."></textarea>
						</label>
					</div>
					<div class="lr-dn-submit">
						<button class="btn btn-gold" type="submit">ارسال درخواست</button>
						<small>با ارسال این فرم، کارشناسان ما در اولین فرصت با شما تماس خواهند گرفت.</small>
					</div>
				</form>
			</div>
			<aside class="lr-dn-banner">
				<img src="<?php echo esc_url( liferuss_danestaniha_img( 'side-banner-kremlin.webp' ) ); ?>" width="600" height="900" alt="برج کرملین هنگام غروب" loading="lazy" decoding="async">
				<div class="lr-dn-banner__copy">
					<?php liferuss_the_wordmark(); ?>
					<strong>روسیه را</strong>
					<span>آگاهانه کشف کنید</span>
				</div>
			</aside>
		</div>
	</section>

	<section class="lr-dn-tiles" aria-labelledby="lr-dn-tiles-title">
		<div class="lr-dn-wrap">
			<div class="lr-dn-tiles__head">
				<h2 id="lr-dn-tiles-title">موضوعات محبوب در دانستنی‌های <span>روسیه</span></h2>
				<a href="<?php echo esc_url( liferuss_danestaniha_all_url() ); ?>">مشاهده همه مطالب</a>
			</div>
			<div class="lr-dn-tiles__grid">
				<?php foreach ( $tiles as $tile ) : ?>
					<a href="<?php echo esc_url( liferuss_danestaniha_topic_url( $tile['slug'] ) ); ?>" aria-label="<?php echo esc_attr( 'مطالعه بیشتر دربارهٔ ' . $tile['title'] ); ?>">
						<img src="<?php echo esc_url( liferuss_danestaniha_img( $tile['file'] ) ); ?>" width="500" height="400" alt="<?php echo esc_attr( $tile['alt'] ); ?>" loading="lazy" decoding="async">
						<span class="lr-dn-card__badge" aria-hidden="true"><?php echo liferuss_icon( $tile['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<strong><?php echo esc_html( $tile['title'] ); ?></strong>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="lr-dn-cta">
		<img src="<?php echo esc_url( liferuss_danestaniha_img( 'cta-band.webp' ) ); ?>" width="1600" height="300" alt="" loading="lazy" decoding="async">
		<div class="lr-dn-cta__copy">
			<h2>با <span>LifeRuss</span>، روسیه را متفاوت تجربه کنید.</h2>
			<p>اطلاعات معتبر، راهنمایی کاربردی و همراهی در تمام مسیر.</p>
			<a class="btn btn-gold" href="<?php echo esc_url( liferuss_danestaniha_consult_url() ); ?>">دریافت مشاوره رایگان</a>
		</div>
	</section>
</article>
<?php
get_footer();
