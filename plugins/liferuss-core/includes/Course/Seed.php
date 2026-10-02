<?php
/**
 * A small A1–B2 catalogue so the course has something to study.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once. Editors can change everything afterwards.
 */
class Seed {

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe' ), 30 );
	}

	/**
	 * Insert levels, four courses, and starter lessons.
	 */
	public static function maybe(): void {
		if ( get_option( 'lr_language_seeded' ) ) {
			return;
		}
		$levels = array(
			'a1' => 'A1',
			'a2' => 'A2',
			'b1' => 'B1',
			'b2' => 'B2',
		);
		foreach ( $levels as $slug => $name ) {
			if ( ! term_exists( $slug, 'lr_level' ) ) {
				wp_insert_term( $name, 'lr_level', array( 'slug' => $slug ) );
			}
		}
		$made  = array();
		$order = 1;
		foreach (
			array(
				'a1' => array( 'الفبا و سلام', 'حروف، سلام، و معرفی کوتاه.' ),
				'a2' => array( 'روزمره', 'خرید، ساعت، و مسیر.' ),
				'b1' => array( 'گفتگوی مستقل', 'نظر دادن و تعریف یک ماجرا.' ),
				'b2' => array( 'بحث و متن', 'استدلال و خواندن متن کوتاه.' ),
			) as $level => $info
		) {
			$course = self::post( 'lr_course', $level, $info[0], $info[1], 0, $order );
			if ( $course ) {
				wp_set_object_terms( $course, $level, 'lr_level' );
				$made[ $level ] = $course;
			}
			++$order;
		}
		if ( ! empty( $made['a1'] ) ) {
			$hello = self::post( 'lr_lesson', 'privet', 'سلام و معرفی', 'Привет یعنی سلام. Меня зовут برای گفتن نام است.', $made['a1'], 1 );
			if ( $hello ) {
				update_post_meta( $hello, '_lr_video', 'https://www.youtube.com/watch?v=jNQXAC9IVRw' );
				Store::save_questions(
					$hello,
					array(
						array(
							'type'    => 'choice',
							'prompt'  => 'Привет یعنی چه؟',
							'choices' => array( 'خداحافظ', 'سلام', 'متشکرم' ),
							'answer'  => 1,
						),
						array(
							'type'   => 'match',
							'prompt' => 'معنی را وصل کنید.',
							'pairs'  => array(
								array(
									'left'  => 'да',
									'right' => 'بله',
								),
								array(
									'left'  => 'нет',
									'right' => 'نه',
								),
							),
						),
						array(
							'type'   => 'fill',
							'prompt' => 'سلام به روسی: ____',
							'accept' => 'привет',
						),
					)
				);
			}
			self::post( 'lr_lesson', 'alphabet', 'الفبا', 'الفبای روسی ۳۳ حرف دارد. А و О را بلند بخوانید.', $made['a1'], 2 );
			$placement = self::post( 'lr_lesson', 'placement', 'تعیین سطح', 'این درس در فهرست دوره نیست.', $made['a1'], 99 );
			if ( $placement ) {
				update_post_meta( $placement, '_lr_placement', '1' );
				Store::save_questions(
					$placement,
					array(
						array(
							'type'    => 'choice',
							'prompt'  => 'Привет یعنی…',
							'choices' => array( 'سلام', 'شب بخیر' ),
							'answer'  => 0,
						),
						array(
							'type'    => 'choice',
							'prompt'  => 'کدام یک عدد است؟',
							'choices' => array( 'спасибо', 'два' ),
							'answer'  => 1,
						),
						array(
							'type'   => 'fill',
							'prompt' => '«بله» به روسی: ____',
							'accept' => 'да',
						),
						array(
							'type'   => 'match',
							'prompt' => 'وصل کنید.',
							'pairs'  => array(
								array(
									'left'  => 'вода',
									'right' => 'آب',
								),
							),
						),
					)
				);
			}
		}
		if ( ! empty( $made['a2'] ) ) {
			self::post( 'lr_lesson', 'shop', 'گفتگو در فروشگاه', 'Сколько стоит برای پرسیدن قیمت است.', $made['a2'], 1 );
		}
		if ( ! empty( $made['b1'] ) ) {
			self::post( 'lr_lesson', 'opinion', 'نظر خودم', 'Я думаю, что… برای شروع یک نظر است.', $made['b1'], 1 );
		}
		if ( ! empty( $made['b2'] ) ) {
			self::post( 'lr_lesson', 'argument', 'یک استدلال کوتاه', 'با потому что دلیل بیاورید و یک مثال بزنید.', $made['b2'], 1 );
		}
		update_option( 'lr_language_seeded', '1', false );
	}

	/**
	 * Insert one published post when the slug is free.
	 *
	 * @param string $type   Post type.
	 * @param string $slug   Slug.
	 * @param string $title  Title.
	 * @param string $body   Content.
	 * @param int    $parent_id Parent id.
	 * @param int    $order     Menu order.
	 */
	private static function post( string $type, string $slug, string $title, string $body, int $parent_id, int $order ): int {
		$existing = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
		$id = wp_insert_post(
			array(
				'post_type'    => $type,
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $body,
				'post_status'  => 'publish',
				'post_parent'  => $parent_id,
				'menu_order'   => $order,
			),
			true
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}
}
