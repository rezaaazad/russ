<?php
/**
 * Course completeness, scope, and the Academy audit log.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Rules shared by the hub, the editor, and the save handlers.
 */
class Flow {

	/**
	 * 0 for every row, a positive instructor id, or -1 when the user has none.
	 */
	public static function scope(): int {
		if ( current_user_can( 'lr_academy_manage' ) || current_user_can( 'lr_view_finance' ) ) {
			return 0;
		}
		$row = Db::find_by( 'instructors', 'user_id', (string) get_current_user_id() );
		return $row ? (int) $row['id'] : -1;
	}

	/**
	 * Whether this user may edit the course.
	 *
	 * @param array<string, mixed> $course Course row.
	 */
	public static function can_course( array $course ): bool {
		$scope = self::scope();
		if ( 0 === $scope ) {
			return true;
		}
		return $scope > 0 && (int) ( $course['instructor_id'] ?? 0 ) === $scope;
	}

	/**
	 * Required publish checks.
	 *
	 * @param array<string, mixed> $course Course row.
	 * @return array<string, bool>
	 */
	public static function checklist( array $course ): array {
		$id       = (int) $course['id'];
		$sections = 0;
		$lessons  = 0;
		foreach ( Db::sorted( 'course_modules', 'course_id', $id ) as $module ) {
			++$sections;
			$kids     = Db::sorted( 'course_lessons', 'module_id', (int) $module['id'] );
			$lessons += count( $kids );
		}
		$priced = ! empty( $course['is_free'] ) || (int) $course['price'] > 0;
		return array(
			'title'      => '' !== trim( (string) $course['title'] ),
			'cover'      => '' !== trim( (string) $course['thumbnail'] ),
			'outline'    => $sections > 0 && $lessons > 0,
			'price'      => $priced,
			'instructor' => (int) ( $course['instructor_id'] ?? 0 ) > 0,
		);
	}

	/**
	 * Percent of the publish checklist.
	 *
	 * @param array<string, mixed> $course Course row.
	 */
	public static function percent( array $course ): int {
		$checks = self::checklist( $course );
		$done   = 0;
		foreach ( $checks as $ok ) {
			if ( $ok ) {
				++$done;
			}
		}
		$total = count( $checks );
		if ( $total < 1 ) {
			return 0;
		}
		return (int) floor( ( $done * 100 ) / $total );
	}

	/**
	 * Pill key and Persian label.
	 *
	 * @param array<string, mixed> $course Course row.
	 * @return array{0: string, 1: string}
	 */
	public static function pill( array $course ): array {
		$status = (string) $course['status'];
		if ( 'published' === $status ) {
			return array( 'published', 'منتشرشده' );
		}
		if ( 'archived' === $status ) {
			return array( 'archived', 'بایگانی' );
		}
		$flow = (string) ( $course['workflow'] ?? '' );
		if ( 'ready' === $flow ) {
			return array( 'ready', 'آماده انتشار' );
		}
		if ( 'review' === $flow ) {
			return array( 'review', 'در انتظار بررسی' );
		}
		return array( 'draft', 'پیش‌نویس' );
	}

	/**
	 * True when every publish check passes.
	 *
	 * @param array<string, mixed> $course Course row.
	 */
	public static function ready( array $course ): bool {
		foreach ( self::checklist( $course ) as $ok ) {
			if ( ! $ok ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Append an audit row.
	 *
	 * @param string $type   Subject type.
	 * @param int    $id     Subject id.
	 * @param string $action Action slug.
	 * @param string $detail Human detail.
	 */
	public static function audit( string $type, int $id, string $action, string $detail ): void {
		Db::insert(
			'audit',
			array(
				'actor_id'     => get_current_user_id(),
				'subject_type' => $type,
				'subject_id'   => $id,
				'action'       => $action,
				'detail'       => $detail,
			)
		);
	}

	/**
	 * Redirect back with a toast.
	 *
	 * @param string               $note Message.
	 * @param string               $kind ok or err.
	 * @param array<string, mixed> $args Extra query args.
	 */
	public static function back( string $note, string $kind = 'ok', array $args = array() ): void {
		$target = wp_get_referer();
		if ( ! $target ) {
			$target = admin_url( 'admin.php?page=lr-academy' );
		}
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					array(
						'lr_note' => $note,
						'lr_kind' => $kind,
					),
					$args
				),
				$target
			)
		);
		exit;
	}

	/**
	 * Publish courses whose schedule has arrived.
	 */
	public static function publish_due(): void {
		if ( ! is_admin() ) {
			return;
		}
		global $wpdb;
		$table = Db::table( 'courses' );
		$now   = Db::now();
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE status = 'draft' AND workflow = 'ready' AND scheduled_at IS NOT NULL AND scheduled_at <= %s", $now ) );
		foreach ( (array) $ids as $id ) {
			$course = Db::find( 'courses', (int) $id );
			if ( $course && self::ready( $course ) ) {
				Db::update(
					'courses',
					(int) $id,
					array(
						'status'       => 'published',
						'published_at' => $now,
						'workflow'     => '',
					)
				);
			}
		}
	}
}
