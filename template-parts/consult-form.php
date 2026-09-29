<?php
/**
 * Shared consultation form.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whatsapp  = liferuss_opt( 'whatsapp' );
$telegram  = liferuss_social_url( liferuss_opt( 'telegram' ), 'telegram' );
$instagram = liferuss_social_url( liferuss_opt( 'instagram' ), 'instagram' );
$phone     = liferuss_opt( 'phone' );
$status    = isset( $_GET['consult'] ) ? sanitize_text_field( wp_unslash( $_GET['consult'] ) ) : '';
$message   = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : '';
$heading   = 'h2';
$form      = isset( $GLOBALS['liferuss_form'] ) && is_array( $GLOBALS['liferuss_form'] ) ? $GLOBALS['liferuss_form'] : array();
$type      = isset( $form['type'] ) ? (string) $form['type'] : 'consult';
if ( ! in_array( $type, array( 'consult', 'admission', 'exchange', 'immigration' ), true ) ) {
	$type = 'consult';
}
$program     = isset( $form['program'] ) ? (string) $form['program'] : '';
$degree      = isset( $form['degree'] ) ? (string) $form['degree'] : '';
$level_value = isset( $form['level'] ) ? (string) $form['level'] : '';
$scholarship = isset( $form['scholarship'] ) ? (string) $form['scholarship'] : '';
$needs_level = in_array( $type, array( 'consult', 'admission' ), true );
$form_title  = liferuss_opt( 'form_title' );
$form_intro  = liferuss_opt( 'form_intro' );
if ( 'admission' === $type ) {
	$form_title = liferuss_t( 'form_admission_title' );
	$form_intro = liferuss_t( 'form_admission_intro' );
} elseif ( 'exchange' === $type ) {
	$form_title = liferuss_t( 'form_exchange_title' );
	$form_intro = liferuss_t( 'form_exchange_intro' );
} elseif ( 'immigration' === $type ) {
	$form_title = liferuss_t( 'form_immigration_title' );
	$form_intro = liferuss_t( 'form_immigration_intro' );
}
?>
<section class="section consult-section" id="consultation">
	<div class="container consult-wrap">
		<div class="consult-copy">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'form_eyebrow' ) ); ?></p>
			<<?php echo $heading; ?>><?php echo esc_html( $form_title ); ?></<?php echo $heading; ?>>
			<p><?php echo esc_html( $form_intro ); ?></p>
			<?php if ( 'exchange' === $type && function_exists( 'liferuss_fx_panel' ) ) : ?>
				<?php liferuss_fx_panel(); ?>
			<?php endif; ?>
			<div class="consult-channels">
				<p class="screen-reader-text"><?php echo esc_html( liferuss_t( 'channels_aria' ) ); ?></p>
				<?php if ( $whatsapp ) : ?>
					<a class="channel" href="<?php echo esc_url( liferuss_whatsapp_url( $whatsapp ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo liferuss_icon( 'whatsapp' ); ?>
						<span><?php echo esc_html( liferuss_t( 'channel_whatsapp' ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $telegram ) : ?>
					<a class="channel" href="<?php echo esc_url( $telegram ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo liferuss_icon( 'telegram' ); ?>
						<span><?php echo esc_html( liferuss_t( 'channel_telegram' ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $instagram ) : ?>
					<a class="channel" href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo liferuss_icon( 'instagram' ); ?>
						<span><?php echo esc_html( liferuss_t( 'channel_instagram' ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $phone ) : ?>
					<a class="channel" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
						<?php echo liferuss_icon( 'phone' ); ?>
						<span><?php echo esc_html( liferuss_t( 'channel_phone' ) ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="consult-card">
			<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite">
				<?php echo $message ? esc_html( $message ) : ''; ?>
			</div>
			<form class="consult-form" id="consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<input type="hidden" name="action" value="liferuss_consult">
				<input type="hidden" name="consult_type" value="<?php echo esc_attr( $type ); ?>">
				<?php if ( in_array( $program, array( 'degree', 'padfak', 'direct_course', 'scholarship' ), true ) ) : ?>
					<input type="hidden" name="consult_program" value="<?php echo esc_attr( $program ); ?>">
				<?php endif; ?>
				<?php if ( in_array( $degree, array( 'bachelor', 'specialist', 'master', 'phd', 'residency' ), true ) ) : ?>
					<input type="hidden" name="consult_degree" value="<?php echo esc_attr( $degree ); ?>">
				<?php endif; ?>
				<?php if ( $scholarship ) : ?>
					<input type="hidden" name="consult_scholarship" value="<?php echo esc_attr( $scholarship ); ?>">
				<?php endif; ?>
				<?php if ( ! empty( $GLOBALS['liferuss_consult_university'] ) ) : ?>
					<input type="hidden" name="consult_university" value="<?php echo esc_attr( $GLOBALS['liferuss_consult_university'] ); ?>">
				<?php endif; ?>
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
					<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" placeholder="<?php echo esc_attr( liferuss_opt( 'form_phone_ph' ) ); ?>">
				</label>
				<?php if ( $needs_level ) : ?>
				<label>
					<span><?php echo esc_html( liferuss_opt( 'form_level_label' ) ); ?></span>
					<select name="consult_level" required>
						<?php foreach ( liferuss_study_levels() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $level_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php endif; ?>
				<?php if ( 'exchange' === $type ) : ?>
				<label>
					<span><?php echo esc_html( liferuss_t( 'form_amount' ) ); ?></span>
					<input type="number" name="consult_amount" required min="1" step="1" inputmode="decimal">
				</label>
				<label>
					<span><?php echo esc_html( liferuss_t( 'form_currency_from' ) ); ?></span>
					<select name="consult_currency_from">
						<option value="IRR" selected>IRR</option>
						<option value="RUB">RUB</option>
						<option value="USD">USD</option>
						<option value="EUR">EUR</option>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( liferuss_t( 'form_currency_to' ) ); ?></span>
					<select name="consult_currency_to">
						<option value="RUB" selected>RUB</option>
						<option value="IRR">IRR</option>
						<option value="USD">USD</option>
						<option value="EUR">EUR</option>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( liferuss_t( 'form_notes' ) ); ?></span>
					<textarea name="consult_notes" rows="3"></textarea>
				</label>
				<?php endif; ?>
				<?php if ( 'immigration' === $type ) : ?>
				<label>
					<span><?php echo esc_html( liferuss_t( 'form_notes' ) ); ?></span>
					<textarea name="consult_message" rows="3"></textarea>
				</label>
				<?php endif; ?>
				<button class="btn btn-gold btn-block" type="submit">
					<?php echo esc_html( liferuss_opt( 'form_submit' ) ); ?>
					<?php echo liferuss_icon( 'arrow' ); ?>
				</button>
				<p class="form-note"><?php echo esc_html( liferuss_opt( 'form_note' ) ); ?></p>
			</form>
		</div>

		<figure class="consult-photo">
			<?php
			liferuss_the_image(
				array(
					'id'       => liferuss_opt( 'form_image_id' ),
					'fallback' => 'consult-student.jpg',
					'alt'      => liferuss_t( 'form_alt' ),
					'width'    => 720,
					'height'   => 480,
					'size'     => 'medium_large',
					'sizes'    => '(max-width: 860px) 92vw, 520px',
				)
			);
			?>
		</figure>
	</div>
</section>
