<?php
/**
 * Mobile bottom navigation (education / consult — not e-commerce).
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '1' !== (string) liferuss_opt( 'bottom_nav_enabled', '1' ) ) {
	return;
}

$items = array();
foreach ( (array) liferuss_opt( 'bottom_nav_items', array() ) as $item ) {
	if ( empty( $item['enabled'] ) || '0' === (string) $item['enabled'] ) {
		continue;
	}
	$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
	$url   = isset( $item['url'] ) ? trim( (string) $item['url'] ) : '';
	if ( '' === $label || '' === $url ) {
		continue;
	}
	$icon = isset( $item['icon'] ) ? sanitize_key( $item['icon'] ) : 'chat';
	if ( ! liferuss_icon( $icon ) ) {
		$icon = 'chat';
	}
	$items[] = array(
		'label'   => $label,
		'href'    => liferuss_cta_url( $url ),
		'icon'    => $icon,
		'primary' => ! empty( $item['primary'] ) && '0' !== (string) $item['primary'],
	);
}

if ( ! $items ) {
	return;
}
?>
<nav class="lr-bottom-nav" aria-label="<?php echo esc_attr( liferuss_t( 'bottom_nav_aria' ) ); ?>">
	<?php foreach ( $items as $item ) : ?>
		<?php
		$classes = 'lr-bottom-nav__item';
		if ( $item['primary'] ) {
			$classes .= ' is-primary';
		}
		if ( liferuss_href_is_current( $item['href'] ) ) {
			$classes .= ' is-current';
		}
		if ( false !== strpos( $item['href'], '#consultation' ) ) {
			$classes .= ' js-scroll-consult';
		}
		?>
		<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $item['href'] ); ?>">
			<span class="lr-bottom-nav__icon" aria-hidden="true"><?php echo liferuss_icon( $item['icon'] ); ?></span>
			<span class="lr-bottom-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>
