<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Scan lifecycle endpoints.
 *
 *   POST /wp-security/v1/scans          — start a scan
 *   GET  /wp-security/v1/scans/{id}     — poll run status
 *   GET  /wp-security/v1/history        — paginated run history
 *
 * POST body: { "module": "all" | "<module-id>" }
 * Returns:   { "run_id": int }
 *
 * TODO Sprint 2: implement all methods.
 */
class ScansController extends AbstractController {

    public function register(): void {
        register_rest_route(
            self::NAMESPACE,
            '/scans',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create' ],
                'permission_callback' => [ $this, 'permissionCheck' ],
                'args'                => [
                    'module' => [
                        'type'              => 'string',
                        'default'           => 'all',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/scans/(?P<id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'status' ],
                'permission_callback' => [ $this, 'permissionCheck' ],
                'args'                => [
                    'id' => [
                        'type'    => 'integer',
                        'minimum' => 1,
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/history',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'history' ],
                'permission_callback' => [ $this, 'permissionCheck' ],
            ]
        );
    }

    public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        // TODO Sprint 2.
        return $this->respond( [ 'run_id' => 0 ], 202 );
    }

    public function status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        // TODO Sprint 2.
        return $this->respond( [ 'status' => 'unknown', 'progress' => 0, 'total' => 0 ] );
    }

    public function history( WP_REST_Request $request ): WP_REST_Response {
        // TODO Sprint 2.
        return $this->respond( [] );
    }
}
