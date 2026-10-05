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
$phone     = liferuss_public_contact( 'phone' );
$phone_alt = liferuss_public_contact( 'phone_alt' );
$email     = liferuss_public_contact( 'email' );
$address   = liferuss_public_contact( 'address' );
$whatsapp  = liferuss_public_contact( 'whatsapp' );
$telegram  = liferuss_social_url( liferuss_public_contact( 'telegram' ), 'telegram' );
$instagram = liferuss_social_url( liferuss_public_contact( 'instagram' ), 'instagram' );
$linkedin  = liferuss_public_contact( 'linkedin' );
$youtube   = liferuss_public_contact( 'youtube' );
$has_contact = $phone || $phone_alt || $email || $address || $whatsapp || $telegram || $instagram || $linkedin || $youtube;
$footer_cols = 2 + ( liferuss_services() ? 1 : 0 ) + ( $has_contact ? 1 : 0 );
$copy        = str_replace( '{year}', liferuss_local_digits( gmdate( 'Y' ) ), liferuss_opt( 'footer_copyright' ) );
$logo_id   = absint( liferuss_opt( 'logo_id', 0 ) );
?>
</main>
<footer class="site-footer">
	<div class="container footer-grid footer-grid--<?php echo (int) $footer_cols; ?>">
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

		<details class="footer-col footer-acc" open>
			<summary><h2><?php echo esc_html( liferuss_t( 'footer_quick' ) ); ?></h2></summary>
			<ul class="footer-links">
				<?php
				$footer_seen = array();
				$footer_rows = array();
				foreach ( (array) liferuss_opt( 'footer_links', array() ) as $item ) {
					if ( empty( $item['label'] ) ) {
						continue;
					}
					$footer_rows[] = array(
						'label' => (string) $item['label'],
						'url'   => (string) ( $item['url'] ?? '' ),
					);
				}
				foreach ( liferuss_path_footer_extra() as $extra ) {
					$footer_rows[] = array(
						'label' => (string) $extra['title'],
						'url'   => (string) $extra['url'],
					);
				}
				foreach ( $footer_rows as $item ) :
					$link = $item['url'];
					$path = untrailingslashit( (string) ( preg_match( '#^https?://#i', $link ) ? (string) wp_parse_url( $link, PHP_URL_PATH ) : $link ) );
					if ( '/services' === $path && 'خدمات' === $item['label'] ) {
						continue;
					}
					if ( '/migration-russia' === $path ) {
						$link = '/immigration/';
						$path = '/immigration';
					}
					$key  = $path . '|' . $item['label'];
					if ( isset( $footer_seen[ $path ] ) || isset( $footer_seen[ 'label:' . $item['label'] ] ) ) {
						continue;
					}
					$footer_seen[ $path ]                      = true;
					$footer_seen[ 'label:' . $item['label'] ] = true;
					$href = preg_match( '#^https?://#i', $link ) ? liferuss_localize_url( $link ) : liferuss_url( $link );
					?>
					<li><a href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</details>

		<?php if ( liferuss_services() ) : ?>
		<details class="footer-col footer-acc" open>
			<summary><h2><?php echo esc_html( liferuss_t( 'footer_services' ) ); ?></h2></summary>
			<ul class="footer-links">
				<?php foreach ( liferuss_services() as $service ) : ?>
					<li><a href="<?php echo esc_url( liferuss_service_href( (string) ( $service['icon'] ?? '' ) ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</details>
		<?php endif; ?>

		<?php if ( $has_contact ) : ?>
		<details class="footer-col footer-acc" open>
			<summary><h2><?php echo esc_html( liferuss_t( 'footer_contact' ) ); ?></h2></summary>
			<ul class="footer-contact">
				<?php if ( $phone ) : ?>
					<li><?php echo liferuss_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><bdi dir="ltr"><?php echo esc_html( $phone ); ?></bdi></a></li>
				<?php endif; ?>
				<?php if ( $phone_alt ) : ?>
					<li><?php echo liferuss_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone_alt ) ); ?>"><bdi dir="ltr"><?php echo esc_html( $phone_alt ); ?></bdi></a></li>
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
		</details>
		<?php endif; ?>
	</div>
	<div class="footer-bottom">
		<div class="container footer-bottom-inner">
			<p><?php echo esc_html( $copy ); ?></p>
			<?php liferuss_language_switcher( 'footer' ); ?>
			<p class="footer-en"><?php echo esc_html( liferuss_opt( 'footer_en' ) ); ?></p>
		</div>
	</div>
</footer>
<?php
liferuss_compare_bar();
get_template_part( 'template-parts/bottom-nav' );
get_template_part( 'template-parts/contact-widget' );
wp_footer();
?>
</body>
</html>
