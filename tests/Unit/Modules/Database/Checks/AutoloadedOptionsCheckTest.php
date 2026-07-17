<?php
/*
 * Feature: Autoloaded Options Check — S6-4
 *
 * Scenario: size is null (DB unavailable) → SKIPPED
 *   Given a MockContext where get('autoloaded_options_size') returns null
 *   When AutoloadedOptionsCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: size is within threshold → PASS
 *   Given autoloaded_options_size = 512000 (500 KB)
 *   When AutoloadedOptionsCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: size equals threshold exactly → PASS
 *   Given autoloaded_options_size = 1048576 (exactly 1 MB)
 *   When AutoloadedOptionsCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: size exceeds threshold → WARN/MEDIUM
 *   Given autoloaded_options_size = 1100000 (> 1 MB)
 *   When AutoloadedOptionsCheck::run() is called
 *   Then the Finding status is "warn" and severity is "medium"
 *   And evidence contains 'autoloaded_size_bytes' = 1100000
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Database\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Database\Checks\AutoloadedOptionsCheck;
use WPSecurity\Tests\Support\MockContext;

class AutoloadedOptionsCheckTest extends TestCase {

	private AutoloadedOptionsCheck $check;

	protected function setUp(): void {
		$this->check = new AutoloadedOptionsCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'database.autoloaded_options', $this->check->id() );
	}

	public function test_null_size_returns_skipped(): void {
		$ctx = new MockContext( values: [ 'autoloaded_options_size' => null ] );

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_size_within_threshold_returns_pass(): void {
		$ctx = new MockContext( values: [ 'autoloaded_options_size' => 512000 ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_size_at_threshold_returns_pass(): void {
		$ctx = new MockContext( values: [ 'autoloaded_options_size' => 1048576 ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_size_exceeds_threshold_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'autoloaded_options_size' => 1100000 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 1100000, $finding->evidence->get( 'autoloaded_size_bytes' ) );
	}

	public function test_zero_bytes_returns_pass(): void {
		$ctx = new MockContext( values: [ 'autoloaded_options_size' => 0 ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}
}
