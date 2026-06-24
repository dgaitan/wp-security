<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Persistence\FindingRepository;

/**
 * Module and findings endpoints.
 *
 *   GET /wp-security/v1/modules                       — all modules + latest score
 *   GET /wp-security/v1/modules/{id}/findings         — findings for one module's latest run
 *   POST /wp-security/v1/findings/external            — ingest browser-side findings (axe-core)
 */
class ModulesController extends AbstractController {

	public function __construct(
		private ModuleRegistry $registry,
		private FindingRepository $findingRepository,
	) {}

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
		$modules = [];
		foreach ( $this->registry->all() as $module ) {
			$modules[] = [
				'id'    => $module->id(),
				'label' => $module->label(),
				'icon'  => $module->icon(),
				'score' => null,
			];
		}
		return $this->respond( $modules );
	}

	public function findings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (string) $request->get_param( 'id' );
		if ( ! $this->registry->has( $id ) ) {
			return $this->notFound(
				/* translators: %s: module ID */
				sprintf( __( 'Module "%s" not found.', 'wp-security' ), esc_html( $id ) )
			);
		}
		return $this->respond( $this->findingRepository->latestByModule( $id ) );
	}

	public function ingestExternal( WP_REST_Request $request ): WP_REST_Response {
		// TODO Sprint 7 (Accessibility module).
		return $this->respond( null, 204 );
	}
}
