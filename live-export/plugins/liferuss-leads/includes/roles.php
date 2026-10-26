<?php
/**
 * Support role and user assignment UI.
 *
 * @package LifeRussLeads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the support role.
 */
function liferuss_leads_register_role() {
	add_role(
		'liferuss_support',
		'پشتیبانی لایف روس',
		array(
			'read'                   => true,
			'liferuss_manage_leads'  => true,
		)
	);
}

/**
 * Grant plugin caps to administrators.
 */
function liferuss_leads_grant_admin_caps() {
	$role = get_role( 'administrator' );
	if ( ! $role ) {
		return;
	}
	$role->add_cap( 'liferuss_manage_leads' );
	$role->add_cap( 'liferuss_manage_all_leads' );
	$role->add_cap( 'liferuss_assign_leads' );
}
add_action( 'init', 'liferuss_leads_grant_admin_caps', 1 );

/**
 * User profile: assigned form types.
 *
 * @param WP_User $user User.
 */
function liferuss_leads_user_profile( $user ) {
	if ( ! current_user_can( 'liferuss_assign_leads' ) && ! current_user_can( 'promote_users' ) ) {
		return;
	}
	if ( ! user_can( $user->ID, 'liferuss_manage_leads' ) ) {
		return;
	}
	$assigned = liferuss_leads_user_types( $user->ID );
	?>
	<h2>دسترسی فرم‌های لایف روس</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th>نوع درخواست‌های مجاز</th>
			<td>
				<?php if ( liferuss_leads_user_is_admin( $user->ID ) ) : ?>
					<p>مدیران همهٔ نوع‌ها را می‌بینند.</p>
				<?php else : ?>
					<fieldset>
						<?php foreach ( liferuss_leads_form_types() as $slug => $type ) : ?>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="liferuss_allowed_forms[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $assigned, true ) ); ?>>
								<?php echo esc_html( $type['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description">کاربر پشتیبانی فقط درخواست‌های همین نوع‌ها را در صندوق می‌بیند.</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'liferuss_leads_user_profile' );
add_action( 'edit_user_profile', 'liferuss_leads_user_profile' );

/**
 * Save assigned form types.
 *
 * @param int $user_id User ID.
 */
function liferuss_leads_save_user_profile( $user_id ) {
	if ( ! current_user_can( 'liferuss_assign_leads' ) && ! current_user_can( 'promote_users' ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
		return;
	}
	$raw = isset( $_POST['liferuss_allowed_forms'] ) ? (array) wp_unslash( $_POST['liferuss_allowed_forms'] ) : array();
	$valid = array_keys( liferuss_leads_form_types() );
	$clean = array();
	foreach ( $raw as $slug ) {
		$slug = sanitize_key( $slug );
		if ( in_array( $slug, $valid, true ) ) {
			$clean[] = $slug;
		}
	}
	update_user_meta( $user_id, '_liferuss_allowed_forms', $clean );
}
add_action( 'personal_options_update', 'liferuss_leads_save_user_profile' );
add_action( 'edit_user_profile_update', 'liferuss_leads_save_user_profile' );
