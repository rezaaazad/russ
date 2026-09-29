<?php
/**
 * Unlimited university programs: paged inline edit and bulk add.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Table names are prefixed identifiers, not user input.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

/**
 * Sub-screen linked from the university meta box. CSV import is unchanged.
 */
class ProgramsScreen {

	/**
	 * POST handlers.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_lr_programs_save', array( self::class, 'save' ) );
		add_action( 'admin_post_lr_programs_bulk', array( self::class, 'bulk' ) );
	}

	/**
	 * Paged editor.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'به رشته‌های دانشگاه دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$university = isset( $_GET['university'] ) ? absint( $_GET['university'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $university ) {
			self::index();
			return;
		}
		$row = Repository::for( 'universities' )->find( $university );
		if ( ! $row ) {
			wp_die( esc_html__( 'دانشگاه پیدا نشد.', 'liferuss-core' ), '', array( 'response' => 404 ) );
		}
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$fields = self::fields();
		$total  = self::count( $university );
		$pages  = max( 1, (int) ceil( $total / 25 ) );
		$page   = min( $page, $pages );
		$rows   = self::page( $university, $page );
		$back   = $row['post_id'] ? get_edit_post_link( (int) $row['post_id'], 'raw' ) : '';
		echo '<div class="wrap lr-wrap"><h1>' . esc_html( (string) $row['name_fa'] ) . '</h1>';
		echo '<p>' . esc_html( sprintf( '%d رشته', $total ) );
		if ( $back ) {
			echo ' — <a href="' . esc_url( $back ) . '">' . esc_html__( 'بازگشت به دانشگاه', 'liferuss-core' ) . '</a>';
		}
		echo '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_programs_save' );
		echo '<input type="hidden" name="action" value="lr_programs_save">';
		echo '<input type="hidden" name="university" value="' . esc_attr( (string) $university ) . '">';
		echo '<input type="hidden" name="paged" value="' . esc_attr( (string) $page ) . '">';
		echo '<table class="widefat striped"><thead><tr><th>رشته</th><th>مقطع</th><th>زبان</th><th>مدت</th><th>شهریه</th><th>ارز</th><th>سال</th></tr></thead><tbody>';
		foreach ( $rows as $program ) {
			self::row_inputs( (int) $program['id'], $program, $fields );
		}
		self::row_inputs( 0, array(), $fields );
		echo '</tbody></table>';
		submit_button( __( 'ذخیره این صفحه', 'liferuss-core' ) );
		echo '</form>';
		self::pager( $university, $page, $pages );
		echo '<h2>' . esc_html__( 'افزودن گروهی', 'liferuss-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'هر خط: شناسه رشته، مقطع، زبان، مدت، شهریه، ارز، سال. مثال: computer-science,bachelor,ru,4,380000,RUB,2026', 'liferuss-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_programs_bulk' );
		echo '<input type="hidden" name="action" value="lr_programs_bulk">';
		echo '<input type="hidden" name="university" value="' . esc_attr( (string) $university ) . '">';
		echo '<textarea name="bulk" rows="8" class="large-text"></textarea>';
		submit_button( __( 'افزودن', 'liferuss-core' ) );
		echo '</form></div>';
	}

	/**
	 * University picker when the screen is opened from the menu.
	 */
	private static function index(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_universities';
		$rows  = $wpdb->get_results( "SELECT id, name_fa FROM `{$table}` WHERE deleted_at IS NULL ORDER BY name_fa ASC", ARRAY_A );
		echo '<div class="wrap lr-wrap"><h1>' . esc_html__( 'رشته‌های دانشگاه', 'liferuss-core' ) . '</h1><ul>';
		foreach ( (array) $rows as $row ) {
			$url = admin_url( 'admin.php?page=lr-programs&university=' . (int) $row['id'] );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( (string) $row['name_fa'] ) . '</a></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Save the visible page.
	 */
	public static function save(): void {
		check_admin_referer( 'lr_programs_save' );
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'به رشته‌های دانشگاه دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$university = isset( $_POST['university'] ) ? absint( $_POST['university'] ) : 0;
		$page       = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
		$posted     = isset( $_POST['programs'] ) && is_array( $_POST['programs'] ) ? wp_unslash( $_POST['programs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $posted as $item ) {
			if ( is_array( $item ) ) {
				self::write( $university, $item );
			}
		}
		Store::recompute_university( $university );
		Store::bump();
		wp_safe_redirect( admin_url( 'admin.php?page=lr-programs&university=' . $university . '&paged=' . $page ) );
		exit;
	}

	/**
	 * Bulk lines.
	 */
	public static function bulk(): void {
		check_admin_referer( 'lr_programs_bulk' );
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'به رشته‌های دانشگاه دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
		$university = isset( $_POST['university'] ) ? absint( $_POST['university'] ) : 0;
		$raw        = isset( $_POST['bulk'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['bulk'] ) ) : '';
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( ',', $line ) );
			$field = Repository::for( 'fields' )->find_by( 'slug', sanitize_title( (string) ( $parts[0] ?? '' ) ) );
			if ( ! $field ) {
				continue;
			}
			self::write(
				$university,
				array(
					'field_id' => (int) $field['id'],
					'degree'   => (string) ( $parts[1] ?? '' ),
					'language' => (string) ( $parts[2] ?? 'ru' ),
					'duration' => (string) ( $parts[3] ?? '' ),
					'tuition'  => (string) ( $parts[4] ?? '' ),
					'currency' => (string) ( $parts[5] ?? 'RUB' ),
					'year'     => (string) ( $parts[6] ?? '' ),
				)
			);
		}
		Store::recompute_university( $university );
		Store::bump();
		wp_safe_redirect( admin_url( 'admin.php?page=lr-programs&university=' . $university ) );
		exit;
	}

	/**
	 * One editable row. Id 0 is the blank add row.
	 *
	 * @param int                             $id      Row id.
	 * @param array<string, mixed>            $program Current values.
	 * @param array<int, array<string,mixed>> $fields  Field choices.
	 */
	private static function row_inputs( int $id, array $program, array $fields ): void {
		$key = $id ? (string) $id : 'new';
		echo '<tr><td><input type="hidden" name="programs[' . esc_attr( $key ) . '][id]" value="' . esc_attr( (string) $id ) . '">';
		echo '<select name="programs[' . esc_attr( $key ) . '][field_id]"><option value="">—</option>';
		foreach ( $fields as $field ) {
			printf(
				'<option value="%d" %s>%s</option>',
				(int) $field['id'],
				selected( (int) ( $program['field_id'] ?? 0 ), (int) $field['id'], false ),
				esc_html( (string) $field['name_fa'] )
			);
		}
		echo '</select></td><td><select name="programs[' . esc_attr( $key ) . '][degree]"><option value="">—</option>';
		foreach ( Editor::degrees() as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( (string) ( $program['degree'] ?? '' ), $value, false ), esc_html( $label ) );
		}
		echo '</select></td><td><select name="programs[' . esc_attr( $key ) . '][language]">';
		foreach ( Editor::languages() as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( (string) ( $program['language'] ?? 'ru' ), $value, false ), esc_html( $label ) );
		}
		echo '</select></td>';
		echo '<td><input type="number" step="0.1" name="programs[' . esc_attr( $key ) . '][duration]" value="' . esc_attr( (string) ( $program['duration_years'] ?? '' ) ) . '"></td>';
		echo '<td><input type="number" step="0.01" name="programs[' . esc_attr( $key ) . '][tuition]" value="' . esc_attr( (string) ( $program['tuition'] ?? '' ) ) . '"></td>';
		echo '<td><input name="programs[' . esc_attr( $key ) . '][currency]" maxlength="3" value="' . esc_attr( (string) ( $program['currency'] ?? 'RUB' ) ) . '"></td>';
		echo '<td><input name="programs[' . esc_attr( $key ) . '][year]" maxlength="9" value="' . esc_attr( (string) ( $program['academic_year'] ?? '' ) ) . '"></td></tr>';
	}

	/**
	 * Upsert or delete one posted row.
	 *
	 * @param int                  $university University id.
	 * @param array<string, mixed> $item       Posted fields.
	 */
	private static function write( int $university, array $item ): void {
		$field_id = absint( $item['field_id'] ?? 0 );
		$degree   = sanitize_key( (string) ( $item['degree'] ?? '' ) );
		$existing = absint( $item['id'] ?? 0 );
		if ( ! $field_id || ! isset( Editor::degrees()[ $degree ] ) ) {
			if ( $existing ) {
				Repository::for( 'university_fields' )->delete( $existing );
			}
			return;
		}
		$lang = sanitize_key( (string) ( $item['language'] ?? 'ru' ) );
		if ( ! isset( Editor::languages()[ $lang ] ) ) {
			$lang = 'ru';
		}
		$tuition = isset( $item['tuition'] ) && is_numeric( $item['tuition'] ) ? (float) $item['tuition'] : null;
		Store::upsert_program(
			$university,
			$field_id,
			$degree,
			$lang,
			isset( $item['duration'] ) && is_numeric( $item['duration'] ) ? (float) $item['duration'] : 0,
			$tuition,
			strtoupper( sanitize_text_field( (string) ( $item['currency'] ?? 'RUB' ) ) ),
			sanitize_text_field( (string) ( $item['year'] ?? '' ) ),
			false
		);
	}

	/**
	 * Field choices.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function fields(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_fields';
		$rows  = $wpdb->get_results( "SELECT id, name_fa, slug FROM `{$table}` WHERE deleted_at IS NULL ORDER BY name_fa ASC", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Live program count.
	 *
	 * @param int $university University id.
	 */
	private static function count( int $university ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'lr_university_fields';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL", $university ) );
	}

	/**
	 * One page of programs.
	 *
	 * @param int $university University id.
	 * @param int $page       Page number.
	 * @return array<int, array<string, mixed>>
	 */
	private static function page( int $university, int $page ): array {
		global $wpdb;
		$table  = $wpdb->prefix . 'lr_university_fields';
		$offset = ( $page - 1 ) * 25;
		$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE university_id = %d AND deleted_at IS NULL ORDER BY id ASC LIMIT 25 OFFSET %d", $university, $offset ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Page links.
	 *
	 * @param int $university University id.
	 * @param int $page       Current page.
	 * @param int $pages      Page count.
	 */
	private static function pager( int $university, int $page, int $pages ): void {
		if ( $pages < 2 ) {
			return;
		}
		echo '<p class="lr-meta">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			$url = admin_url( 'admin.php?page=lr-programs&university=' . $university . '&paged=' . $i );
			if ( $i === $page ) {
				echo ' <strong>' . esc_html( (string) $i ) . '</strong>';
			} else {
				echo ' <a href="' . esc_url( $url ) . '">' . esc_html( (string) $i ) . '</a>';
			}
		}
		echo '</p>';
	}
}
