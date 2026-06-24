<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies the PHP runtime is a currently supported release (8.1+).
 *
 * PHP 8.0 reached EOL December 2023; the WordPress minimum moved to 7.2.5
 * but PHP 8.1+ enables JIT, readonly properties, and security-relevant fixes.
 */
class PhpVersionCheck implements Check {

	private const MIN_SECURE = '8.1.0';

	public function id(): string {
		return 'server.php_version';
	}

	public function label(): string {
		return __( 'PHP Version', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$version = $context->phpVersion();
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not read PHP version.' );
		}

		if ( version_compare( $version, self::MIN_SECURE, '>=' ) ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				/* translators: %s: PHP version number */
				sprintf( __( 'PHP %s is a supported, secure release.', 'wp-security' ), $version )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       Severity::HIGH,
			title:          $this->label(),
			/* translators: %s: PHP version number */
			description:    sprintf( __( 'PHP %s is no longer receiving security updates.', 'wp-security' ), $version ),
			recommendation: __( 'Upgrade to PHP 8.1 or later. Contact your hosting provider if you cannot upgrade independently.', 'wp-security' ),
			evidence:       [
				'current'        => $version,
				'minimum_secure' => self::MIN_SECURE,
			],
		);
	}
}
