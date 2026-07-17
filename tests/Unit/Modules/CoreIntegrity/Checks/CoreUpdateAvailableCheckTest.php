<?php
/*
 * Feature: Core Update Available Check — Sprint 9
 *
 * Scenario: core_update_available is null → PASS
 *   Given a MockContext where get('core_update_available') returns null
 *   When CoreUpdateAvailableCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: response is not "upgrade" → PASS
 *   Given a MockContext with core_update_available.response = "latest"
 *   When CoreUpdateAvailableCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: response is "upgrade" → WARN/HIGH
 *   Given a MockContext with core_update_available = { current: "6.4.0", latest: "6.5.0", response: "upgrade" }
 *   When CoreUpdateAvailableCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "high"
 *   And evidence contains current_version and latest_version
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\CoreUpdateAvailableCheck;
use WPSecurity\Tests\Support\MockContext;

class CoreUpdateAvailableCheckTest extends TestCase {

	private CoreUpdateAvailableCheck $check;

	protected function setUp(): void {
		$this->check = new CoreUpdateAvailableCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.core_update_available', $this->check->id() );
	}

	public function test_null_update_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'core_update_available' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_non_upgrade_response_returns_pass(): void {
		$ctx     = new MockContext(
			values: [
				'core_update_available' => [
					'current'  => '6.5.0',
					'latest'   => '6.5.0',
					'response' => 'latest',
				],
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_upgrade_response_returns_warn_high(): void {
		$ctx     = new MockContext(
			values: [
				'core_update_available' => [
					'current'  => '6.4.0',
					'latest'   => '6.5.0',
					'response' => 'upgrade',
				],
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( '6.4.0', $finding->evidence->get( 'current_version' ) );
		$this->assertSame( '6.5.0', $finding->evidence->get( 'latest_version' ) );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext( values: [ 'core_update_available' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
