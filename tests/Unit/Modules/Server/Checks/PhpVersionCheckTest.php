<?php

/**
 * Feature: PhpVersionCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: PHP 7.4 returns FAIL with HIGH severity
 *     Given a mocked Context with phpVersion "7.4.33"
 *     When PhpVersionCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *     And the Finding checkId is "server.php_version"
 *
 *   Scenario: PHP 8.0 (EOL) returns FAIL with HIGH severity
 *     Given a mocked Context with phpVersion "8.0.30"
 *     When PhpVersionCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *
 *   Scenario: PHP 8.1.0 returns PASS
 *     Given a mocked Context with phpVersion "8.1.0"
 *     When PhpVersionCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: PHP 8.2 returns PASS
 *     Given a mocked Context with phpVersion "8.2.5"
 *     When PhpVersionCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Evidence includes current version and minimum_secure
 *     Given a mocked Context with phpVersion "7.4.0"
 *     When PhpVersionCheck::run() is called
 *     Then the Finding evidence contains 'current' and 'minimum_secure'
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\PhpVersionCheck;
use WPSecurity\Tests\Support\MockContext;

final class PhpVersionCheckTest extends TestCase {

	private PhpVersionCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new PhpVersionCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.php_version', $this->check->id() );
	}

	public function test_php_74_returns_fail_with_high_severity(): void {
		$context = new MockContext( phpVersion: '7.4.33' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( 'server.php_version', $finding->checkId );
	}

	public function test_php_80_returns_fail_with_high_severity(): void {
		$context = new MockContext( phpVersion: '8.0.30' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_php_81_returns_pass(): void {
		$context = new MockContext( phpVersion: '8.1.0' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_php_82_returns_pass(): void {
		$context = new MockContext( phpVersion: '8.2.5' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_php_83_returns_pass(): void {
		$context = new MockContext( phpVersion: '8.3.1' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_fail_finding_includes_version_evidence(): void {
		$context = new MockContext( phpVersion: '7.4.0' );
		$finding = $this->check->run( $context );

		$this->assertTrue( $finding->evidence->has( 'current' ) );
		$this->assertTrue( $finding->evidence->has( 'minimum_secure' ) );
		$this->assertSame( '7.4.0', $finding->evidence->get( 'current' ) );
	}

	public function test_pass_finding_has_no_score_penalty(): void {
		$context = new MockContext( phpVersion: '8.2.0' );
		$finding = $this->check->run( $context );

		$this->assertSame( 0, $finding->penalty() );
	}

	public function test_fail_finding_has_high_penalty(): void {
		$context = new MockContext( phpVersion: '7.4.0' );
		$finding = $this->check->run( $context );

		$this->assertSame( 20, $finding->penalty() );
	}
}
