<?php

/**
 * Feature: HstsCheck — Security Headers module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: HSTS header present returns PASS
 *     Given a mocked Context with response_headers containing 'strict-transport-security'
 *     When HstsCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: HSTS header absent returns FAIL with HIGH severity
 *     Given a mocked Context with response_headers that does NOT contain 'strict-transport-security'
 *     When HstsCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *
 *   Scenario: HSTS max-age below the 6-month threshold returns WARN with MEDIUM severity
 *     Given a mocked Context with response_headers where strict-transport-security has max-age=3600
 *     When HstsCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: HSTS max-age=0 returns WARN with HIGH severity
 *     Given a mocked Context with response_headers where strict-transport-security has max-age=0
 *     When HstsCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "high"
 *
 *   Scenario: Response headers null returns SKIPPED
 *     Given a mocked Context where get('response_headers') returns null
 *     When HstsCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Check ID is stable
 *     Given an HstsCheck instance
 *     When id() is called
 *     Then it returns "headers.hsts"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Headers\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Headers\Checks\HstsCheck;
use WPSecurity\Tests\Support\MockContext;

final class HstsCheckTest extends TestCase {

	private HstsCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new HstsCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'headers.hsts', $this->check->id() );
	}

	public function test_hsts_header_present_returns_pass(): void {
		$context = new MockContext(
			values: [
				'response_headers' => [
					'strict-transport-security' => 'max-age=31536000; includeSubDomains',
					'content-type'              => 'text/html; charset=UTF-8',
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_hsts_header_absent_returns_fail_high(): void {
		$context = new MockContext(
			values: [
				'response_headers' => [
					'content-type' => 'text/html; charset=UTF-8',
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( 'headers.hsts', $finding->checkId );
	}

	public function test_null_headers_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_headers_array_returns_fail(): void {
		$context = new MockContext( values: [ 'response_headers' => [] ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
	}

	public function test_max_age_below_threshold_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'response_headers' => [
					'strict-transport-security' => 'max-age=3600',
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 3600, $finding->evidence['max_age'] );
	}

	public function test_max_age_zero_returns_warn_high(): void {
		$context = new MockContext(
			values: [
				'response_headers' => [
					'strict-transport-security' => 'max-age=0',
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}
}
