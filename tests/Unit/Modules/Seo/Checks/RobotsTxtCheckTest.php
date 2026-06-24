<?php
/*
 * Feature: RobotsTxtCheck — S7-5 (robots.txt)
 *
 * Scenario: robots_txt_status is null returns SKIPPED
 *   Given a MockContext with robots_txt_status = null
 *   When RobotsTxtCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: robots_txt_status 200 returns PASS
 *   Given a MockContext with robots_txt_status = 200
 *   When RobotsTxtCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: robots_txt_status 404 returns WARN/MEDIUM
 *   Given a MockContext with robots_txt_status = 404
 *   When RobotsTxtCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: evidence contains the HTTP status on warn
 *   Given a MockContext with robots_txt_status = 403
 *   When RobotsTxtCheck::run() is called
 *   Then evidence['http_status'] is 403
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Seo\Checks\RobotsTxtCheck;
use WPSecurity\Tests\Support\MockContext;

final class RobotsTxtCheckTest extends TestCase {

	private RobotsTxtCheck $check;

	protected function setUp(): void {
		$this->check = new RobotsTxtCheck();
	}

	public function test_id_is_seo_robots_txt(): void {
		$this->assertSame( 'seo.robots_txt', $this->check->id() );
	}

	public function test_null_status_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'robots_txt_status' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_http_200_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'robots_txt_status' => 200 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_http_404_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'robots_txt_status' => 404 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_evidence_contains_http_status(): void {
		$ctx     = new MockContext( values: [ 'robots_txt_status' => 403 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( 403, $finding->evidence['http_status'] );
	}
}
