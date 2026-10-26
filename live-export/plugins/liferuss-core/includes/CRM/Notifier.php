<?php
/**
 * Email and Telegram notices, queued on wp-cron.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\ActivityLog;
use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fail-safe delivery. A transport error never bubbles to the visitor.
 */
class Notifier {

	/**
	 * Register the cron handler.
	 */
	public static function hooks(): void {
		add_action( 'lr_notify_lead', array( self::class, 'send' ), 10, 1 );
	}

	/**
	 * Queue one notice. Duplicate events for the same lead are skipped.
	 *
	 * @param int $lead_id Lead id.
	 */
	public static function enqueue( int $lead_id ): void {
		if ( $lead_id < 1 ) {
			return;
		}
		$next = wp_next_scheduled( 'lr_notify_lead', array( $lead_id ) );
		if ( $next ) {
			return;
		}
		wp_schedule_single_event( time() + 15, 'lr_notify_lead', array( $lead_id ) );
	}

	/**
	 * Send whatever channels are enabled. Stores a short result for support.
	 *
	 * @param int $lead_id Lead id.
	 */
	public static function send( int $lead_id ): void {
		$lead = Repository::for( 'leads' )->find( $lead_id );
		if ( ! $lead ) {
			return;
		}
		$result = array(
			'lead_id'  => $lead_id,
			'email'    => 'skipped',
			'telegram' => 'skipped',
			'at'       => gmdate( 'Y-m-d H:i:s' ),
		);
		try {
			$result['email']    = self::email( $lead );
			$result['telegram'] = self::telegram( $lead );
		} catch ( \Throwable $error ) {
			$result['error'] = $error->getMessage();
		}
		update_option( 'lr_last_notice', $result, false );
		ActivityLog::record( 'lead.notify', 'lead', $lead_id, 'اعلان لید ارسال شد.', $result );
	}

	/**
	 * Email the service inbox.
	 *
	 * @param array<string, mixed> $lead Lead row.
	 */
	private static function email( array $lead ): string {
		$settings = Settings::get( 'notifications' );
		if ( '1' !== (string) $settings['email_enabled'] ) {
			return 'skipped';
		}
		$service = Repository::for( 'services' )->find( (int) $lead['service_id'] );
		$group   = $service ? (string) $service['service_group'] : '';
		$to      = '';
		if ( isset( $settings['services'][ $group ]['email'] ) ) {
			$to = sanitize_email( (string) $settings['services'][ $group ]['email'] );
		}
		if ( ! is_email( $to ) ) {
			$to = sanitize_email( (string) $settings['from_email'] );
		}
		$forms = Settings::get( 'forms' );
		if ( ! is_email( $to ) ) {
			$to = sanitize_email( (string) ( $forms['notify_email'] ?? '' ) );
		}
		if ( ! is_email( $to ) && function_exists( 'liferuss_opt' ) ) {
			$to = sanitize_email( (string) liferuss_opt( 'email', '' ) );
		}
		if ( ! is_email( $to ) ) {
			return 'no-recipient';
		}
		$subject = 'لید جدید ' . $lead['lead_code'] . ' — ' . $lead['name'];
		$body    = self::body( $lead );
		$headers = array();
		$from    = sanitize_email( (string) $settings['from_email'] );
		if ( is_email( $from ) ) {
			$name      = (string) $settings['from_name'];
			$headers[] = 'From: ' . $name . ' <' . $from . '>';
		}
		if ( apply_filters( 'lr_notice_mock', false ) ) {
			return 'mocked';
		}
		$sent = wp_mail( $to, $subject, $body, $headers );
		return $sent ? 'sent' : 'failed';
	}

	/**
	 * Telegram Bot API. Skipped when the token or chat is empty.
	 *
	 * @param array<string, mixed> $lead Lead row.
	 */
	private static function telegram( array $lead ): string {
		$settings = Settings::get( 'notifications' );
		if ( '1' !== (string) $settings['telegram_enabled'] ) {
			return 'skipped';
		}
		$token   = (string) $settings['bot_token'];
		$chat    = (string) $settings['chat_id'];
		$service = Repository::for( 'services' )->find( (int) $lead['service_id'] );
		$group   = $service ? (string) $service['service_group'] : '';
		if ( $group && ! empty( $settings['services'][ $group ]['chat_id'] ) ) {
			$chat = (string) $settings['services'][ $group ]['chat_id'];
		}
		if ( '' === $token || '' === $chat ) {
			return 'unconfigured';
		}
		if ( apply_filters( 'lr_notice_mock', false ) ) {
			return 'mocked';
		}
		$response = wp_remote_post(
			'https://api.telegram.org/bot' . rawurlencode( $token ) . '/sendMessage',
			array(
				'timeout' => 8,
				'body'    => array(
					'chat_id' => $chat,
					'text'    => self::body( $lead ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return 'failed';
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return ( $code >= 200 && $code < 300 ) ? 'sent' : 'failed';
	}

	/**
	 * Email every administrator and the shared Telegram channel.
	 *
	 * @param string $subject Subject.
	 * @param string $body    Body.
	 */
	public static function notify_admins( string $subject, string $body ): void {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'fields' => array( 'user_email' ),
				'number' => 20,
			)
		);
		foreach ( $admins as $admin ) {
			if ( is_email( $admin->user_email ) ) {
				self::mail_to( $admin->user_email, $subject, $body );
			}
		}
		self::telegram_text( $body );
	}

	/**
	 * One plain email. Failures stay inside the mailer.
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Subject.
	 * @param string $body    Body.
	 */
	public static function mail_to( string $to, string $subject, string $body ): void {
		$to = sanitize_email( $to );
		if ( ! is_email( $to ) ) {
			return;
		}
		if ( apply_filters( 'lr_notice_mock', false ) ) {
			return;
		}
		$settings = Settings::get( 'notifications' );
		$headers  = array();
		$from     = sanitize_email( (string) $settings['from_email'] );
		if ( is_email( $from ) ) {
			$headers[] = 'From: ' . (string) $settings['from_name'] . ' <' . $from . '>';
		}
		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Telegram text when the bot is configured.
	 *
	 * @param string $text Message.
	 */
	private static function telegram_text( string $text ): void {
		$settings = Settings::get( 'notifications' );
		if ( '1' !== (string) $settings['telegram_enabled'] ) {
			return;
		}
		$token = (string) $settings['bot_token'];
		$chat  = (string) $settings['chat_id'];
		if ( '' === $token || '' === $chat || apply_filters( 'lr_notice_mock', false ) ) {
			return;
		}
		wp_remote_post(
			'https://api.telegram.org/bot' . rawurlencode( $token ) . '/sendMessage',
			array(
				'timeout' => 8,
				'body'    => array(
					'chat_id' => $chat,
					'text'    => $text,
				),
			)
		);
	}

	/**
	 * Plain-text notice. No secrets.
	 *
	 * @param array<string, mixed> $lead Lead row.
	 */
	private static function body( array $lead ): string {
		$lines   = array(
			'کد: ' . $lead['lead_code'],
			'نام: ' . $lead['name'],
			'تلفن: ' . $lead['phone'],
			'فرم: ' . $lead['form_type'],
			'منبع: ' . $lead['utm_source'],
			'لندینگ: ' . $lead['landing_page'],
		);
		$message = trim( (string) $lead['message'] );
		if ( '' !== $message ) {
			$lines[] = $message;
		}
		return implode( "\n", $lines );
	}
}
