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
 * Surfaces recent PHP error/warning log entries as a proxy for "dashboard
 * notices, errors, and warnings" — a genuine wp-admin notice banner is
 * rendered at request time inside an authenticated admin screen and isn't
 * enumerable from a headless scan, so the error log is the available
 * PHP-side signal instead.
 */
class DashboardNoticesCheck implements Check {

	public function id(): string {
		return 'core_integrity.dashboard_notices';
	}

	public function label(): string {
		return __( 'Dashboard Notices', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, string>|null $lines */
		$lines = $context->get( 'php_error_log_tail' );

		if ( null === $lines ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::INFO,
				severity:       Severity::INFO,
				title:          $this->label(),
				description:    __( 'No readable PHP error log was found; enable WP_DEBUG_LOG to allow this check to review recent errors.', 'wp-security' ),
				recommendation: '',
			);
		}

		$flagged = array_values(
			array_filter(
				$lines,
				static fn ( string $line ): bool => (bool) preg_match( '/\b(fatal error|error|warning)\b/i', $line )
			)
		);

		if ( [] === $flagged ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No recent errors or warnings found in the PHP error log.', 'wp-security' )
			);
		}

		$count = count( $flagged );

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of flagged log lines */
				__( '%d recent PHP error/warning log line(s) found.', 'wp-security' ),
				$count
			),
			recommendation: __( 'Review the PHP error log and resolve the underlying issues; recurring errors can indicate a broken plugin/theme update or misconfiguration.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'recent_log_lines', array_slice( $flagged, -10 ) ),
		);
	}
}
