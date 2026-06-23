<?php
/**
 * Unit tests for the Severity enum.
 *
 * Feature: Severity enum — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Penalty values match the scoring contract
 *     Given each Severity case
 *     When penalty() is called
 *     Then it returns CRITICAL 40, HIGH 20, MEDIUM 10, LOW 3, INFO 0
 *
 *   Scenario: Priority orders severities for display
 *     Given the Severity cases
 *     When priority() is compared across cases
 *     Then CRITICAL ranks highest and INFO lowest
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;

final class SeverityTest extends TestCase {

	public function test_penalties_match_contract(): void {
		$this->assertSame( 40, Severity::CRITICAL->penalty() );
		$this->assertSame( 20, Severity::HIGH->penalty() );
		$this->assertSame( 10, Severity::MEDIUM->penalty() );
		$this->assertSame( 3, Severity::LOW->penalty() );
		$this->assertSame( 0, Severity::INFO->penalty() );
	}

	public function test_priority_orders_critical_highest(): void {
		$this->assertGreaterThan( Severity::HIGH->priority(), Severity::CRITICAL->priority() );
		$this->assertGreaterThan( Severity::MEDIUM->priority(), Severity::HIGH->priority() );
		$this->assertGreaterThan( Severity::LOW->priority(), Severity::MEDIUM->priority() );
		$this->assertGreaterThan( Severity::INFO->priority(), Severity::LOW->priority() );
	}
}
