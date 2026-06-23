<?php

declare( strict_types=1 );

namespace WPSecurity\Contracts;

/**
 * Scanner contract.
 *
 * The scanner knows how to run a Module (or all modules) and persist the
 * resulting Findings to a ScanRun.  Concrete implementation:
 * WPSecurity\Scanning\ScanManager.
 */
interface Scanner {

    /**
     * Enqueue a full scan across all registered modules.
     * Returns the newly created run ID.
     */
    public function scanAll(): int;

    /**
     * Enqueue a scan for a single module.
     * Returns the newly created run ID.
     */
    public function scanModule( string $moduleId ): int;

    /**
     * Retrieve the status of a running or completed scan run.
     *
     * @return array{ status: string, progress: int, total: int }
     */
    public function status( int $runId ): array;
}
