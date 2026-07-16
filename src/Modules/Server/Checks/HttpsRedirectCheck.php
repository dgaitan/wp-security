<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that plain HTTP requests to the site are redirected to HTTPS,
 * and that no unencrypted hop sits between the request and the final HTTPS
 * destination.
 *
 * This is distinct from HttpsCheck (server.https), which only confirms the
 * configured home URL string is https:// — it never issues an actual
 * http:// request, so it cannot detect a site that is also fully reachable
 * over plain HTTP (no redirect), or a redirect chain that passes through an
 * insecure intermediate hop before reaching HTTPS.
 */
class HttpsRedirectCheck implements Check {

	public function id(): string {
		return 'server.https_redirect_enforcement';
	}

	public function label(): string {
		return __( 'HTTPS Redirect Enforcement', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, array{url: string, status: int, scheme: string}>|null $chain */
		$chain = $context->get( 'https_redirect_chain' );

		if ( ! is_array( $chain ) || [] === $chain ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine the site host, or no response was recorded, when testing the HTTP redirect chain.'
			);
		}

		$firstHop = $chain[0];

		if ( 0 === $firstHop['status'] ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'The site could not be reached over plain HTTP — no unencrypted access path was found.', 'wp-security' )
			);
		}

		/** @var array{url: string, status: int, scheme: string} $finalHop */
		$finalHop = end( $chain );

		if ( 'https' !== $finalHop['scheme'] ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    __( 'The site does not fully redirect HTTP traffic to HTTPS.', 'wp-security' ),
				recommendation: __( 'Configure your web server or CDN to redirect all HTTP requests to HTTPS with a 301 response.', 'wp-security' ),
				evidence:       [ 'chain' => $chain ],
			);
		}

		$insecureHopCount = count(
			array_filter(
				$chain,
				static fn ( array $hop ): bool => 'http' === $hop['scheme']
			)
		);

		if ( $insecureHopCount > 1 ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    __( 'The redirect chain to HTTPS passes through more than one unencrypted hop.', 'wp-security' ),
				recommendation: __( 'Ensure the first redirect from HTTP goes directly to HTTPS, avoiding intermediate plain-HTTP hops.', 'wp-security' ),
				evidence:       [ 'chain' => $chain ],
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			__( 'HTTP requests are redirected directly to HTTPS.', 'wp-security' )
		);
	}
}
