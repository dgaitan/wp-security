<?php
/**
 * Unit tests for Scheduler.
 *
 * Feature: Scheduler — Sprint 2 recurring scans
 *   Background:
 *     Given the WP Security plugin is installed
 *     And Action Scheduler functions are stubbed and recordable
 *
 *   Scenario: Scheduling registers a recurring action when none exists
 *     Given no scheduled scan
 *     When schedule() is called
 *     Then a recurring action is registered at the configured interval
 *
 *   Scenario: Scheduling is skipped when an action already exists
 *     Given a scan is already scheduled
 *     When schedule() is called
 *     Then no new recurring action is registered
 *
 *   Scenario: The interval honours the configured frequency
 *     Given the scan_frequency setting is "weekly"
 *     When schedule() is called
 *     Then the recurring interval is one week
 *
 *   Scenario: Unscheduling clears the recurring action
 *     When unschedule() is called
 *     Then all scheduled actions for the scan hook are removed
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Scanning\Scheduler;

final class SchedulerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_as_recurring']   = [];
		$GLOBALS['wp_security_test_as_unscheduled'] = [];
		$GLOBALS['wp_security_test_options']        = [];
		unset( $GLOBALS['wp_security_test_as_next'] );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['wp_security_test_as_recurring'],
			$GLOBALS['wp_security_test_as_unscheduled'],
			$GLOBALS['wp_security_test_options'],
			$GLOBALS['wp_security_test_as_next']
		);
		parent::tearDown();
	}

	public function test_schedule_registers_recurring_action_when_none_exists(): void {
		( new Scheduler() )->schedule();

		$this->assertCount( 1, $GLOBALS['wp_security_test_as_recurring'] );
		$this->assertSame( Scheduler::ACTION_HOOK, $GLOBALS['wp_security_test_as_recurring'][0]['hook'] );
		$this->assertSame( DAY_IN_SECONDS, $GLOBALS['wp_security_test_as_recurring'][0]['interval'] );
	}

	public function test_schedule_is_skipped_when_already_scheduled(): void {
		$GLOBALS['wp_security_test_as_next'] = 1750000000;

		( new Scheduler() )->schedule();

		$this->assertCount( 0, $GLOBALS['wp_security_test_as_recurring'] );
	}

	public function test_interval_honours_configured_frequency(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [ 'scan_frequency' => 'weekly' ];

		( new Scheduler() )->schedule();

		$this->assertSame( WEEK_IN_SECONDS, $GLOBALS['wp_security_test_as_recurring'][0]['interval'] );
	}

	public function test_unschedule_clears_recurring_action(): void {
		( new Scheduler() )->unschedule();

		$this->assertCount( 1, $GLOBALS['wp_security_test_as_unscheduled'] );
		$this->assertSame( Scheduler::ACTION_HOOK, $GLOBALS['wp_security_test_as_unscheduled'][0]['hook'] );
	}
}
