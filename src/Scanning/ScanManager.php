<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

use WPSecurity\Contracts\Scanner;

/**
 * Orchestrates scans by enqueueing Action Scheduler jobs.
 *
 * A full scan never runs in a single HTTP request.  ScanManager creates a
 * ScanRun record, then enqueues one Action Scheduler job per module.  Each
 * job runs one module's checks in small batches, writing Finding rows as it
 * completes and updating the run's progress.
 *
 * The React dashboard polls GET /scans/{id} to render a live progress bar.
 *
 * TODO Sprint 2: implement scanAll(), scanModule(), status(), and the
 * Action Scheduler callbacks.  Requires Action Scheduler to be available
 * (ships with WooCommerce; we will bundle or require it separately).
 */
class ScanManager implements Scanner {

	public function scanAll(): int {
		// TODO Sprint 2.
		throw new \RuntimeException( 'ScanManager::scanAll() not yet implemented.' );
	}

	public function scanModule( string $moduleId ): int {
		// TODO Sprint 2.
		throw new \RuntimeException( 'ScanManager::scanModule() not yet implemented.' );
	}

	/**
	 * @return array{ status: string, progress: int, total: int }
	 */
	public function status( int $runId ): array {
		// TODO Sprint 2.
		return [
			'status'   => 'unknown',
			'progress' => 0,
			'total'    => 0,
		];
	}
}
