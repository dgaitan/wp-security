<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Server\Checks\DiskSpaceCheck;
use WPSecurity\Modules\Server\Checks\HttpsCheck;
use WPSecurity\Modules\Server\Checks\HttpsRedirectCheck;
use WPSecurity\Modules\Server\Checks\MemoryLimitCheck;
use WPSecurity\Modules\Server\Checks\OpcacheCheck;
use WPSecurity\Modules\Server\Checks\PhpExtensionsCheck;
use WPSecurity\Modules\Server\Checks\PhpVersionCheck;
use WPSecurity\Modules\Server\Checks\TlsCertificateExpiryCheck;

/**
 * Server Health module — first complete vertical slice (Sprint 4).
 *
 * Each check inspects one aspect of the hosting environment and returns a
 * Finding.  Third-party code may add extra checks via the filter below.
 */
class ServerModule implements Module {

	public function id(): string {
		return 'server';
	}

	public function label(): string {
		return __( 'Server Health', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-desktop';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new PhpVersionCheck(),
			new MemoryLimitCheck(),
			new PhpExtensionsCheck(),
			new OpcacheCheck(),
			new DiskSpaceCheck(),
			new HttpsCheck(),
			new HttpsRedirectCheck(),
			new TlsCertificateExpiryCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Server module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/server', $checks );
	}
}
