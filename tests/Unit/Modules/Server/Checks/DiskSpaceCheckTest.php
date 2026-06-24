<?php

/**
 * Feature: DiskSpaceCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Ample disk space (10 GB free) returns PASS
 *     Given a mocked Context with disk_free_bytes = 10 GB
 *     When DiskSpaceCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Low disk space (300 MB free) returns WARN with MEDIUM severity
 *     Given a mocked Context with disk_free_bytes = 300 MB
 *     When DiskSpaceCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Critically low disk space (50 MB free) returns FAIL with HIGH severity
 *     Given a mocked Context with disk_free_bytes = 50 MB
 *     When DiskSpaceCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *
 *   Scenario: Unavailable disk info returns SKIPPED
 *     Given a mocked Context where get('disk_free_bytes') returns null
 *     When DiskSpaceCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Finding evidence includes free_mb
 *     Given a mocked Context with disk_free_bytes = 300 MB
 *     When DiskSpaceCheck::run() is called
 *     Then the Finding evidence contains 'free_mb'
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\DiskSpaceCheck;
use WPSecurity\Tests\Support\MockContext;

final class DiskSpaceCheckTest extends TestCase {

	private DiskSpaceCheck $check;

	private const MB = 1024 * 1024;
	private const GB = 1024 * 1024 * 1024;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new DiskSpaceCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.disk_space', $this->check->id() );
	}

	public function test_ample_disk_space_returns_pass(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 10 * self::GB ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_low_disk_300mb_returns_warn_medium(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 300 * self::MB ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'server.disk_space', $finding->checkId );
	}

	public function test_critically_low_50mb_returns_fail_high(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 50 * self::MB ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_null_disk_info_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_false_disk_info_returns_skipped(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => false ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_warn_finding_includes_free_mb_evidence(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 300 * self::MB ] );
		$finding = $this->check->run( $context );

		$this->assertArrayHasKey( 'free_mb', $finding->evidence );
	}

	public function test_boundary_at_500mb_is_pass(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 500 * self::MB ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_just_below_100mb_is_fail(): void {
		$context = new MockContext( values: [ 'disk_free_bytes' => 99 * self::MB ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
	}
}
