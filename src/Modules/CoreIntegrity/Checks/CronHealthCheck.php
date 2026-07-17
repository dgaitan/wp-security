<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags a stalled scheduling layer: overdue WP-Cron events while WP-Cron's
 * own pseudo-cron trigger is disabled, or an excessive backlog of overdue
 * Action Scheduler actions (the mechanism this plugin's own scans and
 * remediation jobs rely on).
 */
class CronHealthCheck implements Check {

	/**
	 * Overdue Action Scheduler actions beyond this count are treated as a
	 * genuine backlog rather than the normal handful of in-flight jobs.
	 */
	private const OVERDUE_ACTION_THRESHOLD = 50;

	public function id(): string {
		return 'core_integrity.cron_health';
	}

	public function label(): string {
		return __( 'Scheduled Tasks', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$cronDisabled       = (bool) $context->get( 'wp_cron_disabled' );
		$cronPendingCount   = $context->get( 'cron_pending_count' );
		$overdueActionCount = $context->get( 'action_scheduler_overdue_count' );

		if ( null === $cronPendingCount && null === $overdueActionCount ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine the state of WP-Cron or Action Scheduler.'
			);
		}

		$evidence = ( new Evidence() )
			->add( 'wp_cron_disabled', $cronDisabled )
			->add( 'overdue_wp_cron_events', $cronPendingCount )
			->add( 'overdue_action_scheduler_actions', $overdueActionCount );

		if ( $cronDisabled && is_int( $cronPendingCount ) && $cronPendingCount > 0 ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: number of overdue WP-Cron events */
					__( 'WP-Cron is disabled (DISABLE_WP_CRON) and %d scheduled event(s) are overdue.', 'wp-security' ),
					$cronPendingCount
				),
				recommendation: __( 'Confirm a real system cron job is calling wp-cron.php on a schedule, or re-enable WP-Cron.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		if ( is_int( $overdueActionCount ) && $overdueActionCount > self::OVERDUE_ACTION_THRESHOLD ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: number of overdue Action Scheduler actions */
					__( '%d Action Scheduler actions are overdue, well beyond normal in-flight volume.', 'wp-security' ),
					$overdueActionCount
				),
				recommendation: __( 'Investigate why Action Scheduler jobs are not processing — a stalled queue can silently block this plugin\'s own scans as well as other plugins that depend on it.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::PASS,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    __( 'Scheduled tasks appear to be functioning normally.', 'wp-security' ),
			recommendation: '',
			evidence:       $evidence,
		);
	}
}
