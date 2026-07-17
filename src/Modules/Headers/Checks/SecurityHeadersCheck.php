<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that common browser-security headers are present in HTTP
 * responses, and that a present Content-Security-Policy is not weakened by
 * unsafe directives.
 *
 * HEADERS maps lowercase header names to the severity if they are absent.
 * Missing CSP or X-Frame-Options are MEDIUM because they enable XSS and
 * clickjacking. The remaining headers are LOW because they harden but are
 * less critical. A present-but-weak CSP (unsafe-inline/unsafe-eval/wildcard
 * script sources) is treated at the same MEDIUM tier as a fully absent CSP,
 * since those directives provide close to zero real XSS protection.
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

	/**
	 * Directives inspected for weak source values. Scoped narrowly to
	 * script-src/default-src — a bare '*' in e.g. img-src/font-src is
	 * common and low-risk, and flagging it would be noisy.
	 *
	 * @var array<int, string>
	 */
	private const CSP_DIRECTIVES_TO_INSPECT = [ 'script-src', 'default-src' ];

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

		$cspWeaknesses = isset( $headers['content-security-policy'] )
			? $this->analyzeCspStrength( $headers['content-security-policy'] )
			: [];

		if ( [] !== $cspWeaknesses && ( null === $topSeverity || Severity::MEDIUM->priority() > $topSeverity->priority() ) ) {
			$topSeverity = Severity::MEDIUM;
		}

		if ( [] === $missing && [] === $cspWeaknesses ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All recommended security headers are present and the Content-Security-Policy does not permit unsafe sources.', 'wp-security' )
			);
		}

		$descriptionParts = [];
		if ( [] !== $missing ) {
			$descriptionParts[] = sprintf(
				/* translators: %s: comma-separated list of missing HTTP security headers */
				__( 'The following security headers are missing: %s.', 'wp-security' ),
				implode( ', ', array_keys( $missing ) )
			);
		}
		if ( [] !== $cspWeaknesses ) {
			$descriptionParts[] = sprintf(
				/* translators: %s: comma-separated list of weak CSP directive tokens found */
				__( 'The Content-Security-Policy permits unsafe sources: %s.', 'wp-security' ),
				implode( ', ', $cspWeaknesses )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       $topSeverity ?? Severity::MEDIUM,
			title:          $this->label(),
			description:    implode( ' ', $descriptionParts ),
			recommendation: __( 'Configure your web server or CDN to include the missing security headers, and tighten the Content-Security-Policy to remove unsafe-inline, unsafe-eval, and wildcard sources.', 'wp-security' ),
			evidence:       ( new Evidence() )
				->add( 'missing', $missing )
				->add( 'csp_weaknesses', $cspWeaknesses ),
		);
	}

	/**
	 * Scans the script-src/default-src directive segments of a CSP header
	 * value for unsafe-inline, unsafe-eval, or a bare wildcard source.
	 *
	 * @return array<int, string>
	 */
	private function analyzeCspStrength( string $csp ): array {
		$weaknesses = [];
		$directives = array_map( 'trim', explode( ';', $csp ) );

		foreach ( $directives as $directive ) {
			if ( '' === $directive ) {
				continue;
			}

			$splitDirective = preg_split( '/\s+/', $directive );
			$parts          = false !== $splitDirective ? $splitDirective : [];
			$directiveName  = strtolower( (string) array_shift( $parts ) );

			if ( ! in_array( $directiveName, self::CSP_DIRECTIVES_TO_INSPECT, true ) ) {
				continue;
			}

			$values = array_map( 'strtolower', $parts );

			if ( in_array( "'unsafe-inline'", $values, true ) && ! in_array( 'unsafe-inline', $weaknesses, true ) ) {
				$weaknesses[] = 'unsafe-inline';
			}

			if ( in_array( "'unsafe-eval'", $values, true ) && ! in_array( 'unsafe-eval', $weaknesses, true ) ) {
				$weaknesses[] = 'unsafe-eval';
			}

			if ( in_array( '*', $values, true ) ) {
				$token = sprintf( 'wildcard-%s', $directiveName );
				if ( ! in_array( $token, $weaknesses, true ) ) {
					$weaknesses[] = $token;
				}
			}
		}

		return $weaknesses;
	}
}
