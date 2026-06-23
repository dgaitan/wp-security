<?php

declare( strict_types=1 );

namespace WPSecurity\Scoring;

use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Score;

/**
 * Converts a set of Findings into a Score.
 *
 * Algorithm (from spec §7):
 *   1. Start at 100.
 *   2. For every Finding whose Status is WARN or FAIL, subtract Severity::penalty().
 *   3. Floor the result at 0.
 *
 * The overall site score is the weighted average of module scores.
 * Default weights are defined in DEFAULT_WEIGHTS; they are overridable via
 * the `wp_security/scoring_weights` filter.
 *
 * Scoring reads only Finding objects — it is completely decoupled from what
 * any individual Check does.  New modules slot into the score automatically.
 */
class ScoringService {

	/**
	 * Default weight for each module.
	 * Security-oriented modules are weighted higher than SEO/performance.
	 *
	 * @var array<string, int>
	 */
	private const DEFAULT_WEIGHTS = [
		'server'         => 15,
		'dns'            => 10,
		'headers'        => 10,
		'core'           => 20,
		'plugins_themes' => 15,
		'database'       => 10,
		'performance'    => 5,
		'accessibility'  => 5,
		'seo'            => 5,
		'users'          => 5,
	];

	/**
	 * Calculate the score for a single module from its findings.
	 *
	 * @param array<Finding> $findings
	 */
	public function scoreModule( string $moduleId, array $findings ): Score {
		$penalty = 0;

		foreach ( $findings as $finding ) {
			$penalty += $finding->penalty();
		}

		$value = max( 0, 100 - $penalty );

		return new Score( $value, $moduleId );
	}

	/**
	 * Calculate the overall site score as a weighted average of module scores.
	 *
	 * @param array<string, Score> $moduleScores Keyed by module ID.
	 */
	public function overallScore( array $moduleScores ): Score {
		/** @var array<string, int> $weights */
		$weights = apply_filters( 'wp_security/scoring_weights', self::DEFAULT_WEIGHTS );

		$weightedSum  = 0;
		$totalWeights = 0;

		foreach ( $moduleScores as $moduleId => $score ) {
			$weight        = $weights[ $moduleId ] ?? 5;
			$weightedSum  += $score->value * $weight;
			$totalWeights += $weight;
		}

		$overall = $totalWeights > 0
			? (int) round( $weightedSum / $totalWeights )
			: 0;

		return new Score( $overall, 'overall' );
	}
}
