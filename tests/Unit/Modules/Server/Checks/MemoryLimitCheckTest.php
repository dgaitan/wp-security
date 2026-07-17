<?php

/**
 * Feature: MemoryLimitCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: 32M memory limit returns WARN with MEDIUM severity
 *     Given a mocked Context with memory_limit "32M"
 *     When MemoryLimitCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *     And the Finding checkId is "server.memory_limit"
 *
 *   Scenario: 256M memory limit returns PASS
 *     Given a mocked Context with memory_limit "256M"
 *     When MemoryLimitCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Unlimited (-1) memory limit returns PASS
 *     Given a mocked Context with memory_limit "-1"
 *     When MemoryLimitCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Null memory_limit returns SKIPPED
 *     Given a mocked Context where get('memory_limit') returns null
 *     When MemoryLimitCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: 64M (at the boundary) returns PASS
 *     Given a mocked Context with memory_limit "64M"
 *     When MemoryLimitCheck::run() is called
 *     Then the Finding status is "pass"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\MemoryLimitCheck;
use WPSecurity\Tests\Support\MockContext;

final class MemoryLimitCheckTest extends TestCase {

	private MemoryLimitCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new MemoryLimitCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.memory_limit', $this->check->id() );
	}

	public function test_low_memory_32m_returns_warn_medium(): void {
		$context = new MockContext( values: [ 'memory_limit' => '32M' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'server.memory_limit', $finding->checkId );
	}

	public function test_sufficient_memory_256m_returns_pass(): void {
		$context = new MockContext( values: [ 'memory_limit' => '256M' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_unlimited_memory_returns_pass(): void {
		$context = new MockContext( values: [ 'memory_limit' => '-1' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_boundary_64m_returns_pass(): void {
		$context = new MockContext( values: [ 'memory_limit' => '64M' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_just_below_boundary_returns_warn(): void {
		$context = new MockContext( values: [ 'memory_limit' => '63M' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
	}

	public function test_null_memory_limit_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_false_memory_limit_returns_skipped(): void {
		$context = new MockContext( values: [ 'memory_limit' => false ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_gigabyte_value_parses_correctly(): void {
		$context = new MockContext( values: [ 'memory_limit' => '1G' ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_warn_finding_includes_evidence(): void {
		$context = new MockContext( values: [ 'memory_limit' => '32M' ] );
		$finding = $this->check->run( $context );

		$this->assertTrue( $finding->evidence->has( 'current' ) );
		$this->assertTrue( $finding->evidence->has( 'recommended_minimum' ) );
	}
}
