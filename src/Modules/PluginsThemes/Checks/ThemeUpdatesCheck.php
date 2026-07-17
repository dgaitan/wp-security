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
 * Flags installed themes that have available updates.
 *
 * Direct sibling of PluginUpdatesCheck — themes ship security patches the
 * same way plugins do, and an outdated theme is just as viable an attack
 * surface, but update hygiene tooling tends to focus on plugins and miss it.
 */
class ThemeUpdatesCheck implements Check {

	public function id(): string {
		return 'plugins_themes.theme_updates';
	}

	public function label(): string {
		return __( 'Theme Updates', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, string>|null $slugs */
		$slugs = $context->get( 'theme_update_slugs' );

		if ( null === $slugs ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine which themes have available updates.'
			);
		}

		if ( [] === $slugs ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All themes are up to date.', 'wp-security' )
			);
		}

		$count   = count( $slugs );
		$message = 1 === $count
			? __( '1 theme has an available update.', 'wp-security' )
			: sprintf(
				/* translators: %d: number of themes that have available updates */
				__( '%d themes have available updates.', 'wp-security' ),
				$count
			);

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    $message,
			recommendation: __( 'Update all themes promptly to patch known security vulnerabilities. Attackers scan for unpatched versions shortly after CVEs are published.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'themes_needing_update', $slugs ),
		);
	}
}
