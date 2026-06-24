<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags plugins that are installed but not active.
 *
 * Inactive plugins still exist on disk and can be exploited if they contain
 * vulnerabilities; they are dormant attack surface that should be removed.
 */
class InactivePluginsCheck implements Check {

	public function id(): string {
		return 'plugins_themes.inactive_plugins';
	}

	public function label(): string {
		return __( 'Inactive Plugins', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<string, array<string, mixed>>|null $plugins */
		$plugins = $context->get( 'plugins' );
		/** @var array<int, string>|null $activePlugins */
		$activePlugins = $context->get( 'active_plugins' );

		if ( null === $plugins || null === $activePlugins ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not retrieve the plugin list.'
			);
		}

		$inactive = [];
		foreach ( array_keys( $plugins ) as $pluginFile ) {
			if ( ! in_array( $pluginFile, $activePlugins, true ) ) {
				$inactive[] = $pluginFile;
			}
		}

		if ( [] === $inactive ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No inactive plugins found.', 'wp-security' )
			);
		}

		$count   = count( $inactive );
		$message = 1 === $count
			? __( '1 inactive plugin is installed.', 'wp-security' )
			: sprintf(
				/* translators: %d: number of inactive plugins installed */
				__( '%d inactive plugins are installed.', 'wp-security' ),
				$count
			);

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    $message,
			recommendation: __( 'Remove inactive plugins to reduce your attack surface. Even deactivated plugins can be exploited if they contain vulnerabilities, because their files are still accessible on disk.', 'wp-security' ),
			evidence:       [ 'inactive_plugins' => $inactive ],
		);
	}
}
