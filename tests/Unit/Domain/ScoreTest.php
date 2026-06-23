<?php
/**
 * Unit tests for the Score value object.
 *
 * Feature: Score value object — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Grade thresholds follow the spec
 *     Given a Score value
 *     When grade() is called
 *     Then 90+ is A, 80+ B, 70+ C, 60+ D, and below 60 is F
 *
 *   Scenario: Out-of-range values are rejected
 *     Given a value below 0 or above 100
 *     When a Score is constructed
 *     Then an InvalidArgumentException is thrown
 *
 *   Scenario: Score serialises for REST
 *     Given a Score of 85 for module "server"
 *     When toArray() is called
 *     Then it returns module_id, value, and grade
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Score;

final class ScoreTest extends TestCase {

	public function test_grade_thresholds(): void {
		$this->assertSame( 'A', ( new Score( 100, 'server' ) )->grade() );
		$this->assertSame( 'A', ( new Score( 90, 'server' ) )->grade() );
		$this->assertSame( 'B', ( new Score( 80, 'server' ) )->grade() );
		$this->assertSame( 'C', ( new Score( 70, 'server' ) )->grade() );
		$this->assertSame( 'D', ( new Score( 60, 'server' ) )->grade() );
		$this->assertSame( 'F', ( new Score( 59, 'server' ) )->grade() );
		$this->assertSame( 'F', ( new Score( 0, 'server' ) )->grade() );
	}

	public function test_grade_class_is_lowercased(): void {
		$this->assertSame( 'grade-a', ( new Score( 95, 'server' ) )->gradeClass() );
		$this->assertSame( 'grade-f', ( new Score( 10, 'server' ) )->gradeClass() );
	}

	public function test_rejects_value_above_100(): void {
		$this->expectException( InvalidArgumentException::class );
		new Score( 101, 'server' );
	}

	public function test_rejects_value_below_zero(): void {
		$this->expectException( InvalidArgumentException::class );
		new Score( -1, 'server' );
	}

	public function test_to_array_exposes_grade_and_value(): void {
		$score = new Score( 85, 'server' );

		$this->assertSame(
			[
				'module_id' => 'server',
				'value'     => 85,
				'grade'     => 'B',
			],
			$score->toArray()
		);
	}
}
