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
 * Verifies that the Strict-Transport-Security header is present and its
 * max-age meets the recommended minimum (6 months).
 *
 * HSTS tells browsers to only access the site over HTTPS. Without it, or
 * with too short a max-age, browsers may silently downgrade connections to
 * HTTP, or stop enforcing HTTPS shortly after the header was last seen.
 */
class HstsCheck implements Check {

	private const MIN_MAX_AGE_SECONDS = 15552000; // 6 months.

	public function id(): string {
		return 'headers.hsts';
	}

	public function label(): string {
		return __( 'HTTP Strict Transport Security (HSTS)', 'wp-security' );
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

		if ( ! isset( $headers['strict-transport-security'] ) ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    __( 'The Strict-Transport-Security header is missing. Browsers cannot enforce HTTPS-only access to your domain.', 'wp-security' ),
				recommendation: __( 'Add "Strict-Transport-Security: max-age=31536000; includeSubDomains" to your web server or CDN configuration.', 'wp-security' ),
			);
		}

		$maxAge = $this->parseMaxAge( $headers['strict-transport-security'] );

		if ( null === $maxAge || $maxAge <= 0 ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    __( 'The Strict-Transport-Security header is present but its max-age is missing or zero, which instructs browsers to stop enforcing HTTPS.', 'wp-security' ),
				recommendation: __( 'Set "Strict-Transport-Security: max-age=31536000; includeSubDomains" with a max-age of at least 6 months.', 'wp-security' ),
				evidence:       ( new Evidence() )->add( 'max_age', $maxAge ),
			);
		}

		if ( $maxAge < self::MIN_MAX_AGE_SECONDS ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: current max-age value in seconds */
					__( 'The Strict-Transport-Security max-age is %d seconds, below the recommended 6-month (15552000 second) minimum.', 'wp-security' ),
					$maxAge
				),
				recommendation: __( 'Increase the HSTS max-age to at least 15552000 seconds (6 months).', 'wp-security' ),
				evidence:       ( new Evidence() )->add( 'max_age', $maxAge ),
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			__( 'The Strict-Transport-Security (HSTS) header is present with an adequate max-age.', 'wp-security' )
		);
	}

	private function parseMaxAge( string $headerValue ): ?int {
		if ( 1 !== preg_match( '/max-age=(\d+)/i', $headerValue, $matches ) ) {
			return null;
		}

		return (int) $matches[1];
	}
}
