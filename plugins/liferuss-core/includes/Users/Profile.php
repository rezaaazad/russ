<?php
/**
 * User meta from the ERD: services, soft-delete, last login.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Users;

use LifeRuss\Core\Roles\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile fields and login bookkeeping.
 */
class Profile {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'show_user_profile', array( self::class, 'render' ) );
		add_action( 'edit_user_profile', array( self::class, 'render' ) );
		add_action( 'personal_options_update', array( self::class, 'save' ) );
		add_action( 'edit_user_profile_update', array( self::class, 'save' ) );
		add_action( 'set_user_role', array( self::class, 'default_services' ), 10, 2 );
		add_action( 'wp_login', array( self::class, 'touch_login' ), 10, 2 );
		add_filter( 'wp_authenticate_user', array( self::class, 'block_soft_deleted' ), 10, 1 );
		add_action( 'delete_user', array( self::class, 'reassign_references' ), 10, 2 );
	}

	/**
	 * Profile fields.
	 *
	 * @param \WP_User $user User being edited.
	 */
	public static function render( \WP_User $user ): void {
		if ( ! current_user_can( 'list_users' ) && get_current_user_id() !== (int) $user->ID ) {
			return;
		}
		$services = get_user_meta( $user->ID, 'lr_allowed_services', true );
		if ( ! is_array( $services ) ) {
			$services = array();
		}
		$groups   = array(
			'education' => 'تحصیل',
			'language'  => 'زبان',
			'migration' => 'مهاجرت',
			'exchange'  => 'صرافی',
			'cargo'     => 'کارگو',
			'trade'     => 'تجارت',
			'general'   => 'عمومی',
		);
		$deleted  = (string) get_user_meta( $user->ID, 'lr_deleted_at', true );
		$can_edit = current_user_can( 'promote_users' ) || Access::is_super();
		wp_nonce_field( 'lr_user_meta_' . $user->ID, 'lr_user_meta_nonce' );
		echo '<h2>' . esc_html__( 'لایف‌روس', 'liferuss-core' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tr><th>' . esc_html__( 'سرویس‌های مجاز', 'liferuss-core' ) . '</th><td>';
		foreach ( $groups as $slug => $label ) {
			echo '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="lr_allowed_services[]" value="' . esc_attr( $slug ) . '" ' . checked( in_array( $slug, $services, true ), true, false ) . ' ' . disabled( ! $can_edit, true, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		echo '<p class="description">' . esc_html__( 'اپراتور فقط در همین سرویس‌ها قابل ارجاع است (lr_allowed_services).', 'liferuss-core' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'تلفن / تلگرام', 'liferuss-core' ) . '</th><td>';
		echo '<input type="text" name="lr_phone" value="' . esc_attr( (string) get_user_meta( $user->ID, 'lr_phone', true ) ) . '" class="regular-text" ' . disabled( ! $can_edit && get_current_user_id() !== (int) $user->ID, true, false ) . '> ';
		echo '<input type="text" name="lr_telegram" value="' . esc_attr( (string) get_user_meta( $user->ID, 'lr_telegram', true ) ) . '" class="regular-text" ' . disabled( ! $can_edit && get_current_user_id() !== (int) $user->ID, true, false ) . '>';
		echo '</td></tr>';
		if ( $can_edit && ! Access::is_super( (int) $user->ID ) ) {
			echo '<tr><th>' . esc_html__( 'حذف نرم', 'liferuss-core' ) . '</th><td><label><input type="checkbox" name="lr_soft_delete" value="1" ' . checked( '' !== $deleted, true, false ) . '> ' . esc_html__( 'ورود این کاربر مسدود شود و lr_deleted_at ثبت شود.', 'liferuss-core' ) . '</label></td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Save profile fields.
	 *
	 * @param int $user_id User id.
	 */
	public static function save( int $user_id ): void {
		if ( ! isset( $_POST['lr_user_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lr_user_meta_nonce'] ) ), 'lr_user_meta_' . $user_id ) ) {
			return;
		}
		$can_edit = current_user_can( 'promote_users' ) || Access::is_super();
		if ( ! $can_edit && get_current_user_id() !== $user_id ) {
			return;
		}
		if ( $can_edit ) {
			$raw  = isset( $_POST['lr_allowed_services'] ) ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['lr_allowed_services'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$keep = array();
			foreach ( $raw as $slug ) {
				$slug = sanitize_key( (string) $slug );
				if ( $slug ) {
					$keep[] = $slug;
				}
			}
			update_user_meta( $user_id, 'lr_allowed_services', $keep );
			if ( ! Access::is_super( $user_id ) ) {
				if ( ! empty( $_POST['lr_soft_delete'] ) ) {
					if ( ! get_user_meta( $user_id, 'lr_deleted_at', true ) ) {
						update_user_meta( $user_id, 'lr_deleted_at', gmdate( 'Y-m-d H:i:s' ) );
					}
				} else {
					delete_user_meta( $user_id, 'lr_deleted_at' );
				}
			}
		}
		if ( $can_edit || get_current_user_id() === $user_id ) {
			update_user_meta( $user_id, 'lr_phone', sanitize_text_field( wp_unslash( $_POST['lr_phone'] ?? '' ) ) );
			update_user_meta( $user_id, 'lr_telegram', sanitize_text_field( wp_unslash( $_POST['lr_telegram'] ?? '' ) ) );
		}
	}

	/**
	 * Fill lr_allowed_services the first time a scoped role is assigned.
	 *
	 * @param int    $user_id User id.
	 * @param string $role    New role.
	 */
	public static function default_services( int $user_id, string $role ): void {
		$existing = get_user_meta( $user_id, 'lr_allowed_services', true );
		if ( is_array( $existing ) && $existing ) {
			return;
		}
		$defaults = Access::default_services_for_role( $role );
		if ( $defaults ) {
			update_user_meta( $user_id, 'lr_allowed_services', $defaults );
		}
	}

	/**
	 * Store lr_last_login_at in UTC.
	 *
	 * @param string   $login Login.
	 * @param \WP_User $user  User.
	 */
	public static function touch_login( string $login, \WP_User $user ): void {
		unset( $login );
		update_user_meta( $user->ID, 'lr_last_login_at', gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Reject soft-deleted accounts.
	 *
	 * @param \WP_User|\WP_Error $user User.
	 * @return \WP_User|\WP_Error
	 */
	public static function block_soft_deleted( $user ) {
		if ( $user instanceof \WP_User && get_user_meta( $user->ID, 'lr_deleted_at', true ) ) {
			return new \WP_Error( 'lr_disabled', __( 'این حساب غیرفعال شده است.', 'liferuss-core' ) );
		}
		return $user;
	}

	/**
	 * Null nullable user references and reassign required ones.
	 *
	 * Audit logs keep the original user id on purpose.
	 *
	 * @param int      $user_id  Deleted user.
	 * @param int|null $reassign User that should take ownership.
	 */
	public static function reassign_references( int $user_id, $reassign ): void {
		global $wpdb;

		$fallback = $reassign ? (int) $reassign : self::fallback_admin( $user_id );
		$nulls    = array(
			'redirects'            => array( 'created_by' ),
			'translations'         => array( 'translated_by' ),
			'cities'               => array( 'verified_by' ),
			'tuition_fees'         => array( 'verified_by' ),
			'dormitory_fees'       => array( 'verified_by' ),
			'university_approvals' => array( 'verified_by' ),
			'prep_programs'        => array( 'verified_by' ),
			'leads'                => array( 'consultant_id', 'assigned_by' ),
			'lead_tasks'           => array( 'created_by' ),
			'lead_files'           => array( 'uploaded_by' ),
			'lead_status_history'  => array( 'changed_by' ),
			'exchange_requests'    => array( 'operator_id' ),
			'cargo_requests'       => array( 'operator_id' ),
			'trade_requests'       => array( 'operator_id' ),
		);
		foreach ( $nulls as $suffix => $columns ) {
			$table = $wpdb->prefix . 'lr_' . $suffix;
			foreach ( $columns as $column ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column} = NULL WHERE {$column} = %d", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}
		if ( $fallback ) {
			$required = array(
				'lead_notes' => array( 'user_id' ),
				'lead_tasks' => array( 'assigned_to' ),
			);
			foreach ( $required as $suffix => $columns ) {
				$table = $wpdb->prefix . 'lr_' . $suffix;
				foreach ( $columns as $column ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column} = %d WHERE {$column} = %d", $fallback, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
			}
		}
	}

	/**
	 * Another administrator to keep NOT NULL user columns valid.
	 *
	 * @param int $except User id being deleted.
	 */
	private static function fallback_admin( int $except ): int {
		$users = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'exclude' => array( $except ),
				'fields'  => 'ID',
			)
		);
		return $users ? (int) $users[0] : 0;
	}
}
