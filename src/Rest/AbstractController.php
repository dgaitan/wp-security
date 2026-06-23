<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Base class for all REST controllers.
 *
 * Enforces the security invariants that must hold for every endpoint:
 *   - All routes require `manage_options` capability (configurable per-route).
 *   - Nonces are handled automatically by @wordpress/api-fetch on the client.
 *   - Input validation uses the WP REST `args` schema — never raw $_POST access.
 *   - Output is always a typed WP_REST_Response; raw $wpdb rows never leave this layer.
 *
 * TODO Sprint 2: register() implementation and route definitions live in subclasses.
 */
abstract class AbstractController {

	protected const NAMESPACE = 'wp-security/v1';

	/**
	 * Register this controller's routes with the REST API.
	 * Called on the `rest_api_init` action.
	 */
	abstract public function register(): void;

	/**
	 * Default permission callback — requires `manage_options`.
	 * Override in a subclass for finer-grained control.
	 */
	public function permissionCheck( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'wp-security' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Wrap data in a standard success envelope.
	 *
	 * @param mixed $data
	 * @param int   $status HTTP status code.
	 */
	protected function respond( mixed $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	/**
	 * Return a 404 response.
	 */
	protected function notFound( string $message = '' ): WP_Error {
		return new WP_Error(
			'rest_not_found',
			'' !== $message ? $message : __( 'Resource not found.', 'wp-security' ),
			[ 'status' => 404 ]
		);
	}
}
