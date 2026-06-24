<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Dns;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Dns\Checks\DmarcCheck;
use WPSecurity\Modules\Dns\Checks\SpfCheck;

/**
 * DNS module — audits DNS records relevant to email anti-spoofing.
 */
class DnsModule implements Module {

	public function id(): string {
		return 'dns';
	}

	public function label(): string {
		return __( 'DNS', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-admin-site-alt3';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new SpfCheck(),
			new DmarcCheck(),
		];

		/**
		 * Allow third-party code to add checks to the DNS module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/dns', $checks );
	}
}
