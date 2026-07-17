<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags when a newer WordPress core version is available.
 *
 * Distinct from CoreFilesCheck (which detects tampering of the *installed*
 * core against known-good checksums): this check is about whether a newer,
 * already-released core version exists at all — the "is my core outdated"
 * question, mirroring PluginUpdatesCheck/ThemeUpdatesCheck for core itself.
 */
class CoreUpdateAvailableCheck implements Check {

	public function id(): string {
		return 'core_integrity.core_update_available';
	}

	public function label(): string {
		return __( 'Core Update', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array{current: string, latest: string, response: string}|null $update */
		$update = $context->get( 'core_update_available' );

		if ( null === $update ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'WordPress core is up to date.', 'wp-security' )
			);
		}

		if ( 'upgrade' !== $update['response'] ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'WordPress core is up to date.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    sprintf(
				/* translators: 1: currently installed core version, 2: latest available core version */
				__( 'WordPress core %1$s is installed; %2$s is available.', 'wp-security' ),
				$update['current'],
				$update['latest']
			),
			recommendation: __( 'Update WordPress core promptly. Core releases frequently include security fixes, and unpatched installs are a leading cause of site compromise.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'current_version', $update['current'] )->add( 'latest_version', $update['latest'] ),
		);
	}
}
