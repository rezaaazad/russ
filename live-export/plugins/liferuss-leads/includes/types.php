<?php
/**
 * Form type registry.
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Built-in form types. Filter `liferuss_leads_form_types` to add more.
 *
 * @return array<string, array<string, mixed>>
 */
function liferuss_leads_form_types() {
	$types = array(
		'consult' => array(
			'label'           => 'مشاوره تحصیل',
			'requires_level'  => true,
			'allows_file'     => false,
			'success_option'  => 'form_success',
		),
		'freight' => array(
			'label'           => 'باربری و ارسال',
			'requires_level'  => false,
			'allows_file'     => true,
			'success_option'  => 'freight_form_success',
		),
		'trade'   => array(
			'label'           => 'تجارت و تأمین کالا',
			'requires_level'  => false,
			'allows_file'     => true,
			'success_option'  => 'trade_form_success',
		),
		'contact' => array(
			'label'           => 'تماس',
			'requires_level'  => false,
			'allows_file'     => false,
			'success_option'  => 'contact_form_success',
		),
	);

	return apply_filters( 'liferuss_leads_form_types', $types );
}

/**
 * Default plugin settings.
 *
 * @return array<string, mixed>
 */
function liferuss_leads_default_settings() {
	$enabled = array();
	foreach ( array_keys( liferuss_leads_form_types() ) as $slug ) {
		$enabled[ $slug ] = '1';
	}
	return array(
		'notify_email' => '',
		'enabled'      => $enabled,
	);
}

/**
 * Get plugin settings merged with defaults.
 *
 * @return array<string, mixed>
 */
function liferuss_leads_settings() {
	$saved = get_option( 'liferuss_leads_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( liferuss_leads_default_settings(), $saved );
}

/**
 * Whether a form type is enabled.
 *
 * @param string $slug Type slug.
 * @return bool
 */
function liferuss_leads_type_enabled( $slug ) {
	$settings = liferuss_leads_settings();
	$enabled  = isset( $settings['enabled'] ) && is_array( $settings['enabled'] ) ? $settings['enabled'] : array();
	if ( ! array_key_exists( $slug, $enabled ) ) {
		return true;
	}
	return '1' === (string) $enabled[ $slug ];
}

/**
 * Label for a type slug.
 *
 * @param string $slug Slug.
 * @return string
 */
function liferuss_leads_type_label( $slug ) {
	$types = liferuss_leads_form_types();
	if ( isset( $types[ $slug ]['label'] ) ) {
		return (string) $types[ $slug ]['label'];
	}
	return $slug;
}

/**
 * Infer type slug from stored Persian label (legacy theme leads).
 *
 * @param string $label Label.
 * @return string
 */
function liferuss_leads_slug_from_label( $label ) {
	foreach ( liferuss_leads_form_types() as $slug => $type ) {
		if ( isset( $type['label'] ) && (string) $type['label'] === (string) $label ) {
			return $slug;
		}
	}
	return 'consult';
}

/**
 * Type slug for a lead post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function liferuss_leads_post_type_slug( $post_id ) {
	$slug = get_post_meta( $post_id, '_liferuss_type_slug', true );
	if ( $slug ) {
		return sanitize_key( $slug );
	}
	return liferuss_leads_slug_from_label( (string) get_post_meta( $post_id, '_liferuss_type', true ) );
}

/**
 * Status labels.
 *
 * @return array<string, string>
 */
function liferuss_leads_statuses() {
	return array(
		'new'         => 'جدید',
		'in_progress' => 'در حال پیگیری',
		'done'        => 'انجام شد',
	);
}

/**
 * Status for a lead.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function liferuss_leads_post_status_key( $post_id ) {
	$status = get_post_meta( $post_id, '_liferuss_status', true );
	return isset( liferuss_leads_statuses()[ $status ] ) ? $status : 'new';
}
