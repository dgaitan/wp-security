<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks whether PHP OPcache is enabled.
 *
 * OPcache caches compiled PHP bytecode and dramatically reduces response time.
 * Disabled OPcache is a WARN rather than FAIL because the site remains
 * functional — it is purely a performance and resource concern.
 */
class OpcacheCheck implements Check {

	public function id(): string {
		return 'server.opcache';
	}

	public function label(): string {
		return __( 'OPcache', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$enabled = $context->get( 'opcache_enabled' );
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not determine OPcache status.' );
		}

		if ( null === $enabled ) {
			return Finding::skipped( $this->id(), $this->label(), 'OPcache status is unavailable in this environment.' );
		}

		if ( $enabled ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'OPcache is enabled and will improve site response time.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'OPcache is disabled. Without it, PHP must recompile every script on every request.', 'wp-security' ),
			recommendation: __( 'Enable OPcache by setting opcache.enable=1 in php.ini, then restart PHP-FPM.', 'wp-security' ),
		);
	}
}
