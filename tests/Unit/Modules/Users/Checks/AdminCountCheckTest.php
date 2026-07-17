<?php
/*
 * Feature: Admin Count Check — S6-7
 *
 * Scenario: admin_user_count is null → SKIPPED
 *   Given a MockContext where get('admin_user_count') returns null
 *   When AdminCountCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: exactly one administrator → PASS
 *   Given admin_user_count = 1
 *   When AdminCountCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: two administrators → WARN/LOW
 *   Given admin_user_count = 2
 *   When AdminCountCheck::run() is called
 *   Then the Finding status is "warn" and severity is "low"
 *   And evidence contains admin_user_count = 2
 *
 * Scenario: three administrators → WARN/LOW (below MEDIUM threshold)
 *   Given admin_user_count = 3
 *   When AdminCountCheck::run() is called
 *   Then severity is "low"
 *
 * Scenario: four or more administrators → WARN/MEDIUM
 *   Given admin_user_count = 4
 *   When AdminCountCheck::run() is called
 *   Then severity is "medium"
 *
 * Scenario: zero administrators → WARN (edge case, something's wrong)
 *   Given admin_user_count = 0
 *   When AdminCountCheck::run() is called
 *   Then the Finding status is "warn"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Users\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Users\Checks\AdminCountCheck;
use WPSecurity\Tests\Support\MockContext;

class AdminCountCheckTest extends TestCase {

	private AdminCountCheck $check;

	protected function setUp(): void {
		$this->check = new AdminCountCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'users.admin_count', $this->check->id() );
	}

	public function test_null_count_returns_skipped(): void {
		$ctx = new MockContext( values: [ 'admin_user_count' => null ] );

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_one_admin_returns_pass(): void {
		$ctx = new MockContext( values: [ 'admin_user_count' => 1 ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_two_admins_returns_warn_low(): void {
		$ctx     = new MockContext( values: [ 'admin_user_count' => 2 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
		$this->assertSame( 2, $finding->evidence->get( 'admin_user_count' ) );
	}

	public function test_three_admins_returns_warn_low(): void {
		$ctx     = new MockContext( values: [ 'admin_user_count' => 3 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Severity::LOW, $finding->severity );
	}

	public function test_four_admins_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'admin_user_count' => 4 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_many_admins_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'admin_user_count' => 10 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 10, $finding->evidence->get( 'admin_user_count' ) );
	}

	public function test_zero_admins_returns_warn(): void {
		$ctx     = new MockContext( values: [ 'admin_user_count' => 0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
