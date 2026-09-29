<?php
/**
 * One scholarship. Application writes an admission request with program_type scholarship.
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
$post_id = (int) get_the_ID();
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_t( 'nav_scholarships' ) ); ?></p>
		<h1><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?>
			<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<?php liferuss_breadcrumbs(); ?>
	</div>
</header>
<section class="section">
	<div class="container">
		<ul class="lr-facts">
			<?php
			$row   = \LifeRuss\Core\Scholarships\Store::for_post( $post_id );
			$facts = array();
			if ( $row ) {
				$types = \LifeRuss\Core\Scholarships\Store::coverage_types();
				$degs  = \LifeRuss\Core\Scholarships\Store::degrees();
				$facts[ liferuss_t( 'path_deadline' ) ] = (string) $row['deadline'];
				$facts['رشته']                         = (string) $row['field_name'];
				$facts['مقطع']                         = $degs[ (string) $row['degree'] ] ?? '';
				$cover                                 = $types[ (string) $row['coverage_type'] ] ?? '';
				if ( $row['coverage_percent'] ) {
					$cover .= ' ' . (int) $row['coverage_percent'] . '%';
				}
				$facts[ liferuss_t( 'path_coverage' ) ] = $cover;
				$facts[ liferuss_t( 'path_source' ) ]   = (string) $row['source'];
				$facts[ liferuss_t( 'path_verified' ) ] = \LifeRuss\Core\CRM\Jalali::plain( (string) $row['last_updated'] );
			}
			foreach ( $facts as $label => $value ) {
				if ( '' === $value || '—' === $value ) {
					continue;
				}
				echo '<li>' . esc_html( $label ) . ': ' . esc_html( $value ) . '</li>';
			}
			$source_url = (string) get_post_meta( $post_id, '_lr_source_url', true );
			if ( $source_url ) {
				echo '<li><a href="' . esc_url( $source_url ) . '">' . esc_html( liferuss_t( 'path_source' ) ) . '</a></li>';
			}
			?>
		</ul>
		<?php
		$eligibility = $row ? (string) $row['requirements'] : (string) get_post_meta( $post_id, '_lr_eligibility', true );
		if ( $eligibility ) {
			echo '<h2>' . esc_html( liferuss_t( 'path_eligibility' ) ) . '</h2>';
			echo '<div class="path-prose"><p>' . esc_html( $eligibility ) . '</p></div>';
		}
		?>
		<div class="path-prose"><?php the_content(); ?></div>
	</div>
</section>
<?php
liferuss_path_faqs( $post_id );
liferuss_path_quotes( $post_id );
liferuss_path_related( $post_id );
liferuss_path_form( 'admission', 'scholarship', '', '', get_the_title( $post_id ) );
get_footer();
