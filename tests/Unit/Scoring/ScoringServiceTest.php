<?php
/**
 * Unit tests for ScoringService.
 *
 * Feature: ScoringService — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: One CRITICAL finding subtracts its penalty
 *     Given a module "server" with one FAIL finding of CRITICAL severity
 *     When ScoringService::scoreModule() is called
 *     Then the Score value is 60
 *     And the Score grade is "D"
 *
 *   Scenario: Penalties greater than 100 floor the score at 0
 *     Given a module "server" with three CRITICAL findings
 *     When ScoringService::scoreModule() is called
 *     Then the Score value is 0
 *     And the Score grade is "F"
 *
 *   Scenario: Non-scoring findings leave the score at 100
 *     Given a module with only PASS and INFO findings
 *     When ScoringService::scoreModule() is called
 *     Then the Score value is 100
 *
 *   Scenario: Overall score is the weighted average of module scores
 *     Given module scores keyed by module ID
 *     When ScoringService::overallScore() is called
 *     Then the result is the rounded weighted average
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scoring;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Score;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Scoring\ScoringService;

final class ScoringServiceTest extends TestCase {

	private ScoringService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new ScoringService();
	}

	public function test_single_critical_finding_returns_60(): void {
		$score = $this->service->scoreModule(
			'server',
			[ $this->finding( Status::FAIL, Severity::CRITICAL ) ]
		);

		$this->assertSame( 60, $score->value );
		$this->assertSame( 'D', $score->grade() );
	}

	public function test_penalties_over_100_floor_at_zero(): void {
		$score = $this->service->scoreModule(
			'server',
			[
				$this->finding( Status::FAIL, Severity::CRITICAL ),
				$this->finding( Status::FAIL, Severity::CRITICAL ),
				$this->finding( Status::FAIL, Severity::CRITICAL ),
			]
		);

		$this->assertSame( 0, $score->value );
		$this->assertSame( 'F', $score->grade() );
	}

	public function test_non_scoring_findings_keep_full_score(): void {
		$score = $this->service->scoreModule(
			'server',
			[
				$this->finding( Status::PASS, Severity::INFO ),
				$this->finding( Status::INFO, Severity::INFO ),
				$this->finding( Status::SKIPPED, Severity::CRITICAL ),
			]
		);

		$this->assertSame( 100, $score->value );
	}

	public function test_overall_score_is_weighted_average(): void {
		$scores = [
			'core'   => new Score( 50, 'core' ),
			'server' => new Score( 100, 'server' ),
		];

		// core weight 20, server weight 15 => (50*20 + 100*15) / 35 = 71.43 -> 71.
		$overall = $this->service->overallScore( $scores );

		$this->assertSame( 71, $overall->value );
		$this->assertSame( 'overall', $overall->moduleId );
	}

	public function test_overall_score_of_no_modules_is_zero(): void {
		$overall = $this->service->overallScore( [] );

		$this->assertSame( 0, $overall->value );
	}

	private function finding( Status $status, Severity $severity ): Finding {
		return new Finding(
			'test.check',
			$status,
			$severity,
			'Test check',
			'A test finding.',
			'No action.'
		);
	}
}
