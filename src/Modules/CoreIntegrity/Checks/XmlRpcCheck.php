<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Reports whether XML-RPC is enabled.
 *
 * XML-RPC is enabled by default in WordPress.  When not required it can be
 * exploited for brute-force credential attacks and DDoS amplification via the
 * system.multicall method.
 */
class XmlRpcCheck implements Check {

	public function id(): string {
		return 'core_integrity.xmlrpc';
	}

	public function label(): string {
		return __( 'XML-RPC', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$enabled = $context->get( 'xmlrpc_enabled' );

		if ( null === $enabled ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'XML-RPC status could not be determined.'
			);
		}

		if ( ! $enabled ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'XML-RPC is disabled.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::LOW,
			title:          $this->label(),
			description:    __( 'XML-RPC is enabled. It can be exploited for brute-force attacks and DDoS amplification if not needed.', 'wp-security' ),
			recommendation: __( 'If XML-RPC is not required (e.g. for Jetpack or mobile apps), disable it: add_filter( "xmlrpc_enabled", "__return_false" ).', 'wp-security' ),
		);
	}
}
