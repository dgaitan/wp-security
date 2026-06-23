<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

/**
 * Manages the recurring automatic-scan schedule via Action Scheduler.
 *
 * The interval is read from the plugin settings (`scan_frequency`), defaulting
 * to daily.  All scheduling is guarded by function_exists() so the plugin stays
 * safe even if Action Scheduler is unavailable; in normal installs it is bundled
 * and always present (see wp-security.php).
 */
class Scheduler {

	public const ACTION_HOOK = 'wp_security/run_scheduled_scan';

	public const GROUP = 'wp-security';

	public const OPTION_KEY = 'wp_security_settings';

	/**
	 * Schedule the recurring scan, unless one is already scheduled.
	 * Called from Plugin::activate().
	 */
	public function schedule(): void {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( function_exists( 'as_next_scheduled_action' )
			&& false !== as_next_scheduled_action( self::ACTION_HOOK, [], self::GROUP ) ) {
			return;
		}

		$interval = $this->intervalSeconds();

		as_schedule_recurring_action( time() + $interval, $interval, self::ACTION_HOOK, [], self::GROUP );
	}

	/**
	 * Remove the recurring scan from the queue.
	 * Called from Plugin::deactivate().
	 */
	public function unschedule(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::ACTION_HOOK, [], self::GROUP );
		}
	}

	/**
	 * Translate the configured scan frequency into seconds.
	 */
	public function intervalSeconds(): int {
		$settings  = get_option( self::OPTION_KEY, [] );
		$frequency = is_array( $settings ) && isset( $settings['scan_frequency'] )
			? (string) $settings['scan_frequency']
			: 'daily';

		return match ( $frequency ) {
			'hourly' => HOUR_IN_SECONDS,
			'weekly' => WEEK_IN_SECONDS,
			default  => DAY_IN_SECONDS,
		};
	}
}
