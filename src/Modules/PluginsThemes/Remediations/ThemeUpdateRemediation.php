<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes\Remediations;

use Automatic_Upgrader_Skin;
use Theme_Upgrader;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;

/**
 * Applies a pending theme update via WordPress core's own Theme_Upgrader.
 * Direct sibling of PluginUpdateRemediation.
 *
 * Params: [ 'theme' => 'theme-stylesheet-slug' ] — matches the key used in
 * get_site_transient('theme_updates')->response and ThemeUpdatesCheck's evidence.
 */
class ThemeUpdateRemediation implements RemediationAction {

	public function id(): string {
		return 'plugins_themes.update_theme';
	}

	public function label(): string {
		return __( 'Apply Theme Update', 'wp-security' );
	}

	public function capability(): string {
		return 'update_themes';
	}

	public function describe( array $params ): string {
		$theme = (string) ( $params['theme'] ?? '' );
		return sprintf(
			/* translators: %s: theme stylesheet slug */
			__( 'This will update the theme "%s" to its latest available version.', 'wp-security' ),
			$theme
		);
	}

	public function isAvailable( Context $context, array $params ): bool {
		$theme = (string) ( $params['theme'] ?? '' );

		if ( '' === $theme ) {
			return false;
		}

		/** @var array<int, string>|null $slugs */
		$slugs = $context->get( 'theme_update_slugs' );

		return null !== $slugs && in_array( $theme, $slugs, true );
	}

	public function apply( Context $context, array $params ): RemediationResult {
		$theme = (string) ( $params['theme'] ?? '' );

		if ( '' === $theme ) {
			return RemediationResult::failure( __( 'No theme was specified.', 'wp-security' ) );
		}

		$this->loadUpgraderDependencies();

		$beforeVersion = $this->themeVersion( $theme );

		try {
			$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->upgrade( $theme );
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
				/* translators: %s: theme stylesheet slug */
				__( 'Updated "%s" successfully.', 'wp-security' ),
				$theme
			),
			[ 'version' => $beforeVersion ],
			[ 'version' => $this->themeVersion( $theme ) ]
		);
	}

	private function themeVersion( string $theme ): string {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return '';
		}
		return (string) wp_get_theme( $theme )->get( 'Version' );
	}

	private function loadUpgraderDependencies(): void {
		if ( ! class_exists( Theme_Upgrader::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( Automatic_Upgrader_Skin::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		}
	}
}
