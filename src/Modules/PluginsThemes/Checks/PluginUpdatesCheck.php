<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags installed plugins that have available updates.
 *
 * Outdated plugins are a leading cause of WordPress site compromise because
 * security patches are released with public disclosure of the fixed CVE.
 */
class PluginUpdatesCheck implements Check {

	public function id(): string {
		return 'plugins_themes.plugin_updates';
	}

	public function label(): string {
		return __( 'Plugin Updates', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, string>|null $slugs */
		$slugs = $context->get( 'plugin_update_slugs' );

		if ( null === $slugs ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine which plugins have available updates.'
			);
		}

		if ( [] === $slugs ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All plugins are up to date.', 'wp-security' )
			);
		}

		$count   = count( $slugs );
		$message = 1 === $count
			? __( '1 plugin has an available update.', 'wp-security' )
			: sprintf(
				/* translators: %d: number of plugins that have available updates */
				__( '%d plugins have available updates.', 'wp-security' ),
				$count
			);

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    $message,
			recommendation: __( 'Update all plugins promptly to patch known security vulnerabilities. Attackers scan for unpatched versions shortly after CVEs are published.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'plugins_needing_update', $slugs ),
		);
	}
}
