<?php
/**
 * Front routes for comparison and search.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Front;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules that resolve before the 404 handler.
 */
class Routes {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'rules' ) );
		add_filter( 'query_vars', array( self::class, 'vars' ) );
		add_filter( 'pre_handle_404', array( self::class, 'keep' ), 10, 2 );
		add_action( 'template_redirect', array( self::class, 'status' ), 0 );
		add_filter( 'liferuss_cache_headers', array( self::class, 'cache_headers' ) );
	}

	/**
	 * Pretty permalinks. A version bump flushes them.
	 */
	public static function rules(): void {
		add_rewrite_rule( '^compare/?$', 'index.php?lr_compare=1', 'top' );
		add_rewrite_rule( '^compare/([^/]+)/?$', 'index.php?lr_compare=1&lr_compare_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^search/?$', 'index.php?lr_find=1', 'top' );
		add_rewrite_rule( '^account/?$', 'index.php?lr_account=dashboard', 'top' );
		add_rewrite_rule( '^account/requests/?$', 'index.php?lr_account=requests', 'top' );
		add_rewrite_rule( '^account/request/([0-9]+)/?$', 'index.php?lr_account=request&lr_account_id=$matches[1]', 'top' );
		add_rewrite_rule( '^account/saved/?$', 'index.php?lr_account=saved', 'top' );
		add_rewrite_rule( '^account/profile/?$', 'index.php?lr_account=profile', 'top' );
		add_rewrite_rule( '^pay/([a-f0-9]{32})/?$', 'index.php?lr_pay=$matches[1]', 'top' );
	}

	/**
	 * Public query vars.
	 *
	 * @param string[] $vars Vars.
	 * @return string[]
	 */
	public static function vars( array $vars ): array {
		$vars[] = 'lr_compare';
		$vars[] = 'lr_compare_slug';
		$vars[] = 'lr_find';
		$vars[] = 'lr_account';
		$vars[] = 'lr_account_id';
		$vars[] = 'lr_learn';
		$vars[] = 'lr_course_slug';
		$vars[] = 'lr_pay';
		return $vars;
	}

	/**
	 * These routes are real pages even without a post.
	 *
	 * @param bool      $preempt Whether 404 handling was preempted.
	 * @param \WP_Query $query   Query.
	 */
	public static function keep( $preempt, $query ) {
		unset( $query );
		if ( get_query_var( 'lr_compare' ) || get_query_var( 'lr_find' ) || get_query_var( 'lr_account' ) || get_query_var( 'lr_learn' ) || get_query_var( 'lr_pay' ) ) {
			return true;
		}
		return $preempt;
	}

	/**
	 * Send 200, or 404 when a curated slug does not exist.
	 */
	public static function status(): void {
		if ( ! get_query_var( 'lr_compare' ) && ! get_query_var( 'lr_find' ) && ! get_query_var( 'lr_account' ) && ! get_query_var( 'lr_learn' ) && ! get_query_var( 'lr_pay' ) ) {
			return;
		}
		if ( get_query_var( 'lr_pay' ) && class_exists( '\LifeRuss\Core\Payments\Checkout' ) ) {
			$payment = \LifeRuss\Core\Payments\Checkout::by_token( (string) get_query_var( 'lr_pay' ) );
			if ( ! $payment ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				return;
			}
		}
		if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
			$data = \LifeRuss\Core\Compare\Set::current();
			if ( ! empty( $data['missing'] ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				return;
			}
		}
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
	}

	/**
	 * Query-string search and ad-hoc comparisons stay uncached. Curated pages can cache.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	public static function cache_headers( array $headers ): array {
		if ( get_query_var( 'lr_find' ) || get_query_var( 'lr_account' ) || get_query_var( 'lr_pay' ) ) {
			return array();
		}
		$learn = (string) get_query_var( 'lr_learn' );
		if ( 'placement' === $learn || 'certificate' === $learn ) {
			return array();
		}
		if ( get_query_var( 'lr_compare' ) && class_exists( '\LifeRuss\Core\Compare\Set' ) ) {
			$data = \LifeRuss\Core\Compare\Set::current();
			if ( empty( $data['indexable'] ) ) {
				return array();
			}
		}
		return $headers;
	}
}
