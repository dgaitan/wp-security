<?php
/*
 * Feature: TtfbCheck — S7-1 (Page Speed)
 *
 * Scenario: ttfb_ms is null returns SKIPPED
 *   Given a MockContext with ttfb_ms = null
 *   When TtfbCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: TTFB below 200 ms returns PASS
 *   Given a MockContext with ttfb_ms = 150.0
 *   When TtfbCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: TTFB between 200 and 800 ms returns WARN/MEDIUM
 *   Given a MockContext with ttfb_ms = 400.0
 *   When TtfbCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: TTFB above 800 ms returns FAIL/HIGH
 *   Given a MockContext with ttfb_ms = 1200.0
 *   When TtfbCheck::run() is called
 *   Then the Finding status is "fail"
 *   And the Finding severity is "high"
 *
 * Scenario: evidence contains the measured TTFB on non-pass outcomes
 *   Given a MockContext with ttfb_ms = 900.0
 *   When TtfbCheck::run() is called
 *   Then evidence['ttfb_ms'] is 900.0
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Performance\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Performance\Checks\TtfbCheck;
use WPSecurity\Tests\Support\MockContext;

final class TtfbCheckTest extends TestCase {

	private TtfbCheck $check;

	protected function setUp(): void {
		$this->check = new TtfbCheck();
	}

	public function test_id_is_performance_ttfb(): void {
		$this->assertSame( 'performance.ttfb', $this->check->id() );
	}

	public function test_null_ttfb_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_fast_ttfb_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 150.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_moderate_ttfb_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 400.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_slow_ttfb_returns_fail_high(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 1200.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_exactly_200ms_returns_warn(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 200.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
	}

	public function test_exactly_800ms_returns_fail(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 800.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
	}

	public function test_evidence_contains_ttfb_ms_on_fail(): void {
		$ctx     = new MockContext( values: [ 'ttfb_ms' => 900.0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( 900.0, $finding->evidence->get( 'ttfb_ms' ) );
	}
}
