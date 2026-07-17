<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPSecurity\Admin\RemediationRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Persistence\RemediationLogRepository;

/**
 * Remediation action endpoints — the plugin's one mutating-action surface.
 *
 *   GET  /wp-security/v1/remediations             — available actions
 *   POST /wp-security/v1/remediations/{id}/apply  — apply one action (single or bulk)
 *   GET  /wp-security/v1/remediations/log         — paginated remediation history
 *
 * Every apply() call is gated twice: the REST layer's own manage_options
 * floor (permissionCheck(), inherited from AbstractController), and the
 * action's own declared capability() — plus an explicit confirm:true flag
 * that must be present in the request body, so an authenticated,
 * nonce-valid POST can never mutate anything by accident.
 *
 * Single-item apply runs synchronously (matches the "confirm → immediate
 * feedback" UX). Bulk apply (an `items` array) fans out one Action
 * Scheduler job per item, mirroring ScanManager::enqueue()'s degradation
 * pattern when Action Scheduler is unavailable, and returns a batch_id the
 * client polls via GET /remediations/log?batch_id=...
 */
class RemediationsController extends AbstractController {

	public const ACTION_APPLY = 'wp_security/apply_remediation';

	public const GROUP = 'wp-security';

	public function __construct(
		private RemediationRegistry $registry,
		private RemediationLogRepository $log,
		private Context $context,
	) {}

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/remediations',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'index' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/remediations/(?P<id>[a-z0-9_.]+)/apply',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'apply' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
				'args'                => [
					'id'      => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'confirm' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'params'  => [
						'type'    => 'object',
						'default' => [],
					],
					'items'   => [
						'type'    => 'array',
						'default' => [],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/remediations/log',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'log' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
				'args'                => [
					'batch_id' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'limit'    => [
						'type'    => 'integer',
						'default' => 20,
					],
				],
			]
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		$actions = [];
		foreach ( $this->registry->all() as $action ) {
			$actions[] = [
				'id'         => $action->id(),
				'label'      => $action->label(),
				'capability' => $action->capability(),
			];
		}
		return $this->respond( $actions );
	}

	public function apply( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id     = (string) $request->get_param( 'id' );
		$action = $this->registry->get( $id );

		if ( null === $action ) {
			return $this->notFound( __( 'Remediation action not found.', 'wp-security' ) );
		}

		if ( ! current_user_can( $action->capability() ) ) {
			return new WP_Error(
				'wp_security_remediation_forbidden',
				__( 'You do not have permission to perform this action.', 'wp-security' ),
				[ 'status' => 403 ]
			);
		}

		if ( true !== $request->get_param( 'confirm' ) ) {
			return new WP_Error(
				'wp_security_remediation_not_confirmed',
				__( 'This action requires explicit confirmation.', 'wp-security' ),
				[ 'status' => 400 ]
			);
		}

		/** @var array<int, mixed> $items */
		$items  = (array) $request->get_param( 'items' );
		$userId = get_current_user_id();

		if ( [] !== $items ) {
			return $this->applyBulk( $action, $items, $userId );
		}

		/** @var array<string, mixed> $params */
		$params = (array) $request->get_param( 'params' );

		if ( ! $action->isAvailable( $this->context, $params ) ) {
			return new WP_Error(
				'wp_security_remediation_unavailable',
				__( 'This action is no longer applicable.', 'wp-security' ),
				[ 'status' => 409 ]
			);
		}

		$result = $this->runAndLog( $action, $params, $userId, null );

		return $this->respond( $result->toArray() );
	}

	public function log( WP_REST_Request $request ): WP_REST_Response {
		$batchId = $request->get_param( 'batch_id' );

		if ( null !== $batchId && '' !== $batchId ) {
			return $this->respond( $this->log->forBatch( (string) $batchId ) );
		}

		$limit = (int) $request->get_param( 'limit' );
		return $this->respond( $this->log->recent( $limit > 0 ? $limit : 20 ) );
	}

	/**
	 * Action Scheduler callback: apply one item of a bulk batch and log it.
	 * Registered against self::ACTION_APPLY in Plugin::registerHooks().
	 *
	 * @param array<string, mixed> $params
	 */
	public function applyJob( string $actionId, array $params, int $userId, ?string $batchId ): void {
		$action = $this->registry->get( $actionId );

		if ( null === $action ) {
			return;
		}

		if ( ! $action->isAvailable( $this->context, $params ) ) {
			$this->log->save(
				$actionId,
				$this->moduleIdFromActionId( $actionId ),
				$this->targetFromParams( $params ),
				$params,
				RemediationResult::skipped( __( 'No longer applicable at execution time.', 'wp-security' ) ),
				$userId,
				$batchId
			);
			return;
		}

		$this->runAndLog( $action, $params, $userId, $batchId );
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private function runAndLog( RemediationAction $action, array $params, int $userId, ?string $batchId ): RemediationResult {
		try {
			$result = $action->apply( $this->context, $params );
		} catch ( \Throwable $e ) {
			$result = RemediationResult::failure( 'The action failed unexpectedly: ' . $e->getMessage() );
		}

		$this->log->save(
			$action->id(),
			$this->moduleIdFromActionId( $action->id() ),
			$this->targetFromParams( $params ),
			$params,
			$result,
			$userId,
			$batchId
		);

		return $result;
	}

	/**
	 * @param array<int, mixed> $items
	 */
	private function applyBulk( RemediationAction $action, array $items, int $userId ): WP_REST_Response {
		$batchId = wp_generate_uuid4();

		foreach ( $items as $item ) {
			$params = is_array( $item ) ? $item : [];
			$this->enqueue( $action->id(), $params, $userId, $batchId );
		}

		return $this->respond(
			[
				'batch_id'   => $batchId,
				'item_count' => count( $items ),
			],
			202
		);
	}

	/**
	 * Enqueue one item of a bulk batch, falling back to synchronous execution
	 * only when Action Scheduler is unavailable — same degradation pattern as
	 * ScanManager::enqueue().
	 *
	 * @param array<string, mixed> $params
	 */
	private function enqueue( string $actionId, array $params, int $userId, string $batchId ): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_APPLY, [ $actionId, $params, $userId, $batchId ], self::GROUP );
			return;
		}

		$this->applyJob( $actionId, $params, $userId, $batchId );
	}

	/**
	 * RemediationAction ids follow the same dot-namespaced convention as
	 * Check ids (e.g. "plugins_themes.update_plugin") — derive the owning
	 * module id from that prefix rather than adding a moduleId() method to
	 * the contract.
	 */
	private function moduleIdFromActionId( string $actionId ): ?string {
		$dotPosition = strpos( $actionId, '.' );
		return false !== $dotPosition ? substr( $actionId, 0, $dotPosition ) : null;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private function targetFromParams( array $params ): ?string {
		$target = $params['target'] ?? $params['plugin'] ?? $params['theme'] ?? null;
		return null !== $target ? (string) $target : null;
	}
}
