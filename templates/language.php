<?php
/**
 * Russian course: catalogue, lesson, placement test, and certificate.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$learn = (string) get_query_var( 'lr_learn' );
$mode  = $learn ? $learn : ( is_singular( 'lr_lesson' ) ? 'lesson' : 'course' );

get_header();

$score = isset( $_GET['score'] ) ? absint( $_GET['score'] ) : -1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$max   = isset( $_GET['max'] ) ? absint( $_GET['max'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_language_course' ) ); ?></p>
		<h1>
			<?php
			if ( 'index' === $mode ) {
				echo 'آموزش زبان روسی';
			} elseif ( 'placement' === $mode ) {
				echo 'تعیین سطح';
			} elseif ( 'certificate' === $mode ) {
				echo 'گواهی پایان دوره';
			} else {
				the_title();
			}
			?>
		</h1>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<article class="section">
	<div class="container lr-language">
		<?php if ( $score >= 0 ) : ?>
			<p class="lr-notice" role="status">نتیجه: <?php echo esc_html( (string) $score ); ?> از <?php echo esc_html( (string) $max ); ?></p>
		<?php endif; ?>

		<?php if ( 'index' === $mode ) : ?>
			<p><a class="btn btn-gold" href="<?php echo esc_url( liferuss_url( '/russian-language/placement/' ) ); ?>">آزمون تعیین سطح</a></p>
			<?php foreach ( \LifeRuss\Core\Course\Store::courses() as $course ) : ?>
				<?php $level = \LifeRuss\Core\Course\Store::level_name( (int) $course->ID ); ?>
				<section class="lr-course-block">
					<h2><a href="<?php echo esc_url( get_permalink( $course ) ); ?>"><?php echo esc_html( $level ? $level . ' — ' . $course->post_title : $course->post_title ); ?></a></h2>
					<p><?php echo esc_html( wp_strip_all_tags( $course->post_excerpt ? $course->post_excerpt : $course->post_content ) ); ?></p>
					<ul class="lr-account-list">
						<?php foreach ( \LifeRuss\Core\Course\Store::lessons( (int) $course->ID ) as $lesson ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $lesson ) ); ?>"><?php echo esc_html( get_the_title( $lesson ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
			<?php liferuss_language_schema( 'index' ); ?>

		<?php elseif ( 'course' === $mode ) : ?>
			<?php
			$course_id = get_the_ID();
			$level     = \LifeRuss\Core\Course\Store::level_name( (int) $course_id );
			$lessons   = \LifeRuss\Core\Course\Store::lessons( (int) $course_id );
			$done      = is_user_logged_in() ? \LifeRuss\Core\Course\Store::completed_ids( get_current_user_id() ) : array();
			?>
			<div class="prose"><?php the_content(); ?></div>
			<?php if ( $lessons ) : ?>
				<p><?php echo esc_html( (string) count( array_intersect( $done, wp_list_pluck( $lessons, 'ID' ) ) ) ); ?> از <?php echo esc_html( (string) count( $lessons ) ); ?> درس تمام شده است.</p>
			<?php endif; ?>
			<ol class="lr-account-list">
				<?php foreach ( $lessons as $lesson ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $lesson ) ); ?>"><?php echo esc_html( get_the_title( $lesson ) ); ?></a>
						<?php if ( in_array( (int) $lesson->ID, $done, true ) ) : ?>
							<span>تمام شد</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php liferuss_language_schema( 'course', $level ); ?>

		<?php elseif ( 'lesson' === $mode ) : ?>
			<?php
			$lesson_id = get_the_ID();
			$audio     = (string) get_post_meta( $lesson_id, '_lr_audio', true );
			$video     = \LifeRuss\Core\Course\Store::video_embed( (string) get_post_meta( $lesson_id, '_lr_video', true ) );
			$questions = \LifeRuss\Core\Course\Store::questions( (int) $lesson_id );
			$done      = is_user_logged_in() && in_array( (int) $lesson_id, \LifeRuss\Core\Course\Store::completed_ids( get_current_user_id() ), true );
			$latest    = is_user_logged_in() ? \LifeRuss\Core\Course\Store::latest( get_current_user_id(), (int) $lesson_id, 'lesson' ) : null;
			?>
			<div class="prose">
				<?php
				$lesson_html = (string) get_post_field( 'post_content', $lesson_id );
				if ( get_post_meta( $lesson_id, '_lr_placement', true ) || str_contains( wp_strip_all_tags( $lesson_html ), 'این درس در فهرست دوره نیست' ) ) {
					echo '<p>این آزمون جدا از فهرست درس‌هاست و فقط سطح پیشنهادی را نشان می‌دهد.</p>';
				} else {
					the_content();
				}
				?>
			</div>
			<?php if ( $audio ) : ?>
				<audio controls src="<?php echo esc_url( $audio ); ?>"></audio>
			<?php endif; ?>
			<?php if ( $video ) : ?>
				<div class="lr-embed-wrap"><?php echo $video; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- video_embed() returns a fixed iframe. ?></div>
			<?php endif; ?>
			<?php if ( $done ) : ?>
				<p class="lr-notice">این درس تمام شده است.</p>
			<?php endif; ?>
			<?php if ( $latest ) : ?>
				<p>آخرین آزمون: <?php echo esc_html( (string) $latest['score'] ); ?> از <?php echo esc_html( (string) $latest['max_score'] ); ?></p>
			<?php endif; ?>
			<?php if ( is_user_logged_in() && ! $done ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'lr_course', 'lr_course_nonce' ); ?>
					<input type="hidden" name="lr_course_action" value="complete">
					<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson_id ); ?>">
					<button class="btn btn-ghost" type="submit">این درس را تمام کردم</button>
				</form>
			<?php endif; ?>
			<?php if ( $questions ) : ?>
				<h2>آزمون درس</h2>
				<?php liferuss_quiz_form( $questions, 'quiz', (int) $lesson_id ); ?>
			<?php endif; ?>
			<?php liferuss_language_schema( 'lesson' ); ?>

		<?php elseif ( 'placement' === $mode ) : ?>
			<?php
			$questions = \LifeRuss\Core\Course\Store::placement_questions();
			$latest    = is_user_logged_in() ? \LifeRuss\Core\Course\Store::latest( get_current_user_id(), 0, 'placement' ) : null;
			?>
			<p>چند سؤال کوتاه. نتیجه یک سطح از A1 تا B2 پیشنهاد می‌کند.</p>
			<?php if ( $latest ) : ?>
				<p>آخرین نتیجه: <?php echo esc_html( (string) $latest['score'] ); ?> از <?php echo esc_html( (string) $latest['max_score'] ); ?> — سطح <?php echo esc_html( \LifeRuss\Core\Course\Store::recommend( (int) $latest['score'], (int) $latest['max_score'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $score >= 0 ) : ?>
				<p>سطح پیشنهادی: <strong><?php echo esc_html( \LifeRuss\Core\Course\Store::recommend( $score, $max ) ); ?></strong></p>
			<?php endif; ?>
			<?php liferuss_quiz_form( $questions, 'placement', 0 ); ?>

		<?php else : ?>
			<?php if ( ! is_user_logged_in() ) : ?>
				<p><a href="<?php echo esc_url( liferuss_url( '/account/' ) ); ?>">برای گواهی وارد شوید.</a></p>
			<?php else : ?>
				<?php $earned = \LifeRuss\Core\Course\Store::certificates( get_current_user_id() ); ?>
				<?php if ( ! $earned ) : ?>
					<p class="lr-empty">هنوز دوره‌ای را کامل نکرده‌اید.</p>
				<?php endif; ?>
				<?php foreach ( $earned as $item ) : ?>
					<section class="lr-certificate">
						<p><?php echo esc_html( liferuss_brand() ); ?></p>
						<h2>گواهی پایان دوره</h2>
						<p><?php echo esc_html( wp_get_current_user()->display_name ); ?></p>
						<p>دوره <?php echo esc_html( $item['course']->post_title ); ?><?php echo $item['level'] ? ' — سطح ' . esc_html( $item['level'] ) : ''; ?></p>
						<p>تاریخ <?php echo \LifeRuss\Core\CRM\Jalali::html( (string) $item['at'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Jalali::html() returns escaped markup. ?></p>
					</section>
				<?php endforeach; ?>
				<?php if ( $earned ) : ?>
					<p class="no-print"><button class="btn btn-gold" type="button" onclick="window.print()">چاپ یا ذخیره PDF</button></p>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();

/**
 * Quiz form shared by a lesson and the placement test.
 *
 * @param array<int, array<string, mixed>> $questions Questions.
 * @param string                           $action    quiz or placement.
 * @param int                              $lesson_id Lesson id.
 */
