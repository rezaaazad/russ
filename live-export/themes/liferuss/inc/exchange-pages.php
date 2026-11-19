<?php
/**
 * Exchange-family navigation and the empty image slot.
 *
 * The picture is an attachment the owner supplies later. No stock image is shipped.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slugs that belong to the exchange section.
 *
 * @return string[]
 */
function liferuss_exchange_slugs() {
	return array( 'exchange', 'calculator', 'transfer-to-russia', 'tuition-payment', 'currencies' );
}

/**
 * Whether the current page is the exchange hub or one of its children.
 */
function liferuss_exchange_family() {
	if ( ! is_page() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	if ( in_array( $post->post_name, liferuss_exchange_slugs(), true ) ) {
		return true;
	}
	$parent = (int) $post->post_parent;
	if ( $parent < 1 ) {
		return false;
	}
	$parent_post = get_post( $parent );
	return $parent_post instanceof WP_Post && 'exchange' === $parent_post->post_name;
}

/**
 * Links between the exchange pages.
 */
function liferuss_exchange_nav() {
	if ( ! liferuss_exchange_family() ) {
		return;
	}
	$links = array(
		'/exchange/'                    => 'نرخ روز',
		'/exchange/calculator/'         => 'ماشین‌حساب مرجع',
		'/exchange/transfer-to-russia/' => 'حواله به روسیه',
		'/exchange/tuition-payment/'    => 'پرداخت شهریه',
		'/exchange/currencies/'         => 'انواع ارز',
	);
	echo '<nav class="lr-exnav" aria-label="' . esc_attr( 'صفحات ارز' ) . '">';
	foreach ( $links as $path => $label ) {
		echo '<a href="' . esc_url( liferuss_url( $path ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav>';
}

/**
 * Reserved frame. An attachment fills it. Otherwise the slot stays labeled and empty.
 *
 * @param int $post_id Page id.
 */
function liferuss_the_image_slot( $post_id ) {
	$post_id = (int) $post_id;
	$width   = (int) get_post_meta( $post_id, '_lr_slot_w', true );
	$height  = (int) get_post_meta( $post_id, '_lr_slot_h', true );
	if ( $width < 1 ) {
		$width = 1600;
	}
	if ( $height < 1 ) {
		$height = 900;
	}
	$image = (int) get_post_meta( $post_id, '_lr_slot_image', true );
	$label = 'جای تصویر لایف‌روس';
	echo '<figure class="lr-slot" style="aspect-ratio:' . esc_attr( $width . ' / ' . $height ) . '">';
	if ( $image && wp_attachment_is_image( $image ) ) {
		echo wp_get_attachment_image(
			$image,
			'large',
			false,
			array(
				'width'  => $width,
				'height' => $height,
				'alt'    => $label,
			)
		);
	} else {
		echo '<div class="lr-slot__hold" role="img" aria-label="' . esc_attr( $label ) . '">';
		echo '<span>' . esc_html( $label ) . '</span>';
		echo '<small dir="ltr">' . esc_html( $width . '×' . $height ) . '</small>';
		echo '</div>';
	}
	echo '</figure>';
}

/**
 * Attachment field on exchange pages.
 */
function liferuss_exchange_slot_box() {
	add_meta_box(
		'lr_exchange_slot',
		'جای تصویر',
		'liferuss_exchange_slot_box_html',
		'page',
		'side'
	);
}
add_action( 'add_meta_boxes', 'liferuss_exchange_slot_box' );

/**
 * Meta box body.
 *
 * @param WP_Post $post Page.
 */
function liferuss_exchange_slot_box_html( $post ) {
	wp_nonce_field( 'lr_exchange_slot', 'lr_exchange_slot_nonce' );
	$image  = (int) get_post_meta( $post->ID, '_lr_slot_image', true );
	$width  = (int) get_post_meta( $post->ID, '_lr_slot_w', true );
	$height = (int) get_post_meta( $post->ID, '_lr_slot_h', true );
	if ( $width < 1 ) {
		$width = 1600;
	}
	if ( $height < 1 ) {
		$height = 900;
	}
	echo '<p>شناسهٔ پیوست. تا وقتی خالی است، عکس همراه قالب در سرصفحه نشان داده می‌شود. عرض و ارتفاع را با همان عکس یکی بگذارید.</p>';
	echo '<p><label>شناسه<input class="widefat" type="number" name="lr_slot_image" min="0" value="' . esc_attr( (string) $image ) . '"></label></p>';
	echo '<p><label>عرض<input class="widefat" type="number" name="lr_slot_w" min="1" value="' . esc_attr( (string) $width ) . '"></label></p>';
	echo '<p><label>ارتفاع<input class="widefat" type="number" name="lr_slot_h" min="1" value="' . esc_attr( (string) $height ) . '"></label></p>';
}

/**
 * Save the slot fields.
 *
 * @param int $post_id Page id.
 */
function liferuss_exchange_slot_save( $post_id ) {
	if ( ! isset( $_POST['lr_exchange_slot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_exchange_slot_nonce'] ) ), 'lr_exchange_slot' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}
	$image = isset( $_POST['lr_slot_image'] ) ? absint( $_POST['lr_slot_image'] ) : 0;
	$width = isset( $_POST['lr_slot_w'] ) ? absint( $_POST['lr_slot_w'] ) : 1600;
	$height = isset( $_POST['lr_slot_h'] ) ? absint( $_POST['lr_slot_h'] ) : 900;
	if ( $width < 1 ) {
		$width = 1600;
	}
	if ( $height < 1 ) {
		$height = 900;
	}
	update_post_meta( $post_id, '_lr_slot_image', $image );
	update_post_meta( $post_id, '_lr_slot_w', (string) $width );
	update_post_meta( $post_id, '_lr_slot_h', (string) $height );
}
add_action( 'save_post_page', 'liferuss_exchange_slot_save' );
