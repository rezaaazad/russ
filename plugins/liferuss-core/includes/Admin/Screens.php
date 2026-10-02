<?php
/**
 * Admin screen renderers.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Admin;

use LifeRuss\Core\CRM\Jalali;
use LifeRuss\Core\Repositories\Repository;
use LifeRuss\Core\Roles\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placeholder and list screens.
 */
class Screens {

	/**
	 * Dashboard counts. Widgets follow the caller's caps.
	 */
	public static function dashboard(): void {
		self::guard( 'lr_view_dashboard' );
		Chrome::open( __( 'پیشخوان لایف‌روس', 'liferuss-core' ), 'لایف‌روس' );
		echo '<div class="lr-kpis">';
		$lead_scope = null;
		if ( current_user_can( 'lr_manage_leads' ) || current_user_can( 'lr_view_own_leads' ) ) {
			$lead_scope = Access::apply( array(), Access::lead_scope() );
		}
		if ( is_array( $lead_scope ) ) {
			$leads = Repository::for( 'leads' );
			$now   = gmdate( 'Y-m-d H:i:s' );
			self::kpi( __( 'لیدهای امروز', 'liferuss-core' ), $leads->count( array_merge( $lead_scope, array( 'created_after' => Jalali::period_start_utc( 'today' ) ) ) ) );
			self::kpi( __( 'لیدهای این هفته', 'liferuss-core' ), $leads->count( array_merge( $lead_scope, array( 'created_after' => Jalali::period_start_utc( 'week' ) ) ) ) );
			self::kpi( __( 'پیگیری‌های سررسید', 'liferuss-core' ), $leads->count( array_merge( $lead_scope, array( 'follow_up_due' => $now ) ) ) );
		}
		self::request_kpis();
		if ( current_user_can( 'lr_view_university_data' ) ) {
			self::kpi( __( 'دانشگاه‌ها', 'liferuss-core' ), Repository::for( 'universities' )->count() );
			self::kpi( __( 'شهریه‌ها', 'liferuss-core' ), Repository::for( 'tuition_fees' )->count() );
		}
		echo '</div>';
		if ( is_array( $lead_scope ) ) {
			self::breakdown( __( 'لید به تفکیک سرویس', 'liferuss-core' ), self::service_counts( $lead_scope ) );
			self::breakdown( __( 'لید به تفکیک منبع', 'liferuss-core' ), Repository::for( 'leads' )->counts_grouped( 'source', $lead_scope ) );
		}
		echo '</div>';
	}

	/**
	 * Universities shadow table.
	 */
	public static function universities(): void {
		self::list_screen(
			'lr_view_university_data',
			'universities',
			array(
				'id'              => 'ID',
				'name_fa'         => 'نام',
				'name_en'         => 'English',
				'slug'            => 'نامک',
				'city_id'         => 'شهر',
				'status'          => 'وضعیت',
				'min_tuition_usd' => 'کمترین شهریه (USD)',
				'updated_at'      => 'به‌روزرسانی',
			),
			array( 'name_fa', 'name_en', 'name_ru', 'slug' )
		);
	}

	/**
	 * Tuition rows.
	 */
	public static function tuition(): void {
		self::list_screen(
			'lr_view_university_data',
			'tuition_fees',
			array(
				'id'            => 'ID',
				'university_id' => 'دانشگاه',
				'field_id'      => 'رشته',
				'degree'        => 'مقطع',
				'language'      => 'زبان',
				'amount'        => 'مبلغ',
				'currency'      => 'ارز',
				'amount_usd'    => 'USD',
				'academic_year' => 'سال',
				'is_current'    => 'جاری',
			),
			array( 'academic_year', 'source', 'currency' )
		);
	}

	/**
	 * Leads, scoped for consultants and operators.
	 */
	public static function leads(): void {
		$scope = Access::apply( array(), Access::lead_scope() );
		self::list_screen(
			'lr_access_crm',
			'leads',
			array(
				'id'            => 'ID',
				'lead_code'     => 'کد',
				'name'          => 'نام',
				'phone'         => 'تلفن',
				'status'        => 'وضعیت',
				'service_id'    => 'سرویس',
				'consultant_id' => 'مسئول',
				'created_at'    => 'تاریخ',
			),
			array( 'lead_code', 'name', 'phone', 'email' ),
			$scope ?? array( 'consultant_id' => -1 )
		);
	}

