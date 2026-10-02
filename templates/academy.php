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

$buy = static function ( $type, $id, $label ) {
	echo '<form method="post" action="' . esc_url( liferuss_url( '/academy/checkout/' ) ) . '">';
	wp_nonce_field( 'lr_academy', 'lr_academy_nonce' );
	echo '<input type="hidden" name="lr_academy_action" value="checkout">';
	echo '<input type="hidden" name="item_type" value="' . esc_attr( $type ) . '">';
	echo '<input type="hidden" name="item_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<input type="hidden" name="redirect_to" value="' . esc_url( liferuss_url( '/academy/checkout/' ) ) . '">';
	echo '<button class="btn btn-gold" type="submit">' . esc_html( $label ) . '</button></form>';
};

$icons = array(
	'book'     => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
	'cross'    => '<path d="M12 5v14M5 12h14"/>',
	'plane'    => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9L2 9 22 2z"/>',
	'cap'      => '<path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
	'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.8V21h14V9.8"/>',
	'building' => '<path d="M3 21h18"/><path d="M6 21V4h7v17"/><path d="M13 21V9h5v12"/>',
	'card'     => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
	'file'     => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/>',
	'pulse'    => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
	'video'    => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/>',
	'text'     => '<path d="M5 6h14M5 12h14M5 18h9"/>',
	'quiz'     => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1.5 1-1.5 2"/><path d="M12 17h.01"/>',
	'live'     => '<circle cx="12" cy="12" r="3"/><path d="M7 7a7 7 0 0 0 0 10M17 7a7 7 0 0 1 0 10"/>',
	'lock'     => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
	'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a3 3 0 0 1 0 5.74"/>',
	'check'    => '<path d="M20 6 9 17l-5-5"/>',
	'shield'   => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>',
	'star'     => '<path d="m12 3 2.4 5.4L20 9.2l-4 3.8.9 5.5L12 16.2 7.1 18.5 8 13 4 9.2l5.6-.8z"/>',
);

$icon = static function ( $name, $class = 'icon-circle' ) use ( $icons ) {
	$path = $icons[ $name ] ?? $icons['book'];
	echo '<span class="' . esc_attr( $class ) . '" aria-hidden="true"><svg viewBox="0 0 24 24">' . $path . '</svg></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon paths from this file.
};

$cat_icons = array(
	'russian-from-zero' => 'book',
	'medical-russian'   => 'cross',
	'newcomer-russian'  => 'plane',
	'padfak-prep'       => 'cap',
	'life-in-russia'    => 'home',
	'university-entry'  => 'building',
	'banking-russia'    => 'card',
	'migration-docs'    => 'file',
	'medical-specialty' => 'pulse',
);

$lesson_meta = array();
$by_teacher  = array();
if ( in_array( $screen, array( 'landing', 'courses', 'category', 'course' ), true ) && class_exists( '\LifeRuss\Core\Academy\Catalog' ) ) {
	$lesson_meta = \LifeRuss\Core\Academy\Catalog::lesson_meta();
	foreach ( (array) ( $context['instructors'] ?? array() ) as $teacher ) {
		$by_teacher[ (int) $teacher['id'] ] = $teacher;
	}
}

