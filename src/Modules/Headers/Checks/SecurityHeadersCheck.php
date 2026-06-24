<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that common browser-security headers are present in HTTP responses.
 *
 * HEADERS maps lowercase header names to the severity if they are absent.
 * Missing CSP or X-Frame-Options are MEDIUM because they enable XSS and
 * clickjacking. The remaining headers are LOW because they harden but are
 * less critical.
 */
class SecurityHeadersCheck implements Check {

	/** @var array<string, Severity> */
	private const HEADERS = [
		'content-security-policy' => Severity::MEDIUM,
		'x-frame-options'         => Severity::MEDIUM,
		'x-content-type-options'  => Severity::LOW,
		'referrer-policy'         => Severity::LOW,
		'permissions-policy'      => Severity::LOW,
	];

	public function id(): string {
		return 'headers.security_headers';
	}

	public function label(): string {
		return __( 'Security Headers', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$headers = $context->get( 'response_headers' );

		if ( ! is_array( $headers ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not retrieve HTTP response headers from the loopback request.'
			);
		}

		$missing     = [];
		$topSeverity = null;

		foreach ( self::HEADERS as $header => $severity ) {
			if ( ! isset( $headers[ $header ] ) ) {
				$missing[ $header ] = $severity->value;
				if ( null === $topSeverity || $severity->priority() > $topSeverity->priority() ) {
					$topSeverity = $severity;
				}
			}
		}

		if ( [] === $missing || null === $topSeverity ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All recommended security headers are present.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       $topSeverity,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %s: comma-separated list of missing HTTP security headers */
				__( 'The following security headers are missing: %s.', 'wp-security' ),
				implode( ', ', array_keys( $missing ) )
			),
			recommendation: __( 'Configure your web server or CDN to include the missing security headers.', 'wp-security' ),
			evidence:       [ 'missing' => $missing ],
		);
	}
}
