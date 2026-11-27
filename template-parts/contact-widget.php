<?php
/**
 * Floating contact FAB + channel sheet.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '1' !== (string) liferuss_opt( 'float_widget_enabled', '1' ) ) {
	return;
}

$channels = array();
foreach ( (array) liferuss_opt( 'float_channels', array() ) as $item ) {
	if ( empty( $item['enabled'] ) || '0' === (string) $item['enabled'] ) {
		continue;
	}
	$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
	if ( '' === $label ) {
		continue;
	}
	$href = liferuss_channel_href( $item );
	if ( '' === $href ) {
		continue;
	}
	$type = isset( $item['type'] ) ? sanitize_key( $item['type'] ) : 'custom';
	$icon = isset( $item['icon'] ) ? sanitize_key( $item['icon'] ) : '';
	if ( '' === $icon ) {
		$icon = $type;
	}
	if ( ! liferuss_icon( $icon ) ) {
		$icon = 'chat';
	}
	$channels[] = array(
		'label' => $label,
		'href'  => $href,
		'type'  => $type,
		'icon'  => $icon,
	);
}

if ( ! $channels ) {
	return;
}

$title    = liferuss_opt( 'float_widget_title' );
$subtitle = liferuss_opt( 'float_widget_subtitle' );
$offset_x = max( 0, min( 80, absint( liferuss_opt( 'float_widget_offset_x', 16 ) ) ) );
$offset_y = max( 0, min( 80, absint( liferuss_opt( 'float_widget_offset_y', 16 ) ) ) );
$style    = '--lr-fab-x:' . $offset_x . 'px;--lr-fab-y:' . $offset_y . 'px;';
?>
<div class="lr-float" data-float-widget style="<?php echo esc_attr( $style ); ?>">
	<div class="lr-float__backdrop" data-float-backdrop hidden></div>
	<div
		class="lr-float__panel"
		id="lr-float-panel"
		role="dialog"
		aria-modal="true"
		aria-labelledby="lr-float-title"
		aria-hidden="true"
		data-float-panel
		hidden
	>
		<?php if ( $title ) : ?>
			<h2 class="lr-float__title" id="lr-float-title"><?php echo esc_html( $title ); ?></h2>
		<?php else : ?>
			<h2 class="screen-reader-text" id="lr-float-title"><?php echo esc_html( liferuss_t( 'float_dialog' ) ); ?></h2>
		<?php endif; ?>
		<?php if ( $subtitle ) : ?>
			<p class="lr-float__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
		<ul class="lr-float__list">
			<?php foreach ( $channels as $channel ) : ?>
				<?php
				$external = liferuss_href_is_external( $channel['href'] );
				$consult  = ( 'consult' === $channel['type'] || false !== strpos( $channel['href'], '#consultation' ) );
				$classes  = 'lr-float__row is-' . $channel['type'];
				if ( $consult ) {
					$classes .= ' js-scroll-consult';
				}
				?>
				<li>
					<a
						class="<?php echo esc_attr( $classes ); ?>"
						href="<?php echo esc_url( $channel['href'] ); ?>"
						<?php echo $external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>
					>
						<span class="lr-float__row-icon" aria-hidden="true"><?php echo liferuss_icon( $channel['icon'] ); ?></span>
						<span class="lr-float__row-label"><?php echo esc_html( $channel['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<button
		type="button"
		class="lr-float__fab"
		data-float-toggle
		aria-expanded="false"
		aria-controls="lr-float-panel"
		aria-label="<?php echo esc_attr( liferuss_t( 'float_open' ) ); ?>"
	>
		<span class="lr-float__fab-icon lr-float__fab-icon--chat" aria-hidden="true"><?php echo liferuss_icon( 'chat' ); ?></span>
		<span class="lr-float__fab-icon lr-float__fab-icon--close" aria-hidden="true"><?php echo liferuss_icon( 'close' ); ?></span>
	</button>
</div>
