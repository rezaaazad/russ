<?php
/**
 * Admin fields for homepage testimonials and completed work.
 * Nothing is seeded. Drafts and placeholder copy stay off the homepage.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta boxes for lr_testimonial and lr_case.
 */
class Stories {

	/**
	 * Hook the boxes.
	 */
	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post_lr_testimonial', array( self::class, 'save' ) );
		add_action( 'save_post_lr_case', array( self::class, 'save' ) );
	}

	/**
	 * Register boxes.
	 */
	public static function boxes(): void {
		add_meta_box( 'lr_story', 'نمایش در صفحهٔ اصلی', array( self::class, 'box' ), array( 'lr_testimonial', 'lr_case' ), 'side', 'high' );
	}

	/**
	 * Fields. Title is the name or case title. Body is the quote or the story.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function box( \WP_Post $post ): void {
		wp_nonce_field( 'lr_story_save', 'lr_story_nonce' );
		$context = (string) get_post_meta( $post->ID, '_lr_context', true );
		$extra   = 'lr_case' === $post->post_type ? (string) get_post_meta( $post->ID, '_lr_result', true ) : (string) get_post_meta( $post->ID, '_lr_rating', true );
		echo '<p>فقط نوشتهٔ <strong>منتشرشده</strong> در صفحهٔ اصلی می‌آید. متن‌های نمونهٔ بسته‌شده (مثل «نظر نمونه» یا «(نمونه)») چاپ نمی‌شوند. تا وقتی چیزی منتشر نکنید این بخش خالی می‌ماند.</p>';
		echo '<p><label for="lr_context">زمینه (رشته، دانشگاه، شهر یا خدمت)<br>';
		echo '<input class="widefat" id="lr_context" name="lr_context" value="' . esc_attr( $context ) . '"></label></p>';
		if ( 'lr_case' === $post->post_type ) {
			echo '<p><label for="lr_result">نتیجهٔ کوتاه، با عبارت خودتان<br>';
			echo '<input class="widefat" id="lr_result" name="lr_result" value="' . esc_attr( $extra ) . '"></label></p>';
			echo '<p>عنوان، متن و تصویر شاخص را در بدنهٔ نوشته بگذارید. عددی ننویسید مگر سندش را داشته باشید.</p>';
			return;
		}
		echo '<p><label for="lr_rating">امتیاز ۱ تا ۵، اختیاری<br>';
		echo '<input class="small-text" id="lr_rating" name="lr_rating" inputmode="numeric" value="' . esc_attr( $extra ) . '"></label></p>';
		echo '<p>عنوان = نام شخص. متن = نقل‌قول. چکیده = یک خط زمینه، اگر فیلد بالا خالی بماند. تصویر شاخص اختیاری است.</p>';
	}

	/**
	 * Save the side fields.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['lr_story_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_story_nonce'] ) ), 'lr_story_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$context = isset( $_POST['lr_context'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_context'] ) ) : '';
		update_post_meta( $post_id, '_lr_context', $context );
		if ( 'lr_case' === get_post_type( $post_id ) ) {
			$result = isset( $_POST['lr_result'] ) ? sanitize_text_field( wp_unslash( $_POST['lr_result'] ) ) : '';
			update_post_meta( $post_id, '_lr_result', $result );
			return;
		}
		$rating = isset( $_POST['lr_rating'] ) ? absint( wp_unslash( $_POST['lr_rating'] ) ) : 0;
		if ( $rating < 1 || $rating > 5 ) {
			delete_post_meta( $post_id, '_lr_rating' );
			return;
		}
		update_post_meta( $post_id, '_lr_rating', (string) $rating );
	}
}
