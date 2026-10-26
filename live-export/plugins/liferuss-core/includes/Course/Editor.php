<?php
/**
 * Lesson fields: media and the quiz builder.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta box on the lesson edit screen.
 */
class Editor {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( self::class, 'box' ) );
		add_action( 'save_post_lr_lesson', array( self::class, 'save' ) );
	}

	/**
	 * Register the box.
	 */
	public static function box(): void {
		add_meta_box( 'lr_lesson_quiz', 'درس و آزمون', array( self::class, 'render' ), 'lr_lesson', 'normal', 'high' );
	}

	/**
	 * Fields.
	 *
	 * @param \WP_Post $post Lesson.
	 */
	public static function render( $post ): void {
		wp_nonce_field( 'lr_lesson_save', 'lr_lesson_nonce' );
		$audio   = (string) get_post_meta( $post->ID, '_lr_audio', true );
		$video   = (string) get_post_meta( $post->ID, '_lr_video', true );
		$place   = (string) get_post_meta( $post->ID, '_lr_placement', true );
		$parent  = (int) $post->post_parent;
		$courses = get_posts(
			array(
				'post_type'      => 'lr_course',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		echo '<p><label>دوره <select name="lr_course_parent"><option value="0">—</option>';
		foreach ( $courses as $course ) {
			echo '<option value="' . esc_attr( (string) $course->ID ) . '" ' . selected( $parent, (int) $course->ID, false ) . '>' . esc_html( $course->post_title ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>نشانی صوت <input class="large-text" type="url" name="lr_audio" value="' . esc_attr( $audio ) . '"></label></p>';
		echo '<p><label>نشانی ویدیو (یوتیوب یا آپارات) <input class="large-text" type="url" name="lr_video" value="' . esc_attr( $video ) . '"></label></p>';
		echo '<p><label><input type="checkbox" name="lr_placement" value="1" ' . checked( '1', $place, false ) . '> این درس فقط آزمون تعیین سطح است و در فهرست دوره نمی‌آید.</label></p>';
		echo '<p class="description">هر سؤال یک ردیف است. چندگزینه‌ای: هر گزینه یک خط و پاسخ درست با * در ابتدا. تطبیق: هر خط «چپ | راست». جای‌خالی: خود پاسخ.</p>';
		$rows   = Store::questions( (int) $post->ID );
		$rows[] = array();
		$rows[] = array();
		foreach ( $rows as $row ) {
			$type    = (string) ( $row['type'] ?? 'choice' );
			$prompt  = (string) ( $row['prompt'] ?? '' );
			$payload = self::payload( $row );
			echo '<div style="margin:12px 0;padding:12px;background:#f6f7f7">';
			echo '<select name="lr_quiz_type[]">';
			foreach ( array(
				'choice' => 'چندگزینه‌ای',
				'match'  => 'تطبیق',
				'fill'   => 'جای‌خالی',
			) as $key => $label ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $type, $key, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select> ';
			echo '<input class="large-text" name="lr_quiz_prompt[]" value="' . esc_attr( $prompt ) . '" placeholder="صورت سؤال">';
			echo '<textarea class="large-text" name="lr_quiz_payload[]" rows="4">' . esc_textarea( $payload ) . '</textarea>';
			echo '</div>';
		}
	}

	/**
	 * Save media, parent, and quiz rows.
	 *
	 * @param int $post_id Lesson id.
	 */
	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['lr_lesson_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_lesson_nonce'] ) ), 'lr_lesson_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$audio = isset( $_POST['lr_audio'] ) ? esc_url_raw( wp_unslash( $_POST['lr_audio'] ) ) : '';
		$video = isset( $_POST['lr_video'] ) ? esc_url_raw( wp_unslash( $_POST['lr_video'] ) ) : '';
		update_post_meta( $post_id, '_lr_audio', $audio );
		update_post_meta( $post_id, '_lr_video', $video );
		update_post_meta( $post_id, '_lr_placement', isset( $_POST['lr_placement'] ) ? '1' : '0' );
		$parent = isset( $_POST['lr_course_parent'] ) ? absint( $_POST['lr_course_parent'] ) : 0;
		if ( $parent && 'lr_course' !== get_post_type( $parent ) ) {
			$parent = 0;
		}
		$post = get_post( $post_id );
		if ( $post && (int) $post->post_parent !== $parent ) {
			remove_action( 'save_post_lr_lesson', array( self::class, 'save' ) );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_parent' => $parent,
				)
			);
			add_action( 'save_post_lr_lesson', array( self::class, 'save' ) );
		}
		$types    = isset( $_POST['lr_quiz_type'] ) ? map_deep( wp_unslash( $_POST['lr_quiz_type'] ), 'sanitize_key' ) : array();
		$prompts  = isset( $_POST['lr_quiz_prompt'] ) ? map_deep( wp_unslash( $_POST['lr_quiz_prompt'] ), 'sanitize_text_field' ) : array();
		$payloads = isset( $_POST['lr_quiz_payload'] ) ? map_deep( wp_unslash( $_POST['lr_quiz_payload'] ), 'sanitize_textarea_field' ) : array();
		$rows     = array();
		if ( is_array( $prompts ) ) {
			foreach ( $prompts as $index => $prompt ) {
				$type   = is_array( $types ) && isset( $types[ $index ] ) ? (string) $types[ $index ] : 'choice';
				$body   = is_array( $payloads ) && isset( $payloads[ $index ] ) ? (string) $payloads[ $index ] : '';
				$parsed = Store::parse( $type, (string) $prompt, $body );
				if ( $parsed ) {
					$rows[] = $parsed;
				}
			}
		}
		Store::save_questions( $post_id, array_slice( $rows, 0, 20 ) );
	}

	/**
	 * Builder text for a stored question.
	 *
	 * @param array<string, mixed> $row Question.
	 */
	private static function payload( array $row ): string {
		$type = (string) ( $row['type'] ?? '' );
		if ( 'match' === $type && ! empty( $row['pairs'] ) && is_array( $row['pairs'] ) ) {
			$lines = array();
			foreach ( $row['pairs'] as $pair ) {
				$lines[] = (string) ( $pair['left'] ?? '' ) . ' | ' . (string) ( $pair['right'] ?? '' );
			}
			return implode( "\n", $lines );
		}
		if ( 'fill' === $type ) {
			return (string) ( $row['accept'] ?? '' );
		}
		if ( empty( $row['choices'] ) || ! is_array( $row['choices'] ) ) {
			return '';
		}
		$lines = array();
		foreach ( $row['choices'] as $index => $choice ) {
			$lines[] = ( (int) ( $row['answer'] ?? 0 ) === (int) $index ? '* ' : '' ) . (string) $choice;
		}
		return implode( "\n", $lines );
	}
}
