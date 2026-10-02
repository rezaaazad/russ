<?php
/**
 * Blog search form.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="search-form" role="search" method="get" action="<?php echo esc_url( liferuss_home() ); ?>">
	<label>
		<span class="screen-reader-text"><?php echo esc_html( liferuss_t( 'search_open' ) ); ?></span>
		<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( liferuss_t( 'search_open' ) ); ?>">
	</label>
	<button class="btn btn-gold" type="submit"><?php echo esc_html( liferuss_t( 'search_open' ) ); ?></button>
</form>
