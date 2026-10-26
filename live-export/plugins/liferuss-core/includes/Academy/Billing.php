<?php
/**
 * Manual subscription renewal. Zarinpal does not bill again by itself.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\Account\Sms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Monthly and yearly plans, a 3-day reminder, and a 3-day grace period.
 */
class Billing {

	/**
	 * Open a subscription from a paid plan order.
	 *
	 * @param int $student_id Student id.
	 * @param int $plan_id    Plan id.
	 * @param int $order_id   Order id.
	 */
	public static function activate( int $student_id, int $plan_id, int $order_id ): void {
		$plan = Db::find( 'subscription_plans', $plan_id );
		if ( ! $plan ) {
			return;
		}
		$start = time();
		$end   = strtotime( 'year' === $plan['billing_interval'] ? '+1 year' : '+1 month', $start );
		if ( ! $end ) {
			return;
		}
		Db::insert(
			'subscriptions',
			array(
				'student_id'  => $student_id,
				'plan_id'     => $plan_id,
				'order_id'    => $order_id,
				'status'      => 'active',
				'starts_at'   => gmdate( 'Y-m-d H:i:s', $start ),
				'ends_at'     => gmdate( 'Y-m-d H:i:s', $end ),
				'grace_until' => gmdate( 'Y-m-d H:i:s', $end + ( 3 * DAY_IN_SECONDS ) ),
			)
		);
	}

	/**
	 * A plan that still covers the student, including the grace window.
	 *
	 * @param int $student_id Student id.
	 * @return array<string, mixed>|null
	 */
	public static function covering( int $student_id ): ?array {
		global $wpdb;
		$subs  = Db::table( 'subscriptions' );
		$plans = Db::table( 'subscription_plans' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, p.tier, p.title AS plan_title, p.billing_interval AS plan_interval, p.price AS plan_price
				FROM `{$subs}` s
				INNER JOIN `{$plans}` p ON p.id = s.plan_id
				WHERE s.student_id = %d AND s.status IN ('active','past_due') AND s.grace_until >= %s
				ORDER BY s.ends_at DESC LIMIT 1",
				$student_id,
				Db::now()
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Daily: remind three days out, then move through grace to expired.
	 */
	public static function tick(): void {
		self::remind();
		self::advance();
	}

	/**
	 * Email and SMS once, three days before ends_at.
	 */
	private static function remind(): void {
		global $wpdb;
		$table = Db::table( 'subscriptions' );
		$soon  = gmdate( 'Y-m-d H:i:s', time() + ( 3 * DAY_IN_SECONDS ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE status = 'active' AND reminded_at IS NULL AND ends_at <= %s AND ends_at > %s",
				$soon,
				Db::now()
			),
			ARRAY_A
		);
		foreach ( (array) $rows as $row ) {
			self::notify( $row );
			Db::update( 'subscriptions', (int) $row['id'], array( 'reminded_at' => Db::now() ) );
		}
	}

	/**
	 * Active becomes past_due at ends_at, then expired after grace.
	 */
	private static function advance(): void {
		global $wpdb;
		$table = Db::table( 'subscriptions' );
		$now   = Db::now();
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET status = 'past_due', updated_at = %s WHERE status = 'active' AND ends_at < %s AND grace_until >= %s",
				$now,
				$now,
				$now
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET status = 'expired', updated_at = %s WHERE status IN ('active','past_due') AND grace_until < %s",
				$now,
				$now
			)
		);
	}

	/**
	 * One reminder. SMS stays on the existing stub until a provider is connected.
	 *
	 * @param array<string, mixed> $row Subscription.
	 */
	private static function notify( array $row ): void {
		$student = Db::find( 'students', (int) $row['student_id'] );
		if ( ! $student ) {
			return;
		}
		$user = get_userdata( (int) $student['user_id'] );
		$text = 'اشتراک آکادمی لایف‌روس سه روز دیگر تمام می‌شود. تمدید از صفحهٔ اشتراک در حساب شماست. بعد از پایان، سه روز دسترسی باقی می‌ماند.';
		if ( $user && is_email( $user->user_email ) ) {
			wp_mail( $user->user_email, 'یادآوری تمدید اشتراک آکادمی', $text );
		}
		if ( '' !== (string) $student['phone'] ) {
			Sms::send( (string) $student['phone'], $text );
		}
	}
}
