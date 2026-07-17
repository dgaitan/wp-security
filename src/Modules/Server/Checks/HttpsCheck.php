<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies the site's home URL uses HTTPS, confirming TLS is configured.
 */
class HttpsCheck implements Check {

	public function id(): string {
		return 'server.https';
	}

	public function label(): string {
		return __( 'HTTPS / TLS', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$url = $context->homeUrl();
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not read site URL.' );
		}

		if ( '' === $url ) {
			return Finding::skipped( $this->id(), $this->label(), 'Site URL is not set.' );
		}

		if ( str_starts_with( $url, 'https://' ) ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'Site is served over HTTPS. TLS is correctly configured.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    __( 'The site home URL is not using HTTPS. Traffic is transmitted in plain text.', 'wp-security' ),
			recommendation: __( 'Install a TLS certificate (e.g. via Let\'s Encrypt) and update the WordPress home URL and site URL to https://.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'home_url', $url ),
		);
	}
}
