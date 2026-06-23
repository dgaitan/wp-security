<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * The outcome status of a single Check run.
 *
 * PASS     — the check passed with no issues.
 * WARN     — a potential problem was found; investigate.
 * FAIL     — a definite problem was found; action required.
 * INFO     — informational only; no pass/fail judgement.
 * SKIPPED  — the check could not run (missing dependency, unsupported env).
 */
enum Status: string {
	case PASS    = 'pass';
	case WARN    = 'warn';
	case FAIL    = 'fail';
	case INFO    = 'info';
	case SKIPPED = 'skipped';

	/**
	 * Whether this status contributes a penalty to the score.
	 */
	public function affectsScore(): bool {
		return match ( $this ) {
			self::WARN, self::FAIL => true,
			default                => false,
		};
	}
}
