<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Module and findings endpoints.
 *
 *   GET /wp-security/v1/modules                       — all modules + latest score
 *   GET /wp-security/v1/modules/{id}/findings         — findings for one module's latest run
 *   POST /wp-security/v1/findings/external            — ingest browser-side findings (axe-core)
 *
 * TODO Sprint 3: implement all methods.
 */
class ModulesController extends AbstractController {

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/modules',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'index' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/modules/(?P<id>[a-z_]+)/findings',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'findings' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
				'args'                => [
					'id' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/findings/external',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'ingestExternal' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
			]
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		// TODO Sprint 3.
		return $this->respond( [] );
	}

	public function findings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// TODO Sprint 3.
		return $this->respond( [] );
	}

	public function ingestExternal( WP_REST_Request $request ): WP_REST_Response {
		// TODO Sprint 7 (Accessibility module).
		return $this->respond( null, 204 );
	}
}
