<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Confirms the homepage responds with HTTP 200 — the baseline smoke test
 * every other Functional QA check assumes has already passed.
 */
class HomepageReachabilityCheck implements Check {

	public function id(): string {
		return 'functional_qa.homepage_reachability';
	}

	public function label(): string {
		return __( 'Homepage Reachability', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $status */
		$status = $context->get( 'homepage_http_status' );

		if ( null === $status ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not reach the homepage to check its response status.'
			);
		}

		if ( 200 === $status ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'The homepage loads correctly (HTTP 200).', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::CRITICAL,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: HTTP status code */
				__( 'The homepage returned HTTP %d instead of 200.', 'wp-security' ),
				$status
			),
			recommendation: __( 'Investigate immediately — a non-200 homepage response affects every visitor.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'http_status', $status ),
		);
	}
}
