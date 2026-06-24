<?php

declare( strict_types=1 );

namespace WpSecuritySampleModule\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;

/**
 * Example check that always returns PASS.
 *
 * Replace the logic in run() with your own site-specific test.
 * Remember: run() must be side-effect free (reads only, never writes)
 * and must never throw — return Finding::skipped() if the check cannot run.
 */
class SampleCheck implements Check {

	public function id(): string {
		return 'sample.hello_world';
	}

	public function label(): string {
		return 'Sample Check';
	}

	public function run( Context $context ): Finding {
		return Finding::pass(
			$this->id(),
			$this->label(),
			'The sample check ran successfully.'
		);
	}
}
