<?php

/**
 * Feature: WpConfigCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: DISALLOW_FILE_EDIT true and WP_DEBUG false returns PASS
 *     Given a mocked Context with disallow_file_edit = true and wp_debug = false
 *     When WpConfigCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: DISALLOW_FILE_EDIT false returns WARN with MEDIUM severity
 *     Given a mocked Context with disallow_file_edit = false and wp_debug = false
 *     When WpConfigCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: WP_DEBUG true returns WARN with MEDIUM severity
 *     Given a mocked Context with disallow_file_edit = true and wp_debug = true
 *     When WpConfigCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Both issues present returns WARN with MEDIUM severity
 *     Given a mocked Context with disallow_file_edit = false and wp_debug = true
 *     When WpConfigCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Evidence records both constant values
 *     Given a mocked Context with any combination of values
 *     When WpConfigCheck::run() is called
 *     Then the Finding evidence contains 'disallow_file_edit' and 'wp_debug'
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\WpConfigCheck;
use WPSecurity\Tests\Support\MockContext;

final class WpConfigCheckTest extends TestCase {

	private WpConfigCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new WpConfigCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.wp_config', $this->check->id() );
	}

	public function test_both_correctly_set_returns_pass(): void {
		$context = new MockContext(
			values: [
				'disallow_file_edit' => true,
				'wp_debug'           => false,
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_disallow_file_edit_false_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'disallow_file_edit' => false,
				'wp_debug'           => false,
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_wp_debug_true_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'disallow_file_edit' => true,
				'wp_debug'           => true,
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_both_issues_present_returns_warn(): void {
		$context = new MockContext(
			values: [
				'disallow_file_edit' => false,
				'wp_debug'           => true,
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_evidence_contains_both_constant_values(): void {
		$context = new MockContext(
			values: [
				'disallow_file_edit' => false,
				'wp_debug'           => true,
			]
		);
		$finding = $this->check->run( $context );

		$this->assertTrue( $finding->evidence->has( 'disallow_file_edit' ) );
		$this->assertTrue( $finding->evidence->has( 'wp_debug' ) );
	}
}
