<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\CoreIntegrity\Checks\CoreFilesCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\CoreUpdateAvailableCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\CronHealthCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\DashboardNoticesCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\RestUserEnumerationCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\SuspiciousFilesCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\VulnerabilityAdvisoryCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\WpConfigCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\WpContentStructureCheck;
use WPSecurity\Modules\CoreIntegrity\Checks\XmlRpcCheck;

/**
 * WordPress Health module — core file integrity and production hardening.
 */
class CoreIntegrityModule implements Module {

	public function id(): string {
		return 'core_integrity';
	}

	public function label(): string {
		return __( 'WordPress Health', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-wordpress-alt';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new CoreFilesCheck(),
			new CoreUpdateAvailableCheck(),
			new WpContentStructureCheck(),
			new SuspiciousFilesCheck(),
			new WpConfigCheck(),
			new XmlRpcCheck(),
			new RestUserEnumerationCheck(),
			new VulnerabilityAdvisoryCheck(),
			new CronHealthCheck(),
			new DashboardNoticesCheck(),
		];

		/**
		 * Allow third-party code to add checks to the WordPress Health module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/core_integrity', $checks );
	}
}
