<?php
/**
 * Consultation request CPT and form handlers.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'LIFERUSS_LEADS_ACTIVE' ) && LIFERUSS_LEADS_ACTIVE ) {
	return;
}

/**
 * Register leads post type for the admin inbox.
 */
function liferuss_register_leads() {
	register_post_type(
		'liferuss_lead',
		array(
			'labels'       => array(
				'name'          => 'درخواست‌های مشاوره',
				'singular_name' => 'درخواست مشاوره',
				'add_new_item'  => 'افزودن درخواست',
				'edit_item'     => 'ویرایش درخواست',
				'menu_name'     => 'مشاوره‌ها',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-email-alt',
			'supports'     => array( 'title' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'liferuss_register_leads' );

/**
 * Add lead meta box.
 */
function liferuss_lead_metabox() {
	add_meta_box(
		'liferuss_lead_details',
		'جزئیات درخواست',
		'liferuss_lead_metabox_html',
		'liferuss_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'liferuss_lead_metabox' );

/**
 * Meta box markup.
 *
 * @param WP_Post $post Post.
 */
function liferuss_lead_metabox_html( $post ) {
	$phone = get_post_meta( $post->ID, '_liferuss_phone', true );
	$level = get_post_meta( $post->ID, '_liferuss_level', true );
	$type  = get_post_meta( $post->ID, '_liferuss_type', true );
	$ip    = get_post_meta( $post->ID, '_liferuss_ip', true );
	$extra = get_post_meta( $post->ID, '_liferuss_extra', true );
	$file  = get_post_meta( $post->ID, '_liferuss_file', true );
	echo '<p><strong>نام:</strong> ' . esc_html( $post->post_title ) . '</p>';
	echo '<p><strong>تلفن:</strong> ' . esc_html( $phone ) . '</p>';
	echo '<p><strong>نوع درخواست:</strong> ' . esc_html( $type ? $type : 'مشاوره تحصیل' ) . '</p>';
	if ( $level ) {
		echo '<p><strong>مقطع:</strong> ' . esc_html( $level ) . '</p>';
	}
	if ( is_array( $extra ) ) {
		foreach ( $extra as $label => $value ) {
			if ( '' === $value ) {
				continue;
			}
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
		}
	}
	if ( $file ) {
		echo '<p><strong>فایل:</strong> <a href="' . esc_url( $file ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $file ) . '</a></p>';
	}
	echo '<p><strong>IP:</strong> ' . esc_html( $ip ) . '</p>';
}

/**
 * Process a consultation submission (AJAX or regular POST).
 *
 * @return array{ok:bool,message:string}
 */
function liferuss_process_consultation() {
	if ( ! empty( $_POST['consult_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		liferuss_force_lang( sanitize_key( wp_unslash( $_POST['consult_lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	$honeypot = isset( $_POST['liferuss_company'] ) ? trim( (string) wp_unslash( $_POST['liferuss_company'] ) ) : '';
	if ( '' !== $honeypot ) {
		return array(
			'ok'      => true,
			'message' => liferuss_opt( 'form_success', liferuss_t( 'form_ok_short' ) ),
		);
	}

	$nonce = isset( $_POST['liferuss_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['liferuss_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'liferuss_consult' ) ) {
		return array(
			'ok'      => false,
			'message' => liferuss_t( 'form_nonce' ),
		);
	}

	$name  = isset( $_POST['consult_name'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_name'] ) ) : '';
	$phone = isset( $_POST['consult_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_phone'] ) ) : '';
	$level = isset( $_POST['consult_level'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_level'] ) ) : '';
	$type  = isset( $_POST['consult_type'] ) ? sanitize_key( wp_unslash( $_POST['consult_type'] ) ) : 'consult';
	if ( ! in_array( $type, array( 'consult', 'freight', 'trade' ), true ) ) {
		$type = 'consult';
	}

	if ( mb_strlen( $name ) < 2 ) {
		return array( 'ok' => false, 'message' => liferuss_t( 'form_need_name' ) );
	}
	$digits = preg_replace( '/\D+/', '', $phone );
	if ( strlen( $digits ) < 8 ) {
		return array( 'ok' => false, 'message' => liferuss_t( 'form_need_phone' ) );
	}

	$level_label = '';
	$extra       = array();
	$type_label  = 'مشاوره تحصیل';
	$success_key = 'form_success';
	$file        = array(
		'ok'  => true,
		'id'  => 0,
		'url' => '',
	);

	if ( 'consult' === $type ) {
		$levels = liferuss_study_levels();
		unset( $levels[''] );
		if ( ! isset( $levels[ $level ] ) ) {
			return array( 'ok' => false, 'message' => liferuss_t( 'form_need_level' ) );
		}
		$level_label = $levels[ $level ];
	} else {
		$file = liferuss_handle_consult_file();
		if ( empty( $file['ok'] ) ) {
			return array( 'ok' => false, 'message' => $file['message'] );
		}
		if ( 'freight' === $type ) {
			$type_label  = 'باربری و ارسال';
			$success_key = 'freight_form_success';
			$types       = liferuss_choice_label( 'freight_form_types', isset( $_POST['consult_cargo_type'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_cargo_type'] ) ) : '' );
			$extra       = array(
				'مبدأ'           => isset( $_POST['consult_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_origin'] ) ) : '',
				'مقصد'           => isset( $_POST['consult_dest'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dest'] ) ) : '',
				'نوع محموله'     => $types,
				'وزن'            => isset( $_POST['consult_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_weight'] ) ) : '',
				'ابعاد'          => isset( $_POST['consult_dims'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dims'] ) ) : '',
				'تعداد بسته'     => isset( $_POST['consult_packages'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_packages'] ) ) : '',
				'ارزش تقریبی'    => isset( $_POST['consult_value'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_value'] ) ) : '',
				'توضیحات'        => isset( $_POST['consult_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consult_notes'] ) ) : '',
			);
		} else {
			$type_label  = 'تجارت و تأمین کالا';
			$success_key = 'trade_form_success';
			$category    = liferuss_choice_label( 'trade_form_categories', isset( $_POST['consult_category'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_category'] ) ) : '' );
			$extra       = array(
				'نام کالا'       => isset( $_POST['consult_product'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_product'] ) ) : '',
				'دسته‌بندی'      => $category,
				'کشور مبدأ'      => isset( $_POST['consult_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_origin'] ) ) : '',
				'کشور مقصد'      => isset( $_POST['consult_dest'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_dest'] ) ) : '',
				'حجم'            => isset( $_POST['consult_qty'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_qty'] ) ) : '',
				'مشخصات'         => isset( $_POST['consult_specs'] ) ? sanitize_text_field( wp_unslash( $_POST['consult_specs'] ) ) : '',
				'توضیحات'        => isset( $_POST['consult_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consult_notes'] ) ) : '',
			);
		}
	}

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
		return array( 'ok' => false, 'message' => liferuss_t( 'form_save_fail' ) );
	}

	update_post_meta( $post_id, '_liferuss_phone', $phone );
	update_post_meta( $post_id, '_liferuss_level', $level_label );
	update_post_meta( $post_id, '_liferuss_type', $type_label );
	update_post_meta( $post_id, '_liferuss_extra', $extra );
	update_post_meta( $post_id, '_liferuss_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	if ( ! empty( $file['url'] ) ) {
		update_post_meta( $post_id, '_liferuss_file', esc_url_raw( $file['url'] ) );
		update_post_meta( $post_id, '_liferuss_file_id', absint( $file['id'] ) );
	}

	$to = sanitize_email( liferuss_opt( 'form_email', liferuss_opt( 'email', 'info@liferuss.com' ) ) );
	if ( $to && is_email( $to ) ) {
		$subject = 'درخواست جدید — ' . $type_label . ' — ' . liferuss_brand();
		$body    = "نام: {$name}\nتلفن: {$phone}\nنوع: {$type_label}\n";
		if ( $level_label ) {
			$body .= "مقطع: {$level_label}\n";
		}
		foreach ( $extra as $label => $value ) {
			if ( '' !== $value ) {
				$body .= "{$label}: {$value}\n";
			}
		}
		if ( ! empty( $file['url'] ) ) {
			$body .= 'فایل: ' . $file['url'] . "\n";
		}
		wp_mail( $to, $subject, $body );
	}

	return array(
		'ok'      => true,
		'message' => liferuss_opt( $success_key, liferuss_t( 'form_ok_short' ) ),
	);
}

/**
 * Map a select value to its option label.
 *
 * @param string $key   Repeater option key.
 * @param string $value Posted value.
 * @return string
 */
function liferuss_choice_label( $key, $value ) {
	foreach ( (array) liferuss_opt( $key, array() ) as $row ) {
		if ( isset( $row['value'] ) && (string) $row['value'] === (string) $value ) {
			return isset( $row['label'] ) ? $row['label'] : $value;
		}
	}
	return $value;
}

/**
 * Handle optional landing-form file upload.
 *
 * @return array{ok:bool,message?:string,id?:int,url?:string}
 */
function liferuss_handle_consult_file() {
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
		return array( 'ok' => false, 'message' => liferuss_t( 'form_bad_file' ) );
	}

	$size = isset( $_FILES['consult_file']['size'] ) ? (int) $_FILES['consult_file']['size'] : 0;
	if ( $size > 10 * 1024 * 1024 ) {
		return array( 'ok' => false, 'message' => liferuss_t( 'form_big_file' ) );
	}

	$ext = strtolower( pathinfo( sanitize_file_name( wp_unslash( $_FILES['consult_file']['name'] ) ), PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'pdf' ), true ) ) {
		return array( 'ok' => false, 'message' => liferuss_t( 'form_bad_file' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload( 'consult_file', 0 );
	if ( is_wp_error( $attachment_id ) ) {
		return array( 'ok' => false, 'message' => liferuss_t( 'form_bad_file' ) );
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
function liferuss_ajax_consultation() {
	$result = liferuss_process_consultation();
	if ( $result['ok'] ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}
add_action( 'wp_ajax_liferuss_consult', 'liferuss_ajax_consultation' );
add_action( 'wp_ajax_nopriv_liferuss_consult', 'liferuss_ajax_consultation' );

/**
 * Regular POST fallback (works without JavaScript).
 */
function liferuss_admin_post_consultation() {
	$result = liferuss_process_consultation();
	$target = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$target = remove_query_arg( array( 'consult', 'consult_msg' ), $target );
	$target = add_query_arg(
		array(
			'consult'     => $result['ok'] ? 'ok' : 'err',
			'consult_msg' => rawurlencode( $result['message'] ),
		),
		$target . '#consultation'
	);
	wp_safe_redirect( $target );
	exit;
}
add_action( 'admin_post_nopriv_liferuss_consult', 'liferuss_admin_post_consultation' );
add_action( 'admin_post_liferuss_consult', 'liferuss_admin_post_consultation' );

/**
 * Admin columns for leads.
 *
 * @param array $columns Columns.
 * @return array
 */
function liferuss_lead_columns( $columns ) {
	$columns['type']  = 'نوع';
	$columns['phone'] = 'تلفن';
	$columns['level'] = 'مقطع';
	return $columns;
}
add_filter( 'manage_liferuss_lead_posts_columns', 'liferuss_lead_columns' );

/**
 * Admin column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function liferuss_lead_column_values( $column, $post_id ) {
	if ( 'type' === $column ) {
		$stored = get_post_meta( $post_id, '_liferuss_type', true );
		echo esc_html( $stored ? $stored : 'مشاوره تحصیل' );
	}
	if ( 'phone' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_liferuss_phone', true ) );
	}
	if ( 'level' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_liferuss_level', true ) );
	}
}
add_action( 'manage_liferuss_lead_posts_custom_column', 'liferuss_lead_column_values', 10, 2 );
