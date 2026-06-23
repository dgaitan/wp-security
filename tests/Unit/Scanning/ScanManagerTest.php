<?php
/**
 * Unit tests for ScanManager.
 *
 * Feature: ScanManager — Sprint 2 background scanning
 *   Background:
 *     Given the WP Security plugin is installed
 *     And Action Scheduler functions are stubbed and recordable
 *
 *   Scenario: Starting a single-module scan enqueues one job
 *     Given a registered module "server"
 *     When scanModule("server") is called
 *     Then a run is created and an Action Scheduler job is enqueued for it
 *     And status() reports the run as running with total 1
 *
 *   Scenario: Starting a full scan enqueues one job per module
 *     Given two registered modules
 *     When scanAll() is called
 *     Then one Action Scheduler job is enqueued per module
 *     And status() reports total 2
 *
 *   Scenario: A module job runs its checks, persists findings, and finalises
 *     Given a single-module run with one failing CRITICAL check
 *     When runModuleJob() runs for the last module
 *     Then the finding is persisted
 *     And the run is marked complete with the computed overall score
 *
 *   Scenario: A full scan with no modules completes immediately
 *     Given no registered modules
 *     When scanAll() is called
 *     Then no jobs are enqueued
 *     And the run is marked complete with total 0
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\Module;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Scanning\ScanContext;
use WPSecurity\Scanning\ScanManager;
use WPSecurity\Scoring\ScoringService;
use WPSecurity\Tests\Support\FakeWpdb;

final class ScanManagerTest extends TestCase {

	private ScanRunRepository $runs;

	private FindingRepository $findings;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_as_actions'] = [];
	}

	protected function tearDown(): void {
		remove_all_filters( 'wp_security/modules' );
		unset( $GLOBALS['wp_security_test_as_actions'] );
		parent::tearDown();
	}

	public function test_scan_module_enqueues_one_job(): void {
		$this->registerModules( [ $this->module( 'server', [ $this->check( 'server.x', Status::PASS, Severity::INFO ) ] ) ] );
		$manager = $this->manager();

		$runId = $manager->scanModule( 'server' );

		$this->assertSame( 1, $runId );
		$this->assertCount( 1, $GLOBALS['wp_security_test_as_actions'] );
		$this->assertSame( ScanManager::ACTION_RUN_MODULE, $GLOBALS['wp_security_test_as_actions'][0]['hook'] );
		$this->assertSame( [ $runId, 'server' ], $GLOBALS['wp_security_test_as_actions'][0]['args'] );

		$status = $manager->status( $runId );
		$this->assertSame( 'running', $status['status'] );
		$this->assertSame( 1, $status['total'] );
		$this->assertSame( 0, $status['progress'] );
	}

	public function test_scan_all_enqueues_one_job_per_module(): void {
		$this->registerModules(
			[
				$this->module( 'server', [ $this->check( 'server.x', Status::PASS, Severity::INFO ) ] ),
				$this->module( 'headers', [ $this->check( 'headers.y', Status::PASS, Severity::INFO ) ] ),
			]
		);
		$manager = $this->manager();

		$runId = $manager->scanAll();

		$this->assertCount( 2, $GLOBALS['wp_security_test_as_actions'] );
		$this->assertSame( 2, $manager->status( $runId )['total'] );
	}

	public function test_module_job_persists_findings_and_finalises_run(): void {
		$this->registerModules( [ $this->module( 'server', [ $this->check( 'server.bad', Status::FAIL, Severity::CRITICAL ) ] ) ] );
		$manager = $this->manager();

		$runId = $manager->scanModule( 'server' );
		$manager->runModuleJob( $runId, 'server' );

		$persisted = $this->findings->forRun( $runId );
		$this->assertCount( 1, $persisted );
		$this->assertSame( 'server.bad', $persisted[0]['check_id'] );

		$status = $manager->status( $runId );
		$this->assertSame( 'complete', $status['status'] );
		$this->assertSame( 1, $status['progress'] );

		$run = $this->runs->find( $runId );
		$this->assertNotNull( $run );
		$this->assertSame( 60, $run['overall_score'] );
	}

	public function test_full_scan_with_no_modules_completes_immediately(): void {
		$manager = $this->manager();

		$runId = $manager->scanAll();

		$this->assertCount( 0, $GLOBALS['wp_security_test_as_actions'] );

		$status = $manager->status( $runId );
		$this->assertSame( 'complete', $status['status'] );
		$this->assertSame( 0, $status['total'] );
	}

	private function manager(): ScanManager {
		$this->runs     = new ScanRunRepository( new FakeWpdb() );
		$this->findings = new FindingRepository( new FakeWpdb() );

		return new ScanManager(
			new ModuleRegistry(),
			$this->runs,
			$this->findings,
			new ScoringService(),
			new ScanContext(),
		);
	}

	/**
	 * @param array<int, Module> $modules
	 */
	private function registerModules( array $modules ): void {
		add_filter(
			'wp_security/modules',
			static fn ( array $existing ): array => array_merge( $existing, $modules )
		);
	}

	/**
	 * @param array<int, Check> $checks
	 */
	private function module( string $id, array $checks ): Module {
		return new class( $id, $checks ) implements Module {

			/**
			 * @param array<int, Check> $checks
			 */
			public function __construct( private string $id, private array $checks ) {}

			public function id(): string {
				return $this->id;
			}

			public function label(): string {
				return ucfirst( $this->id );
			}

			public function icon(): string {
				return 'dashicons-shield';
			}

			public function checks(): iterable {
				return $this->checks;
			}
		};
	}

	private function check( string $id, Status $status, Severity $severity ): Check {
		$finding = new Finding( $id, $status, $severity, 'Title', 'Description.', 'Recommendation.' );

		return new class( $id, $finding ) implements Check {

			public function __construct( private string $id, private Finding $finding ) {}

			public function id(): string {
				return $this->id;
			}

			public function label(): string {
				return 'Check ' . $this->id;
			}

			public function run( Context $context ): Finding {
				return $this->finding;
			}
		};
	}
}
