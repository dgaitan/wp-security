<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Shared logic for "is this tracking tag present in the homepage HTML"
 * checks. Per the confirmed decision, absence only WARNs when the matching
 * `expect_*` setting is enabled; otherwise it's informational (Status::INFO,
 * unscored) — avoids penalizing sites that legitimately don't run a given
 * tool. GtmPresenceCheck/Ga4PresenceCheck/MetaPixelPresenceCheck only differ
 * in their detection regex and which `expect_*` Context key gates them.
 */
abstract class AbstractPresenceCheck implements Check {

	abstract public function id(): string;

	abstract public function label(): string;

	abstract protected function isPresent( string $html ): bool;

	/** The ScanContext key holding this tag's `expect_*` setting. */
	abstract protected function expectContextKey(): string;

	public function run( Context $context ): Finding {
		/** @var string|null $html */
		$html = $context->get( 'homepage_html' );

		if ( null === $html ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch the homepage to check for ' . $this->label() . '.'
			);
		}

		if ( $this->isPresent( $html ) ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				sprintf(
					/* translators: %s: tracking tool label, e.g. "Google Tag Manager" */
					__( '%s detected on the homepage.', 'wp-security' ),
					$this->label()
				)
			);
		}

		$expected = (bool) $context->get( $this->expectContextKey() );

		if ( $expected ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %s: tracking tool label */
					__( '%s is expected but was not found on the homepage.', 'wp-security' ),
					$this->label()
				),
				recommendation: __( 'Confirm the tracking snippet is still installed and rendering.', 'wp-security' ),
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::INFO,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %s: tracking tool label */
				__( '%s was not found on the homepage.', 'wp-security' ),
				$this->label()
			),
			recommendation: '',
		);
	}
}
