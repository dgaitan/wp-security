<?php

declare( strict_types=1 );

/**
 * Sample module bootstrap — registers the module via the wp_security/modules filter.
 *
 * In a real third-party plugin you would require your own autoloader here, or
 * rely on Composer's.  For this bundled example, the classes are required
 * directly so no extra setup is needed.
 */

require_once __DIR__ . '/Checks/SampleCheck.php';
require_once __DIR__ . '/SampleModule.php';

add_filter(
	'wp_security/modules',
	static function ( array $modules ): array {
		$modules[] = new WpSecuritySampleModule\SampleModule();
		return $modules;
	}
);
