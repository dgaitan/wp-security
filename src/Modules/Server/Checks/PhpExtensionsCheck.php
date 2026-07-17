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
 * Verifies that the PHP extensions WordPress depends on are loaded.
 *
 * REQUIRED map: extension name → severity if missing.
 * The Finding severity escalates to the highest missing extension's severity.
 * Note: gd is listed MEDIUM because imagick is an alternative; if neither
 * is loaded, a separate check can be added.
 */
class PhpExtensionsCheck implements Check {

	private const REQUIRED = [
		'curl'     => Severity::HIGH,
		'json'     => Severity::HIGH,
		'mbstring' => Severity::HIGH,
		'openssl'  => Severity::HIGH,
		'gd'       => Severity::MEDIUM,
	];

	public function id(): string {
		return 'server.php_extensions';
	}

	public function label(): string {
		return __( 'PHP Extensions', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$loaded = $context->get( 'php_extensions' );
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not read loaded extensions list.' );
		}

		if ( ! is_array( $loaded ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Extension list is unavailable in this environment.' );
		}

		$loadedLower = array_map( 'strtolower', $loaded );
		$missing     = [];
		$topSeverity = null;

		foreach ( self::REQUIRED as $ext => $severity ) {
			if ( ! in_array( $ext, $loadedLower, true ) ) {
				$missing[ $ext ] = $severity->value;
				if ( null === $topSeverity || $severity->priority() > $topSeverity->priority() ) {
					$topSeverity = $severity;
				}
			}
		}

		if ( [] === $missing || null === $topSeverity ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All required PHP extensions are loaded.', 'wp-security' )
			);
		}

		$status = $topSeverity->priority() >= Severity::HIGH->priority()
			? Status::FAIL
			: Status::WARN;

		return new Finding(
			checkId:        $this->id(),
			status:         $status,
			severity:       $topSeverity,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %s: comma-separated list of missing extensions */
				__( 'The following required PHP extensions are missing: %s.', 'wp-security' ),
				implode( ', ', array_keys( $missing ) )
			),
			recommendation: __( 'Contact your hosting provider and ask them to enable the missing extensions.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'missing', $missing ),
		);
	}
}
