<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Database;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Database\Checks\AutoloadedOptionsCheck;
use WPSecurity\Modules\Database\Checks\SuspiciousContentCheck;

/**
 * Database module — performance bloat and malware injection detection.
 *
 * All SQL issued by this module's context resolution uses $wpdb->prepare()
 * exclusively, satisfying the Sprint 6 acceptance criterion.
 */
class DatabaseModule implements Module {

	public function id(): string {
		return 'database';
	}

	public function label(): string {
		return __( 'Database', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-database';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new AutoloadedOptionsCheck(),
			new SuspiciousContentCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Database module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		yield from apply_filters( 'wp_security/checks/database', $checks );
	}
}
