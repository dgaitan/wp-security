<?php
/*
 * Feature: Dashboard Notices Check — Sprint 9
 *
 * Scenario: no error log available → INFO
 *   Given a MockContext where php_error_log_tail is null
 *   When DashboardNoticesCheck::run() is called
 *   Then the Finding status is "info"
 *
 * Scenario: log has no error/warning lines → PASS
 *   Given a MockContext with php_error_log_tail containing only benign lines
 *   When DashboardNoticesCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: log has recent error/warning lines → WARN/MEDIUM
 *   Given a MockContext with php_error_log_tail containing "PHP Warning: ..." and "PHP Fatal error: ..."
 *   When DashboardNoticesCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *   And evidence contains the flagged lines
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\DashboardNoticesCheck;
use WPSecurity\Tests\Support\MockContext;

class DashboardNoticesCheckTest extends TestCase {

	private DashboardNoticesCheck $check;

	protected function setUp(): void {
		$this->check = new DashboardNoticesCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.dashboard_notices', $this->check->id() );
	}

	public function test_null_log_returns_info(): void {
		$ctx     = new MockContext( values: [ 'php_error_log_tail' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::INFO, $finding->status );
	}

	public function test_no_flagged_lines_returns_pass(): void {
		$ctx     = new MockContext(
			values: [
				'php_error_log_tail' => [
					'2026-01-01 00:00:00 PHP Deprecated: something minor',
					'a routine log line',
				],
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_flagged_lines_return_warn_medium(): void {
		$ctx     = new MockContext(
			values: [
				'php_error_log_tail' => [
					'2026-01-01 00:00:00 PHP Warning: array key does not exist',
					'2026-01-01 00:01:00 PHP Fatal error: uncaught exception',
					'a routine log line',
				],
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertCount( 2, $finding->evidence->get( 'recent_log_lines' ) );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext( values: [ 'php_error_log_tail' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
