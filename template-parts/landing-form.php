<?php
/**
 * Shared freight / trade request form (mirrors consult-form patterns).
 *
 * @package LifeRuss
 *
 * @var array $args { prefix: freight|trade }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prefix = isset( $args['prefix'] ) ? sanitize_key( $args['prefix'] ) : 'freight';
if ( ! in_array( $prefix, array( 'freight', 'trade' ), true ) ) {
	$prefix = 'freight';
}

$status  = isset( $_GET['consult'] ) ? sanitize_text_field( wp_unslash( $_GET['consult'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$message = isset( $_GET['consult_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['consult_msg'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$choices_key = 'freight' === $prefix ? 'freight_form_types' : 'trade_form_categories';
$choices     = (array) liferuss_opt( $choices_key, array() );
?>
<section class="section consult-section landing-form-section" id="consultation">
	<div class="container landing-form-wrap">
		<div class="consult-card landing-form-card">
			<h2><?php echo esc_html( liferuss_opt( $prefix . '_form_title' ) ); ?></h2>
			<?php if ( liferuss_opt( $prefix . '_form_intro' ) ) : ?>
				<p class="landing-form-intro"><?php echo esc_html( liferuss_opt( $prefix . '_form_intro' ) ); ?></p>
			<?php endif; ?>
			<div class="form-status<?php echo $status ? ' is-visible is-' . esc_attr( $status ) : ''; ?>" role="status" aria-live="polite">
				<?php echo $message ? esc_html( $message ) : ''; ?>
			</div>
			<form class="consult-form landing-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="liferuss_consult">
				<input type="hidden" name="consult_type" value="<?php echo esc_attr( $prefix ); ?>">
				<input type="hidden" name="consult_lang" value="<?php echo esc_attr( liferuss_current_lang() ); ?>">
				<input type="hidden" name="liferuss_nonce" value="<?php echo esc_attr( wp_create_nonce( 'liferuss_consult' ) ); ?>">
				<div class="hp" aria-hidden="true">
					<label>شرکت<input type="text" name="liferuss_company" tabindex="-1" autocomplete="off"></label>
				</div>

				<div class="landing-form-grid">
					<label>
						<span><?php echo esc_html( liferuss_opt( $prefix . '_form_name_label' ) ); ?></span>
						<input type="text" name="consult_name" required autocomplete="name" placeholder="<?php echo esc_attr( liferuss_opt( $prefix . '_form_name_ph' ) ); ?>">
					</label>
					<label>
						<span><?php echo esc_html( liferuss_opt( $prefix . '_form_phone_label' ) ); ?></span>
						<input type="tel" name="consult_phone" required autocomplete="tel" inputmode="tel" placeholder="<?php echo esc_attr( liferuss_opt( $prefix . '_form_phone_ph' ) ); ?>">
					</label>

					<?php if ( 'freight' === $prefix ) : ?>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_origin_label' ) ); ?></span>
							<input type="text" name="consult_origin" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_origin_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_dest_label' ) ); ?></span>
							<input type="text" name="consult_dest" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_dest_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_type_label' ) ); ?></span>
							<select name="consult_cargo_type">
								<?php foreach ( $choices as $choice ) : ?>
									<option value="<?php echo esc_attr( $choice['value'] ?? '' ); ?>"><?php echo esc_html( $choice['label'] ?? '' ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_weight_label' ) ); ?></span>
							<input type="text" name="consult_weight" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_weight_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_dims_label' ) ); ?></span>
							<input type="text" name="consult_dims" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_dims_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'freight_form_packages_label' ) ); ?></span>
							<input type="text" name="consult_packages" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_packages_ph' ) ); ?>">
						</label>
						<label class="landing-span-2">
							<span><?php echo esc_html( liferuss_opt( 'freight_form_value_label' ) ); ?></span>
							<input type="text" name="consult_value" placeholder="<?php echo esc_attr( liferuss_opt( 'freight_form_value_ph' ) ); ?>">
						</label>
					<?php else : ?>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_product_label' ) ); ?></span>
							<input type="text" name="consult_product" placeholder="<?php echo esc_attr( liferuss_opt( 'trade_form_product_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_category_label' ) ); ?></span>
							<select name="consult_category">
								<?php foreach ( $choices as $choice ) : ?>
									<option value="<?php echo esc_attr( $choice['value'] ?? '' ); ?>"><?php echo esc_html( $choice['label'] ?? '' ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_origin_label' ) ); ?></span>
							<input type="text" name="consult_origin" placeholder="<?php echo esc_attr( liferuss_opt( 'trade_form_origin_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_dest_label' ) ); ?></span>
							<input type="text" name="consult_dest" placeholder="<?php echo esc_attr( liferuss_opt( 'trade_form_dest_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_qty_label' ) ); ?></span>
							<input type="text" name="consult_qty" placeholder="<?php echo esc_attr( liferuss_opt( 'trade_form_qty_ph' ) ); ?>">
						</label>
						<label>
							<span><?php echo esc_html( liferuss_opt( 'trade_form_specs_label' ) ); ?></span>
							<input type="text" name="consult_specs" placeholder="<?php echo esc_attr( liferuss_opt( 'trade_form_specs_ph' ) ); ?>">
						</label>
					<?php endif; ?>

					<label class="landing-span-2">
						<span><?php echo esc_html( liferuss_opt( $prefix . '_form_notes_label' ) ); ?></span>
						<textarea name="consult_notes" rows="3" placeholder="<?php echo esc_attr( liferuss_opt( $prefix . '_form_notes_ph' ) ); ?>"></textarea>
					</label>
					<label class="landing-span-2">
						<span><?php echo esc_html( liferuss_opt( $prefix . '_form_file_label' ) ); ?></span>
						<input type="file" name="consult_file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
						<small class="form-note"><?php echo esc_html( liferuss_opt( $prefix . '_form_file_note' ) ); ?></small>
					</label>
				</div>

				<button class="btn btn-gold btn-block" type="submit">
					<?php echo esc_html( liferuss_opt( $prefix . '_form_submit' ) ); ?>
					<?php echo liferuss_icon( 'arrow' ); ?>
				</button>
				<p class="form-note"><?php echo esc_html( liferuss_opt( $prefix . '_form_note' ) ); ?></p>
			</form>
		</div>

		<aside class="landing-form-banner">
			<?php
			liferuss_the_image(
				array(
					'id'       => liferuss_opt( $prefix . '_form_banner_image_id' ),
					'fallback' => liferuss_opt( $prefix . '_form_banner_image', 'consult-student.jpg' ),
					'alt'      => liferuss_opt( $prefix . '_form_banner_title' ),
					'width'    => 520,
					'height'   => 640,
					'size'     => 'medium_large',
				)
			);
			?>
			<div class="landing-form-banner-copy">
				<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
				<h3><?php echo esc_html( liferuss_opt( $prefix . '_form_banner_title' ) ); ?></h3>
				<p><?php echo esc_html( liferuss_opt( $prefix . '_form_banner_text' ) ); ?></p>
			</div>
		</aside>
	</div>
</section>
