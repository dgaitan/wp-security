<?php
/*
 * Feature: RemediationStatus enum — Sprint 9
 *
 * Scenario: Status cases expose their wire values
 *   Given the RemediationStatus enum
 *   When a case value is read
 *   Then it equals the documented lowercase string
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationStatus;

final class RemediationStatusTest extends TestCase {

	public function test_cases_have_expected_values(): void {
		$this->assertSame( 'queued', RemediationStatus::QUEUED->value );
		$this->assertSame( 'success', RemediationStatus::SUCCESS->value );
		$this->assertSame( 'failed', RemediationStatus::FAILED->value );
		$this->assertSame( 'skipped', RemediationStatus::SKIPPED->value );
	}
}
