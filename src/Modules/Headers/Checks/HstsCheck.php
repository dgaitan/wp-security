<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that the Strict-Transport-Security header is present.
 *
 * HSTS tells browsers to only access the site over HTTPS.
 * Without it, browsers may silently downgrade connections to HTTP.
 */
class HstsCheck implements Check {

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

		if ( isset( $headers['strict-transport-security'] ) ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'The Strict-Transport-Security (HSTS) header is present.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    __( 'The Strict-Transport-Security header is missing. Browsers cannot enforce HTTPS-only access to your domain.', 'wp-security' ),
			recommendation: __( 'Add "Strict-Transport-Security: max-age=31536000; includeSubDomains" to your web server or CDN configuration.', 'wp-security' ),
		);
	}
}
