<?php
/**
 * Single magazine article.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article <?php post_class( 'single-article' ); ?>>
			<header class="page-hero">
				<div class="container">
					<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( number_format_i18n( liferuss_reading_minutes( get_the_ID() ) ) ); ?> <?php echo esc_html( liferuss_t( 'reading_min' ) ); ?></p>
					<h1><?php the_title(); ?></h1>
					<?php liferuss_breadcrumbs(); ?>
				</div>
			</header>
			<div class="container single-layout">
				<div class="prose">
					<figure class="single-thumb"><?php liferuss_the_entry_image( 'single' ); ?></figure>
					<?php $toc = liferuss_toc_items(); ?>
					<?php if ( $toc ) : ?>
						<nav class="lr-toc" aria-label="<?php echo esc_attr( liferuss_t( 'toc' ) ); ?>">
							<strong><?php echo esc_html( liferuss_t( 'toc' ) ); ?></strong>
							<ol>
								<?php foreach ( $toc as $item ) : ?>
									<li class="lr-toc-<?php echo (int) $item['level']; ?>"><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						</nav>
					<?php endif; ?>
					<?php the_content(); ?>
					<?php if ( class_exists( '\LifeRuss\Core\Seo\Links' ) ) { \LifeRuss\Core\Seo\Links::related_box( get_the_ID() ); } ?>
					<aside class="lr-author">
						<?php
						$author_id   = (int) get_the_author_meta( 'ID' );
						$author_name = get_the_author();
						$avatar_data = get_avatar_data( $author_id, array( 'size' => 64 ) );
						$avatar_url  = isset( $avatar_data['url'] ) ? (string) $avatar_data['url'] : '';
						$real_avatar = ! empty( $avatar_data['found_avatar'] ) && ! str_contains( $avatar_url, 'gravatar.com' );
						if ( $author_name === get_the_author_meta( 'user_login' ) ) {
							$author_name = liferuss_t( 'author_fallback' );
						}
						$author_bits = preg_split( '/\s+/u', trim( (string) $author_name ) );
						$author_mark = '';
						if ( is_array( $author_bits ) ) {
							foreach ( array_slice( $author_bits, 0, 2 ) as $bit ) {
								$author_mark .= mb_substr( $bit, 0, 1 );
							}
						}
						?>
						<?php if ( $real_avatar ) : ?>
							<?php echo get_avatar( $author_id, 64 ); ?>
						<?php else : ?>
							<span class="lr-author-mark" aria-hidden="true"><?php echo esc_html( $author_mark ); ?></span>
						<?php endif; ?>
						<div>
							<strong><?php echo esc_html( $author_name ); ?></strong>
							<?php $bio = trim( (string) get_the_author_meta( 'description' ) ); ?>
							<?php if ( $bio && $bio !== $author_name ) : ?>
								<p><?php echo esc_html( $bio ); ?></p>
							<?php endif; ?>
						</div>
					</aside>
					<?php $related = liferuss_related_posts( get_the_ID() ); ?>
					<?php if ( $related ) : ?>
						<section class="lr-related">
							<h2><?php echo esc_html( liferuss_t( 'related_posts' ) ); ?></h2>
							<ul>
								<?php foreach ( $related as $item ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>
					<p class="lr-inline-cta"><a class="btn btn-gold" href="#consultation"><?php echo esc_html( liferuss_t( 'magazine_cta' ) ); ?></a></p>
					<?php if ( get_previous_post() || get_next_post() ) : ?>
					<nav class="post-nav">
						<?php if ( get_previous_post() ) : ?>
							<?php previous_post_link( '%link', liferuss_t( 'post_prev' ) ); ?>
						<?php endif; ?>
						<?php if ( get_next_post() ) : ?>
							<?php next_post_link( '%link', liferuss_t( 'post_next' ) ); ?>
						<?php endif; ?>
					</nav>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
		$form = liferuss_magazine_form_type( get_the_ID() );
		if ( in_array( $form, array( 'freight', 'trade' ), true ) ) {
			get_template_part( 'template-parts/landing-form', null, array( 'prefix' => $form ) );
		} else {
			liferuss_path_form( $form, 'degree', '', '', '' );
		}
		?>
	<?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
