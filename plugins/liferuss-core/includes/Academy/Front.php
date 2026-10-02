<?php
/**
 * Public Academy routes and student actions.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\CRM\LeadWriter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pretty URLs under /academy/ and /account/academy/.
 */
class Front {

	/**
	 * Data for the theme template.
	 *
	 * @var array<string, mixed>
	 */
	private static $context = array();

	/**
	 * Rewrite rules. Added at top so they win over pages.
	 */
	public static function rules(): void {
		$rules = array(
			array( '^academy/pay/([a-f0-9]{32})/?$', 'index.php?lr_academy=pay&lr_academy_a=$matches[1]' ),
			array( '^academy/play/([0-9]+)/?$', 'index.php?lr_academy=play&lr_academy_a=$matches[1]' ),
			array( '^academy/download/([0-9]+)/?$', 'index.php?lr_academy=download&lr_academy_a=$matches[1]' ),
			array( '^academy/certificate/([a-f0-9]+)/?$', 'index.php?lr_academy=certificate&lr_academy_a=$matches[1]' ),
			array( '^academy/courses/([^/]+)/([^/]+)/?$', 'index.php?lr_academy=lesson&lr_academy_a=$matches[1]&lr_academy_b=$matches[2]' ),
			array( '^academy/courses/([^/]+)/?$', 'index.php?lr_academy=course&lr_academy_a=$matches[1]' ),
			array( '^academy/courses/?$', 'index.php?lr_academy=courses' ),
			array( '^academy/category/([^/]+)/?$', 'index.php?lr_academy=category&lr_academy_a=$matches[1]' ),
			array( '^academy/instructors/([^/]+)/?$', 'index.php?lr_academy=instructor&lr_academy_a=$matches[1]' ),
			array( '^academy/plans/?$', 'index.php?lr_academy=plans' ),
			array( '^academy/checkout/?$', 'index.php?lr_academy=checkout' ),
			array( '^academy/?$', 'index.php?lr_academy=landing' ),
			array( '^account/academy/([^/]+)/?$', 'index.php?lr_account=academy&lr_academy_tab=$matches[1]' ),
			array( '^account/academy/?$', 'index.php?lr_account=academy' ),
		);
		foreach ( $rules as $rule ) {
			add_rewrite_rule( $rule[0], $rule[1], 'top' );
		}
	}

	/**
	 * Query vars.
	 *
	 * @param string[] $vars Vars.
	 * @return string[]
	 */
	public static function vars( array $vars ): array {
		$vars[] = 'lr_academy';
		$vars[] = 'lr_academy_a';
		$vars[] = 'lr_academy_b';
		$vars[] = 'lr_academy_tab';
		return $vars;
	}

	/**
	 * These routes are real pages.
	 *
	 * @param bool      $preempt Preempt.
	 * @param \WP_Query $query   Query.
	 * @return bool
	 */
	public static function keep( $preempt, $query ) {
		unset( $query );
		if ( get_query_var( 'lr_academy' ) ) {
			return true;
		}
		return $preempt;
	}

