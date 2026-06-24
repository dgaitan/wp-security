<?php

declare( strict_types=1 );

namespace WpSecuritySampleModule;

use WPSecurity\Contracts\Module;
use WpSecuritySampleModule\Checks\SampleCheck;

/**
 * Example WP Security module demonstrating the third-party extension API.
 *
 * Third-party developers can distribute their own modules as standalone plugins
 * or as part of their theme.  All that's needed is:
 *
 *   1. A class implementing WPSecurity\Contracts\Module.
 *   2. A bootstrap.php that hooks into 'wp_security/modules'.
 *
 * See bootstrap.php in this directory for the registration hook.
 */
class SampleModule implements Module {

	public function id(): string {
		return 'sample';
	}

	public function label(): string {
		return 'Sample Module';
	}

	public function icon(): string {
		return 'dashicons-star-filled';
	}

	public function checks(): iterable {
		yield new SampleCheck();
	}
}