function liferuss_quiz_form( array $questions, string $action, int $lesson_id ): void {
	if ( ! $questions ) {
		echo '<p class="lr-empty">سؤالی ثبت نشده است.</p>';
		return;
	}
	if ( ! is_user_logged_in() ) {
		echo '<p><a href="' . esc_url( liferuss_url( '/account/' ) ) . '">برای ثبت نتیجه وارد شوید.</a></p>';
	}
	echo '<form class="lr-quiz" method="post">';
	wp_nonce_field( 'lr_course', 'lr_course_nonce' );
	echo '<input type="hidden" name="lr_course_action" value="' . esc_attr( $action ) . '">';
	if ( $lesson_id ) {
		echo '<input type="hidden" name="lesson_id" value="' . esc_attr( (string) $lesson_id ) . '">';
	}
	foreach ( $questions as $index => $question ) {
		echo '<fieldset><legend>' . esc_html( (string) $question['prompt'] ) . '</legend>';
		$type = (string) ( $question['type'] ?? '' );
		if ( 'choice' === $type && ! empty( $question['choices'] ) && is_array( $question['choices'] ) ) {
			foreach ( $question['choices'] as $choice => $label ) {
				echo '<label><input type="radio" name="choice[' . esc_attr( (string) $index ) . ']" value="' . esc_attr( (string) $choice ) . '" required> ' . esc_html( (string) $label ) . '</label>';
			}
		} elseif ( 'match' === $type && ! empty( $question['pairs'] ) && is_array( $question['pairs'] ) ) {
			$rights = array();
			foreach ( $question['pairs'] as $pair ) {
				$rights[] = (string) ( $pair['right'] ?? '' );
			}
			$shuffled = $rights;
			shuffle( $shuffled );
			foreach ( $question['pairs'] as $pair_index => $pair ) {
				echo '<label>' . esc_html( (string) ( $pair['left'] ?? '' ) ) . ' <select name="match[' . esc_attr( (string) $index ) . '][' . esc_attr( (string) $pair_index ) . ']">';
				foreach ( $shuffled as $right ) {
					echo '<option value="' . esc_attr( $right ) . '">' . esc_html( $right ) . '</option>';
				}
				echo '</select></label>';
			}
		} elseif ( 'fill' === $type ) {
			echo '<input type="text" name="fill[' . esc_attr( (string) $index ) . ']" required>';
		}
		echo '</fieldset>';
	}
	if ( is_user_logged_in() ) {
		echo '<button class="btn btn-gold" type="submit">ثبت پاسخ‌ها</button>';
	}
	echo '</form>';
}

