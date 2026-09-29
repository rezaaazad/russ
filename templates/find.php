<?php
/**
 * Faceted site search.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$city  = isset( $_GET['city'] ) ? sanitize_title( wp_unslash( $_GET['city'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page  = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$data  = class_exists( '\LifeRuss\Core\Search\Engine' ) ? \LifeRuss\Core\Search\Engine::search( $q, $type, $city, max( 1, $page ), true ) : array( 'items' => array(), 'total' => 0, 'type_facets' => array(), 'cities' => array(), 'pages' => 1, 'query' => $q );
$base  = liferuss_url( '/search/' );
?>
<header class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( liferuss_brand() ); ?></p>
		<h1>جستجو</h1>
	</div>
</header>
<article class="section">
	<div class="container">
		<form class="lr-find-form" method="get" action="<?php echo esc_url( $base ); ?>" role="search">
			<label>
				<span class="screen-reader-text">عبارت جستجو</span>
				<input type="search" name="q" value="<?php echo esc_attr( (string) ( $data['query'] ?? $q ) ); ?>" required>
			</label>
			<button class="btn btn-gold" type="submit">جستجو</button>
		</form>
		<?php if ( '' === trim( $q ) ) : ?>
			<p class="lr-empty">نام دانشگاه، رشته، شهر، بورسیه یا عنوان نوشته را بنویسید.</p>
		<?php else : ?>
			<p class="lr-meta"><?php echo esc_html( (string) (int) ( $data['total'] ?? 0 ) ); ?> نتیجه</p>
			<nav class="lr-facets" aria-label="نوع نتیجه">
				<?php
				$all_url = add_query_arg( 'q', $data['query'] ?? $q, $base );
				echo '<a href="' . esc_url( $all_url ) . '"' . ( '' === (string) ( $data['type'] ?? '' ) ? ' aria-current="page"' : '' ) . '>همه</a>';
				foreach ( (array) ( $data['type_facets'] ?? array() ) as $facet => $count ) {
					$url = add_query_arg(
						array(
							'q'    => $data['query'] ?? $q,
							'type' => $facet,
						),
						$base
					);
					$label = class_exists( '\LifeRuss\Core\Search\Engine' ) ? \LifeRuss\Core\Search\Engine::type_label( (string) $facet ) : (string) $facet;
					$current = (string) ( $data['type'] ?? '' ) === (string) $facet ? ' aria-current="page"' : '';
					echo '<a href="' . esc_url( $url ) . '"' . $current . '>' . esc_html( $label ) . ' (' . (int) $count . ')</a>';
				}
				?>
			</nav>
			<?php if ( ! empty( $data['cities'] ) ) : ?>
				<nav class="lr-facets" aria-label="شهر">
					<?php foreach ( $data['cities'] as $facet_city ) : ?>
						<?php
						$url = add_query_arg(
							array(
								'q'    => $data['query'] ?? $q,
								'type' => $data['type'] ?? '',
								'city' => $facet_city['slug'],
							),
							$base
						);
						?>
						<a href="<?php echo esc_url( $url ); ?>"<?php echo (string) ( $data['city'] ?? '' ) === (string) $facet_city['slug'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $facet_city['name'] ); ?> (<?php echo (int) $facet_city['count']; ?>)</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<?php if ( empty( $data['items'] ) ) : ?>
				<p class="lr-empty">نتیجه‌ای پیدا نشد.</p>
			<?php else : ?>
				<ul class="lr-find-results">
					<?php foreach ( $data['items'] as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) $item['url'] ); ?>"><?php echo esc_html( (string) $item['title'] ); ?></a>
							<?php if ( ! empty( $item['excerpt'] ) ) : ?>
								<p><?php echo esc_html( (string) $item['excerpt'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( (int) ( $data['pages'] ?? 1 ) > 1 ) : ?>
				<nav class="lr-pager" aria-label="صفحه‌ها">
					<?php
					for ( $i = 1; $i <= (int) $data['pages'] && $i <= 8; $i++ ) {
						$url = add_query_arg(
							array(
								'q'    => $data['query'] ?? $q,
								'type' => $data['type'] ?? '',
								'city' => $data['city'] ?? '',
								'page' => $i,
							),
							$base
						);
						if ( $i === (int) ( $data['page'] ?? 1 ) ) {
							echo '<span aria-current="page">' . (int) $i . '</span>';
						} else {
							echo '<a href="' . esc_url( $url ) . '">' . (int) $i . '</a>';
						}
					}
					?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
