<?php

/**
 * Unit tests for DashboardController.
 *
 * Feature: DashboardController — GET /wp-security/v1/dashboard (Sprint 3)
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the REST request/response stubs are available
 *
 *   Scenario: GET /dashboard returns 200 with all expected keys
 *     Given a GET request to /wp-security/v1/dashboard
 *     When DashboardController::get() is called
 *     Then the response status is 200
 *     And the body contains key "overall_score"
 *     And the body contains key "module_scores"
 *     And the body contains key "top_findings"
 *     And the body contains key "last_scan_at"
 *     And the body contains key "trend"
 *
 *   Scenario: GET /dashboard module_scores is an array
 *     Given a GET request to /wp-security/v1/dashboard
 *     When DashboardController::get() is called
 *     Then the "module_scores" value is an array
 *     And the "top_findings" value is an array
 *     And the "trend" value is an array
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WPSecurity\Rest\DashboardController;

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
}
