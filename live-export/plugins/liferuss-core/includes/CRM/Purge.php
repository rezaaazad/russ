<?php
/**
 * Daily purge of lead documents 12 months after the lead closes.
 *
 * @package LifeRussCore
 */

namespace LifeRuss\Core\CRM;

use LifeRuss\Core\Repositories\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron deletes private files whose purge_after date has passed.
 */
class Purge {

	/**
	 * Schedule the daily job.
	 */
	public static function hooks(): void {
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( 'lr_purge_lead_files', array( self::class, 'run' ) );
	}

	/**
	 * Register the event once.
	 */
	public static function schedule(): void {
		if ( wp_next_scheduled( 'lr_purge_lead_files' ) ) {
			return;
		}
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lr_purge_lead_files' );
	}

	/**
	 * Purge due files. Safe to run more than once.
	 *
	 * @return int Rows purged.
	 */
	public static function run(): int {
		$batch = Repository::for( 'lead_files' )->paginate(
			array(
				'purge_due' => true,
				'page'      => 1,
				'per_page'  => 100,
				'orderby'   => 'id',
				'order'     => 'ASC',
			)
		);
		$n     = 0;
		foreach ( $batch['items'] as $file ) {
			Files::purge_row( $file );
			++$n;
		}
		return $n;
	}
}
