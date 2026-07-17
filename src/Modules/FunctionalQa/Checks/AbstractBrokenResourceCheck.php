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
 * Shared logic for checks that report a capped list of {url,status,found_on}
 * broken resources discovered by ScanContext's site crawler. Distinct null
 * (couldn't crawl → SKIPPED) vs empty-array (crawled, nothing broken → PASS)
 * semantics are shared between BrokenInternalLinkCheck and MediaLoadingCheck.
 */
abstract class AbstractBrokenResourceCheck implements Check {

	abstract public function id(): string;

	abstract public function label(): string;

	/** The ScanContext key holding this check's broken-resource list. */
	abstract protected function contextKey(): string;

	/** e.g. "link(s)" / "media file(s)" — used in the PASS/WARN description. */
	abstract protected function resourceNoun(): string;

	public function run( Context $context ): Finding {
		/** @var array<int, array{url: string, status: int, found_on: string}>|null $broken */
		$broken = $context->get( $this->contextKey() );

		if ( null === $broken ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not crawl the site to check for broken ' . $this->resourceNoun() . '.'
			);
		}

		if ( [] === $broken ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				sprintf(
					/* translators: %s: resource noun, e.g. "link(s)" */
					__( 'No broken %s found across the crawled pages.', 'wp-security' ),
					$this->resourceNoun()
				)
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    sprintf(
				/* translators: 1: number of broken resources, 2: resource noun */
				__( '%1$d broken %2$s found across the crawled pages.', 'wp-security' ),
				count( $broken ),
				$this->resourceNoun()
			),
			recommendation: __( 'Fix or remove the broken URLs below.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'broken', $broken ),
		);
	}
}
