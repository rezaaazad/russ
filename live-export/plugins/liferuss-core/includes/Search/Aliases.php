<?php
/**
 * Extra names for universities that people type in Persian, English, or Russian.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static transliterations. Stored names are indexed as well.
 */
class Aliases {

	/**
	 * Slug => extra search text.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		return array(
			'msu'      => 'مگو ام اس یو msu moscow state lomonosov мгу mgu',
			'spbu'     => 'спбгу spbu spbgu saint petersburg st petersburg سن پترزبورگ',
			'hse'      => 'hse вшэ مدرسه عالی اقتصاد higher school of economics',
			'kfu'      => 'kfu kpfu kazan federal кфу',
			'sechenov' => 'sechenov сеченов first moscow medical',
		);
	}

	/**
	 * Extra alias text for a slug.
	 *
	 * @param string $slug Post or catalog slug.
	 */
	public static function for_slug( string $slug ): string {
		$map = self::map();
		return $map[ $slug ] ?? '';
	}
}