$card = static function ( $course ) use ( $lesson_meta, $by_teacher ) {
	$meta     = $lesson_meta[ (int) $course['id'] ] ?? array( 'n' => 0, 'seconds' => 0 );
	$teacher  = $by_teacher[ (int) ( $course['instructor_id'] ?? 0 ) ] ?? null;
	$duration = class_exists( '\LifeRuss\Core\Academy\Catalog' ) ? \LifeRuss\Core\Academy\Catalog::duration_label( $course, $meta ) : '';
	$sale     = class_exists( '\LifeRuss\Core\Academy\Catalog' ) ? \LifeRuss\Core\Academy\Catalog::price( $course ) : (int) $course['price'];
	$list     = (int) $course['price'];
	$cut      = empty( $course['is_free'] ) && $sale > 0 && $sale < $list;
	$thumb    = trim( (string) ( $course['thumbnail'] ?? '' ) );
	echo '<a class="academy-course-card" href="' . esc_url( liferuss_url( '/academy/courses/' . $course['slug'] . '/' ) ) . '">';
	echo '<span class="academy-thumb">';
	if ( '' !== $thumb ) {
		echo '<img src="' . esc_url( $thumb ) . '" alt="">';
	} else {
		echo '<svg viewBox="0 0 640 360" role="img" aria-label="' . esc_attr( (string) $course['title'] ) . '"><rect width="640" height="360" fill="#0b2341"/><circle cx="320" cy="150" r="54" fill="none" stroke="#e8b923" stroke-width="6"/><path d="M300 128l48 28-48 28z" fill="#e8b923"/><text x="320" y="250" text-anchor="middle" fill="#ffffff" font-size="28" font-family="Vazirmatn, Tahoma, sans-serif">آکادمی لایف‌روس</text></svg>';
	}
	echo '</span><span class="academy-course-body">';
	echo '<h3>' . esc_html( (string) $course['title'] ) . '</h3>';
	echo '<p class="academy-course-meta">';
	if ( ! empty( $course['level'] ) ) {
		echo '<span>' . esc_html( (string) $course['level'] ) . '</span>';
	}
	echo '<span>' . esc_html( sprintf( '%s درس', number_format_i18n( (int) $meta['n'] ) ) ) . '</span>';
	if ( '' !== $duration ) {
		echo '<span>' . esc_html( $duration ) . '</span>';
	}
	echo '</p>';
	echo '<p class="academy-course-foot"><span>' . esc_html( $teacher ? (string) $teacher['name'] : 'آکادمی لایف‌روس' ) . '</span>';
	if ( ! empty( $course['is_free'] ) || $sale < 1 ) {
		echo '<b class="academy-free">رایگان</b>';
	} elseif ( $cut ) {
		echo '<b><del>' . esc_html( number_format_i18n( $list ) ) . '</del> ' . esc_html( number_format_i18n( $sale ) ) . ' تومان</b>';
	} else {
		echo '<b>' . esc_html( number_format_i18n( $sale ) ) . ' تومان</b>';
	}
	echo '</span></span></a>';
};

