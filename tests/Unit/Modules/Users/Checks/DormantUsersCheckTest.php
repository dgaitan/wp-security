<?php
/*
 * Feature: Dormant Users Check — S6-6
 *
 * Scenario: dormant_user_count is null → SKIPPED
 *   Given a MockContext where get('dormant_user_count') returns null
 *   When DormantUsersCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: no dormant users → PASS
 *   Given dormant_user_count = 0
 *   When DormantUsersCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: one dormant user → WARN/MEDIUM
 *   Given dormant_user_count = 1
 *   When DormantUsersCheck::run() is called
 *   Then the Finding status is "warn" and severity is "medium"
 *   And evidence contains dormant_user_count = 1
 *
 * Scenario: multiple dormant users → WARN/MEDIUM with plural message
 *   Given dormant_user_count = 5
 *   When DormantUsersCheck::run() is called
 *   Then the Finding status is "warn" and evidence contains dormant_user_count = 5
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Users\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Users\Checks\DormantUsersCheck;
use WPSecurity\Tests\Support\MockContext;

class DormantUsersCheckTest extends TestCase {

	private DormantUsersCheck $check;

	protected function setUp(): void {
		$this->check = new DormantUsersCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'users.dormant_users', $this->check->id() );
	}

	public function test_null_count_returns_skipped(): void {
		$ctx = new MockContext( values: [ 'dormant_user_count' => null ] );

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_zero_dormant_users_returns_pass(): void {
		$ctx = new MockContext( values: [ 'dormant_user_count' => 0 ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_one_dormant_user_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'dormant_user_count' => 1 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 1, $finding->evidence->get( 'dormant_user_count' ) );
	}

	public function test_multiple_dormant_users_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'dormant_user_count' => 5 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 5, $finding->evidence->get( 'dormant_user_count' ) );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext( values: [ 'dormant_user_count' => 0 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
