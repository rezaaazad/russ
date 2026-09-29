<?php
/**
 * Template Name: مسیر تحصیل و مهاجرت
 *
 * Fixed sections. A content manager edits the hero, intro, six section slots,
 * live catalog mode, form, FAQ, testimonials, and related posts from the page.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) {
	the_post();
}

$post_id  = (int) get_the_ID();
$eyebrow  = (string) get_post_meta( $post_id, '_lr_eyebrow', true );
$lead     = (string) get_post_meta( $post_id, '_lr_lead', true );
$sections = json_decode( (string) get_post_meta( $post_id, '_lr_sections', true ), true );
$sections = is_array( $sections ) ? $sections : array();
$degree   = (string) get_post_meta( $post_id, '_lr_degree', true );
$field    = (string) get_post_meta( $post_id, '_lr_field', true );
$form     = (string) get_post_meta( $post_id, '_lr_form', true );
$program  = (string) get_post_meta( $post_id, '_lr_program', true );
?>

<header class="page-hero">
	<div class="container">
		<?php if ( $eyebrow ) : ?>
			<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<h1><?php the_title(); ?></h1>
		<?php if ( $lead ) : ?>
			<p><?php echo esc_html( $lead ); ?></p>
		<?php endif; ?>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>

<?php if ( get_the_content() ) : ?>
<section class="section">
	<div class="container path-prose">
		<?php the_content(); ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $sections ) : ?>
<section class="section path-sections">
	<div class="container">
		<?php foreach ( $sections as $section ) : ?>
			<?php
			$title = isset( $section['title'] ) ? (string) $section['title'] : '';
			$body  = isset( $section['body'] ) ? (string) $section['body'] : '';
			if ( '' === $title && '' === trim( wp_strip_all_tags( $body ) ) ) {
				continue;
			}
			?>
			<article class="path-section">
				<?php if ( $title ) : ?>
					<h2><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
				<div class="path-prose"><?php echo wp_kses_post( wpautop( $body ) ); ?></div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php
liferuss_path_live( $post_id );
liferuss_path_faqs( $post_id );
liferuss_path_quotes( $post_id );
liferuss_path_related( $post_id );
if ( $form ) {
	liferuss_path_form( $form, $program, $degree, liferuss_path_level( $degree, $field, $program ), '' );
}
get_footer();
