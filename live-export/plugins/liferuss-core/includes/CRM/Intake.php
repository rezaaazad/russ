<?php
/**
 * Public lead intake: REST and the theme's admin-post / AJAX action.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Currency\Market;
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
		if ( 'exchange' === $type ) {
			$exchange_error = self::exchange_error();
			if ( '' !== $exchange_error ) {
				return array(
					'ok'      => false,
					'message' => $exchange_error,
				);
			}
		}
		if ( 'sourcing' === $type ) {
			$sourcing_error = self::sourcing_error();
			if ( '' !== $sourcing_error ) {
				return array(
					'ok'      => false,
					'message' => $sourcing_error,
				);
			}
		}
		if ( 'cargo' === $type && '' !== self::text( 'consult_cargo_kind' ) ) {
			$cargo_error = self::cargo_lane_error();
			if ( '' !== $cargo_error ) {
				return array(
					'ok'      => false,
					'message' => $cargo_error,
				);
			}
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

		if ( 'sourcing' === $type || ( 'cargo' === $type && ! empty( $_FILES['consult_files']['name'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$doc    = 'cargo' === $type ? 'cargo_doc' : 'trade_doc';
			$stored = Files::store_many(
				(int) $lead_id,
				'consult_files',
				$doc,
				Files::SOURCING_MAX_FILES,
				Files::sourcing_limit(),
				array(
					'image/jpeg'      => 'jpg',
					'image/png'       => 'png',
					'image/webp'      => 'webp',
					'application/pdf' => 'pdf',
				)
			);
			if ( is_wp_error( $stored ) ) {
				return array(
					'ok'      => false,
					'message' => $stored->get_error_message(),
				);
			}
		} elseif ( ! empty( $_FILES['consult_file']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
		if ( 'exchange' === $type ) {
			Notifier::notify_exchange_operators( (int) $lead_id );
		}
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
		if ( 'cargo' === $type && '' !== self::text( 'consult_cargo_kind' ) ) {
			$lines[] = 'هدف: کارگو و ارسال بار';
			$lines[] = 'مبدأ: ' . self::text( 'consult_origin' );
			$lines[] = 'مقصد: ' . self::text( 'consult_dest' );
			$lines[] = 'نوع بار: ' . self::text( 'consult_cargo_kind' );
		}
		if ( 'sourcing' === $type ) {
			$lines[] = 'هدف: سورسینگ کالا';
			$lines[] = 'کالا: ' . self::text( 'consult_product' );
			$lines[] = 'دسته: ' . self::text( 'consult_category' );
			$lines[] = 'کشور خرید: ' . self::text( 'consult_buy_country' );
			$lines[] = 'کشور مقصد: ' . self::text( 'consult_dest_country' );
			$lines[] = 'حجم: ' . self::text( 'consult_volume' );
			$specs   = self::text( 'consult_specs' );
			if ( '' !== $specs ) {
				$lines[] = 'مشخصات: ' . $specs;
			}
		}
		if ( 'exchange' === $type ) {
			$pay = self::payment_method();
			$labels = array(
				'cash'     => 'نقد',
				'card'     => 'کارت',
				'transfer' => 'انتقال',
			);
			$purpose_labels = array(
				'tuition'  => 'پرداخت شهریه',
				'transfer' => 'حواله به روسیه',
				'other'    => 'سایر',
			);
			$purpose = self::purpose();
			$lines[] = 'صف: صرافی';
			$lines[] = 'هدف: ' . ( $purpose_labels[ $purpose ] ?? 'سایر' );
			if ( 'tuition' === $purpose ) {
				$lines[] = 'نام دانشجو: ' . self::text( 'consult_student' );
				$lines[] = 'دانشگاه: ' . self::text( 'consult_uni' );
				$lines[] = 'شمارهٔ دانشجویی/فاکتور: ' . self::text( 'consult_invoice' );
				$due     = self::text( 'consult_invoice_due' );
				if ( '' !== $due ) {
					$lines[] = 'مهلت فاکتور: ' . $due;
				}
			}
			$lines[] = 'روش پرداخت: ' . ( $labels[ $pay ] ?? $pay );
			$lines[] = 'شهر: ' . self::text( 'consult_city' );
			$lines[] = 'نرخ مرجع بازار کنار درخواست ذخیره شد. نرخ نهایی را در پاسخ همین درخواست بنویسید.';
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
			$city   = self::text( 'consult_city' );
			return array(
				'amount'         => is_numeric( $amount ) ? $amount : 0,
				'currency_from'  => self::currency( 'consult_currency_from', 'IRR' ),
				'currency_to'    => self::currency( 'consult_currency_to', 'RUB' ),
				'city'           => function_exists( 'mb_substr' ) ? mb_substr( $city, 0, 100 ) : substr( $city, 0, 100 ),
				'payment_method' => self::payment_method(),
				'purpose'        => self::purpose(),
				'description'    => self::exchange_notes(),
				'rate_snapshot'  => wp_json_encode( Market::snapshot() ),
				'stage'          => 'new',
			);
		}
		if ( 'freight' === $type || 'cargo' === $type ) {
			$kind = sanitize_key( self::text( 'consult_cargo_kind' ) );
			$note = trim( self::text( 'consult_dims' ) . "\n" . self::area( 'consult_notes' ) );
			if ( '' !== $kind ) {
				$note = trim( "نوع: " . $kind . "\n" . $note );
			}
			return array(
				'direction'        => self::direction(),
				'cargo_type'       => '' !== $kind ? self::cargo_kind_enum( $kind ) : self::cargo_type(),
				'origin_city'      => self::text( 'consult_origin' ),
				'destination_city' => self::text( 'consult_dest' ),
				'weight_kg'        => self::decimal( 'consult_weight' ),
				'pieces'           => absint( self::text( 'consult_packages' ) ) ? absint( self::text( 'consult_packages' ) ) : null,
				'declared_value'   => self::decimal( 'consult_value' ),
				'currency'         => 'USD',
				'description'      => $note,
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
		if ( 'trade' === $type || 'sourcing' === $type ) {
			$buy  = self::text( 'consult_buy_country' );
			$dest = self::text( 'consult_dest_country' );
			$dir  = ( 'روسیه' === $buy && 'ایران' === $dest ) ? 'ru_to_ir' : 'ir_to_ru';
			$note = trim( self::text( 'consult_product' ) . "\n" . self::text( 'consult_specs' ) . "\n" . self::area( 'consult_notes' ) );
			if ( 'sourcing' === $type ) {
				$note = trim( $note . "\nخرید: " . $buy . "\nمقصد: " . $dest . "\nحجم: " . self::text( 'consult_volume' ) );
			}
			return array(
				'direction'           => 'sourcing' === $type ? $dir : self::direction(),
				'request_type'        => 'sourcing',
				'product_category'    => self::text( 'consult_category' ),
				'product_description' => $note,
				'company_name'        => self::text( 'consult_name' ),
				'timeline'            => substr( self::text( 'consult_volume' ), 0, 100 ),
				'quantity'            => 'sourcing' === $type ? null : self::decimal( 'consult_qty' ),
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
		return Market::inquiry_code( self::text( $key ), $fallback );
	}

	/**
	 * tuition, transfer, or other. Anything else is other.
	 */
	private static function purpose(): string {
		$purpose = sanitize_key( self::text( 'consult_purpose' ) );
		return in_array( $purpose, array( 'tuition', 'transfer', 'other' ), true ) ? $purpose : 'other';
	}

	/**
	 * Notes, plus the tuition invoice lines when this request is a tuition payment.
	 */
	private static function exchange_notes(): string {
		$notes = self::area( 'consult_notes' );
		if ( 'tuition' !== self::purpose() ) {
			return $notes;
		}
		$lines = array(
			'نام دانشجو: ' . self::text( 'consult_student' ),
			'دانشگاه: ' . self::text( 'consult_uni' ),
			'شمارهٔ دانشجویی/فاکتور: ' . self::text( 'consult_invoice' ),
		);
		$due = self::text( 'consult_invoice_due' );
		if ( '' !== $due ) {
			$lines[] = 'مهلت فاکتور: ' . $due;
		}
		$extra = implode( "\n", $lines );
		return '' === trim( $notes ) ? $extra : $notes . "\n" . $extra;
	}

	/**
	 * Cargo lane fields. Weight and value stay optional.
	 */
	private static function cargo_lane_error(): string {
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( self::text( 'consult_origin' ) ) : strlen( self::text( 'consult_origin' ) ) ) < 2 ) {
			return 'مبدأ را بنویسید.';
		}
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( self::text( 'consult_dest' ) ) : strlen( self::text( 'consult_dest' ) ) ) < 2 ) {
			return 'مقصد را بنویسید.';
		}
		$allowed = array( 'documents', 'personal', 'samples', 'student', 'cosmetics', 'parts', 'commercial', 'clothing', 'other' );
		if ( ! in_array( sanitize_key( self::text( 'consult_cargo_kind' ) ), $allowed, true ) ) {
			return 'نوع کالا را انتخاب کنید.';
		}
		return self::sourcing_files_error();
	}

	/**
	 * Map a goods slug onto the cargo_type enum. The exact slug stays in the note.
	 *
	 * @param string $kind Goods slug.
	 */
	private static function cargo_kind_enum( string $kind ): string {
		if ( 'documents' === $kind ) {
			return 'documents';
		}
		if ( in_array( $kind, array( 'personal', 'clothing' ), true ) ) {
			return 'personal_items';
		}
		if ( in_array( $kind, array( 'samples', 'student' ), true ) ) {
			return 'samples';
		}
		return 'commercial';
	}

	/**
	 * Sourcing fields. Countries are only Iran and Russia.
	 */
	private static function sourcing_error(): string {
		$product = self::text( 'consult_product' );
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $product ) : strlen( $product ) ) < 2 ) {
			return 'نام کالا را بنویسید.';
		}
		$allowed = array( 'pistachio', 'dates', 'food', 'raw', 'spices', 'grains', 'cosmetics', 'russian', 'other' );
		if ( ! in_array( sanitize_key( self::text( 'consult_category' ) ), $allowed, true ) ) {
			return 'دسته‌بندی کالا را انتخاب کنید.';
		}
		foreach ( array( 'consult_buy_country', 'consult_dest_country' ) as $key ) {
			if ( ! in_array( self::text( $key ), array( 'ایران', 'روسیه' ), true ) ) {
				return 'کشور را از بین ایران و روسیه انتخاب کنید.';
			}
		}
		if ( '' === self::text( 'consult_volume' ) ) {
			return 'حجم یا تعداد را بنویسید.';
		}
		return self::sourcing_files_error();
	}

	/**
	 * Check sourcing uploads before a lead row is written.
	 */
	private static function sourcing_files_error(): string {
		if ( empty( $_FILES['consult_files']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		$names = $_FILES['consult_files']['name']; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$errors = $_FILES['consult_files']['error'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sizes  = $_FILES['consult_files']['size'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tmps   = $_FILES['consult_files']['tmp_name'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_array( $names ) ) {
			$names  = array( $names );
			$errors = array( $errors );
			$sizes  = array( $sizes );
			$tmps   = array( $tmps );
		}
		$count = 0;
		$limit = Files::sourcing_limit();
		$allow = array( 'image/jpeg', 'image/png', 'image/webp', 'application/pdf' );
		foreach ( $errors as $index => $error ) {
			$error = (int) $error;
			if ( UPLOAD_ERR_NO_FILE === $error ) {
				continue;
			}
			++$count;
			if ( $count > Files::SOURCING_MAX_FILES ) {
				return 'حداکثر ۵ فایل می‌توانید بفرستید.';
			}
			if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
				return 'حجم فایل از سقف سرور بیشتر است.';
			}
			if ( UPLOAD_ERR_OK !== $error ) {
				return 'آپلود ناموفق بود.';
			}
			if ( (int) ( $sizes[ $index ] ?? 0 ) > $limit ) {
				return 'هر فایل باید کوچک‌تر از سقف مجاز باشد.';
			}
			$tmp = (string) ( $tmps[ $index ] ?? '' );
			$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
			$mime  = $finfo ? (string) finfo_file( $finfo, $tmp ) : '';
			if ( $finfo ) {
				finfo_close( $finfo );
			}
			if ( ! in_array( $mime, $allow, true ) ) {
				return 'فقط JPG، PNG، WEBP یا PDF پذیرفته می‌شود.';
			}
		}
		return '';
	}

	/**
	 * cash, card, or transfer. Anything else is empty.
	 */
	private static function payment_method(): string {
		$method = sanitize_key( self::text( 'consult_payment' ) );
		return in_array( $method, array( 'cash', 'card', 'transfer' ), true ) ? $method : '';
	}

	/**
	 * Exchange submissions need a known payment method and a city.
	 */
	private static function exchange_error(): string {
		if ( '' === self::payment_method() ) {
			return self::t( 'form_need_pay', 'روش پرداخت را انتخاب کنید.' );
		}
		$city = self::text( 'consult_city' );
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $city ) : strlen( $city );
		if ( $len < 2 ) {
			return self::t( 'form_need_city', 'شهر را بنویسید.' );
		}
		if ( 'tuition' === self::purpose() ) {
			$required = array(
				'consult_student'  => 'نام دانشجو را بنویسید.',
				'consult_uni'      => 'دانشگاه را بنویسید.',
				'consult_invoice'  => 'شمارهٔ دانشجویی یا فاکتور را بنویسید.',
			);
			foreach ( $required as $key => $message ) {
				$value = self::text( $key );
				$size  = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
				if ( $size < 2 ) {
					return self::t( 'form_need_' . $key, $message );
				}
			}
		}
		return '';
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
			'sourcing'    => 'trade_form_success',
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
