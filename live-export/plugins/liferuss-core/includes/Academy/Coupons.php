<?php
/**
 * Percent and fixed coupons.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Coupon checks. A zero limit means unlimited.
 */
class Coupons {

	/**
	 * Discount in Toman for one checkout, or an error code.
	 *
	 * @param string $code     Coupon code.
	 * @param int    $user_id  Buyer.
	 * @param string $type     course, plan, or other.
	 * @param int    $item_id  Item id. Bundles and classes only match scope all.
	 * @param int    $subtotal Subtotal in Toman.
	 * @return array{ok: bool, discount: int, coupon: array<string, mixed>|null, message: string}
	 */
	public static function quote( string $code, int $user_id, string $type, int $item_id, int $subtotal ): array {
		$empty = array(
			'ok'       => false,
			'discount' => 0,
			'coupon'   => null,
			'message'  => '',
		);
		$code  = strtoupper( trim( $code ) );
		if ( '' === $code ) {
			$empty['ok'] = true;
			return $empty;
		}
		$row = Db::find_by( 'coupons', 'code', $code );
		if ( ! $row || 'published' !== $row['status'] ) {
			$empty['message'] = 'این کد تخفیف معتبر نیست.';
			return $empty;
		}
		if ( ! empty( $row['expires_at'] ) && strtotime( (string) $row['expires_at'] . ' UTC' ) < time() ) {
			$empty['message'] = 'مهلت این کد تمام شده است.';
			return $empty;
		}
		if ( (int) $row['max_uses'] > 0 && (int) $row['used_count'] >= (int) $row['max_uses'] ) {
			$empty['message'] = 'سقف استفاده از این کد پر شده است.';
			return $empty;
		}
		if ( (int) $row['max_per_user'] > 0 && self::user_uses( (int) $row['id'], $user_id ) >= (int) $row['max_per_user'] ) {
			$empty['message'] = 'شما قبلاً از این کد استفاده کرده‌اید.';
			return $empty;
		}
		if ( ! self::scoped( $row, $type, $item_id ) ) {
			$empty['message'] = 'این کد برای این مورد نیست.';
			return $empty;
		}
		$discount = 0;
		if ( 'percent' === $row['type'] ) {
			$percent  = min( 100, (int) $row['amount'] );
			$discount = (int) floor( $subtotal * $percent / 100 );
		} else {
			$discount = min( $subtotal, (int) $row['amount'] );
		}
		return array(
			'ok'       => true,
			'discount' => $discount,
			'coupon'   => $row,
			'message'  => '',
		);
	}

	/**
	 * Record one successful use.
	 *
	 * @param int $coupon_id Coupon id.
	 * @param int $order_id  Order id.
	 * @param int $user_id   User id.
	 */
	public static function redeem( int $coupon_id, int $order_id, int $user_id ): void {
		if ( $coupon_id < 1 ) {
			return;
		}
		Db::insert(
			'coupon_redemptions',
			array(
				'coupon_id' => $coupon_id,
				'order_id'  => $order_id,
				'user_id'   => $user_id,
			)
		);
		global $wpdb;
		$table = Db::table( 'coupons' );
		$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET used_count = used_count + 1, updated_at = %s WHERE id = %d", Db::now(), $coupon_id ) );
	}

	/**
	 * How many times this user has redeemed the coupon.
	 *
	 * @param int $coupon_id Coupon id.
	 * @param int $user_id   User id.
	 */
	private static function user_uses( int $coupon_id, int $user_id ): int {
		global $wpdb;
		$table = Db::table( 'coupon_redemptions' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE coupon_id = %d AND user_id = %d", $coupon_id, $user_id ) );
	}

	/**
	 * Scope all, a course list, or a plan list.
	 *
	 * @param array<string, mixed> $row     Coupon.
	 * @param string               $type    Item type.
	 * @param int                  $item_id Item id.
	 */
	private static function scoped( array $row, string $type, int $item_id ): bool {
		$scope = (string) $row['scope'];
		if ( 'all' === $scope ) {
			return true;
		}
		$parts = preg_split( '/[^0-9]+/', (string) $row['scope_ids'] );
		$ids   = array_filter( array_map( 'intval', is_array( $parts ) ? $parts : array() ) );
		if ( 'courses' === $scope ) {
			return 'course' === $type && in_array( $item_id, $ids, true );
		}
		if ( 'plans' === $scope ) {
			return 'plan' === $type && in_array( $item_id, $ids, true );
		}
		return false;
	}
}