	/**
	 * Admission requests.
	 */
	public static function admission(): void {
		$scope = Access::apply( array( 'lead_consultant' => true ), Access::admission_scope() );
		self::list_screen(
			'lr_access_admission',
			'admission_requests',
			array(
				'id'            => 'ID',
				'lead_id'       => 'لید',
				'university_id' => 'دانشگاه',
				'field_id'      => 'رشته',
				'degree'        => 'مقطع',
				'stage'         => 'مرحله',
				'created_at'    => 'تاریخ',
			),
			array( 'stage', 'application_ref' ),
			$scope ?? array( 'lead_consultant_id' => -1 )
		);
	}

	/**
	 * Exchange queue.
	 */
	public static function exchange(): void {
		self::request_screen(
			'lr_manage_exchange_requests',
			'exchange_requests',
			array(
				'id'            => 'ID',
				'lead_id'       => 'لید',
				'amount'        => 'مبلغ',
				'currency_from' => 'از',
				'currency_to'   => 'به',
				'stage'         => 'مرحله',
				'operator_id'   => 'اپراتور',
				'created_at'    => 'تاریخ',
			)
		);
	}

	/**
	 * Cargo queue.
	 */
	public static function cargo(): void {
		self::request_screen(
			'lr_manage_cargo_requests',
			'cargo_requests',
			array(
				'id'          => 'ID',
				'lead_id'     => 'لید',
				'direction'   => 'جهت',
				'cargo_type'  => 'نوع',
				'stage'       => 'مرحله',
				'operator_id' => 'اپراتور',
				'created_at'  => 'تاریخ',
			)
		);
	}

	/**
	 * Trade queue.
	 */
	public static function trade(): void {
		self::request_screen(
			'lr_manage_trade_requests',
			'trade_requests',
			array(
				'id'           => 'ID',
				'lead_id'      => 'لید',
				'request_type' => 'نوع',
				'direction'    => 'جهت',
				'stage'        => 'مرحله',
				'operator_id'  => 'اپراتور',
				'created_at'   => 'تاریخ',
			)
		);
	}

