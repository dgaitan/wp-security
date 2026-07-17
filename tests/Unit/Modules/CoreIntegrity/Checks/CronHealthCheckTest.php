<?php
/*
 * Feature: Cron Health Check — Sprint 9
 *
 * Scenario: no cron data available → SKIPPED
 *   Given a MockContext where cron_pending_count and action_scheduler_overdue_count are both null
 *   When CronHealthCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: cron disabled with overdue events → WARN/MEDIUM
 *   Given a MockContext with wp_cron_disabled = true and cron_pending_count = 3
 *   When CronHealthCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: excessive Action Scheduler backlog → WARN/HIGH
 *   Given a MockContext with action_scheduler_overdue_count = 100
 *   When CronHealthCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "high"
 *
 * Scenario: healthy scheduling layer → PASS
 *   Given a MockContext with wp_cron_disabled = false, cron_pending_count = 0, action_scheduler_overdue_count = 2
 *   When CronHealthCheck::run() is called
 *   Then the Finding status is "pass"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\CronHealthCheck;
use WPSecurity\Tests\Support\MockContext;

class CronHealthCheckTest extends TestCase {

	private CronHealthCheck $check;

	protected function setUp(): void {
		$this->check = new CronHealthCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.cron_health', $this->check->id() );
	}

	public function test_no_data_returns_skipped(): void {
		$ctx     = new MockContext(
			values: [
				'wp_cron_disabled'               => false,
				'cron_pending_count'             => null,
				'action_scheduler_overdue_count' => null,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_disabled_cron_with_overdue_events_returns_warn_medium(): void {
		$ctx     = new MockContext(
			values: [
				'wp_cron_disabled'               => true,
				'cron_pending_count'             => 3,
				'action_scheduler_overdue_count' => 0,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_excessive_action_scheduler_backlog_returns_warn_high(): void {
		$ctx     = new MockContext(
			values: [
				'wp_cron_disabled'               => false,
				'cron_pending_count'             => 0,
				'action_scheduler_overdue_count' => 100,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_healthy_scheduling_returns_pass(): void {
		$ctx     = new MockContext(
			values: [
				'wp_cron_disabled'               => false,
				'cron_pending_count'             => 0,
				'action_scheduler_overdue_count' => 2,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext(
			values: [
				'wp_cron_disabled'               => false,
				'cron_pending_count'             => 0,
				'action_scheduler_overdue_count' => 0,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
