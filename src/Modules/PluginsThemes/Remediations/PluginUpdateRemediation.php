<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes\Remediations;

use Automatic_Upgrader_Skin;
use Plugin_Upgrader;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;

/**
 * Applies a pending plugin update via WordPress core's own Plugin_Upgrader.
 *
 * Params: [ 'plugin' => 'plugin-folder/plugin-file.php' ] — the plugin file
 * key exactly as it appears in get_site_transient('update_plugins')->response
 * and in PluginUpdatesCheck's evidence.
 */
class PluginUpdateRemediation implements RemediationAction {

	public function id(): string {
		return 'plugins_themes.update_plugin';
	}

	public function label(): string {
		return __( 'Apply Plugin Update', 'wp-security' );
	}

	public function capability(): string {
		return 'update_plugins';
	}

	public function describe( array $params ): string {
		$plugin = (string) ( $params['plugin'] ?? '' );
		return sprintf(
			/* translators: %s: plugin file path */
			__( 'This will update the plugin "%s" to its latest available version.', 'wp-security' ),
			$plugin
		);
	}

	public function isAvailable( Context $context, array $params ): bool {
		$plugin = (string) ( $params['plugin'] ?? '' );

		if ( '' === $plugin ) {
			return false;
		}

		/** @var array<int, string>|null $slugs */
		$slugs = $context->get( 'plugin_update_slugs' );

		return null !== $slugs && in_array( $plugin, $slugs, true );
	}

	public function apply( Context $context, array $params ): RemediationResult {
		$plugin = (string) ( $params['plugin'] ?? '' );

		if ( '' === $plugin ) {
			return RemediationResult::failure( __( 'No plugin was specified.', 'wp-security' ) );
		}

		$this->loadUpgraderDependencies();

		$beforeVersion = $this->pluginVersion( $plugin );

		try {
			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->upgrade( $plugin );
		} catch ( \Throwable $e ) {
			return RemediationResult::failure(
				sprintf(
					/* translators: %s: exception message */
					__( 'The update failed unexpectedly: %s', 'wp-security' ),
					$e->getMessage()
				),
				[ 'version' => $beforeVersion ]
			);
		}

		if ( is_wp_error( $result ) ) {
			return RemediationResult::failure( $result->get_error_message(), [ 'version' => $beforeVersion ] );
		}

		if ( true !== $result ) {
			return RemediationResult::failure(
				__( 'The update could not be applied. This usually means the server cannot write files directly — try the native Updates screen in wp-admin, which can prompt for FTP credentials.', 'wp-security' ),
				[ 'version' => $beforeVersion ]
			);
		}

		return RemediationResult::success(
			sprintf(
				/* translators: %s: plugin file path */
				__( 'Updated "%s" successfully.', 'wp-security' ),
				$plugin
			),
			[ 'version' => $beforeVersion ],
			[ 'version' => $this->pluginVersion( $plugin ) ]
		);
	}

	private function pluginVersion( string $plugin ): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pluginFile = WP_PLUGIN_DIR . '/' . $plugin;

		if ( ! file_exists( $pluginFile ) ) {
			return '';
		}

		$data = get_plugin_data( $pluginFile, false, false );
		return (string) $data['Version'];
	}

	private function loadUpgraderDependencies(): void {
		if ( ! class_exists( Plugin_Upgrader::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( Automatic_Upgrader_Skin::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		}
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
