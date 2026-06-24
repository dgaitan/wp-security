<?php

declare( strict_types=1 );

namespace WPSecurity\Admin;

/**
 * Registers the top-level WP Security menu and enqueues the React SPA bundle.
 *
 * The admin page is a single empty div (`<div id="wp-security-root">`).
 * React Router takes over from there and renders every section client-side.
 * Bootstrap data (REST root, nonce) is passed via wp_add_inline_script so the
 * SPA never has to make an extra round-trip for auth configuration.
 */
class AdminPage {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'addMenuPage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}

	public function addMenuPage(): void {
		add_menu_page(
			__( 'WP Security', 'wp-security' ),
			__( 'WP Security', 'wp-security' ),
			'manage_options',
			'wp-security',
			[ $this, 'renderPage' ],
			'dashicons-shield',
			80
		);
	}

	public function enqueueAssets( string $hook ): void {
		if ( 'toplevel_page_wp-security' !== $hook ) {
			return;
		}

		$asset_file = WP_SECURITY_DIR . 'build/index.asset.php';
		$asset      = file_exists( $asset_file )
			? ( require $asset_file )
			: [
				'dependencies' => [ 'wp-element', 'wp-api-fetch', 'wp-i18n' ],
				'version'      => WP_SECURITY_VERSION,
			];

		wp_register_script(
			'wp-security-app',
			WP_SECURITY_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_add_inline_script(
			'wp-security-app',
			'window.wpSecurityData = ' . (string) wp_json_encode(
				[
					'restRoot' => rest_url( 'wp-security/v1' ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'homeUrl'  => get_home_url(),
				]
			) . ';',
			'before'
		);

		wp_enqueue_script( 'wp-security-app' );

		if ( file_exists( WP_SECURITY_DIR . 'build/index.css' ) ) {
			wp_register_style( 'wp-security-app', WP_SECURITY_URL . 'build/index.css', [], $asset['version'] );
			wp_enqueue_style( 'wp-security-app' );
		}
	}

	public function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-security' ) );
		}

		echo '<div id="wp-security-root"></div>';
	}
}
