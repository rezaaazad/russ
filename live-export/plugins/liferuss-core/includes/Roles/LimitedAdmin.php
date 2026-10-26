<?php
/**
 * Limited-admin behaviour modelled on modiriat-wordpress.
 *
 * The lr_admin role replaces that plugin's restricted manager: the role can run
 * LifeRuss and day-to-day content, but cannot install plugins or themes,
 * open the file editors, change core settings, or edit a Super Admin.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hides and blocks screens that do not belong to a non-administrator role.
 */
class LimitedAdmin {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'admin_menu', array( self::class, 'hide_menus' ), 999 );
		add_action( 'admin_init', array( self::class, 'block_screens' ) );
		add_filter( 'editable_roles', array( self::class, 'editable_roles' ) );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Core screens non-administrators must not open, even by URL.
	 *
	 * @return string[]
	 */
	private static function blocked_pages(): array {
		return array(
			'plugins.php',
			'plugin-install.php',
			'plugin-editor.php',
			'themes.php',
			'theme-install.php',
			'theme-editor.php',
			'customize.php',
			'site-editor.php',
			'update-core.php',
			'tools.php',
			'import.php',
			'export.php',
			'site-health.php',
			'options-general.php',
			'options-writing.php',
			'options-reading.php',
			'options-discussion.php',
			'options-media.php',
			'options-permalink.php',
			'options-privacy.php',
		);
	}

	/**
	 * Remove menus the matrix does not grant.
	 */
	public static function hide_menus(): void {
		if ( Access::is_super() ) {
			return;
		}

		foreach ( self::blocked_pages() as $page ) {
			remove_menu_page( $page );
		}
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'plugins.php', 'plugin-editor.php' );

		remove_menu_page( 'index.php' );
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		if ( ! current_user_can( 'list_users' ) ) {
			remove_menu_page( 'users.php' );
		}
	}

	/**
	 * Stop direct requests to blocked admin files.
	 */
	public static function block_screens(): void {
		if ( Access::is_super() || ! is_admin() ) {
			return;
		}
		global $pagenow;
		if ( 'index.php' === $pagenow ) {
			$target = current_user_can( 'lr_view_dashboard' ) ? 'liferuss' : 'lr-academy';
			if ( current_user_can( 'lr_view_dashboard' ) || current_user_can( 'lr_academy_access' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=' . $target ) );
				exit;
			}
		}
		if ( in_array( $pagenow, self::blocked_pages(), true ) ) {
			wp_die( esc_html__( 'به این بخش از مدیریت دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Managers cannot assign the administrator role.
	 *
	 * @param array<string, array<string, mixed>> $roles Editable roles.
	 * @return array<string, array<string, mixed>>
	 */
	public static function editable_roles( array $roles ): array {
		if ( ! Access::is_super() ) {
			unset( $roles['administrator'] );
		}
		return $roles;
	}

	/**
	 * Block editing Super Admins and keep writers inside their own media.
	 *
	 * @param string[] $caps    Required caps.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id User id.
	 * @param mixed[]  $args    Extra arguments.
	 * @return string[]
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( in_array( $cap, array( 'edit_user', 'delete_user', 'promote_user', 'remove_user' ), true ) ) {
			$target = isset( $args[0] ) ? (int) $args[0] : 0;
			if ( $target && Access::is_super( $target ) && ! Access::is_super( $user_id ) ) {
				return array( 'do_not_allow' );
			}
		}

		if ( in_array( $cap, array( 'edit_post', 'delete_post' ), true ) && ! empty( $args[0] ) ) {
			$post = get_post( (int) $args[0] );
			$user = get_userdata( $user_id );
			if ( $post instanceof \WP_Post && 'attachment' === $post->post_type && $user instanceof \WP_User ) {
				if ( in_array( 'lr_writer', (array) $user->roles, true ) && (int) $post->post_author !== $user_id ) {
					return array( 'do_not_allow' );
				}
			}
		}

		return $caps;
	}
}
