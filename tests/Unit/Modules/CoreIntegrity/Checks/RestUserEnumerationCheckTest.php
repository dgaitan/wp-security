<?php

/**
 * Feature: RestUserEnumerationCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Users not exposed returns PASS
 *     Given a mocked Context with rest_users_public = false
 *     When RestUserEnumerationCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Users exposed returns WARN with MEDIUM severity
 *     Given a mocked Context with rest_users_public = true
 *     When RestUserEnumerationCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Status null returns SKIPPED
 *     Given a mocked Context where get('rest_users_public') returns null
 *     When RestUserEnumerationCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\RestUserEnumerationCheck;
use WPSecurity\Tests\Support\MockContext;

final class RestUserEnumerationCheckTest extends TestCase {

	private RestUserEnumerationCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new RestUserEnumerationCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.rest_user_enumeration', $this->check->id() );
	}

	public function test_users_not_exposed_returns_pass(): void {
		$context = new MockContext( values: [ 'rest_users_public' => false ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_users_exposed_returns_warn_medium(): void {
		$context = new MockContext( values: [ 'rest_users_public' => true ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'core_integrity.rest_user_enumeration', $finding->checkId );
	}

	public function test_null_status_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
