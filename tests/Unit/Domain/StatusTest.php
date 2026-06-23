<?php
/**
 * Unit tests for the Status enum.
 *
 * Feature: Status enum — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Status cases expose their wire values
 *     Given the Status enum
 *     When a case value is read
 *     Then it equals the documented lowercase string
 *
 *   Scenario: Only WARN and FAIL affect the score
 *     Given each Status case
 *     When affectsScore() is called
 *     Then WARN and FAIL return true and all others return false
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Status;

final class StatusTest extends TestCase {

	public function test_cases_have_expected_values(): void {
		$this->assertSame( 'pass', Status::PASS->value );
		$this->assertSame( 'warn', Status::WARN->value );
		$this->assertSame( 'fail', Status::FAIL->value );
		$this->assertSame( 'info', Status::INFO->value );
		$this->assertSame( 'skipped', Status::SKIPPED->value );
	}

	public function test_only_warn_and_fail_affect_score(): void {
		$this->assertTrue( Status::WARN->affectsScore() );
		$this->assertTrue( Status::FAIL->affectsScore() );
		$this->assertFalse( Status::PASS->affectsScore() );
		$this->assertFalse( Status::INFO->affectsScore() );
		$this->assertFalse( Status::SKIPPED->affectsScore() );
	}
}
