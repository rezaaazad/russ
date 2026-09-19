<?php
/**
 * Theme footer.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand     = liferuss_brand();
$phone     = liferuss_opt( 'phone' );
$phone_alt = liferuss_opt( 'phone_alt' );
$email     = liferuss_opt( 'email' );
$address   = liferuss_opt( 'address' );
$whatsapp  = liferuss_opt( 'whatsapp' );
$telegram  = liferuss_social_url( liferuss_opt( 'telegram' ), 'telegram' );
$instagram = liferuss_social_url( liferuss_opt( 'instagram' ), 'instagram' );
$linkedin  = esc_url( liferuss_opt( 'linkedin' ) );
$youtube   = esc_url( liferuss_opt( 'youtube' ) );
$copy      = str_replace( '{year}', gmdate( 'Y' ), liferuss_opt( 'footer_copyright' ) );
$logo_id   = absint( liferuss_opt( 'logo_id', 0 ) );
?>
</main>
<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<div class="brand footer-logo">
				<?php if ( $logo_id ) : ?>
					<?php echo wp_get_attachment_image( $logo_id, 'full', false, array( 'alt' => $brand ) ); ?>
				<?php else : ?>
					<span class="brand-mark" aria-hidden="true">
						<svg viewBox="0 0 48 48" class="brand-cap">
							<circle cx="24" cy="24" r="24" fill="#E8B923"/>
							<path d="M10 22.2 24 15.4 38 22.2 24 29 10 22.2z" fill="#0B2341"/>
							<path d="M16 24.4v6.2c0 .7 3.4 2.6 8 2.6s8-1.9 8-2.6v-6.2" fill="none" stroke="#0B2341" stroke-width="1.8"/>
						</svg>
					</span>
					<span class="brand-text">
						<strong><?php echo esc_html( $brand ); ?></strong>
						<small><?php echo esc_html( liferuss_opt( 'tagline' ) ); ?></small>
					</span>
				<?php endif; ?>
			</div>
			<p><?php echo esc_html( liferuss_opt( 'footer_about' ) ); ?></p>
		</div>

		<div class="footer-col">
			<h2><?php echo esc_html( liferuss_t( 'footer_quick' ) ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( (array) liferuss_opt( 'footer_links', array() ) as $item ) : ?>
					<?php
					if ( empty( $item['label'] ) ) {
						continue;
					}
					$link = $item['url'] ?? '';
					$href = preg_match( '#^https?://#i', $link ) ? liferuss_localize_url( $link ) : liferuss_url( $link );
					?>
					<li><a href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="footer-col">
			<h2><?php echo esc_html( liferuss_t( 'footer_services' ) ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( liferuss_services() as $service ) : ?>
					<li><a href="<?php echo esc_url( liferuss_url( '/services/' ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="footer-col">
			<h2><?php echo esc_html( liferuss_t( 'footer_contact' ) ); ?></h2>
			<ul class="footer-contact">
				<?php if ( $phone ) : ?>
					<li><?php echo liferuss_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( $phone_alt ) : ?>
					<li><?php echo liferuss_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone_alt ) ); ?>"><?php echo esc_html( $phone_alt ); ?></a></li>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<li><?php echo liferuss_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
				<?php endif; ?>
				<?php if ( $address ) : ?>
					<li><?php echo liferuss_icon( 'pin' ); ?><span><?php echo esc_html( $address ); ?></span></li>
				<?php endif; ?>
			</ul>
			<div class="social-row">
				<?php if ( $whatsapp ) : ?>
					<a class="social-btn" href="<?php echo esc_url( liferuss_whatsapp_url( $whatsapp ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( liferuss_t( 'channel_whatsapp' ) ); ?>"><?php echo liferuss_icon( 'whatsapp' ); ?></a>
				<?php endif; ?>
				<?php if ( $telegram ) : ?>
					<a class="social-btn" href="<?php echo esc_url( $telegram ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( liferuss_t( 'channel_telegram' ) ); ?>"><?php echo liferuss_icon( 'telegram' ); ?></a>
				<?php endif; ?>
				<?php if ( $instagram ) : ?>
					<a class="social-btn" href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( liferuss_t( 'channel_instagram' ) ); ?>"><?php echo liferuss_icon( 'instagram' ); ?></a>
				<?php endif; ?>
				<?php if ( $linkedin ) : ?>
					<a class="social-btn" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><?php echo liferuss_icon( 'linkedin' ); ?></a>
				<?php endif; ?>
				<?php if ( $youtube ) : ?>
					<a class="social-btn" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><?php echo liferuss_icon( 'youtube' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="footer-bottom">
		<div class="container footer-bottom-inner">
			<p><?php echo esc_html( $copy ); ?></p>
			<?php liferuss_language_switcher( 'footer' ); ?>
			<p class="footer-en"><?php echo esc_html( liferuss_opt( 'footer_en' ) ); ?></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
