<?php
/**
 * CRM labels and the form-type map.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Statuses, stages, and which service a public form writes.
 */
class Catalog {

	/**
	 * Nine lead statuses from the ERD.
	 *
	 * @return array<string, string>
	 */
	public static function lead_statuses(): array {
		return array(
			'new'       => 'جدید',
			'contacted' => 'تماس گرفته شد',
			'qualified' => 'واجد شرایط',
			'follow_up' => 'پیگیری',
			'documents' => 'مدارک',
			'contract'  => 'قرارداد',
			'paid'      => 'پرداخت',
			'completed' => 'انجام شد',
			'lost'      => 'از دست رفته',
		);
	}

	/**
	 * Lead priorities.
	 *
	 * @return array<string, string>
	 */
	public static function priorities(): array {
		return array(
			'low'    => 'کم',
			'normal' => 'عادی',
			'high'   => 'زیاد',
			'urgent' => 'فوری',
		);
	}

	/**
	 * Closed statuses that start the document purge clock.
	 *
	 * @return string[]
	 */
	public static function closed_statuses(): array {
		return array( 'completed', 'lost' );
	}

	/**
	 * Public form type => service slug and request table suffix.
	 *
	 * Theme consult is a study enquiry, so it lands on the admission service.
	 * Freight lands on the cargo service. Contact has no request row.
	 *
	 * @return array<string, array{service: string, request: string}>
	 */
	public static function forms(): array {
		return array(
			'consult'     => array(
				'service' => 'admission',
				'request' => 'admission_requests',
			),
			'admission'   => array(
				'service' => 'admission',
				'request' => 'admission_requests',
			),
			'freight'     => array(
				'service' => 'cargo',
				'request' => 'cargo_requests',
			),
			'cargo'       => array(
				'service' => 'cargo',
				'request' => 'cargo_requests',
			),
			'trade'       => array(
				'service' => 'trade',
				'request' => 'trade_requests',
			),
			'exchange'    => array(
				'service' => 'exchange',
				'request' => 'exchange_requests',
			),
			'immigration' => array(
				'service' => 'migration',
				'request' => '',
			),
			'contact'     => array(
				'service' => 'contact',
				'request' => '',
			),
		);
	}

	/**
	 * Stage labels for one request table.
	 *
	 * @param string $suffix Table suffix.
	 * @return array<string, string>
	 */
	public static function stages( string $suffix ): array {
		$map = array(
			'admission_requests' => array(
				'new'             => 'جدید',
				'collecting_docs' => 'جمع مدارک',
				'submitted'       => 'ارسال شد',
				'offer'           => 'پذیرش',
				'invitation'      => 'دعوت‌نامه',
				'visa'            => 'ویزا',
				'enrolled'        => 'ثبت‌نام',
				'rejected'        => 'رد',
				'cancelled'       => 'لغو',
			),
			'exchange_requests'  => array(
				'new'         => 'جدید',
				'quoted'      => 'اعلام نرخ',
				'accepted'    => 'پذیرفته',
				'in_progress' => 'در حال انجام',
				'done'        => 'انجام شد',
				'cancelled'   => 'لغو',
			),
			'cargo_requests'     => array(
				'new'        => 'جدید',
				'quoted'     => 'اعلام قیمت',
				'confirmed'  => 'تأیید',
				'picked_up'  => 'تحویل گرفته شد',
				'in_transit' => 'در مسیر',
				'customs'    => 'گمرک',
				'delivered'  => 'تحویل شد',
				'cancelled'  => 'لغو',
			),
			'trade_requests'     => array(
				'new'           => 'جدید',
				'reviewing'     => 'بررسی',
				'sourcing'      => 'سورسینگ',
				'proposal_sent' => 'پیشنهاد ارسال شد',
				'negotiating'   => 'مذاکره',
				'in_progress'   => 'در حال انجام',
				'done'          => 'انجام شد',
				'cancelled'     => 'لغو',
			),
		);
		return $map[ $suffix ] ?? array();
	}

	/**
	 * Legacy liferuss_lead status to the nine-status pipeline.
	 *
	 * @param string $status Old meta value.
	 */
	public static function legacy_status( string $status ): string {
		$status = str_replace( '-', '_', $status );
		$map    = array(
			'new'         => 'new',
			'in_progress' => 'contacted',
			'done'        => 'completed',
		);
		return $map[ $status ] ?? 'new';
	}

	/**
	 * Legacy form slug to a current form type.
	 *
	 * @param string $slug Old type slug.
	 */
	public static function legacy_form( string $slug ): string {
		$slug = sanitize_key( $slug );
		if ( isset( self::forms()[ $slug ] ) ) {
			return $slug;
		}
		return 'consult';
	}
}