	/**
	 * Rows whose last_verified_at is older than 12 months.
	 */
	public static function stale(): void {
		self::guard( 'lr_manage_university_data' );
		$before = gmdate( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS );
		$tables = array(
			'tuition_fees'           => 'شهریه',
			'university_approvals'   => 'تأییدیه',
			'university_rankings'    => 'رتبه‌بندی',
			'dormitory_fees'         => 'خوابگاه',
			'cities'                 => 'شهر',
			'prep_programs'          => 'پادفک',
			'intakes'                => 'ورودی',
			'admission_requirements' => 'شرایط پذیرش',
		);
		Chrome::open( __( 'داده‌های نیازمند بررسی', 'liferuss-core' ), 'دانشگاه‌ها' );
		echo '<p>' . esc_html__( 'رکوردهایی که بیش از ۱۲ ماه بررسی نشده‌اند. از ورود CSV یا فهرست همان جدول آن‌ها را تازه کنید.', 'liferuss-core' ) . '</p><ul class="lr-work">';
		foreach ( $tables as $suffix => $label ) {
			$count = Repository::for( $suffix )->count( array( 'stale_before' => $before ) );
			echo '<li>' . esc_html( $label ) . ': <strong>' . esc_html( (string) $count ) . '</strong></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Module that does not have a full UI yet.
	 */
	public static function placeholder(): void {
		self::guard( 'lr_view_dashboard' );
		Chrome::open( get_admin_page_title(), 'لایف‌روس' );
		Chrome::empty( __( 'فرم این بخش هنوز ساخته نشده. از فهرست کناری یک بخش آماده را باز کنید.', 'liferuss-core' ) );
		Chrome::close();
	}

	/**
	 * Shared request list.
	 *
	 * @param string                $cap     Capability.
	 * @param string                $suffix  Table suffix.
	 * @param array<string, string> $columns Columns.
	 */
	private static function request_screen( string $cap, string $suffix, array $columns ): void {
		$scope = Access::apply( array(), Access::operator_request_scope( $cap ) );
		self::list_screen( $cap, $suffix, $columns, array( 'stage' ), $scope ?? array( 'operator_scope' => -1 ) );
	}

	/**
	 * Render a list table.
	 *
	 * @param string                $cap     Capability.
	 * @param string                $suffix  Table suffix.
	 * @param array<string, string> $columns Columns.
	 * @param string[]              $search  Search columns.
	 * @param array<string, mixed>  $query   Extra query args.
	 */
	private static function list_screen( string $cap, string $suffix, array $columns, array $search, array $query = array() ): void {
		self::guard( $cap );
		$table = new RecordListTable(
			array(
				'suffix'         => $suffix,
				'columns'        => $columns,
				'search_columns' => $search,
				'query_args'     => $query,
			)
		);
		$table->prepare_items();
		Chrome::open( get_admin_page_title(), self::crumb() );
		echo '<form class="lr-filters" method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table->search_box( __( 'جستجو', 'liferuss-core' ), $suffix );
		echo '<div class="lr-scroll">';
		$table->display();
		echo '</div></form>';
		Chrome::close();
	}

	/**
	 * Capability gate.
	 *
	 * @param string $cap Capability.
	 */
	private static function guard( string $cap ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'به این بخش دسترسی ندارید.', 'liferuss-core' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Request counts the current user is allowed to see.
	 */
	private static function request_kpis(): void {
		$queues = array(
			'lr_access_admission'            => array( 'admission_requests', __( 'درخواست پذیرش', 'liferuss-core' ), true ),
			'lr_manage_exchange_requests'    => array( 'exchange_requests', __( 'درخواست صرافی', 'liferuss-core' ), false ),
			'lr_manage_cargo_requests'       => array( 'cargo_requests', __( 'درخواست کارگو', 'liferuss-core' ), false ),
			'lr_manage_trade_requests'       => array( 'trade_requests', __( 'درخواست تجارت', 'liferuss-core' ), false ),
			'lr_manage_immigration_requests' => array( 'immigration_requests', __( 'درخواست مهاجرت', 'liferuss-core' ), false ),
		);
		foreach ( $queues as $cap => $meta ) {
			if ( ! current_user_can( $cap ) && ! ( $meta[2] && current_user_can( 'lr_view_own_admission_requests' ) ) ) {
				continue;
			}
			if ( $meta[2] ) {
				$scope = Access::apply( array( 'lead_consultant' => true ), Access::admission_scope() );
			} else {
				$scope = Access::apply( array(), Access::operator_request_scope( $cap ) );
			}
			self::kpi( $meta[1], null === $scope ? 0 : Repository::for( $meta[0] )->count( $scope ) );
		}
	}

	/**
	 * Map service ids to Persian names.
	 *
	 * @param array<string, mixed> $scope Lead scope.
	 * @return array<string, int>
	 */
	private static function service_counts( array $scope ): array {
		$raw = Repository::for( 'leads' )->counts_grouped( 'service_id', $scope );
		$out = array();
		foreach ( $raw as $id => $count ) {
			$service       = Repository::for( 'services' )->find( (int) $id );
			$label         = $service ? (string) $service['name_fa'] : (string) $id;
			$out[ $label ] = $count;
		}
		return $out;
	}

	/**
	 * Label/count list under the KPI row.
	 *
	 * @param string             $title Heading.
	 * @param array<string, int> $rows  Rows.
	 */
	private static function breakdown( string $title, array $rows ): void {
		echo '<h2>' . esc_html( $title ) . '</h2><ul class="lr-breakdown">';
		if ( ! $rows ) {
			echo '<li>' . esc_html__( 'موردی نیست.', 'liferuss-core' ) . '</li>';
		}
		foreach ( $rows as $label => $count ) {
			echo '<li>' . esc_html( (string) $label ) . ': <strong>' . esc_html( (string) $count ) . '</strong></li>';
		}
		echo '</ul>';
	}

	/**
	 * One KPI card.
	 *
	 * @param string $label Label.
	 * @param int    $value Value.
	 */
	private static function kpi( string $label, int $value ): void {
		echo '<article class="lr-kpi"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( Chrome::num( $value ) ) . '</strong></article>';
	}

	/**
	 * Breadcrumb for the current list.
	 */
	private static function crumb(): string {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $page, array( 'lr-uni', 'lr-universities', 'lr-tuition', 'lr-stale', 'lr-programs' ), true ) ) {
			return 'دانشگاه‌ها';
		}
		return 'لایف‌روس';
	}
}
