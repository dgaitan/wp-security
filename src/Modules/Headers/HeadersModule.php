<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Headers\Checks\CookieSecurityCheck;
use WPSecurity\Modules\Headers\Checks\HstsCheck;
use WPSecurity\Modules\Headers\Checks\SecurityHeadersCheck;
use WPSecurity\Modules\Headers\Checks\SriCheck;

/**
 * Security Headers module — audits HTTP response headers via a loopback request.
 */
class HeadersModule implements Module {

	public function id(): string {
		return 'headers';
	}

	public function label(): string {
		return __( 'Security Headers', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-shield-alt';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new HstsCheck(),
			new SecurityHeadersCheck(),
			new CookieSecurityCheck(),
			new SriCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Security Headers module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/headers', $checks );
	}
}
