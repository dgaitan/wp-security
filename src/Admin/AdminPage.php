<?php

declare( strict_types=1 );

namespace WPSecurity\Admin;

/**
 * Registers the top-level WP Security menu and enqueues the React SPA bundle.
 *
 * The admin page is a single empty div (`<div id="wp-security-root">`).
 * React Router takes over from there and renders every section client-side.
 * Bootstrap data (REST root, nonce, current user capabilities) is passed via
 * wp_add_inline_script so the SPA never has to make an extra REST call.
 *
 * TODO Sprint 3: implement register(), enqueue_assets(), render_page().
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

        // TODO Sprint 3: enqueue build/index.js + build/index.css and pass
        // inline bootstrap data (REST URL, nonce, capabilities).
    }

    public function renderPage(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-security' ) );
        }

        echo '<div id="wp-security-root"></div>';
    }
}