/**
 * Course and LearningResource schema.
 *
 * @param string $mode  index, course, or lesson.
 * @param string $level Level label.
 */
function liferuss_language_schema( string $mode, string $level = '' ): void {
	$provider = array(
		'@type' => 'Organization',
		'name'  => liferuss_brand(),
		'url'   => liferuss_home(),
	);
	$graph = array();
	if ( 'index' === $mode ) {
		$list = array();
		$pos  = 1;
		foreach ( \LifeRuss\Core\Course\Store::courses() as $course ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'item'     => array(
					'@type' => 'Course',
					'name'  => get_the_title( $course ),
					'url'   => get_permalink( $course ),
				),
			);
			++$pos;
		}
		$graph[] = array(
			'@type'           => 'ItemList',
			'name'            => 'آموزش زبان روسی',
			'itemListElement' => $list,
		);
	} elseif ( 'course' === $mode ) {
		$graph[] = array(
			'@type'            => 'Course',
			'name'             => get_the_title(),
			'description'      => wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_post_field( 'post_content', get_the_ID() ) ),
			'url'              => get_permalink(),
			'provider'         => $provider,
			'educationalLevel' => $level,
			'inLanguage'       => 'ru',
		);
	} else {
		$parent = get_post( (int) get_post()->post_parent );
		$graph[] = array(
			'@type'                => 'LearningResource',
			'name'                 => get_the_title(),
			'learningResourceType' => 'Lesson',
			'url'                  => get_permalink(),
			'inLanguage'           => 'ru',
			'isPartOf'             => array(
				'@type' => 'Course',
				'name'  => $parent instanceof WP_Post ? $parent->post_title : liferuss_brand(),
				'url'   => $parent instanceof WP_Post ? get_permalink( $parent ) : liferuss_url( '/russian-language/' ),
			),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>';
}
