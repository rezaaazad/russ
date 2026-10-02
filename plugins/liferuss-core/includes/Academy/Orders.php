<?php
/**
 * Academy orders, Zarinpal charges, fulfillment, and refunds.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

use LifeRuss\Core\CRM\LeadWriter;
use LifeRuss\Core\Payments\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Prices are Toman. A zero total is marked paid without calling the gateway.
 */
class Orders {

	/**
	 * Minimum amount Zarinpal will accept.
	 */
	private const MIN_TOMAN = 1000;

	/**
	 * Start or reuse a checkout.
	 *
	 * @param int    $user_id User id.
	 * @param string $type    course, plan, private_class, group_class, or bundle.
	 * @param string $item    Id, or a bundle slug.
	 * @param string $coupon  Coupon code.
	 * @return array{ok: bool, redirect: string, message: string}
	 */
	public static function start( int $user_id, string $type, string $item, string $coupon ): array {
		$fail    = array(
			'ok'       => false,
			'redirect' => '',
			'message'  => 'این مورد برای خرید آماده نیست.',
		);
		$student = Students::ensure( $user_id );
		if ( ! $student ) {
			$fail['message'] = 'برای خرید وارد حساب شوید.';
			return $fail;
		}
		$line = self::line( $type, $item );
		if ( ! $line ) {
			return $fail;
		}
		if ( ! Classes::hold( $line ) ) {
			$fail['message'] = 'ظرفیت این کلاس پر شده است.';
			return $fail;
		}
		$quote = Coupons::quote( $coupon, $user_id, $type, (int) $line['item_id'], (int) $line['amount'] );
		if ( ! $quote['ok'] ) {
			$fail['message'] = $quote['message'];
			return $fail;
		}
		$total = max( 0, (int) $line['amount'] - (int) $quote['discount'] );
		if ( $total > 0 && $total < self::MIN_TOMAN ) {
			$fail['message'] = 'مبلغ قابل پرداخت کمتر از ۱۰۰۰ تومان است.';
			return $fail;
		}
		$order_id = self::create_order( $student, $line, $quote, $total );
		if ( $order_id < 1 ) {
			$fail['message'] = 'سفارش ساخته نشد.';
			return $fail;
		}
		if ( 0 === $total ) {
			self::mark_paid( $order_id, 0, '', '' );
			return array(
				'ok'       => true,
				'redirect' => self::receipt_url( $order_id ),
				'message'  => '',
			);
		}
		return self::gateway( $order_id, $total, (string) $line['title'] );
	}

	/**
	 * Verify a Zarinpal callback. A second call for the same token is a receipt.
	 *
	 * @param string $token     Payment token.
	 * @param string $authority Gateway authority.
	 * @param string $status    Gateway status.
	 * @return array{ok: bool, redirect: string, message: string}
	 */
	public static function verify( string $token, string $authority, string $status ): array {
		$row  = Db::find_by( 'payments', 'token', $token );
		$fail = array(
			'ok'       => false,
			'redirect' => home_url( '/academy/checkout/' ),
			'message'  => 'پرداخت تأیید نشد.',
		);
		if ( ! $row ) {
			return $fail;
		}
		if ( in_array( (string) $row['status'], array( 'paid', 'refund' ), true ) ) {
			return array(
				'ok'       => true,
				'redirect' => self::receipt_url( (int) $row['order_id'] ),
				'message'  => '',
			);
		}
		$row['callback_url'] = self::callback( (string) $row['token'] );
		$result              = Checkout::gateway( $row )->verify( $row, $authority, $status );
		if ( empty( $result['ok'] ) ) {
			self::fail_pending( (int) $row['id'] );
			$fail['message'] = (string) ( $result['message'] ?? $fail['message'] );
			return $fail;
		}
		$changed = self::mark_paid( (int) $row['order_id'], (int) $row['id'], (string) $result['ref_id'], $authority );
		if ( ! $changed && 'paid' !== (string) ( Db::find( 'payments', (int) $row['id'] )['status'] ?? '' ) ) {
			return $fail;
		}
		return array(
			'ok'       => true,
			'redirect' => self::receipt_url( (int) $row['order_id'] ),
			'message'  => '',
		);
	}

