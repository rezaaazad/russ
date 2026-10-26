<?php
/**
 * Generic contact form (plugin type: contact).
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status    = isset( $_GET['consult'] ) ? sanitize_text_field( wp_unslash( $_GET['consult'] ) ) : '';
$message   = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
?>
<section class="section consult-section" id="consultation">
	<div class="container consult-wrap">
		<div class="consult-copy">
			<p class="eyebrow"><?php echo esc_html( liferuss_t( 'contact_page_eye' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_t( 'contact_form_title' ) ); ?></h2>
			<p><?php echo esc_html( liferuss_t( 'contact_form_intro' ) ); ?></p>
			<?php liferuss_the_contact_channels(); ?>
		</div>

		<div class="consult-card">
			<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite">
				<?php echo $message ? esc_html( $message ) : ''; ?>
			</div>
			<form class="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<input type="hidden" name="action" value="liferuss_consult">
				<input type="hidden" name="consult_type" value="contact">
				<input type="hidden" name="consult_lang" value="<?php echo esc_attr( liferuss_current_lang() ); ?>">
				<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
				<div class="hp" aria-hidden="true">
					<label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label>
				</div>
				<?php liferuss_form_tracking_fields(); ?>
				<label>
					<span><?php echo esc_html( liferuss_opt( 'form_name_label' ) ); ?></span>
					<input type="text" name="consult_name" required autocomplete="name" placeholder="<?php echo esc_attr( liferuss_opt( 'form_name_ph' ) ); ?>">
				</label>
				<label>
					<span><?php echo esc_html( liferuss_opt( 'form_phone_label' ) ); ?></span>
					<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" dir="ltr" maxlength="16" pattern="^(\+?98|0)?9\d{9}$|^\+?\d{8,15}$" placeholder="<?php echo esc_attr( liferuss_opt( 'form_phone_ph' ) ); ?>">
				</label>
				<label>
					<span><?php echo esc_html( liferuss_t( 'contact_message_label' ) ); ?></span>
					<textarea name="consult_message" rows="5" placeholder="<?php echo esc_attr( liferuss_t( 'contact_message_ph' ) ); ?>"></textarea>
				</label>
				<button class="btn btn-gold btn-block" type="submit">
					<?php echo esc_html( liferuss_t( 'contact_send' ) ); ?>
					<?php echo liferuss_icon( 'arrow' ); ?>
				</button>
				<?php liferuss_the_form_consent( liferuss_opt( 'form_note' ) ); ?>
			</form>
		</div>
	</div>
</section>
