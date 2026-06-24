<?php

/**
 * Unit tests for DashboardController.
 *
 * Feature: DashboardController — GET /wp-security/v1/dashboard
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the REST request/response stubs are available
 *
 *   Scenario: GET /dashboard returns 200 with all expected keys
 *     Given a GET request to /wp-security/v1/dashboard
 *     When DashboardController::get() is called without a ScanRunRepository
 *     Then the response status is 200
 *     And the body contains key "overall_score"
 *     And the body contains key "module_scores"
 *     And the body contains key "top_findings"
 *     And the body contains key "last_scan_at"
 *     And the body contains key "trend"
 *
 *   Scenario: trend is empty when no repository is injected
 *     Given DashboardController is constructed without a ScanRunRepository
 *     When get() is called
 *     Then "trend" is an empty array
 *
 *   Scenario: trend is populated from completed scan runs
 *     Given a ScanRunRepository with 2 completed runs (scores 72 and 91)
 *     When DashboardController::get() is called
 *     Then the "trend" array has 2 entries
 *     And each entry has "date" and "score" keys
 *     And the entries are ordered oldest first
 *
 *   Scenario: trend excludes non-complete runs
 *     Given a ScanRunRepository with one running and one complete run
 *     When DashboardController::get() is called
 *     Then the "trend" array has 1 entry (only the complete run)
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\DashboardController;
use WPSecurity\Tests\Support\FakeWpdb;

final class DashboardControllerTest extends TestCase {

	public function test_get_returns_200(): void {
		$controller = new DashboardController();
		$response   = $controller->get( new WP_REST_Request() );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_get_contains_all_required_keys(): void {
		$controller = new DashboardController();
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'overall_score', $data );
		$this->assertArrayHasKey( 'module_scores', $data );
		$this->assertArrayHasKey( 'top_findings', $data );
		$this->assertArrayHasKey( 'last_scan_at', $data );
		$this->assertArrayHasKey( 'trend', $data );
	}

	public function test_get_returns_array_values_for_collection_keys(): void {
		$controller = new DashboardController();
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertIsArray( $data['module_scores'] );
		$this->assertIsArray( $data['top_findings'] );
		$this->assertIsArray( $data['trend'] );
	}

	public function test_trend_is_empty_without_repository(): void {
		$controller = new DashboardController();
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertSame( [], $data['trend'] );
	}

	public function test_trend_is_populated_from_completed_runs(): void {
		$runs = new ScanRunRepository( new FakeWpdb() );

		// Insert two completed runs.
		$id1 = $runs->create( null );
		$runs->updateScore( $id1, 72 );
		$runs->updateStatus( $id1, 'complete' );

		$id2 = $runs->create( null );
		$runs->updateScore( $id2, 91 );
		$runs->updateStatus( $id2, 'complete' );

		$controller = new DashboardController( $runs );
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertCount( 2, $data['trend'] );
		$this->assertArrayHasKey( 'date', $data['trend'][0] );
		$this->assertArrayHasKey( 'score', $data['trend'][0] );
	}

	public function test_trend_excludes_non_complete_runs(): void {
		$runs = new ScanRunRepository( new FakeWpdb() );

		// One running (not complete) and one complete.
		$runId1 = $runs->create( null );
		$runs->updateStatus( $runId1, 'running' );

		$runId2 = $runs->create( null );
		$runs->updateScore( $runId2, 85 );
		$runs->updateStatus( $runId2, 'complete' );

		$controller = new DashboardController( $runs );
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertCount( 1, $data['trend'] );
		$this->assertSame( 85, $data['trend'][0]['score'] );
	}

	public function test_trend_score_is_integer(): void {
		$runs  = new ScanRunRepository( new FakeWpdb() );
		$runId = $runs->create( null );
		$runs->updateScore( $runId, 75 );
		$runs->updateStatus( $runId, 'complete' );

		$controller = new DashboardController( $runs );
		$data       = $controller->get( new WP_REST_Request() )->get_data();

		$this->assertIsArray( $data );
		$this->assertIsInt( $data['trend'][0]['score'] );
		$this->assertSame( 75, $data['trend'][0]['score'] );
	}
}
