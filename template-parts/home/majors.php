<?php
/**
 * Homepage section.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="section majors-section" id="majors">
	<div class="container">
		<header class="section-head">
			<p class="eyebrow"><?php echo esc_html( liferuss_opt( 'majors_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( liferuss_home_heading( 'majors', liferuss_opt( 'majors_title' ) ) ); ?></h2>
			<p><?php echo esc_html( liferuss_opt( 'majors_subtitle' ) ); ?></p>
		</header>
		<div class="majors-grid">
			<?php foreach ( liferuss_majors() as $major ) : ?>
				<?php $tag = ! empty( $major['link'] ) ? 'a' : 'article'; ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="major-card"<?php echo ! empty( $major['link'] ) ? ' href="' . esc_url( liferuss_cta_url( $major['link'] ) ) . '"' : ''; ?>>
					<span class="major-orb"><?php echo liferuss_icon( $major['icon'] ); ?></span>
					<h3><?php echo esc_html( $major['title'] ); ?></h3>
				</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
