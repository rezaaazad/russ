<?php
/**
 * Front-end form intake (same action as the theme: liferuss_consult).
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme-aware string helper.
 *
 * @param string $key      i18n key.
 * @param string $fallback Fallback.
 * @return string
 */
function liferuss_leads_t( $key, $fallback ) {
	if ( function_exists( 'liferuss_t' ) ) {
		return liferuss_t( $key );
	}
	return $fallback;
}

/**
 * Theme option or fallback.
 *
 * @param string $key      Option key.
 * @param string $fallback Fallback.
 * @return string
 */
function liferuss_leads_opt( $key, $fallback = '' ) {
	if ( function_exists( 'liferuss_opt' ) ) {
		$value = liferuss_opt( $key, $fallback );
		return '' === $value || null === $value ? $fallback : $value;
	}
	return $fallback;
}

/**
 * Process a public form submission.
 *
 * @return array{ok:bool,message:string}
 */
function liferuss_leads_process() {
	if ( ! empty( $_POST['consult_lang'] ) && function_exists( 'liferuss_force_lang' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		liferuss_force_lang( sanitize_key( wp_unslash( $_POST['consult_lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	$honeypot = isset( $_POST['liferuss_company'] ) ? trim( (string) wp_unslash( $_POST['liferuss_company'] ) ) : '';
	if ( '' !== $honeypot ) {
		return array(
			'ok'      => true,
			'message' => liferuss_leads_opt( 'form_success', liferuss_leads_t( 'form_ok_short', 'ثبت شد.' ) ),
		);
	}

	$nonce = isset( $_POST['liferuss_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['liferuss_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'liferuss_consult' ) ) {
		return array(
			'ok'      => false,
			'message' => liferuss_leads_t( 'form_nonce', 'نشست منقضی شده است.' ),
		);
	}

	$name  = isset( $_POST['consult_name'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_name'] ) ) : '';
	$phone = isset( $_POST['consult_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_phone'] ) ) : '';
	$level = isset( $_POST['consult_level'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_level'] ) ) : '';
	$type  = isset( $_POST['consult_type'] ) ? sanitize_key( wp_unslash( $_POST['consult_type'] ) ) : 'consult';
	$types = liferuss_leads_form_types();
	if ( ! isset( $types[ $type ] ) || ! liferuss_leads_type_enabled( $type ) ) {
		$type = 'consult';
	}

	if ( function_exists( 'mb_strlen' ) ? mb_strlen( $name ) < 2 : strlen( $name ) < 2 ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_need_name', 'لطفاً نام را وارد کنید.' ) );
	}
	$digits = preg_replace( '/\D+/', '', $phone );
	if ( strlen( $digits ) < 8 ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_need_phone', 'شماره تماس معتبر وارد کنید.' ) );
	}

	$level_label = '';
	$extra       = array();
	$type_label  = liferuss_leads_type_label( $type );
	$success_key = isset( $types[ $type ]['success_option'] ) ? $types[ $type ]['success_option'] : 'form_success';
	$file        = array(
		'ok'  => true,
		'id'  => 0,
		'url' => '',
	);

	if ( ! empty( $types[ $type ]['requires_level'] ) ) {
		$levels = function_exists( 'liferuss_study_levels' ) ? liferuss_study_levels() : array();
		unset( $levels[''] );
		if ( ! isset( $levels[ $level ] ) ) {
			return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_need_level', 'مقطع تحصیلی را انتخاب کنید.' ) );
		}
		$level_label = $levels[ $level ];
	}

	if ( ! empty( $types[ $type ]['allows_file'] ) ) {
		$file = liferuss_leads_handle_file();
		if ( empty( $file['ok'] ) ) {
			return array( 'ok' => false, 'message' => $file['message'] );
		}
	}

	if ( 'freight' === $type ) {
		$cargo = liferuss_leads_choice_label( 'freight_form_types', isset( $_POST['consult_cargo_type'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_cargo_type'] ) ) : '' );
		$extra = array(
			'مبدأ'        => isset( $_POST['consult_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_origin'] ) ) : '',
			'مقصد'        => isset( $_POST['consult_dest'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dest'] ) ) : '',
			'نوع محموله'  => $cargo,
			'وزن'         => isset( $_POST['consult_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_weight'] ) ) : '',
			'ابعاد'       => isset( $_POST['consult_dims'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dims'] ) ) : '',
			'تعداد بسته'  => isset( $_POST['consult_packages'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_packages'] ) ) : '',
			'ارزش تقریبی' => isset( $_POST['consult_value'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_value'] ) ) : '',
			'توضیحات'     => isset( $_POST['consult_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consult_notes'] ) ) : '',
		);
	} elseif ( 'trade' === $type ) {
		$category = liferuss_leads_choice_label( 'trade_form_categories', isset( $_POST['consult_category'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_category'] ) ) : '' );
		$extra    = array(
			'نام کالا'   => isset( $_POST['consult_product'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_product'] ) ) : '',
			'دسته‌بندی'  => $category,
			'کشور مبدأ'  => isset( $_POST['consult_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_origin'] ) ) : '',
			'کشور مقصد'  => isset( $_POST['consult_dest'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dest'] ) ) : '',
			'حجم'        => isset( $_POST['consult_qty'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_qty'] ) ) : '',
			'مشخصات'     => isset( $_POST['consult_specs'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_specs'] ) ) : '',
			'توضیحات'    => isset( $_POST['consult_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consult_notes'] ) ) : '',
		);
	} elseif ( 'contact' === $type ) {
		$extra = array(
			'پیام' => isset( $_POST['consult_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consult_message'] ) ) : '',
		);
	}

	$extra = apply_filters( 'liferuss_leads_extra_fields', $extra, $type );

	$summary = $phone . ' — ' . $type_label;
	if ( $level_label ) {
		$summary .= ' — ' . $level_label;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'liferuss_lead',
			'post_status'  => 'private',
			'post_title'   => $name,
			'post_content' => $summary,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_save_fail', 'ثبت درخواست ممکن نشد.' ) );
	}

	update_post_meta( $post_id, '_liferuss_phone', $phone );
	update_post_meta( $post_id, '_liferuss_level', $level_label );
	update_post_meta( $post_id, '_liferuss_type', $type_label );
	update_post_meta( $post_id, '_liferuss_type_slug', $type );
	update_post_meta( $post_id, '_liferuss_status', 'new' );
	update_post_meta( $post_id, '_liferuss_assignee', 0 );
	update_post_meta( $post_id, '_liferuss_extra', $extra );
	update_post_meta( $post_id, '_liferuss_lang', isset( $_POST['consult_lang'] ) ? sanitize_key( wp_unslash( $_POST['consult_lang'] ) ) : '' );
	update_post_meta( $post_id, '_liferuss_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	if ( ! empty( $file['url'] ) ) {
		update_post_meta( $post_id, '_liferuss_file', esc_url_raw( $file['url'] ) );
		update_post_meta( $post_id, '_liferuss_file_id', absint( $file['id'] ) );
	}

	liferuss_leads_notify( $post_id, $name, $phone, $type_label, $level_label, $extra, $file );

	$success = liferuss_leads_opt( $success_key, '' );
	if ( '' === $success ) {
		$success = liferuss_leads_t( $success_key, liferuss_leads_t( 'form_ok_short', 'ثبت شد.' ) );
	}

	return array(
		'ok'      => true,
		'message' => $success,
	);
}

/**
 * Email admins about a new lead.
 *
 * @param int    $post_id     Lead ID.
 * @param string $name        Name.
 * @param string $phone       Phone.
 * @param string $type_label  Type.
 * @param string $level_label Level.
 * @param array  $extra       Extra.
 * @param array  $file        File.
 */
function liferuss_leads_notify( $post_id, $name, $phone, $type_label, $level_label, $extra, $file ) {
	$settings = liferuss_leads_settings();
	$to       = sanitize_email( $settings['notify_email'] );
	if ( ! $to || ! is_email( $to ) ) {
		$to = sanitize_email( liferuss_leads_opt( 'form_email', liferuss_leads_opt( 'email', 'info@liferuss.com' ) ) );
	}
	if ( ! $to || ! is_email( $to ) ) {
		return;
	}

	$brand   = function_exists( 'liferuss_brand' ) ? liferuss_brand() : 'لایف روس';
	$subject = 'درخواست جدید — ' . $type_label . ' — ' . $brand;
	$body    = "نام: {$name}\nتلفن: {$phone}\nنوع: {$type_label}\n";
	if ( $level_label ) {
		$body .= "مقطع: {$level_label}\n";
	}
	foreach ( (array) $extra as $label => $value ) {
		if ( '' !== $value ) {
			$body .= "{$label}: {$value}\n";
		}
	}
	if ( ! empty( $file['url'] ) ) {
		$body .= 'فایل: ' . $file['url'] . "\n";
	}
	$body .= 'شناسه: ' . (int) $post_id . "\n";

	wp_mail( $to, $subject, $body );
}

/**
 * Map a theme repeater select value to its label.
 *
 * @param string $key   Option key.
 * @param string $value Posted value.
 * @return string
 */
function liferuss_leads_choice_label( $key, $value ) {
	if ( function_exists( 'liferuss_choice_label' ) && ! defined( 'LIFERUSS_LEADS_ACTIVE' ) ) {
		return liferuss_choice_label( $key, $value );
	}
	if ( function_exists( 'liferuss_opt' ) ) {
		foreach ( (array) liferuss_opt( $key, array() ) as $row ) {
			if ( isset( $row['value'] ) && (string) $row['value'] === (string) $value ) {
				return isset( $row['label'] ) ? $row['label'] : $value;
			}
		}
	}
	return $value;
}

/**
 * Optional JPG/PNG/PDF upload, max 10 MB.
 *
 * @return array{ok:bool,message?:string,id?:int,url?:string}
 */
function liferuss_leads_handle_file() {
	if ( empty( $_FILES['consult_file'] ) || empty( $_FILES['consult_file']['name'] ) ) {
		return array(
			'ok'  => true,
			'id'  => 0,
			'url' => '',
		);
	}

	$error = isset( $_FILES['consult_file']['error'] ) ? (int) $_FILES['consult_file']['error'] : UPLOAD_ERR_NO_FILE;
	if ( UPLOAD_ERR_NO_FILE === $error ) {
		return array(
			'ok'  => true,
			'id'  => 0,
			'url' => '',
		);
	}
	if ( UPLOAD_ERR_OK !== $error ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_bad_file', 'فایل نامعتبر است.' ) );
	}

	$size = isset( $_FILES['consult_file']['size'] ) ? (int) $_FILES['consult_file']['size'] : 0;
	if ( $size > 10 * 1024 * 1024 ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_big_file', 'حجم فایل بیش از ۱۰ مگابایت است.' ) );
	}

	$ext = strtolower( pathinfo( sanitize_file_name( wp_unslash( $_FILES['consult_file']['name'] ) ), PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'pdf' ), true ) ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_bad_file', 'فقط JPG، PNG یا PDF.' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload( 'consult_file', 0 );
	if ( is_wp_error( $attachment_id ) ) {
		return array( 'ok' => false, 'message' => liferuss_leads_t( 'form_bad_file', 'آپلود ناموفق بود.' ) );
	}

	return array(
		'ok'  => true,
		'id'  => (int) $attachment_id,
		'url' => (string) wp_get_attachment_url( $attachment_id ),
	);
}

/**
 * AJAX handler.
 */
function liferuss_leads_ajax() {
	$result = liferuss_leads_process();
	if ( $result['ok'] ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}
add_action( 'wp_ajax_liferuss_consult', 'liferuss_leads_ajax' );
add_action( 'wp_ajax_nopriv_liferuss_consult', 'liferuss_leads_ajax' );

/**
 * Non-JS POST fallback.
 */
function liferuss_leads_admin_post() {
	$result = liferuss_leads_process();
	$target = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$target = remove_query_arg( array( 'consult', 'consult_msg' ), $target );
	$anchor = false !== strpos( $target, '#' ) ? '' : '#consultation';
	$target = add_query_arg(
		array(
			'consult'     => $result['ok'] ? 'ok' : 'err',
			'consult_msg' => rawurlencode( $result['message'] ),
		),
		$target . $anchor
	);
	wp_safe_redirect( $target );
	exit;
}
add_action( 'admin_post_nopriv_liferuss_consult', 'liferuss_leads_admin_post' );
add_action( 'admin_post_liferuss_consult', 'liferuss_leads_admin_post' );
