<?php
/**
 * Capability helpers.
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current user can see every form type.
 *
 * @param int $user_id User ID, 0 = current.
 * @return bool
 */
function liferuss_leads_user_is_admin( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	return user_can( $user_id, 'liferuss_manage_all_leads' ) || user_can( $user_id, 'manage_options' );
}

/**
 * Form type slugs assigned to a support user. Empty = none.
 *
 * @param int $user_id User ID.
 * @return array<int, string>
 */
function liferuss_leads_user_types( $user_id ) {
	$saved = get_user_meta( $user_id, '_liferuss_allowed_forms', true );
	if ( ! is_array( $saved ) ) {
		return array();
	}
	$valid = array_keys( liferuss_leads_form_types() );
	return array_values( array_intersect( array_map( 'sanitize_key', $saved ), $valid ) );
}

/**
 * Types the user may view.
 *
 * @param int $user_id User ID, 0 = current.
 * @return array<int, string>|null Null means all types.
 */
function liferuss_leads_visible_types( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( liferuss_leads_user_is_admin( $user_id ) ) {
		return null;
	}
	return liferuss_leads_user_types( $user_id );
}

/**
 * Whether the user can open a lead.
 *
 * @param int $post_id Lead ID.
 * @param int $user_id User ID, 0 = current.
 * @return bool
 */
function liferuss_leads_user_can_access( $post_id, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! user_can( $user_id, 'liferuss_manage_leads' ) && ! liferuss_leads_user_is_admin( $user_id ) ) {
		return false;
	}
	$allowed = liferuss_leads_visible_types( $user_id );
	if ( null === $allowed ) {
		return true;
	}
	return in_array( liferuss_leads_post_type_slug( $post_id ), $allowed, true );
}

/**
 * Users who can be assignees.
 *
 * @return array<int, WP_User>
 */
function liferuss_leads_staff_users() {
	return get_users(
		array(
			'capability' => 'liferuss_manage_leads',
			'orderby'    => 'display_name',
			'order'      => 'ASC',
		)
	);
}
