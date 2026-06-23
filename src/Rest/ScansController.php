<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPSecurity\Contracts\Scanner;
use WPSecurity\Persistence\ScanRunRepository;

/**
 * Scan lifecycle endpoints.
 *
 *   POST /wp-security/v1/scans          — start a scan
 *   GET  /wp-security/v1/scans/{id}     — poll run status
 *   GET  /wp-security/v1/history        — paginated run history
 *
 * POST body: { "module": "all" | "<module-id>" }
 * Returns:   { "run_id": int }
 */
class ScansController extends AbstractController {

	public function __construct(
		private Scanner $scanner,
		private ScanRunRepository $runs,
	) {}

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
		$module = (string) $request->get_param( 'module' );

		try {
			$runId = ( '' === $module || 'all' === $module )
				? $this->scanner->scanAll()
				: $this->scanner->scanModule( $module );
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'wp_security_scan_failed',
				__( 'The scan could not be started.', 'wp-security' ),
				[ 'status' => 500 ]
			);
		}

		return $this->respond( [ 'run_id' => $runId ], 202 );
	}

	public function status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( null === $this->runs->find( $id ) ) {
			return $this->notFound( __( 'Scan run not found.', 'wp-security' ) );
		}

		return $this->respond( $this->scanner->status( $id ) );
	}

	public function history( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->runs->history() );
	}
}
