<?php
/**
 * Draft demo catalog. Nothing here is published.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Catalog;

use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A handful of real Russian universities, stored as drafts.
 */
class Demo {

	/**
	 * Seed once.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'maybe_seed' ), 30 );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_lr_publish_demo', array( self::class, 'handle_publish' ) );
	}

	/**
	 * One-click screen under the LifeRuss menu.
	 */
	public static function menu(): void {
		add_submenu_page(
			'liferuss',
			'انتشار کاتالوگ نمونه',
			'انتشار کاتالوگ نمونه',
			'lr_manage_university_data',
			'lr-publish-demo',
			array( self::class, 'screen' )
		);
	}

	/**
	 * Confirm before publishing demo drafts.
	 */
	public static function screen(): void {
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'اجازه این کار را ندارید.', 'liferuss-core' ) );
		}
		$done = isset( $_GET['lr_demo_published'] ) ? absint( wp_unslash( $_GET['lr_demo_published'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap">';
		echo '<h1>انتشار کاتالوگ نمونه</h1>';
		if ( $done ) {
			echo '<div class="notice notice-success"><p>' . esc_html( (string) $done ) . ' پیش‌نویس نمونه منتشر شد.</p></div>';
		}
		echo '<p>دانشگاه، شهر، رشته و بورسیهٔ پیش‌نویس که با برچسب نمونه ذخیره شده‌اند منتشر می‌شوند و در سایت دیده می‌شوند.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'lr_publish_demo' );
		echo '<input type="hidden" name="action" value="lr_publish_demo">';
		submit_button( 'انتشار کاتالوگ نمونه' );
		echo '</form></div>';
	}

	/**
	 * Publish demo drafts after a nonce check.
	 */
	public static function handle_publish(): void {
		if ( ! current_user_can( 'lr_manage_university_data' ) ) {
			wp_die( esc_html__( 'اجازه این کار را ندارید.', 'liferuss-core' ) );
		}
		check_admin_referer( 'lr_publish_demo' );
		$count = self::publish();
		wp_safe_redirect( add_query_arg( 'lr_demo_published', $count, admin_url( 'admin.php?page=lr-publish-demo' ) ) );
		exit;
	}

	/**
	 * Publish catalog drafts marked as demo.
	 */
	public static function publish(): int {
		$ids   = get_posts(
			array(
				'post_type'      => array( 'lr_university', 'lr_city', 'lr_field', 'lr_scholarship' ),
				'post_status'    => array( 'draft', 'pending' ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_key'       => '_lr_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$count = 0;
		foreach ( $ids as $id ) {
			$result = wp_update_post(
				array(
					'ID'          => (int) $id,
					'post_status' => 'publish',
				),
				true
			);
			if ( ! is_wp_error( $result ) ) {
				++$count;
			}
		}
		Store::bump();
		return $count;
	}

	/**
	 * Insert demo rows when the flag is empty.
	 */
	public static function maybe_seed(): void {
		if ( get_option( 'lr_demo_catalog' ) ) {
			return;
		}
		self::ensure_rate();
		$cities = array(
			array( 'moscow', 'مسکو', 'Moscow', 'Москва', 'شهر مسکو', 13100000, 70000, 120000, 'زمستان سرد و تابستان معتدل.' ),
			array( 'saint-petersburg', 'سن‌پترزبورگ', 'Saint Petersburg', 'Санкт-Петербург', 'شهر سن‌پترزبورگ', 5600000, 60000, 110000, 'مرطوب، زمستان طولانی.' ),
			array( 'kazan', 'کازان', 'Kazan', 'Казань', 'تاتارستان', 1300000, 40000, 70000, 'قاره‌ای.' ),
		);
		foreach ( $cities as $city ) {
			self::entity(
				'cities',
				'lr_city',
				$city[0],
				$city[1],
				array(
					'name_en'         => $city[2],
					'name_ru'         => $city[3],
					'federal_subject' => $city[4],
					'population'      => $city[5],
					'living_cost_min' => $city[6],
					'living_cost_max' => $city[7],
					'currency'        => 'RUB',
					'climate_summary' => $city[8],
					'source'          => 'demo',
				)
			);
		}
		$fields = array(
			array( 'general-medicine', 'پزشکی عمومی', 'General Medicine', 'Лечебное дело', 'bachelor,specialist', 'ru', 6 ),
			array( 'computer-science', 'علوم کامپیوتر', 'Computer Science', 'Информатика', 'bachelor,master', 'ru,en', 4 ),
			array( 'economics', 'اقتصاد', 'Economics', 'Экономика', 'bachelor,master', 'ru,en', 4 ),
		);
		foreach ( $fields as $field ) {
			self::entity(
				'fields',
				'lr_field',
				$field[0],
				$field[1],
				array(
					'name_en'                => $field[2],
					'name_ru'                => $field[3],
					'degree_levels'          => $field[4],
					'languages'              => $field[5],
					'default_duration_years' => $field[6],
				)
			);
		}
		$unis = array(
			array( 'msu', 'دانشگاه دولتی مسکو', 'Lomonosov Moscow State University', 'МГУ', 'moscow', 1755, 'https://www.msu.ru', 94, 380000 ),
			array( 'spbu', 'دانشگاه دولتی سن‌پترزبورگ', 'Saint Petersburg State University', 'СПбГУ', 'saint-petersburg', 1724, 'https://spbu.ru', 242, 320000 ),
			array( 'hse', 'مدرسه عالی اقتصاد', 'HSE University', 'ВШЭ', 'moscow', 1992, 'https://www.hse.ru', 410, 390000 ),
			array( 'sechenov', 'دانشگاه سچنوف', 'Sechenov University', 'Сеченовский университет', 'moscow', 1758, 'https://www.sechenov.ru', 0, 450000 ),
			array( 'kfu', 'دانشگاه فدرال کازان', 'Kazan Federal University', 'КФУ', 'kazan', 1804, 'https://kpfu.ru', 396, 280000 ),
		);
		foreach ( $unis as $uni ) {
			$post_id = self::entity(
				'universities',
				'lr_university',
				$uni[0],
				$uni[1],
				array(
					'name_en'                 => $uni[2],
					'name_ru'                 => $uni[3],
					'short_name'              => $uni[3],
					'city_id'                 => self::id_by_slug( 'cities', $uni[4] ),
					'founded_year'            => $uni[5],
					'website'                 => $uni[6],
					'ownership'               => 'state',
					'has_dormitory'           => 1,
					'teaching_languages'      => 'ru,en',
					'health_ministry_status'  => 'sechenov' === $uni[0] ? 'approved' : 'unknown',
					'science_ministry_status' => 'sechenov' === $uni[0] ? 'unknown' : 'approved',
					'address'                 => 'نمونه نمایشی',
				)
			);
			$uni_id  = self::id_by_slug( 'universities', $uni[0] );
			if ( $uni_id && $uni[7] > 0 ) {
				$qs = self::provider( 'qs' );
				if ( $qs ) {
					Store::upsert_ranking( $uni_id, $qs, 'world', '', $uni[7], '', 2025, true );
				}
			}
			if ( $uni_id ) {
				$field  = 'sechenov' === $uni[0] || 'kfu' === $uni[0] ? 'general-medicine' : ( 'spbu' === $uni[0] ? 'economics' : 'computer-science' );
				$degree = 'sechenov' === $uni[0] ? 'specialist' : 'bachelor';
				Store::upsert_program( $uni_id, (int) self::id_by_slug( 'fields', $field ), $degree, 'ru', 'specialist' === $degree ? 6 : 4, (float) $uni[8], 'RUB', '2025/2026', true );
				Store::upsert_dorm( $uni_id, 8000, 15000, 'RUB' );
				Store::upsert_approval( $uni_id, 'sechenov' === $uni[0] ? 'health_ministry' : 'science_ministry', 'approved', 'demo', '', gmdate( 'Y-m-d H:i:s' ) );
			}
			if ( $post_id ) {
				update_post_meta( $post_id, '_lr_demo', '1' );
				update_post_meta( $post_id, '_lr_students_total', 20000 );
				update_post_meta( $post_id, '_lr_phone', '+7 000 000 0000' );
				update_post_meta( $post_id, '_lr_email', 'demo@example.com' );
			}
		}
		$faq = wp_insert_post(
			array(
				'post_type'    => 'lr_faq',
				'post_status'  => 'publish',
				'post_title'   => 'آیا این داده‌ها نمونه هستند؟',
				'post_content' => 'بله. دانشگاه‌های نمونه پیش‌نویس هستند و تا وقتی منتشر نشوند در سایت دیده نمی‌شوند.',
			)
		);
		$msu = self::post_id( 'universities', 'msu' );
		if ( $faq && $msu ) {
			update_post_meta( $msu, '_lr_faq_ids', (string) $faq );
		}
		update_option( 'lr_demo_catalog', '1', false );
		Store::bump();
	}

	/**
	 * Set a sample RUB rate only when none is stored.
	 */
	private static function ensure_rate(): void {
		$currency = Settings::get( 'currency' );
		$current  = $currency['rates']['RUB']['usd_per_unit'] ?? '';
		if ( '' !== (string) $current ) {
			return;
		}
		$currency['rates']['RUB']['usd_per_unit'] = '0.011';
		Settings::update( 'currency', $currency );
	}

	/**
	 * Draft post plus shadow columns.
	 *
	 * @param string               $suffix Table suffix.
	 * @param string               $type   Post type.
	 * @param string               $slug   Slug.
	 * @param string               $title  Persian title.
	 * @param array<string, mixed> $extra  Columns.
	 */
	private static function entity( string $suffix, string $type, string $slug, string $title, array $extra ): int {
		$found = Repository::for( $suffix )->find_by( 'slug', $slug, true );
		if ( $found && get_post( (int) $found['post_id'] ) ) {
			return (int) $found['post_id'];
		}
		$post_id = (int) wp_insert_post(
			array(
				'post_type'    => $type,
				'post_status'  => 'draft',
				'post_title'   => $title . ' (نمونه)',
				'post_name'    => $slug,
				'post_content' => 'دادهٔ نمایشی. منتشر نکنید مگر برای آزمون.',
			)
		);
		$row     = Store::row_for_post( $suffix, $post_id );
		if ( $row ) {
			$extra['name_fa'] = $title . ' (نمونه)';
			if ( ! isset( $extra['name_en'] ) ) {
				$extra['name_en'] = '';
			}
			if ( ! isset( $extra['name_ru'] ) ) {
				$extra['name_ru'] = '';
			}
			Store::update_row( $suffix, (int) $row['id'], $extra );
		}
		update_post_meta( $post_id, '_lr_demo', '1' );
		return $post_id;
	}

	/**
	 * Shadow id by slug.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $slug   Slug.
	 */
	private static function id_by_slug( string $suffix, string $slug ): int {
		$row = Repository::for( $suffix )->find_by( 'slug', $slug, true );
		return $row ? (int) $row['id'] : 0;
	}

	/**
	 * Post id by slug.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $slug   Slug.
	 */
	private static function post_id( string $suffix, string $slug ): int {
		$row = Repository::for( $suffix )->find_by( 'slug', $slug, true );
		return $row ? (int) $row['post_id'] : 0;
	}

	/**
	 * Ranking provider id, inserting QS or THE if the seeder has not run.
	 *
	 * @param string $code Provider code.
	 */
	private static function provider( string $code ): int {
		$row = Repository::for( 'ranking_providers' )->find_by( 'code', $code );
		if ( $row ) {
			return (int) $row['id'];
		}
		$names = array(
			'qs'  => 'QS',
			'the' => 'Times Higher Education',
		);
		if ( ! isset( $names[ $code ] ) ) {
			return 0;
		}
		return Repository::for( 'ranking_providers' )->insert(
			array(
				'code'       => $code,
				'name'       => $names[ $code ],
				'is_active'  => 1,
				'sort_order' => 'qs' === $code ? 10 : 20,
			)
		);
	}
}
