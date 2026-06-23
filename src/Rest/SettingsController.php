<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Settings read/write endpoint.
 *
 *   GET  /wp-security/v1/settings  — retrieve current configuration
 *   POST /wp-security/v1/settings  — update configuration
 *
 * Settings are stored in the options API under `wp_security_settings`
 * WITHOUT autoload so they don't hit every page request.  API keys are
 * stored as-is but NEVER returned in full to the client — only a masked
 * preview is returned (e.g. "SG.••••••••••••••••").
 *
 * TODO Sprint 8: implement get() and update() with full validation schema.
 */
class SettingsController extends AbstractController {

    private const OPTION_KEY = 'wp_security_settings';

    public function register(): void {
        register_rest_route(
            self::NAMESPACE,
            '/settings',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get' ],
                    'permission_callback' => [ $this, 'permissionCheck' ],
                ],
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'update' ],
                    'permission_callback' => [ $this, 'permissionCheck' ],
                ],
            ]
        );
    }

    public function get( WP_REST_Request $request ): WP_REST_Response {
        // TODO Sprint 8: return masked settings.
        return $this->respond( get_option( self::OPTION_KEY, [] ) );
    }

    public function update( WP_REST_Request $request ): WP_REST_Response {
        // TODO Sprint 8: sanitize, validate, persist.
        return $this->respond( null, 204 );
    }
}
