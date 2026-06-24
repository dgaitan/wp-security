<?php
/*
 * Feature: ModulesController::ingestExternal — S7-6 (external findings ingest)
 *
 * Scenario: POST with valid findings creates a scan run and persists findings
 *   Given a POST request with module_id = 'accessibility' and two findings
 *   When ingestExternal() is called
 *   Then the response status is 204
 *   And two findings are stored in the findings table
 *   And a scan run is created with status 'complete'
 *
 * Scenario: POST with empty findings array creates a complete run with no findings
 *   Given a POST request with module_id = 'accessibility' and an empty findings array
 *   When ingestExternal() is called
 *   Then the response status is 204
 *   And the scan run status is 'complete'
 *
 * Scenario: POST with malformed finding entries skips bad entries
 *   Given a POST request containing one valid and one non-array finding
 *   When ingestExternal() is called
 *   Then the response status is 204
 *   And only the valid finding is persisted
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\ModulesController;
use WPSecurity\Tests\Support\FakeWpdb;

final class ModulesControllerTest extends TestCase {

	private FakeWpdb $wpdb;
	private FindingRepository $findingRepo;
	private ScanRunRepository $scanRunRepo;
	private ModuleRegistry $registry;

	protected function setUp(): void {
		parent::setUp();

		$this->wpdb        = new FakeWpdb();
		$this->findingRepo = new FindingRepository( $this->wpdb );
		$this->scanRunRepo = new ScanRunRepository( $this->wpdb );
		$this->registry    = new ModuleRegistry();

		$GLOBALS['wp_security_test_filters']               = [];
		$GLOBALS['wp_security_test_rest_routes']           = [];
		$GLOBALS['wp_security_test_can']['manage_options'] = true;
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset(
			$GLOBALS['wp_security_test_filters'],
			$GLOBALS['wp_security_test_rest_routes'],
			$GLOBALS['wp_security_test_can']
		);
	}

	private function controller(): ModulesController {
		return new ModulesController( $this->registry, $this->findingRepo, $this->scanRunRepo );
	}

	public function test_register_creates_findings_external_route(): void {
		$this->controller()->register();

		$routes = array_column( $GLOBALS['wp_security_test_rest_routes'] ?? [], 'route' );
		$this->assertContains( '/findings/external', $routes );
	}

	public function test_ingest_external_returns_204(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'module_id', 'accessibility' );
		$request->set_param( 'findings', [] );

		$response = $this->controller()->ingestExternal( $request );

		$this->assertSame( 204, $response->get_status() );
	}

	public function test_ingest_external_creates_scan_run(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'module_id', 'accessibility' );
		$request->set_param( 'findings', [] );

		$this->controller()->ingestExternal( $request );

		$run = $this->scanRunRepo->find( 1 );

		$this->assertNotNull( $run );
		$this->assertSame( 'complete', $run['status'] );
		$this->assertSame( 'accessibility', $run['module_id'] );
	}

	public function test_ingest_external_persists_findings(): void {
		$findings = [
			[
				'check_id'       => 'accessibility.button-name',
				'status'         => 'fail',
				'severity'       => 'high',
				'title'          => 'Buttons must have text',
				'description'    => 'Ensure buttons have discernible text.',
				'recommendation' => 'Add aria-label or visible text.',
			],
			[
				'check_id'       => 'accessibility.color-contrast',
				'status'         => 'fail',
				'severity'       => 'medium',
				'title'          => 'Insufficient colour contrast',
				'description'    => 'Text contrast ratio is below 4.5:1.',
				'recommendation' => 'Increase the contrast ratio.',
			],
		];

		$request = new WP_REST_Request();
		$request->set_param( 'module_id', 'accessibility' );
		$request->set_param( 'findings', $findings );

		$this->controller()->ingestExternal( $request );

		$stored = $this->findingRepo->forRun( 1 );

		$this->assertCount( 2, $stored );
		$this->assertSame( 'accessibility.button-name', $stored[0]['check_id'] );
		$this->assertSame( 'accessibility.color-contrast', $stored[1]['check_id'] );
	}

	public function test_ingest_external_skips_non_array_entries(): void {
		$findings = [
			[
				'check_id'       => 'accessibility.button-name',
				'status'         => 'fail',
				'severity'       => 'high',
				'title'          => 'Buttons must have text',
				'description'    => '',
				'recommendation' => '',
			],
			'not-an-array',
		];

		$request = new WP_REST_Request();
		$request->set_param( 'module_id', 'accessibility' );
		$request->set_param( 'findings', $findings );

		$this->controller()->ingestExternal( $request );

		$stored = $this->findingRepo->forRun( 1 );

		$this->assertCount( 1, $stored );
	}
}
