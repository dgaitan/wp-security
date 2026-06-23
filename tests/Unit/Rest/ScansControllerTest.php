<?php
/**
 * Unit tests for ScansController.
 *
 * Feature: ScansController — Sprint 2 REST scan lifecycle
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the REST request/response stubs are available
 *
 *   Scenario: Starting a full scan returns 202 with a run id
 *     Given the module parameter "all"
 *     When POST /scans is handled
 *     Then the response status is 202
 *     And the body contains the new run_id
 *
 *   Scenario: Starting a module scan delegates to scanModule
 *     Given the module parameter "server"
 *     When POST /scans is handled
 *     Then the scanner is asked to scan only the "server" module
 *
 *   Scenario: Polling a run returns its status, progress, and total
 *     Given an existing run
 *     When GET /scans/{id} is handled
 *     Then the body contains status, progress, and total
 *
 *   Scenario: Polling an unknown run returns 404
 *     Given no run with the requested id
 *     When GET /scans/{id} is handled
 *     Then a 404 error is returned
 *
 *   Scenario: History returns the recent runs
 *     Given several runs
 *     When GET /history is handled
 *     Then the body lists the runs
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WPSecurity\Contracts\Scanner;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\ScansController;
use WPSecurity\Tests\Support\FakeWpdb;

final class ScansControllerTest extends TestCase {

	public function test_create_full_scan_returns_202_with_run_id(): void {
		$scanner    = $this->scanner( 7 );
		$controller = new ScansController( $scanner, new ScanRunRepository( new FakeWpdb() ) );

		$response = $controller->create( $this->request( [ 'module' => 'all' ] ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( [ 'run_id' => 7 ], $response->get_data() );
		$this->assertSame( 'all', $scanner->lastCall );
	}

	public function test_create_module_scan_delegates_to_scan_module(): void {
		$scanner    = $this->scanner( 9 );
		$controller = new ScansController( $scanner, new ScanRunRepository( new FakeWpdb() ) );

		$response = $controller->create( $this->request( [ 'module' => 'server' ] ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( [ 'run_id' => 9 ], $response->get_data() );
		$this->assertSame( 'server', $scanner->lastCall );
	}

	public function test_status_returns_progress_for_existing_run(): void {
		$repo  = new ScanRunRepository( new FakeWpdb() );
		$runId = $repo->create( null );
		$repo->setTotal( $runId, 3 );

		$controller = new ScansController( $this->scanner( $runId ), $repo );

		$response = $controller->status( $this->request( [ 'id' => $runId ] ) );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'progress', $data );
		$this->assertArrayHasKey( 'total', $data );
	}

	public function test_status_returns_404_for_unknown_run(): void {
		$controller = new ScansController( $this->scanner( 0 ), new ScanRunRepository( new FakeWpdb() ) );

		$result = $controller->status( $this->request( [ 'id' => 999 ] ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( [ 'status' => 404 ], $result->get_error_data() );
	}

	public function test_history_lists_runs(): void {
		$repo = new ScanRunRepository( new FakeWpdb() );
		$repo->create( null );
		$repo->create( null );

		$controller = new ScansController( $this->scanner( 0 ), $repo );

		$response = $controller->history( $this->request( [] ) );

		$this->assertCount( 2, $response->get_data() );
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private function request( array $params ): WP_REST_Request {
		return new WP_REST_Request( $params );
	}

	/**
	 * A recordable Scanner whose scanAll/scanModule return a fixed run id.
	 */
	private function scanner( int $runId ): Scanner {
		return new class( $runId ) implements Scanner {

			public string $lastCall = '';

			public function __construct( private int $runId ) {}

			public function scanAll(): int {
				$this->lastCall = 'all';
				return $this->runId;
			}

			public function scanModule( string $moduleId ): int {
				$this->lastCall = $moduleId;
				return $this->runId;
			}

			/**
			 * @return array{ status: string, progress: int, total: int }
			 */
			public function status( int $runId ): array {
				return [
					'status'   => 'running',
					'progress' => 1,
					'total'    => 3,
				];
			}
		};
	}
}
