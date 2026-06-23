<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;

/**
 * GET /wp-security/v1/dashboard
 *
 * Returns the aggregated dashboard payload:
 *   - overall_score  (int + grade)
 *   - module_scores  (per-module scores, keyed by module ID)
 *   - top_findings   (highest-severity findings across all modules)
 *   - last_scan_at   (ISO 8601 timestamp of the most recent completed run)
 *   - trend          (array of { date, score } for the sparkline)
 *
 * TODO Sprint 3: implement get().
 */
class DashboardController extends AbstractController {

    public function register(): void {
        register_rest_route(
            self::NAMESPACE,
            '/dashboard',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get' ],
                'permission_callback' => [ $this, 'permissionCheck' ],
            ]
        );
    }

    public function get( WP_REST_Request $request ): WP_REST_Response {
        // TODO Sprint 3.
        return $this->respond( [
            'overall_score' => null,
            'module_scores' => [],
            'top_findings'  => [],
            'last_scan_at'  => null,
            'trend'         => [],
        ] );
    }
}
