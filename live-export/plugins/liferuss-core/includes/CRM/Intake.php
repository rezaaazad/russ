<?php
/**
 * Public lead intake: REST and the theme's admin-post / AJAX action.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One endpoint for consult, freight, trade, contact, exchange, and admission.
 */
class Intake {

	/**
	 * Register routes and the legacy action names the theme already posts.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest' ) );
		add_action( 'wp_ajax_liferuss_consult', array( self::class, 'ajax' ), 1 );
		add_action( 'wp_ajax_nopriv_liferuss_consult', array( self::class, 'ajax' ), 1 );
		add_action( 'admin_post_liferuss_consult', array( self::class, 'admin_post' ), 1 );
		add_action( 'admin_post_nopriv_liferuss_consult', array( self::class, 'admin_post' ), 1 );
	}

	/**
	 * POST /wp-json/liferuss/v1/leads
	 */
	public static function register_rest(): void {
		register_rest_route(
			'liferuss/v1',
			'/leads',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST callback. Same body as the theme forms.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Body is read from the request globals, same as admin-post.
		$result = self::handle();
		$status = $result['ok'] ? 200 : 400;
		return new \WP_REST_Response(
			array(
				'success' => $result['ok'],
				'data'    => $result,
			),
			$status
		);
	}

	/**
	 * AJAX handler. wp_send_json exits, so the old plugin does not also save.
	 */
	public static function ajax(): void {
		$result = self::handle();
		if ( $result['ok'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/**
	 * Non-JS fallback. Redirects back to the form.
	 */
	public static function admin_post(): void {
		$result = self::handle();
		$target = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$target = remove_query_arg( array( 'consult', 'consult_msg' ), $target );
		$anchor = false !== strpos( $target, '#' ) ? '' : '#consultation';
		wp_safe_redirect(
			add_query_arg(
				array(
					'consult'     => $result['ok'] ? 'ok' : 'err',
					'consult_msg' => rawurlencode( $result['message'] ),
				),
				$target
			) . $anchor
		);
		exit;
	}

	/**
	 * Validate and store one submission.
	 *
	 * @return array{ok: bool, message: string, lead_id?: int, analytics?: array<string, mixed>}
	 */
	public static function handle(): array {
		if ( '' !== self::text( 'liferuss_company' ) ) {
			return array(
				'ok'      => true,
				'message' => self::success_message( 'consult' ),
			);
		}
		if ( ! self::nonce_ok() ) {
			return array(
				'ok'      => false,
				'message' => self::t( 'form_nonce', 'نشست منقضی شده است.' ),
			);
		}
		if ( ! self::rate_ok() ) {
			return array(
				'ok'      => false,
				'message' => __( 'درخواست‌های زیادی ارسال شده. چند دقیقه بعد دوباره تلاش کنید.', 'liferuss-core' ),
			);
		}
		$turnstile = self::turnstile_ok();
		if ( is_wp_error( $turnstile ) ) {
			return array(
				'ok'      => false,
				'message' => $turnstile->get_error_message(),
			);
		}

		$type = sanitize_key( self::text( 'consult_type' ) );
		if ( ! isset( Catalog::forms()[ $type ] ) ) {
			$type = 'consult';
		}
		$name  = self::text( 'consult_name' );
		$phone = self::text( 'consult_phone' );
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name ) ) < 2 ) {
			return array(
				'ok'      => false,
				'message' => self::t( 'form_need_name', 'لطفاً نام را وارد کنید.' ),
			);
		}
		if ( strlen( LeadWriter::digits( $phone ) ) < 8 ) {
			return array(
				'ok'      => false,
				'message' => self::t( 'form_need_phone', 'شماره تماس معتبر وارد کنید.' ),
			);
		}

		$level = self::level_label( $type );
		if ( is_wp_error( $level ) ) {
			return array(
				'ok'      => false,
				'message' => $level->get_error_message(),
			);
		}

		$payload = array(
			'name'           => $name,
			'phone'          => $phone,
			'email'          => sanitize_email( self::text( 'consult_email' ) ),
			'form_type'      => $type,
			'lang'           => sanitize_key( self::text( 'consult_lang' ) ),
			'message'        => self::message( $type, is_string( $level ) ? $level : '' ),
			'source'         => 'website_form',
			'landing_page'   => esc_url_raw( self::text( 'landing_page' ) ),
			'referrer'       => esc_url_raw( self::text( 'referrer' ) ),
			'utm_source'     => self::text( 'utm_source' ),
			'utm_medium'     => self::text( 'utm_medium' ),
			'utm_campaign'   => self::text( 'utm_campaign' ),
			'utm_content'    => self::text( 'utm_content' ),
			'utm_term'       => self::text( 'utm_term' ),
			'ip'             => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'request'        => self::request_fields( $type, is_string( $level ) ? $level : '' ),
			'history_reason' => 'intake',
		);
		$lead_id = LeadWriter::create( $payload );
		if ( is_wp_error( $lead_id ) ) {
			return array(
				'ok'      => false,
				'message' => self::t( 'form_save_fail', 'ثبت درخواست ممکن نشد.' ),
			);
		}

		if ( ! empty( $_FILES['consult_file']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$doc  = ( 'freight' === $type || 'cargo' === $type ) ? 'cargo_doc' : ( 'trade' === $type ? 'trade_doc' : 'other' );
			$file = Files::store_upload( (int) $lead_id, 'consult_file', $doc );
			if ( is_wp_error( $file ) ) {
				Repository::for( 'leads' )->delete( (int) $lead_id );
				return array(
					'ok'      => false,
					'message' => $file->get_error_message(),
				);
			}
		}

		Notifier::enqueue( (int) $lead_id );
		return array(
			'ok'        => true,
			'message'   => self::success_message( $type ),
			'lead_id'   => (int) $lead_id,
			'analytics' => array(
				'event'     => 'form_submit',
				'form_type' => $type,
				'lead_id'   => (int) $lead_id,
			),
		);
	}

	/**
	 * Theme nonce, also accepted on the REST route.
	 */
	private static function nonce_ok(): bool {
		$nonce = self::text( 'liferuss_nonce' );
		if ( '' === $nonce && isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_X_WP_NONCE'] ) );
			return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
		}
		return (bool) wp_verify_nonce( $nonce, 'liferuss_consult' );
	}

	/**
	 * Eight submissions per ten minutes per IP.
	 */
	private static function rate_ok(): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'lr_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 8 ) {
			return false;
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Skip Turnstile when the secret is empty.
	 *
	 * @return true|\WP_Error
	 */
	private static function turnstile_ok() {
		$forms  = Settings::get( 'forms' );
		$secret = (string) ( $forms['turnstile_secret'] ?? '' );
		if ( '' === $secret ) {
			return true;
		}
		$token = self::text( 'cf-turnstile-response' );
		if ( '' === $token ) {
			return new \WP_Error( 'lr_turnstile', __( 'لطفاً تأیید امنیتی را کامل کنید.', 'liferuss-core' ) );
		}
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 8,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'lr_turnstile', __( 'تأیید امنیتی در دسترس نیست.', 'liferuss-core' ) );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['success'] ) ) {
			return new \WP_Error( 'lr_turnstile', __( 'تأیید امنیتی ناموفق بود.', 'liferuss-core' ) );
		}
		return true;
	}

	/**
	 * Study level is required for the consult form when the theme defines levels.
	 *
	 * @param string $type Form type.
	 * @return string|\WP_Error Label, or an error.
	 */
	private static function level_label( string $type ) {
		if ( 'consult' !== $type && 'admission' !== $type ) {
			return '';
		}
		$level = self::text( 'consult_level' );
		if ( ! function_exists( 'liferuss_study_levels' ) ) {
			return $level;
		}
		$levels = liferuss_study_levels();
		unset( $levels[''] );
		if ( ! isset( $levels[ $level ] ) ) {
			return new \WP_Error( 'lr_level', self::t( 'form_need_level', 'مقطع تحصیلی را انتخاب کنید.' ) );
		}
		return (string) $levels[ $level ];
	}

	/**
	 * Human message stored on the lead.
	 *
	 * @param string $type  Form type.
	 * @param string $level Level label.
	 */
	private static function message( string $type, string $level ): string {
		$lines = array();
		if ( $level ) {
			$lines[] = 'مقطع: ' . $level;
		}
		$map = array(
			'consult_university'  => 'دانشگاه',
			'consult_scholarship' => 'بورسیه',
			'consult_message'     => 'پیام',
			'consult_notes'       => 'توضیحات',
			'consult_origin'      => 'مبدأ',
			'consult_dest'        => 'مقصد',
			'consult_product'     => 'کالا',
			'consult_qty'         => 'حجم',
			'consult_specs'       => 'مشخصات',
			'consult_weight'      => 'وزن',
			'consult_dims'        => 'ابعاد',
			'consult_value'       => 'ارزش',
			'consult_amount'      => 'مبلغ',
		);
		foreach ( $map as $key => $label ) {
			$value = 'consult_message' === $key || 'consult_notes' === $key ? self::area( $key ) : self::text( $key );
			if ( '' !== $value ) {
				$lines[] = $label . ': ' . $value;
			}
		}
		unset( $type );
		return implode( "\n", $lines );
	}

	/**
	 * Columns for the request table.
	 *
	 * @param string $type  Form type.
	 * @param string $level Level label.
	 * @return array<string, mixed>
	 */
	private static function request_fields( string $type, string $level ): array {
		if ( 'consult' === $type || 'admission' === $type ) {
			$program = sanitize_key( self::text( 'consult_program' ) );
			if ( ! in_array( $program, array( 'degree', 'padfak', 'direct_course', 'scholarship' ), true ) ) {
				$program = 'degree';
			}
			$degree = sanitize_key( self::text( 'consult_degree' ) );
			$fields = array(
				'program_type'      => $program,
				'current_education' => substr( $level, 0, 60 ),
				'stage'             => 'new',
			);
			if ( in_array( $degree, array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ), true ) ) {
				$fields['degree'] = $degree;
			}
			return $fields;
		}
		if ( 'exchange' === $type ) {
			$amount = self::text( 'consult_amount' );
			return array(
				'amount'        => is_numeric( $amount ) ? $amount : 0,
				'currency_from' => self::currency( 'consult_currency_from', 'IRR' ),
				'currency_to'   => self::currency( 'consult_currency_to', 'RUB' ),
				'description'   => self::area( 'consult_notes' ),
				'stage'         => 'new',
			);
		}
		if ( 'freight' === $type || 'cargo' === $type ) {
			return array(
				'direction'        => self::direction(),
				'cargo_type'       => self::cargo_type(),
				'origin_city'      => self::text( 'consult_origin' ),
				'destination_city' => self::text( 'consult_dest' ),
				'weight_kg'        => self::decimal( 'consult_weight' ),
				'pieces'           => absint( self::text( 'consult_packages' ) ) ? absint( self::text( 'consult_packages' ) ) : null,
				'declared_value'   => self::decimal( 'consult_value' ),
				'description'      => trim( self::text( 'consult_dims' ) . ' ' . self::area( 'consult_notes' ) ),
				'stage'            => 'new',
			);
		}
		if ( 'immigration' === $type ) {
			$type_key = sanitize_key( self::text( 'consult_imm_type' ) );
			$allowed  = array( 'visa', 'residency', 'registration', 'work', 'deportation', 'entry-ban' );
			$expiry   = Jalali::filter_utc( self::text( 'consult_visa_expiry' ), false );
			return array(
				'request_type' => in_array( $type_key, $allowed, true ) ? $type_key : 'visa',
				'nationality'  => self::text( 'consult_nationality' ),
				'current_city' => self::text( 'consult_city' ),
				'visa_status'  => self::text( 'consult_visa_status' ),
				'expiry_date'  => '' !== $expiry ? substr( $expiry, 0, 10 ) : null,
				'documents'    => self::area( 'consult_documents' ),
				'stage'        => 'new',
			);
		}
		if ( 'trade' === $type ) {
			return array(
				'direction'           => self::direction(),
				'request_type'        => 'sourcing',
				'product_category'    => self::text( 'consult_category' ),
				'product_description' => trim( self::text( 'consult_product' ) . "\n" . self::text( 'consult_specs' ) . "\n" . self::area( 'consult_notes' ) ),
				'quantity'            => self::decimal( 'consult_qty' ),
				'stage'               => 'new',
			);
		}
		return array();
	}

	/**
	 * Guess cargo direction from the city text. Default is Iran to Russia.
	 */
	private static function direction(): string {
		$origin = strtolower( self::text( 'consult_origin' ) );
		$dest   = strtolower( self::text( 'consult_dest' ) );
		$ru     = static function ( string $value ): bool {
			return str_contains( $value, 'روسیه' ) || str_contains( $value, 'russia' ) || str_contains( $value, 'moscow' );
		};
		$ir     = static function ( string $value ): bool {
			return str_contains( $value, 'ایران' ) || str_contains( $value, 'iran' ) || str_contains( $value, 'تهران' );
		};
		if ( $ru( $origin ) && $ir( $dest ) ) {
			return 'ru_to_ir';
		}
		return 'ir_to_ru';
	}

	/**
	 * Map a free-text cargo choice onto the ERD enum.
	 */
	private static function cargo_type(): string {
		$raw = strtolower( self::text( 'consult_cargo_type' ) );
		if ( str_contains( $raw, 'personal' ) || str_contains( $raw, 'شخص' ) ) {
			return 'personal_items';
		}
		if ( str_contains( $raw, 'sample' ) || str_contains( $raw, 'نمونه' ) ) {
			return 'samples';
		}
		if ( str_contains( $raw, 'commercial' ) || str_contains( $raw, 'تجار' ) ) {
			return 'commercial';
		}
		if ( str_contains( $raw, 'doc' ) || str_contains( $raw, 'مدارک' ) ) {
			return 'documents';
		}
		return 'documents';
	}

	/**
	 * ISO currency or the default.
	 *
	 * @param string $key      Field.
	 * @param string $fallback Default code.
	 */
	private static function currency( string $key, string $fallback ): string {
		$code = strtoupper( self::text( $key ) );
		return preg_match( '/^[A-Z]{3}$/', $code ) ? $code : $fallback;
	}

	/**
	 * Decimal or null.
	 *
	 * @param string $key Field.
	 */
	private static function decimal( string $key ): ?string {
		$raw = str_replace( ',', '.', self::text( $key ) );
		$raw = preg_replace( '/[^0-9.]/', '', $raw );
		return ( null !== $raw && '' !== $raw && is_numeric( $raw ) ) ? $raw : null;
	}

	/**
	 * Success copy from the theme, then the core forms setting.
	 *
	 * @param string $type Form type.
	 */
	private static function success_message( string $type ): string {
		$keys = array(
			'consult'     => 'form_success',
			'admission'   => 'form_success',
			'freight'     => 'freight_form_success',
			'cargo'       => 'freight_form_success',
			'trade'       => 'trade_form_success',
			'contact'     => 'contact_form_success',
			'exchange'    => 'form_success',
			'immigration' => 'form_success',
		);
		$key  = $keys[ $type ] ?? 'form_success';
		if ( function_exists( 'liferuss_opt' ) ) {
			$text = (string) liferuss_opt( $key, '' );
			if ( '' !== $text ) {
				return $text;
			}
		}
		$forms = Settings::get( 'forms' );
		$text  = (string) ( $forms['success_message'] ?? '' );
		return '' !== $text ? $text : self::t( 'form_ok_short', 'ثبت شد.' );
	}

	/**
	 * Theme translation helper.
	 *
	 * @param string $key      Key.
	 * @param string $fallback Fallback.
	 */
	private static function t( string $key, string $fallback ): string {
		if ( function_exists( 'liferuss_t' ) ) {
			$text = (string) liferuss_t( $key );
			if ( '' !== $text ) {
				return $text;
			}
		}
		return $fallback;
	}

	/**
	 * Posted text field. Nonce is checked in handle() before these run.
	 *
	 * @param string $key Field name.
	 */
	private static function text( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Posted textarea.
	 *
	 * @param string $key Field name.
	 */
	private static function area( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		return sanitize_textarea_field( wp_unslash( (string) $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
}
