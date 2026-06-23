<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * How serious a Finding is.
 *
 * The penalty values are used by ScoringService to subtract from a
 * module's base score of 100.
 *
 * CRITICAL  → 40 pts
 * HIGH      → 20 pts
 * MEDIUM    → 10 pts
 * LOW       →  3 pts
 * INFO      →  0 pts
 */
enum Severity: string {
	case CRITICAL = 'critical';
	case HIGH     = 'high';
	case MEDIUM   = 'medium';
	case LOW      = 'low';
	case INFO     = 'info';

	/**
	 * Score penalty applied when a finding with this severity has Status WARN or FAIL.
	 */
	public function penalty(): int {
		return match ( $this ) {
			self::CRITICAL => 40,
			self::HIGH     => 20,
			self::MEDIUM   => 10,
			self::LOW      =>  3,
			self::INFO     =>  0,
		};
	}

	/**
	 * Display order: higher number = more important (useful for sorting findings).
	 */
	public function priority(): int {
		return match ( $this ) {
			self::CRITICAL => 5,
			self::HIGH     => 4,
			self::MEDIUM   => 3,
			self::LOW      => 2,
			self::INFO     => 1,
		};
	}
}
