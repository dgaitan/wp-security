<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Remediations;

use Automatic_Upgrader_Skin;
use Core_Upgrader;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;

/**
 * Applies a pending WordPress core update via core's own Core_Upgrader.
 *
 * The highest-blast-radius action in the plugin — gated behind the
 * `enable_core_update_remediation` setting (default off) in addition to the
 * standard capability + confirm gates every other remediation goes through.
 * Plugin/theme remediation ship enabled by default; this one does not.
 */
class CoreUpdateRemediation implements RemediationAction {

	private const SETTINGS_OPTION = 'wp_security_settings';

	public function id(): string {
		return 'core_integrity.update_core';
	}

	public function label(): string {
		return __( 'Apply Core Update', 'wp-security' );
	}

	public function capability(): string {
		return 'update_core';
	}

	public function describe( array $params ): string {
		return __( 'This will update WordPress core to its latest available version. Core updates carry the highest risk of any action in this plugin — verify you have a recent backup before proceeding.', 'wp-security' );
	}

	public function isAvailable( Context $context, array $params ): bool {
		if ( ! $this->isEnabledInSettings() ) {
			return false;
		}

		/** @var array{current: string, latest: string, response: string}|null $update */
		$update = $context->get( 'core_update_available' );

		return null !== $update && 'upgrade' === $update['response'];
	}

	public function apply( Context $context, array $params ): RemediationResult {
		if ( ! $this->isEnabledInSettings() ) {
			return RemediationResult::skipped(
				__( 'Core update remediation is disabled. Enable it in Settings before applying a core update through this plugin.', 'wp-security' )
			);
		}

		$this->loadUpgraderDependencies();

		$beforeVersion = get_bloginfo( 'version' );
		$updates       = get_core_updates();
		$target        = null;

		if ( is_array( $updates ) ) {
			foreach ( $updates as $update ) {
				if ( is_object( $update ) && isset( $update->response ) && 'upgrade' === $update->response ) {
					$target = $update;
					break;
				}
			}
		}

		if ( null === $target ) {
			return RemediationResult::skipped( __( 'No core update is currently available.', 'wp-security' ) );
		}

		try {
			$upgrader = new Core_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->upgrade( $target );
		} catch ( \Throwable $e ) {
			return RemediationResult::failure(
				sprintf(
					/* translators: %s: exception message */
					__( 'The core update failed unexpectedly: %s', 'wp-security' ),
					$e->getMessage()
				),
				[ 'version' => $beforeVersion ]
			);
		}

		if ( is_wp_error( $result ) ) {
			return RemediationResult::failure( $result->get_error_message(), [ 'version' => $beforeVersion ] );
		}

		// Unlike Plugin_Upgrader/Theme_Upgrader (which return bool|WP_Error),
		// Core_Upgrader::upgrade() returns the new version string on success,
		// or false|WP_Error on failure — it never returns boolean true.
		if ( ! is_string( $result ) || '' === $result ) {
			return RemediationResult::failure(
				__( 'The core update could not be applied. This usually means the server cannot write files directly — try the native Updates screen in wp-admin, which can prompt for FTP credentials.', 'wp-security' ),
				[ 'version' => $beforeVersion ]
			);
		}

		return RemediationResult::success(
			__( 'WordPress core was updated successfully.', 'wp-security' ),
			[ 'version' => $beforeVersion ],
			[ 'version' => $result ]
		);
	}

	private function isEnabledInSettings(): bool {
		$settings = (array) get_option( self::SETTINGS_OPTION, [] );
		return ! empty( $settings['enable_core_update_remediation'] );
	}

	private function loadUpgraderDependencies(): void {
		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! class_exists( Core_Upgrader::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( Automatic_Upgrader_Skin::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		}
	}
}
