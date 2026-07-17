<?php
/*
 * Feature: Homepage Reachability Check — Sprint 10
 *
 * Scenario: homepage_http_status is null → SKIPPED
 * Scenario: status is 200 → PASS
 * Scenario: status is not 200 → WARN/CRITICAL
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\HomepageReachabilityCheck;
use WPSecurity\Tests\Support\MockContext;

final class HomepageReachabilityCheckTest extends TestCase {

	private HomepageReachabilityCheck $check;

	protected function setUp(): void {
		$this->check = new HomepageReachabilityCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.homepage_reachability', $this->check->id() );
	}

	public function test_null_status_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_http_status' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_status_200_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'homepage_http_status' => 200 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_status_500_returns_warn_critical(): void {
		$ctx     = new MockContext( values: [ 'homepage_http_status' => 500 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
		$this->assertSame( 500, $finding->evidence->get( 'http_status' ) );
	}
}
