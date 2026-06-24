<?php

/**
 * Feature: OpcacheCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: OPcache disabled returns WARN with MEDIUM severity
 *     Given a mocked Context with opcache_enabled = false
 *     When OpcacheCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *     And the Finding checkId is "server.opcache"
 *
 *   Scenario: OPcache enabled returns PASS
 *     Given a mocked Context with opcache_enabled = true
 *     When OpcacheCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Null opcache_enabled returns SKIPPED
 *     Given a mocked Context where get('opcache_enabled') returns null
 *     When OpcacheCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\OpcacheCheck;
use WPSecurity\Tests\Support\MockContext;

final class OpcacheCheckTest extends TestCase {

	private OpcacheCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new OpcacheCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.opcache', $this->check->id() );
	}

	public function test_opcache_disabled_returns_warn_medium(): void {
		$context = new MockContext( values: [ 'opcache_enabled' => false ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'server.opcache', $finding->checkId );
	}

	public function test_opcache_enabled_returns_pass(): void {
		$context = new MockContext( values: [ 'opcache_enabled' => true ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertSame( 0, $finding->penalty() );
	}

	public function test_null_opcache_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_warn_affects_score(): void {
		$context = new MockContext( values: [ 'opcache_enabled' => false ] );
		$finding = $this->check->run( $context );

		$this->assertTrue( $finding->affectsScore() );
		$this->assertSame( 10, $finding->penalty() );
	}
}
