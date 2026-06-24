<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Users;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Users\Checks\AdminCountCheck;
use WPSecurity\Modules\Users\Checks\DormantUsersCheck;

/**
 * Users module — dormant account detection and admin privilege auditing.
 */
class UsersModule implements Module {

	public function id(): string {
		return 'users';
	}

	public function label(): string {
		return __( 'Users', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-admin-users';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new DormantUsersCheck(),
			new AdminCountCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Users module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		yield from apply_filters( 'wp_security/checks/users', $checks );
	}
}
