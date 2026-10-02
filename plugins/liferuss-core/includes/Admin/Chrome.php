<?php
/**
 * Shared admin chrome: headers, digits, toasts, and the navy/gold skin.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\CRM\Jalali;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One visual system for Academy, CRM, catalog, finance, and settings.
 */
class Chrome {


	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Mark LifeRuss screens so the shared stylesheet can restyle them.
	 *
	 * @param string $classes Body classes.
	 */
	public static function body_class( string $classes ): string {
		if ( self::ours() ) {
			$classes .= ' lr-ui';
		}
		return $classes;
	}

	/**
	 * Whether the current admin page belongs to LifeRuss.
	 */
	public static function ours(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $page ) {
			return false;
		}
		return 'liferuss' === $page || str_starts_with( $page, 'lr-' );
	}

	/**
	 * Open a page: title, breadcrumb, optional primary action, and a toast.
	 *
	 * @param string $title  Page title.
	 * @param string $crumb  Parent label.
	 * @param string $action Safe HTML for the primary action.
	 */
	public static function open( string $title, string $crumb = '', string $action = '' ): void {
		echo '<div class="wrap lr-app">';
		echo '<header class="lr-pagehead">';
		if ( '' !== $crumb ) {
			echo '<p class="lr-crumb"><bdi>' . esc_html( $crumb ) . '</bdi></p>';
		}
		echo '<div class="lr-pagehead-row"><h1>' . esc_html( $title ) . '</h1>';
		if ( '' !== $action ) {
			echo '<div class="lr-pagehead-action">' . $action . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller passes escaped markup.
		}
		echo '</div></header>';
		self::toast();
	}

	/**
	 * Close the page wrapper.
	 */
	public static function close(): void {
		echo '</div>';
	}

	/**
	 * Persian digits.
	 *
	 * @param int|float|string $value Number.
	 */
	public static function num( $value ): string {
		return Jalali::fa_digits( number_format_i18n( (float) $value ) );
	}

	/**
	 * Jalali date from a UTC timestamp.
	 *
	 * @param string $utc MySQL datetime.
	 */
	public static function date( string $utc ): string {
		if ( '' === $utc || '0000-00-00 00:00:00' === $utc ) {
			return '—';
		}
		return Jalali::fa_digits( Jalali::plain( $utc ) );
	}

	/**
	 * Toman amount.
	 *
	 * @param int $amount Toman.
	 */
	public static function toman( int $amount ): string {
		return self::num( $amount ) . ' تومان';
	}

	/**
	 * Status pill.
	 *
	 * @param string $key   Machine status.
	 * @param string $label Visible label.
	 */
	public static function pill( string $key, string $label ): string {
		return '<span class="lr-pill lr-pill-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Persian label for a stored status.
	 *
	 * @param string $status Status slug.
	 */
	public static function status( string $status ): string {
		$map = array(
			'paid'       => 'پرداخت‌شده',
			'pending'    => 'در انتظار',
			'failed'     => 'ناموفق',
			'expired'    => 'منقضی',
			'cancelled'  => 'لغوشده',
			'refund'     => 'بازپرداخت',
			'refunded'   => 'بازپرداخت‌شده',
			'draft'      => 'پیش‌نویس',
			'published'  => 'منتشرشده',
			'archived'   => 'بایگانی',
			'ready'      => 'آماده انتشار',
			'review'     => 'در انتظار بررسی',
			'active'     => 'فعال',
			'past_due'   => 'سررسید گذشته',
			'revoked'    => 'لغو دسترسی',
			'hidden'     => 'پنهان',
			'open'       => 'باز',
			'full'       => 'تکمیل ظرفیت',
			'done'       => 'برگزارشده',
			'confirmed'  => 'قطعی',
			'question'   => 'پرسش',
			'reviewkind' => 'نظر',
		);
		return $map[ $status ] ?? $status;
	}

	/**
	 * Admin URL for a LifeRuss page.
	 *
	 * @param string               $page Page slug.
	 * @param array<string, mixed> $args Extra query args.
	 */
	public static function url( string $page, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Empty state with the next action.
	 *
	 * @param string $text   Sentence.
	 * @param string $action Safe HTML link or button.
	 */
	public static function empty( string $text, string $action = '' ): void {
		echo '<div class="lr-empty"><span class="lr-empty-ico" aria-hidden="true"><svg viewBox="0 0 48 48" width="28" height="28"><rect x="8" y="6" width="24" height="32" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 16h8M16 22h12M16 28h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="34" cy="34" r="8" fill="#e8b923"/><path d="M31 34h6M34 31v6" stroke="#0b2341" stroke-width="2" stroke-linecap="round"/></svg></span>';
		echo '<p>' . esc_html( $text ) . '</p>';
		if ( '' !== $action ) {
			echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller passes escaped markup.
		}
		echo '</div>';
	}

	/**
	 * Query-string toast.
	 */
	private static function toast(): void {
		$note = isset( $_GET['lr_note'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['lr_note'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $note ) {
			return;
		}
		$kind = isset( $_GET['lr_kind'] ) ? sanitize_key( wp_unslash( (string) $_GET['lr_kind'] ) ) : 'ok'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="lr-toast" data-kind="' . esc_attr( $kind ) . '" role="status">' . esc_html( $note ) . '</div>';
	}
}