	/**
	 * Record a full or partial refund. A non-positive net revokes what the order granted.
	 *
	 * @param int    $order_id Order id.
	 * @param int    $amount   Positive Toman amount.
	 * @param string $reason   Reason.
	 */
	public static function refund( int $order_id, int $amount, string $reason ): bool {
		$order = Db::find( 'orders', $order_id );
		if ( ! $order || $amount < 1 ) {
			return false;
		}
		$net = self::net( $order_id );
		if ( $amount > $net ) {
			$amount = $net;
		}
		if ( $amount < 1 ) {
			return false;
		}
		$now = Db::now();
		Db::insert(
			'payments',
			array(
				'order_id'     => $order_id,
				'token'        => bin2hex( random_bytes( 16 ) ),
				'amount_toman' => 0 - $amount,
				'revenue_line' => 'academy',
				'status'       => 'refund',
				'gateway'      => 'manual',
				'reason'       => $reason,
				'paid_at'      => $now,
			)
		);
		if ( self::net( $order_id ) <= 0 ) {
			Db::update( 'orders', $order_id, array( 'status' => 'refunded' ) );
			self::revoke( $order_id );
		}
		return true;
	}

	/**
	 * Net paid Toman for an order.
	 *
	 * @param int $order_id Order id.
	 */
	public static function net( int $order_id ): int {
		global $wpdb;
		$table = Db::table( 'payments' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount_toman), 0) FROM `{$table}` WHERE order_id = %d AND status IN ('paid','refund')",
				$order_id
			)
		);
	}

	/**
	 * Receipt path.
	 *
	 * @param int $order_id Order id.
	 */
	public static function receipt_url( int $order_id ): string {
		$order = Db::find( 'orders', $order_id );
		$code  = $order ? (string) $order['code'] : '';
		return home_url( '/account/academy/orders/?order=' . rawurlencode( $code ) );
	}

	/**
	 * Resolve a purchasable line.
	 *
	 * @param string $type Item type.
	 * @param string $item Id or bundle slug.
	 * @return array<string, mixed>|null
	 */
	private static function line( string $type, string $item ): ?array {
		if ( 'bundle' === $type ) {
			$bundle = Settings::bundle( $item );
			if ( ! $bundle ) {
				return null;
			}
			return array(
				'item_type' => 'bundle',
				'item_id'   => 0,
				'title'     => (string) $bundle['title'],
				'amount'    => (int) $bundle['price'],
				'meta'      => wp_json_encode( $bundle ),
			);
		}
		$id = (int) $item;
		if ( $id < 1 ) {
			return null;
		}
		if ( 'course' === $type ) {
			$course = Db::find( 'courses', $id );
			if ( ! $course || 'published' !== $course['status'] ) {
				return null;
			}
			$price = (int) $course['price'];
			if ( null !== $course['discount_price'] && '' !== (string) $course['discount_price'] && (int) $course['discount_price'] > 0 ) {
				$price = (int) $course['discount_price'];
			}
			if ( ! empty( $course['is_free'] ) ) {
				$price = 0;
			}
			return array(
				'item_type' => 'course',
				'item_id'   => $id,
				'title'     => (string) $course['title'],
				'amount'    => $price,
				'meta'      => '',
			);
		}
		if ( 'plan' === $type ) {
			$plan = Db::find( 'subscription_plans', $id );
			if ( ! $plan || 'published' !== $plan['status'] ) {
				return null;
			}
			return array(
				'item_type' => 'plan',
				'item_id'   => $id,
				'title'     => (string) $plan['title'],
				'amount'    => (int) $plan['price'],
				'meta'      => '',
			);
		}
		if ( 'private_class' === $type || 'group_class' === $type ) {
			$session = Db::find( 'class_sessions', $id );
			if ( ! $session || 'open' !== $session['status'] ) {
				return null;
			}
			$kind = 'private' === $session['kind'] ? 'private_class' : 'group_class';
			if ( $kind !== $type ) {
				return null;
			}
			return array(
				'item_type' => $kind,
				'item_id'   => $id,
				'title'     => (string) $session['title'],
				'amount'    => (int) $session['price'],
				'meta'      => '',
			);
		}
		return null;
	}

	/**
	 * Persist the order, its item, and leave it pending.
	 *
	 * @param array<string, mixed> $student Student.
	 * @param array<string, mixed> $line    Line.
	 * @param array<string, mixed> $quote   Coupon quote.
	 * @param int                  $total   Total.
	 */
	private static function create_order( array $student, array $line, array $quote, int $total ): int {
		$coupon_id = isset( $quote['coupon']['id'] ) ? (int) $quote['coupon']['id'] : 0;
		$order_id  = Db::insert(
			'orders',
			array(
				'student_id' => (int) $student['id'],
				'user_id'    => (int) $student['user_id'],
				'code'       => self::code(),
				'status'     => 'pending',
				'subtotal'   => (int) $line['amount'],
				'discount'   => (int) $quote['discount'],
				'total'      => $total,
				'coupon_id'  => $coupon_id > 0 ? $coupon_id : null,
			)
		);
		if ( $order_id < 1 ) {
			return 0;
		}
		Db::insert(
			'order_items',
			array(
				'order_id'     => $order_id,
				'item_type'    => (string) $line['item_type'],
				'item_id'      => (int) $line['item_id'],
				'title'        => (string) $line['title'],
				'amount_toman' => $total,
				'meta'         => (string) $line['meta'],
			)
		);
		return $order_id;
	}

	/**
	 * Ask Zarinpal for a redirect.
	 *
	 * @param int    $order_id Order id.
	 * @param int    $total    Toman.
	 * @param string $title    Description.
	 * @return array{ok: bool, redirect: string, message: string}
	 */
	private static function gateway( int $order_id, int $total, string $title ): array {
		$token = bin2hex( random_bytes( 16 ) );
		$pay   = Db::insert(
			'payments',
			array(
				'order_id'     => $order_id,
				'token'        => $token,
				'amount_toman' => $total,
				'revenue_line' => 'academy',
				'status'       => 'pending',
				'gateway'      => 'zarinpal',
			)
		);
		$row   = Db::find( 'payments', $pay );
		if ( ! $row ) {
			return array(
				'ok'       => false,
				'redirect' => '',
				'message'  => 'پرداخت ساخته نشد.',
			);
		}
		$row['description']  = 'آکادمی لایف‌روس — ' . $title;
		$row['callback_url'] = self::callback( $token );
		$result              = Checkout::gateway( $row )->request( $row );
		if ( empty( $result['ok'] ) ) {
			return array(
				'ok'       => false,
				'redirect' => '',
				'message'  => (string) ( $result['message'] ?? 'درگاه پاسخ نداد.' ),
			);
		}
		Db::update( 'payments', $pay, array( 'authority' => (string) $result['authority'] ) );
		return array(
			'ok'       => true,
			'redirect' => (string) $result['url'],
			'message'  => '',
		);
	}

	/**
	 * Mark the charge paid once, then grant access.
	 *
	 * @param int    $order_id  Order id.
	 * @param int    $pay_id    Payment id, or 0 for a free order.
	 * @param string $ref_id    Gateway reference.
	 * @param string $authority Gateway authority.
	 */
	private static function mark_paid( int $order_id, int $pay_id, string $ref_id, string $authority ): bool {
		global $wpdb;
		$now = Db::now();
		if ( $pay_id > 0 ) {
			$table   = Db::table( 'payments' );
			$changed = $wpdb->query(
				$wpdb->prepare(
					"UPDATE `{$table}` SET status = 'paid', ref_id = %s, authority = %s, paid_at = %s, updated_at = %s WHERE id = %d AND status = 'pending'",
					$ref_id,
					$authority,
					$now,
					$now,
					$pay_id
				)
			);
			if ( 1 !== (int) $changed ) {
				return false;
			}
		}
		$order = Db::find( 'orders', $order_id );
		if ( ! $order || 'paid' === $order['status'] ) {
			return true;
		}
		Db::update(
			'orders',
			$order_id,
			array(
				'status'  => 'paid',
				'paid_at' => $now,
			)
		);
		if ( ! empty( $order['coupon_id'] ) ) {
			Coupons::redeem( (int) $order['coupon_id'], $order_id, (int) $order['user_id'] );
		}
		self::fulfill( $order );
		return true;
	}

	/**
	 * Grant the purchased access.
	 *
	 * @param array<string, mixed> $order Paid order.
	 */
	private static function fulfill( array $order ): void {
		$items = Db::where_id( 'order_items', 'order_id', (int) $order['id'] );
		foreach ( $items as $item ) {
			self::fulfill_item( $order, $item );
		}
	}

	/**
	 * Grant one line.
	 *
	 * @param array<string, mixed> $order Order.
	 * @param array<string, mixed> $item  Line.
	 */
	private static function fulfill_item( array $order, array $item ): void {
		$type = (string) $item['item_type'];
		if ( 'course' === $type ) {
			$course = Db::find( 'courses', (int) $item['item_id'] );
			$tier   = $course ? (string) $course['tier'] : 'standard';
			Access::enroll( (int) $order['student_id'], (int) $item['item_id'], (int) $order['id'], 'purchase', $tier );
			return;
		}
		if ( 'plan' === $type ) {
			Billing::activate( (int) $order['student_id'], (int) $item['item_id'], (int) $order['id'] );
			return;
		}
		if ( 'private_class' === $type || 'group_class' === $type ) {
			Classes::confirm( (int) $item['item_id'], (int) $order['student_id'], (int) $order['id'] );
			return;
		}
		if ( 'bundle' === $type ) {
			self::fulfill_bundle( $order, (string) $item['meta'] );
		}
	}

	/**
	 * Enroll the bundle courses and open a study lead.
	 *
	 * @param array<string, mixed> $order Order.
	 * @param string               $meta  Bundle JSON.
	 */
	private static function fulfill_bundle( array $order, string $meta ): void {
		$bundle = json_decode( $meta, true );
		if ( ! is_array( $bundle ) ) {
			return;
		}
		$ids = array_map( 'intval', (array) ( $bundle['course_ids'] ?? array() ) );
		foreach ( $ids as $course_id ) {
			if ( $course_id > 0 ) {
				Access::enroll( (int) $order['student_id'], $course_id, (int) $order['id'], 'bundle', 'premium' );
			}
		}
		$user    = get_userdata( (int) $order['user_id'] );
		$student = Db::find( 'students', (int) $order['student_id'] );
		LeadWriter::create(
			array(
				'form_type'    => 'consult',
				'name'         => $user ? $user->display_name : 'دانشجو',
				'phone'        => $student ? (string) $student['phone'] : '',
				'email'        => $user ? $user->user_email : '',
				'message'      => 'بستهٔ آکادمی: ' . (string) ( $bundle['title'] ?? '' ) . "\n" . (string) ( $bundle['note'] ?? '' ),
				'source'       => 'academy_bundle',
				'landing_page' => home_url( '/academy/' ),
				'skip_dedupe'  => true,
				'consent'      => 1,
			)
		);
	}

	/**
	 * Take back access granted by an order.
	 *
	 * @param int $order_id Order id.
	 */
	private static function revoke( int $order_id ): void {
		global $wpdb;
		$now           = Db::now();
		$enrollments   = Db::table( 'enrollments' );
		$subscriptions = Db::table( 'subscriptions' );
		$bookings      = Db::table( 'class_bookings' );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$enrollments}` SET status = 'revoked', revoked_at = %s, updated_at = %s WHERE order_id = %d AND status = 'active'",
				$now,
				$now,
				$order_id
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$subscriptions}` SET status = 'cancelled', updated_at = %s WHERE order_id = %d",
				$now,
				$order_id
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$bookings}` SET status = 'cancelled', updated_at = %s WHERE order_id = %d",
				$now,
				$order_id
			)
		);
	}

	/**
	 * Mark a still-pending charge as failed.
	 *
	 * @param int $pay_id Payment id.
	 */
	private static function fail_pending( int $pay_id ): void {
		global $wpdb;
		$table = Db::table( 'payments' );
		$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET status = 'failed', updated_at = %s WHERE id = %d AND status = 'pending'", Db::now(), $pay_id ) );
	}

	/**
	 * Callback URL for one token.
	 *
	 * @param string $token Token.
	 */
	private static function callback( string $token ): string {
		return home_url( '/academy/pay/' . $token . '/' );
	}

	/**
	 * Short public order code.
	 */
	private static function code(): string {
		return 'AC' . gmdate( 'ymd' ) . strtoupper( substr( bin2hex( random_bytes( 3 ) ), 0, 4 ) );
	}
}
