<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\PluginsThemes\Checks\InactivePluginsCheck;
use WPSecurity\Modules\PluginsThemes\Checks\PluginUpdatesCheck;

/**
 * Plugins & Themes module — update hygiene and known CVE detection.
 */
class PluginsThemesModule implements Module {

	public function id(): string {
		return 'plugins_themes';
	}

	public function label(): string {
		return __( 'Plugins & Themes', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-admin-plugins';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new PluginUpdatesCheck(),
			new InactivePluginsCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Plugins & Themes module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/plugins_themes', $checks );
	}
}
