<?php
/**
 * Register content and strings with Polylang when it is active.
 *
 * Catalog rows in lr_* stay language-neutral. Translated copy lives on posts.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\I18n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Polylang bridges.
 */
class Polylang {

	/**
	 * Hooks. Filters are ignored when Polylang is absent.
	 */
	public static function hooks(): void {
		add_filter( 'pll_get_post_types', array( self::class, 'types' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( self::class, 'taxes' ), 10, 2 );
		add_action( 'init', array( self::class, 'strings' ), 20 );
		add_filter( 'pll_rel_hreflang_attributes', array( self::class, 'hreflang' ) );
		add_action( 'add_meta_boxes', array( self::class, 'box' ) );
		add_action( 'save_post', array( self::class, 'save' ), 10, 2 );
	}

	/**
	 * Whether Polylang is loaded.
	 */
	public static function active(): bool {
		return function_exists( 'pll_current_language' );
	}

	/**
	 * Public content types that can be translated.
	 *
	 * @param array<string, string> $types    Types.
	 * @param bool                  $settings Settings screen.
	 * @return array<string, string>
	 */
	public static function types( $types, $settings ): array {
		unset( $settings );
		$types = is_array( $types ) ? $types : array();
		foreach ( array( 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_course', 'lr_lesson', 'lr_scholarship' ) as $type ) {
			$types[ $type ] = $type;
		}
		return $types;
	}

	/**
	 * Taxonomies that can be translated.
	 *
	 * @param array<string, string> $taxes    Taxonomies.
	 * @param bool                  $settings Settings screen.
	 * @return array<string, string>
	 */
	public static function taxes( $taxes, $settings ): array {
		unset( $settings );
		$taxes = is_array( $taxes ) ? $taxes : array();
		foreach ( array( 'lr_field_group', 'lr_guide_cat', 'lr_faq_group', 'lr_level', 'category', 'post_tag' ) as $tax ) {
			$taxes[ $tax ] = $tax;
		}
		return $taxes;
	}

	/**
	 * Theme UI strings and the shared form labels.
	 */
	public static function strings(): void {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		if ( function_exists( 'liferuss_ui_strings' ) ) {
			$fa = liferuss_ui_strings()['fa'] ?? array();
			foreach ( $fa as $key => $value ) {
				if ( is_string( $value ) && '' !== $value ) {
					pll_register_string( 'liferuss_' . $key, $value, 'liferuss', false );
				}
			}
		}
		pll_register_string( 'liferuss_form_success', 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.', 'liferuss', false );
		pll_register_string( 'liferuss_menu_primary', 'منوی اصلی', 'liferuss', false );
		pll_register_string( 'liferuss_menu_footer', 'منوی فوتر', 'liferuss', false );
	}

	/**
	 * Hreflang only toward translations marked complete. Persian stays.
	 *
	 * @param array<string, string> $langs Language code => URL.
	 * @return array<string, string>
	 */
	public static function hreflang( $langs ): array {
		if ( ! is_array( $langs ) ) {
			return array();
		}
		if ( function_exists( 'liferuss_index_incomplete' ) && liferuss_index_incomplete() ) {
			return $langs;
		}
		$post_id = get_queried_object_id();
		foreach ( $langs as $code => $url ) {
			if ( 'fa' === $code ) {
				continue;
			}
			if ( ! function_exists( 'liferuss_translation_complete' ) || ! liferuss_translation_complete( (string) $code, $post_id ) ) {
				unset( $langs[ $code ] );
			}
		}
		return $langs;
	}

	/**
	 * Checkbox: this translation is complete enough to index.
	 */
	public static function box(): void {
		$screens = array( 'post', 'page', 'lr_university', 'lr_field', 'lr_city', 'lr_guide', 'lr_course', 'lr_lesson', 'lr_scholarship' );
		foreach ( $screens as $screen ) {
			add_meta_box( 'lr_lang_ready', 'ترجمه برای نمایه', array( self::class, 'render' ), $screen, 'side' );
		}
	}

	/**
	 * Meta box body.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'lr_lang_ready', 'lr_lang_nonce' );
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang = (string) pll_get_post_language( $post->ID, 'slug' );
			if ( '' === $lang || 'fa' === $lang ) {
				echo '<p>فارسی زبان پیش‌فرض است و نمایه می‌شود.</p>';
				return;
			}
			$on = '1' === (string) get_post_meta( $post->ID, '_lr_translation_complete', true );
			echo '<label><input type="checkbox" name="lr_translation_complete" value="1" ' . checked( $on, true, false ) . '> این ترجمه کامل است</label>';
			return;
		}
		$ready = array_filter( array_map( 'sanitize_key', explode( ',', (string) get_post_meta( $post->ID, '_lr_lang_ready', true ) ) ) );
		foreach (
			array(
				'en' => 'انگلیسی',
				'ru' => 'روسی',
				'ar' => 'عربی',
			) as $code => $label
		) {
			echo '<label><input type="checkbox" name="lr_lang_ready[]" value="' . esc_attr( $code ) . '" ' . checked( in_array( $code, $ready, true ), true, false ) . '> ' . esc_html( $label ) . '</label><br>';
		}
		echo '<p class="description">تا وقتی علامت نخورد، آن زبان noindex می‌ماند.</p>';
	}

	/**
	 * Save the completeness flag. Programmatic inserts have no nonce.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		unset( $post );
		if ( ! isset( $_POST['lr_lang_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_lang_nonce'] ) ), 'lr_lang_ready' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( function_exists( 'pll_get_post_language' ) ) {
			$on = empty( $_POST['lr_translation_complete'] ) ? '' : '1';
			if ( $on ) {
				update_post_meta( $post_id, '_lr_translation_complete', '1' );
			} else {
				delete_post_meta( $post_id, '_lr_translation_complete' );
			}
			return;
		}
		$codes  = array();
		$posted = isset( $_POST['lr_lang_ready'] ) ? map_deep( wp_unslash( $_POST['lr_lang_ready'] ), 'sanitize_text_field' ) : array();
		if ( is_array( $posted ) ) {
			foreach ( $posted as $code ) {
				$code = sanitize_key( (string) $code );
				if ( in_array( $code, array( 'en', 'ru', 'ar' ), true ) ) {
					$codes[] = $code;
				}
			}
		}
		if ( $codes ) {
			update_post_meta( $post_id, '_lr_lang_ready', implode( ',', $codes ) );
		} else {
			delete_post_meta( $post_id, '_lr_lang_ready' );
		}
	}
}