	/**
	 * Side effects, then the template context.
	 */
	public static function handle(): void {
		$screen = (string) get_query_var( 'lr_academy' );
		if ( '' === $screen && 'academy' !== (string) get_query_var( 'lr_account' ) ) {
			return;
		}
		if ( 'play' === $screen ) {
			self::play();
		}
		if ( 'download' === $screen ) {
			self::download();
		}
		if ( 'pay' === $screen ) {
			$authority = isset( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['Authority'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status    = isset( $_GET['Status'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['Status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$result    = Orders::verify( (string) get_query_var( 'lr_academy_a' ), $authority, $status );
			wp_safe_redirect( $result['redirect'] );
			exit;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'POST' === $method ) {
			self::post( $screen );
		}
		self::load( $screen );
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
	}

	/**
	 * Template context.
	 *
	 * @return array<string, mixed>
	 */
	public static function context(): array {
		return self::$context;
	}

	/**
	 * Paid lesson pages stay out of the index.
	 */
	public static function noindex(): bool {
		$context = self::$context;
		if ( 'lesson' !== ( $context['screen'] ?? '' ) ) {
			return false;
		}
		$course = $context['course'] ?? array();
		$lesson = $context['lesson'] ?? array();
		if ( ! empty( $lesson['is_preview'] ) || ! empty( $course['is_free'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * POST checkout, quiz, progress, and support.
	 *
	 * @param string $screen Screen.
	 */
	private static function post( string $screen ): void {
		$nonce = isset( $_POST['lr_academy_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['lr_academy_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'lr_academy' ) ) {
			return;
		}
		$action = isset( $_POST['lr_academy_action'] ) ? sanitize_key( wp_unslash( (string) $_POST['lr_academy_action'] ) ) : '';
		if ( 'checkout' === $action ) {
			self::checkout_post();
		}
		if ( 'quiz' === $action && 'lesson' === $screen ) {
			self::quiz_post();
		}
		if ( 'done' === $action && 'lesson' === $screen ) {
			self::done_post();
		}
		if ( 'support' === $action && 'lesson' === $screen ) {
			self::support_post();
		}
	}

	/**
	 * Buy a course, plan, class, or bundle.
	 */
	private static function checkout_post(): void {
		$user = get_current_user_id();
		if ( $user < 1 ) {
			$posted = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
			$target = wp_validate_redirect( $posted, home_url( '/academy/checkout/' ) );
			setcookie( 'lr_academy_next', $target, time() + HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			wp_safe_redirect( home_url( '/account/' ) );
			exit;
		}
		$type   = isset( $_POST['item_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['item_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$item   = isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['item_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$code   = isset( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['coupon'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$result = Orders::start( $user, $type, $item, $code );
		if ( ! $result['ok'] ) {
			wp_safe_redirect( add_query_arg( 'academy_error', $result['message'], home_url( '/academy/checkout/' ) ) );
			exit;
		}
		wp_redirect( $result['redirect'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Grade the lesson quiz.
	 */
	private static function quiz_post(): void {
		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$student = Students::by_user( get_current_user_id() );
		if ( ! $student || $quiz_id < 1 ) {
			return;
		}
		$choice  = isset( $_POST['choice'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['choice'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$fill    = isset( $_POST['fill'] ) ? map_deep( wp_unslash( $_POST['fill'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in post(); map_deep sanitizes.
		$matched = isset( $_POST['match'] ) ? map_deep( wp_unslash( $_POST['match'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in post(); map_deep sanitizes.
		Quizzes::submit( $quiz_id, (int) $student['id'], $choice, $matched, is_array( $fill ) ? $fill : array() );
	}

	/**
	 * Mark a text lesson complete.
	 */
	private static function done_post(): void {
		$course  = Catalog::course( (string) get_query_var( 'lr_academy_a' ) );
		$lesson  = $course ? Catalog::lesson( (int) $course['id'], (string) get_query_var( 'lr_academy_b' ) ) : null;
		$student = Students::by_user( get_current_user_id() );
		if ( $course && $lesson && $student && Access::can_watch( get_current_user_id(), $course, $lesson ) ) {
			Progress::complete( (int) $student['id'], (int) $course['id'], (int) $lesson['id'] );
		}
	}

	/**
	 * Premium support becomes a CRM lead assigned to the instructor when they have a user.
	 */
	private static function support_post(): void {
		$course = Catalog::course( (string) get_query_var( 'lr_academy_a' ) );
		if ( ! $course || ! Access::can_premium( get_current_user_id(), $course ) ) {
			return;
		}
		$user       = wp_get_current_user();
		$student    = Students::by_user( $user->ID );
		$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in post().
		$instructor = ! empty( $course['instructor_id'] ) ? Db::find( 'instructors', (int) $course['instructor_id'] ) : null;
		LeadWriter::create(
			array(
				'form_type'     => 'consult',
				'name'          => $user->display_name,
				'phone'         => $student ? (string) $student['phone'] : '',
				'email'         => $user->user_email,
				'message'       => 'پشتیبانی دوره «' . $course['title'] . '»: ' . $message,
				'source'        => 'academy_support',
				'consultant_id' => $instructor && ! empty( $instructor['user_id'] ) ? (int) $instructor['user_id'] : 0,
				'skip_dedupe'   => true,
				'consent'       => 1,
			)
		);
	}

	/**
	 * Fill the template context.
	 *
	 * @param string $screen Screen.
	 */
	private static function load( string $screen ): void {
		$slug    = (string) get_query_var( 'lr_academy_a' );
		$extra   = (string) get_query_var( 'lr_academy_b' );
		$context = array(
			'screen'      => $screen ? $screen : 'landing',
			'categories'  => Db::published( 'course_categories' ),
			'courses'     => Catalog::courses(),
			'featured'    => Catalog::featured(),
			'plans'       => Db::published( 'subscription_plans' ),
			'bundles'     => Settings::bundles( true ),
			'classes'     => Classes::upcoming(),
			'instructors' => Db::published( 'instructors' ),
		);
		if ( 'course' === $screen || 'lesson' === $screen ) {
			$course = Catalog::course( $slug );
			if ( ! $course ) {
				$context['missing'] = true;
			} else {
				Metrics::hit( (int) $course['id'] );
				$context['course']  = $course;
				$context['modules'] = Catalog::modules( (int) $course['id'] );
				$context['lessons'] = Catalog::lessons( (int) $course['id'], true );
				$context['tier']    = Access::tier( get_current_user_id(), $course );
			}
		}
		if ( 'lesson' === $screen && empty( $context['missing'] ) ) {
			$lesson = Catalog::lesson( (int) $context['course']['id'], $extra );
			if ( ! $lesson ) {
				$context['missing'] = true;
			} else {
				$context['lesson']  = $lesson;
				$context['video']   = Playback::video_for( (int) $lesson['id'] );
				$context['files']   = Db::sorted( 'lesson_attachments', 'lesson_id', (int) $lesson['id'] );
				$context['quizzes'] = Quizzes::for_scope( 'lesson', (int) $lesson['id'] );
				$context['watch']   = Access::can_watch( get_current_user_id(), $context['course'], $lesson );
				$context['premium'] = Access::can_premium( get_current_user_id(), $context['course'] );
			}
		}
		if ( 'category' === $screen ) {
			$category            = Db::find_by( 'course_categories', 'slug', $slug );
			$context['category'] = ( $category && 'published' === $category['status'] ) ? $category : null;
			$context['courses']  = $context['category'] ? Catalog::courses( (int) $context['category']['id'] ) : array();
			$context['missing']  = ! $context['category'];
		}
		if ( 'instructor' === $screen ) {
			$row                   = Db::find_by( 'instructors', 'slug', $slug );
			$context['instructor'] = ( $row && 'published' === $row['status'] ) ? $row : null;
			$context['missing']    = ! $context['instructor'];
		}
		if ( 'certificate' === $screen ) {
			$cert                   = Certificates::by_code( $slug );
			$context['certificate'] = $cert;
			$context['course']      = $cert ? Db::find( 'courses', (int) $cert['course_id'] ) : null;
			$context['student']     = $cert ? Db::find( 'students', (int) $cert['student_id'] ) : null;
			$context['missing']     = ! $cert;
			if ( isset( $_GET['pdf'] ) && $cert && $context['course'] && $context['student'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public certificate download, no state change.
				Certificates::pdf( $cert, (string) $context['course']['title'], (string) $context['student']['display_name'] );
				exit;
			}
		}
		self::$context = $context;
	}

	/**
	 * Player response.
	 */
	private static function play(): void {
		$video = Db::find( 'lesson_videos', absint( get_query_var( 'lr_academy_a' ) ) );
		$exp   = isset( $_GET['exp'] ) ? absint( $_GET['exp'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Signed playback URL.
		$uid   = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Signed playback URL.
		$sig   = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['sig'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Signed playback URL.
		if ( ! $video || ! Playback::valid( (int) $video['id'], $exp, $uid, $sig ) ) {
			status_header( 403 );
			exit;
		}
		$lesson = Db::find( 'course_lessons', (int) $video['lesson_id'] );
		$module = $lesson ? Db::find( 'course_modules', (int) $lesson['module_id'] ) : null;
		$course = $module ? Db::find( 'courses', (int) $module['course_id'] ) : null;
		if ( ! $lesson || ! $course || ! Access::can_watch( $uid, $course, $lesson ) ) {
			status_header( 403 );
			exit;
		}
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="robots" content="noindex"><style>html,body{margin:0;background:#0b2341}iframe,video{width:100%;height:100vh;border:0}</style></head><body>';
		Playback::render( $video, empty( $course['is_free'] ) && empty( $lesson['is_preview'] ) );
		echo '</body></html>';
		exit;
	}

	/**
	 * Handout download after a premium check.
	 */
	private static function download(): void {
		$file = Db::find( 'lesson_attachments', absint( get_query_var( 'lr_academy_a' ) ) );
		if ( ! $file ) {
			status_header( 404 );
			exit;
		}
		$lesson = Db::find( 'course_lessons', (int) $file['lesson_id'] );
		$module = $lesson ? Db::find( 'course_modules', (int) $lesson['module_id'] ) : null;
		$course = $module ? Db::find( 'courses', (int) $module['course_id'] ) : null;
		if ( ! $course || ! Access::can_premium( get_current_user_id(), $course ) ) {
			status_header( 403 );
			exit;
		}
		$path = Playback::private_dir() . '/' . basename( (string) $file['storage_path'] );
		if ( ! is_readable( $path ) ) {
			status_header( 404 );
			exit;
		}
		header( 'Content-Type: ' . ( $file['mime'] ? $file['mime'] : 'application/pdf' ) );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( (string) $file['title'] ) . '.pdf"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
