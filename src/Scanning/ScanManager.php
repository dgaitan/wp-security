<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\Scanner;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Scoring\ScoringService;

/**
 * Orchestrates scans by enqueueing Action Scheduler jobs.
 *
 * A full scan never runs in a single HTTP request.  ScanManager creates a
 * scan-run record, then enqueues one Action Scheduler job per module.  Each job
 * (runModuleJob) runs one module's checks, writes Finding rows, and advances the
 * run's progress; the job that completes the last module finalises the run by
 * computing and storing the overall score.
 *
 * Action Scheduler is bundled with the plugin (see wp-security.php), so the
 * normal path always enqueues.  The synchronous fallback only triggers in a
 * degraded install where Action Scheduler failed to load, keeping the scanner
 * functional rather than fatal.
 *
 * The React dashboard polls GET /scans/{id} (→ status()) for a live progress bar.
 */
class ScanManager implements Scanner {

	public const ACTION_RUN_MODULE = 'wp_security/run_module';

	public const GROUP = 'wp-security';

	public function __construct(
		private ModuleRegistry $registry,
		private ScanRunRepository $runs,
		private FindingRepository $findings,
		private ScoringService $scoring,
		private Context $context,
	) {}

	public function scanAll(): int {
		$runId     = $this->runs->create( null );
		$moduleIds = array_keys( $this->registry->all() );

		$this->runs->setTotal( $runId, count( $moduleIds ) );
		$this->runs->updateStatus( $runId, 'running' );

		if ( [] === $moduleIds ) {
			$this->finalize( $runId );
			return $runId;
		}

		foreach ( $moduleIds as $moduleId ) {
			$this->enqueue( $runId, $moduleId );
		}

		return $runId;
	}

	public function scanModule( string $moduleId ): int {
		$runId = $this->runs->create( $moduleId );

		$this->runs->setTotal( $runId, 1 );
		$this->runs->updateStatus( $runId, 'running' );
		$this->enqueue( $runId, $moduleId );

		return $runId;
	}

	/**
	 * Action Scheduler callback: run one module's checks and persist findings.
	 *
	 * Defensive by contract — Check::run() must not throw, but any stray error is
	 * contained so a single bad check cannot wedge the whole run.
	 */
	public function runModuleJob( int $runId, string $moduleId ): void {
		$module = $this->registry->get( $moduleId );

		if ( null !== $module ) {
			foreach ( $module->checks() as $check ) {
				try {
					$finding = $check->run( $this->context );
				} catch ( \Throwable $e ) {
					$finding = Finding::skipped(
						$check->id(),
						$check->label(),
						'The check failed to run: ' . $e->getMessage()
					);
				}
				$this->findings->save( $runId, $moduleId, $finding );
			}
		}

		$this->runs->incrementProgress( $runId );

		$run = $this->runs->find( $runId );
		if ( null !== $run && (int) $run['progress'] >= (int) $run['total'] ) {
			$this->finalize( $runId );
		}
	}

	/**
	 * @return array{ status: string, progress: int, total: int }
	 */
	public function status( int $runId ): array {
		$run = $this->runs->find( $runId );

		if ( null === $run ) {
			return [
				'status'   => 'unknown',
				'progress' => 0,
				'total'    => 0,
			];
		}

		return [
			'status'   => (string) $run['status'],
			'progress' => (int) $run['progress'],
			'total'    => (int) $run['total'],
		];
	}

	/**
	 * Score every module from its persisted findings and complete the run.
	 */
	private function finalize( int $runId ): void {
		$rows = $this->findings->forRun( $runId );

		/** @var array<string, array<int, Finding>> $byModule */
		$byModule = [];
		foreach ( $rows as $row ) {
			$moduleId                = (string) $row['module_id'];
			$byModule[ $moduleId ]   = $byModule[ $moduleId ] ?? [];
			$byModule[ $moduleId ][] = Finding::fromArray( $row );
		}

		$scores = [];
		foreach ( $byModule as $moduleId => $moduleFindings ) {
			$scores[ $moduleId ] = $this->scoring->scoreModule( $moduleId, $moduleFindings );
		}

		$overall = $this->scoring->overallScore( $scores );

		$this->runs->updateScore( $runId, $overall->value );
		$this->runs->updateStatus( $runId, 'complete' );

		// Collect CRITICAL findings and fire the scan_complete action so alert
		// handlers can notify site owners without coupling here.
		$allFindings = [];
		foreach ( $byModule as $moduleFindings ) {
			foreach ( $moduleFindings as $finding ) {
				$allFindings[] = $finding;
			}
		}
		$criticals = array_values(
			array_filter(
				$allFindings,
				static fn ( Finding $f ): bool => Severity::CRITICAL === $f->severity
			)
		);

		do_action( 'wp_security/scan_complete', $runId, $criticals );
	}

	/**
	 * Enqueue one module's scan, falling back to synchronous execution only when
	 * Action Scheduler is unavailable.
	 */
	private function enqueue( int $runId, string $moduleId ): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_RUN_MODULE, [ $runId, $moduleId ], self::GROUP );
			return;
		}

		$this->runModuleJob( $runId, $moduleId );
	}
}
