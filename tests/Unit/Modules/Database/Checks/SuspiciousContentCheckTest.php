<?php
/*
 * Feature: Suspicious Content Check — S6-5
 *
 * Scenario: both counts null → SKIPPED
 *   Given a MockContext where suspicious_option_count and suspicious_post_count are null
 *   When SuspiciousContentCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: option count null, post count set → SKIPPED (both required)
 *   Given suspicious_option_count = null, suspicious_post_count = 0
 *   Then status is "skipped"
 *
 * Scenario: both counts zero → PASS
 *   Given suspicious_option_count = 0 and suspicious_post_count = 0
 *   When SuspiciousContentCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: suspicious options found → FAIL/HIGH
 *   Given suspicious_option_count = 2, suspicious_post_count = 0
 *   When SuspiciousContentCheck::run() is called
 *   Then the Finding status is "fail" and severity is "high"
 *
 * Scenario: suspicious posts found → FAIL/HIGH
 *   Given suspicious_option_count = 0, suspicious_post_count = 1
 *   When SuspiciousContentCheck::run() is called
 *   Then the Finding status is "fail" and severity is "high"
 *
 * Scenario: both options and posts suspicious → FAIL/HIGH with combined evidence
 *   Given suspicious_option_count = 3, suspicious_post_count = 2
 *   Then evidence contains both counts
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Database\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Database\Checks\SuspiciousContentCheck;
use WPSecurity\Tests\Support\MockContext;

class SuspiciousContentCheckTest extends TestCase {

	private SuspiciousContentCheck $check;

	protected function setUp(): void {
		$this->check = new SuspiciousContentCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'database.suspicious_content', $this->check->id() );
	}

	public function test_null_option_count_returns_skipped(): void {
		$ctx = new MockContext(
			values: [
				'suspicious_option_count' => null,
				'suspicious_post_count'   => 0,
			]
		);

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_null_post_count_returns_skipped(): void {
		$ctx = new MockContext(
			values: [
				'suspicious_option_count' => 0,
				'suspicious_post_count'   => null,
			]
		);

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_both_counts_zero_returns_pass(): void {
		$ctx = new MockContext(
			values: [
				'suspicious_option_count' => 0,
				'suspicious_post_count'   => 0,
			]
		);

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_suspicious_options_returns_fail_high(): void {
		$ctx     = new MockContext(
			values: [
				'suspicious_option_count' => 2,
				'suspicious_post_count'   => 0,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_suspicious_posts_returns_fail_high(): void {
		$ctx     = new MockContext(
			values: [
				'suspicious_option_count' => 0,
				'suspicious_post_count'   => 1,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_both_suspicious_evidence_contains_both_counts(): void {
		$ctx     = new MockContext(
			values: [
				'suspicious_option_count' => 3,
				'suspicious_post_count'   => 2,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( 3, $finding->evidence->get( 'suspicious_option_count' ) );
		$this->assertSame( 2, $finding->evidence->get( 'suspicious_post_count' ) );
	}
}