$lines = class_exists( '\LifeRuss\Core\Academy\Catalog' ) ? \LifeRuss\Core\Academy\Catalog::category_lines() : array();
?>
<?php if ( 'landing' === $screen && empty( $context['missing'] ) ) : ?>
	<?php $stats = class_exists( '\LifeRuss\Core\Academy\Catalog' ) ? \LifeRuss\Core\Academy\Catalog::stats() : array( 'courses' => 0, 'lessons' => 0, 'free' => 0, 'categories' => 0 ); ?>
	<header class="hero academy-hero">
		<div class="container hero-copy">
			<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_academy' ) ); ?></p>
			<h1>آکادمی لایف‌روس</h1>
			<p class="hero-lead">دوره‌های روسی، پادفک و زندگی در روسیه. قیمت‌ها تومان است و پرداخت با زرین‌پال انجام می‌شود.</p>
			<div class="hero-actions">
				<a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/academy/courses/' ) ); ?>">مشاهده دوره‌ها</a>
				<a class="btn btn-ghost-light" href="<?php echo esc_url( liferuss_url( '/academy/plans/' ) ); ?>">اشتراک</a>
			</div>
		</div>
		<div class="hero-trust">
			<div class="container">
				<div class="trust-grid">
					<div class="trust-item"><?php $icon( 'book' ); ?><div><strong><?php echo esc_html( number_format_i18n( (int) $stats['courses'] ) ); ?></strong><p>دوره</p></div></div>
					<div class="trust-item"><?php $icon( 'text' ); ?><div><strong><?php echo esc_html( number_format_i18n( (int) $stats['lessons'] ) ); ?></strong><p>درس</p></div></div>
					<div class="trust-item"><?php $icon( 'star' ); ?><div><strong><?php echo (int) $stats['free'] > 0 ? esc_html( number_format_i18n( (int) $stats['free'] ) ) : 'به‌زود'; ?></strong><p>دورهٔ رایگان</p></div></div>
					<div class="trust-item"><?php $icon( 'cap' ); ?><div><strong><?php echo esc_html( number_format_i18n( (int) $stats['categories'] ) ); ?></strong><p>دسته</p></div></div>
				</div>
			</div>
		</div>
	</header>
	<article class="section academy">
		<div class="container">
			<?php if ( $error ) : ?>
				<p class="lr-notice" role="status"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>
			<section class="academy-block">
				<div class="section-head"><h2>دسته‌ها</h2><p>هر دسته یک مسیر جداست. اگر دوره‌ای هنوز منتشر نشده، همان کارت با نشان به‌زود می‌ماند.</p></div>
				<div class="academy-cat-grid">
					<?php $course_counts = class_exists( '\LifeRuss\Core\Academy\Catalog' ) ? \LifeRuss\Core\Academy\Catalog::course_counts() : array(); ?>
					<?php foreach ( (array) ( $context['categories'] ?? array() ) as $category ) : ?>
						<?php
						$count = (int) ( $course_counts[ (int) $category['id'] ] ?? 0 );
						$blurb = trim( (string) ( $category['description'] ?? '' ) );
						if ( '' === $blurb ) {
							$blurb = (string) ( $lines[ $category['slug'] ] ?? '' );
						}
						?>
						<a class="academy-cat" href="<?php echo esc_url( liferuss_url( '/academy/category/' . $category['slug'] . '/' ) ); ?>">
							<?php $icon( $cat_icons[ $category['slug'] ] ?? 'book' ); ?>
							<span>
								<strong><?php echo esc_html( (string) $category['title'] ); ?></strong>
								<em><?php echo esc_html( $blurb ); ?></em>
								<?php if ( $count < 1 ) : ?>
									<b class="academy-soon">به‌زود</b>
								<?php else : ?>
									<b><?php echo esc_html( sprintf( '%s دوره', number_format_i18n( $count ) ) ); ?></b>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
			$featured = (array) ( $context['featured'] ?? array() );
			$is_feat  = (bool) $featured;
			if ( ! $featured ) {
				$featured = array_slice( (array) ( $context['courses'] ?? array() ), 0, 6 );
			}
			?>
			<section class="academy-block">
				<div class="section-head"><h2><?php echo $is_feat ? 'دوره‌های ویژه' : 'تازه‌ترین دوره‌ها'; ?></h2></div>
				<?php if ( ! $featured ) : ?>
					<div class="academy-empty"><h2>به‌زودی</h2><p>دوره‌های این بخش در حال آماده‌سازی هستند.</p></div>
				<?php else : ?>
					<div class="academy-grid">
						<?php foreach ( $featured as $course ) : ?>
							<?php $card( $course ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
			<?php if ( ! empty( $context['plans'] ) ) : ?>
				<section class="academy-block">
					<div class="section-head"><h2>اشتراک ماهانه و سالانه</h2><p>تمدید خودکار نیست. نزدیک پایان یادآوری می‌شود.</p></div>
					<div class="academy-plan-grid">
						<?php foreach ( $context['plans'] as $plan ) : ?>
							<article class="academy-plan">
								<h3><?php echo esc_html( (string) $plan['title'] ); ?></h3>
								<p class="academy-plan-price"><?php echo esc_html( number_format_i18n( (int) $plan['price'] ) ); ?> <small>تومان / <?php echo 'year' === $plan['billing_interval'] ? 'سال' : 'ماه'; ?></small></p>
								<?php if ( ! empty( $plan['description'] ) ) : ?>
									<p><?php echo esc_html( wp_strip_all_tags( (string) $plan['description'] ) ); ?></p>
								<?php endif; ?>
								<?php $buy( 'plan', (string) $plan['id'], 'خرید اشتراک' ); ?>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
			<section class="academy-block">
				<div class="section-head"><h2>کلاس خصوصی و گروهی</h2><p>جلسهٔ آنلاین با زمان مشخص. لینک جلسه بعد از تأیید رزرو باز می‌شود.</p></div>
				<div class="academy-plan-grid">
					<article class="academy-plan">
						<?php $icon( 'users' ); ?>
						<h3>کلاس خصوصی</h3>
						<p>یک مدرس، یک دانشجو، روی همان موضوعی که لازم دارید.</p>
					</article>
					<article class="academy-plan">
						<?php $icon( 'cap' ); ?>
						<h3>کلاس گروهی</h3>
						<p>ظرفیت محدود و قیمت هر نفر. جا تا سی دقیقه بعد از شروع پرداخت نگه داشته می‌شود.</p>
					</article>
				</div>
				<?php if ( ! empty( $context['classes'] ) ) : ?>
					<ul class="academy-sessions">
						<?php foreach ( $context['classes'] as $session ) : ?>
							<li>
								<strong><?php echo esc_html( (string) $session['title'] ); ?></strong>
								<span><?php echo 'private' === $session['kind'] ? 'خصوصی' : 'گروهی'; ?> · <?php echo esc_html( \LifeRuss\Core\CRM\Jalali::plain( (string) $session['starts_at'] ) ); ?></span>
								<?php $buy( 'private' === $session['kind'] ? 'private_class' : 'group_class', (string) $session['id'], 'رزرو' ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
			<section class="academy-block">
				<div class="section-head"><h2>چرا آکادمی لایف‌روس</h2></div>
				<div class="academy-why">
					<article><?php $icon( 'book' ); ?><h3>مسیر دانشجویی</h3><p>روسی، پادفک و زندگی در روسیه، نه یک فهرست پراکنده.</p></article>
					<article><?php $icon( 'card' ); ?><h3>قیمت شفاف</h3><p>تومان، روی خود کارت. پرداخت با زرین‌پال.</p></article>
					<article><?php $icon( 'lock' ); ?><h3>درس قفل‌شده</h3><p>ویدیوی پولی تا قبل از خرید یا اشتراک پخش نمی‌شود.</p></article>
					<article><?php $icon( 'shield' ); ?><h3>گواهی و پشتیبانی</h3><p>دوره‌های کامل می‌توانند گواهی، جزوه و پرسش از مدرس داشته باشند.</p></article>
				</div>
			</section>
			<section class="academy-block">
				<div class="section-head"><h2>پرسش‌های کوتاه</h2></div>
				<div class="academy-faq">
					<?php foreach ( \LifeRuss\Core\Academy\Catalog::faq() as $item ) : ?>
						<details>
							<summary><?php echo esc_html( $item['q'] ); ?></summary>
							<p><?php echo esc_html( $item['a'] ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</article>
	<section class="academy-final">
		<div class="container">
			<h2>از یک درس رایگان شروع کنید</h2>
			<p>دوره‌ها و اشتراک همین‌جا هستند. لایف‌روس فروشگاه نیست.</p>
			<div class="hero-actions">
				<a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/academy/courses/' ) ); ?>">مشاهده دوره‌ها</a>
				<a class="btn btn-ghost-light" href="<?php echo esc_url( liferuss_url( '/academy/plans/' ) ); ?>">اشتراک</a>
			</div>
		</div>
	</section>
<?php else : ?>
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
			} elseif ( 'category' === $screen && ! empty( $context['category'] ) ) {
				echo esc_html( (string) $context['category']['title'] );
			} else {
				echo 'دوره‌های آکادمی';
			}
			?>
		</h1>
	</div>
</header>
<article class="section academy<?php echo 'course' === $screen ? ' academy-course-page' : ''; ?>">
	<div class="container">
		<?php if ( $error ) : ?>
			<p class="lr-notice" role="status"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $context['missing'] ) ) : ?>
			<p class="academy-empty">این صفحه پیدا نشد.</p>
		<?php elseif ( 'courses' === $screen || 'category' === $screen ) : ?>
			<?php $list = (array) ( $context['courses'] ?? array() ); ?>
			<?php if ( ! $list ) : ?>
				<div class="academy-empty"><h2>به‌زودی</h2><p>دوره‌های این بخش در حال آماده‌سازی هستند.</p></div>
			<?php else : ?>
				<div class="academy-grid">
					<?php foreach ( $list as $course ) : ?>
						<?php $card( $course ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php elseif ( 'course' === $screen && ! empty( $context['course'] ) ) : ?>
			<?php
			$course   = $context['course'];
			$meta     = $lesson_meta[ (int) $course['id'] ] ?? array( 'n' => 0, 'seconds' => 0 );
			$teacher  = $by_teacher[ (int) ( $course['instructor_id'] ?? 0 ) ] ?? null;
			$duration = \LifeRuss\Core\Academy\Catalog::duration_label( $course, $meta );
			$sale     = \LifeRuss\Core\Academy\Catalog::price( $course );
			$list     = (int) $course['price'];
			$cut      = empty( $course['is_free'] ) && $sale > 0 && $sale < $list;
			$label    = ! empty( $course['is_free'] ) ? 'شروع رایگان' : 'خرید دوره';
			$cat_name = '';
			foreach ( (array) ( $context['categories'] ?? array() ) as $category ) {
				if ( (int) $category['id'] === (int) $course['category_id'] ) {
					$cat_name = (string) $category['title'];
				}
			}
			if ( 'russian-language' === $course['slug'] ) {
				$audience = 'اگر روسی را از صفر شروع می‌کنید، یا از A1 تا B2 یک مسیر پیوسته و رایگان می‌خواهید، این دوره برای شماست.';
			} else {
				$audience = 'این دوره برای کسانی است که';
				if ( '' !== $cat_name ) {
					$audience .= ' در «' . $cat_name . '»';
				}
				if ( ! empty( $course['level'] ) ) {
					$audience .= ' در سطح ' . $course['level'];
				}
				$audience .= ' می‌خواهند با درس‌های مشخص جلو بروند.';
			}
			$types = array(
				'video' => 'ویدیو',
				'text'  => 'متن',
				'quiz'  => 'آزمون',
				'live'  => 'زنده',
			);
			$groups = array();
			foreach ( (array) ( $context['modules'] ?? array() ) as $module ) {
				$groups[ (int) $module['id'] ] = array(
					'module'  => $module,
					'lessons' => array(),
				);
			}
			foreach ( (array) ( $context['lessons'] ?? array() ) as $lesson ) {
				$mid = (int) $lesson['module_id'];
				if ( ! isset( $groups[ $mid ] ) ) {
					$groups[ $mid ] = array(
						'module'  => array(
							'id'    => $mid,
							'title' => (string) ( $lesson['module_title'] ?? 'بخش' ),
						),
						'lessons' => array(),
					);
				}
				$groups[ $mid ]['lessons'][] = $lesson;
			}
			?>
			<div class="academy-layout">
				<div class="academy-main">
					<div class="academy-player">
						<?php if ( ! empty( $course['intro_video'] ) ) : ?>
							<?php echo \LifeRuss\Core\Academy\Playback::intro( (string) $course['intro_video'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<div class="academy-intro-fallback">
								<?php $icon( 'video', 'icon-circle' ); ?>
								<p>ویدیوی معرفی این دوره به‌زودی همین‌جا می‌نشیند.</p>
							</div>
						<?php endif; ?>
					</div>
					<div class="academy-copy"><?php echo wp_kses_post( wpautop( (string) $course['description'] ) ); ?></div>
					<section class="academy-panel">
						<h2>این دوره مناسب چه کسانی است</h2>
						<p><?php echo esc_html( $audience ); ?></p>
					</section>
					<?php $opened = false; ?>
					<?php if ( empty( $context['lessons'] ) ) : ?>
						<div class="academy-empty"><h2>به‌زودی</h2><p>درس‌های این دوره به‌زودی منتشر می‌شوند.</p></div>
					<?php else : ?>
						<h2>سرفصل‌ها</h2>
						<div class="academy-curriculum">
							<?php foreach ( $groups as $group ) : ?>
								<?php if ( empty( $group['lessons'] ) ) { continue; } ?>
								<details class="academy-module"<?php echo $opened ? '' : ' open'; ?>>
									<summary><?php echo esc_html( (string) $group['module']['title'] ); ?><span><?php echo esc_html( sprintf( '%s درس', number_format_i18n( count( $group['lessons'] ) ) ) ); ?></span></summary>
									<ol>
										<?php foreach ( $group['lessons'] as $lesson ) : ?>
											<?php
											$open_lesson = ! empty( $course['is_free'] ) || ! empty( $lesson['is_preview'] ) || '' !== (string) ( $context['tier'] ?? '' );
											$seconds     = (int) $lesson['duration_seconds'];
											$length      = '';
											if ( $seconds >= 60 ) {
												$length = (string) (int) round( $seconds / 60 ) . ' دقیقه';
											} elseif ( $seconds > 0 ) {
												$length = (string) $seconds . ' ثانیه';
											}
											$type = (string) ( $lesson['type'] ?? 'video' );
											?>
											<li>
												<a href="<?php echo esc_url( liferuss_url( '/academy/courses/' . $course['slug'] . '/' . $lesson['slug'] . '/' ) ); ?>">
													<?php $icon( isset( $types[ $type ] ) ? $type : 'video', 'academy-type' ); ?>
													<span class="academy-lesson-title"><?php echo esc_html( (string) $lesson['title'] ); ?></span>
													<?php if ( '' !== $length ) : ?>
														<span class="academy-lesson-time"><?php echo esc_html( $length ); ?></span>
													<?php endif; ?>
													<?php if ( ! $open_lesson ) : ?>
														<?php $icon( 'lock', 'academy-lock' ); ?>
													<?php endif; ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ol>
								</details>
								<?php $opened = true; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<section class="academy-instructor">
						<?php if ( $teacher && ! empty( $teacher['photo'] ) ) : ?>
							<img src="<?php echo esc_url( (string) $teacher['photo'] ); ?>" alt="">
						<?php else : ?>
							<?php $icon( 'users' ); ?>
						<?php endif; ?>
						<div>
							<h2>مدرس</h2>
							<?php if ( $teacher ) : ?>
								<h3><a href="<?php echo esc_url( liferuss_url( '/academy/instructors/' . $teacher['slug'] . '/' ) ); ?>"><?php echo esc_html( (string) $teacher['name'] ); ?></a></h3>
								<p><?php echo esc_html( (string) $teacher['bio'] ); ?></p>
							<?php else : ?>
								<h3>تیم آکادمی لایف‌روس</h3>
								<p>این دوره را تیم آموزش لایف‌روس منتشر کرده است.</p>
							<?php endif; ?>
						</div>
					</section>
					<?php $related = \LifeRuss\Core\Academy\Catalog::related( $course ); ?>
					<?php if ( $related ) : ?>
						<h2>دوره‌های مرتبط</h2>
						<div class="academy-grid">
							<?php foreach ( $related as $item ) : ?>
								<?php $card( $item ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<aside class="academy-buy">
					<div class="academy-buy-card">
						<?php if ( ! empty( $course['is_free'] ) || $sale < 1 ) : ?>
							<p class="academy-buy-price"><b class="academy-free">رایگان</b></p>
						<?php elseif ( $cut ) : ?>
							<p class="academy-buy-price"><del><?php echo esc_html( number_format_i18n( $list ) ); ?></del> <strong><?php echo esc_html( number_format_i18n( $sale ) ); ?></strong> <small>تومان</small></p>
						<?php else : ?>
							<p class="academy-buy-price"><strong><?php echo esc_html( number_format_i18n( $sale ) ); ?></strong> <small>تومان</small></p>
						<?php endif; ?>
						<div class="academy-buy-form"><?php $buy( 'course', (string) $course['id'], $label ); ?></div>
						<h2>شامل می‌شود</h2>
						<ul class="academy-includes">
							<li>درس‌های ویدیویی و متنی</li>
							<?php if ( 'premium' === ( $course['tier'] ?? '' ) || ! empty( $course['is_free'] ) ) : ?>
								<li>جزوه، آزمون و پرسش از مدرس</li>
							<?php endif; ?>
							<?php if ( ! empty( $course['certificate_enabled'] ) ) : ?>
								<li>گواهی پایان دوره</li>
							<?php endif; ?>
							<?php if ( ! empty( $course['included_in_subscription'] ) ) : ?>
								<li>قابل استفاده با اشتراک فعال</li>
							<?php endif; ?>
						</ul>
						<dl class="academy-facts">
							<div><dt>سطح</dt><dd><?php echo esc_html( (string) ( $course['level'] ? $course['level'] : '—' ) ); ?></dd></div>
							<div><dt>مدت</dt><dd><?php echo esc_html( '' !== $duration ? $duration : '—' ); ?></dd></div>
							<div><dt>درس</dt><dd><?php echo esc_html( number_format_i18n( (int) $meta['n'] ) ); ?></dd></div>
							<div><dt>گواهی</dt><dd><?php echo ! empty( $course['certificate_enabled'] ) ? 'دارد' : 'ندارد'; ?></dd></div>
						</dl>
					</div>
				</aside>
			</div>
			<div class="academy-buybar">
				<p>
					<?php if ( ! empty( $course['is_free'] ) || $sale < 1 ) : ?>
						<b class="academy-free">رایگان</b>
					<?php else : ?>
						<strong><?php echo esc_html( number_format_i18n( $sale ) ); ?></strong> تومان
					<?php endif; ?>
				</p>
				<?php $buy( 'course', (string) $course['id'], $label ); ?>
			</div>
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
				<div class="academy-plan-grid">
					<?php foreach ( $context['plans'] as $plan ) : ?>
						<article class="academy-plan">
							<h2><?php echo esc_html( (string) $plan['title'] ); ?></h2>
							<p class="academy-plan-price"><?php echo esc_html( number_format_i18n( (int) $plan['price'] ) ); ?> <small>تومان / <?php echo 'year' === $plan['billing_interval'] ? 'سال' : 'ماه'; ?></small></p>
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
			<?php $url = liferuss_url( '/academy/certificate/' . $context['certificate']['code'] . '/' ); ?>
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
<?php endif; ?>
<?php
get_footer();
