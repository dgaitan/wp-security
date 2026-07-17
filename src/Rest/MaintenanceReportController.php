<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\MaintenanceNotesRepository;
use WPSecurity\Persistence\RemediationLogRepository;

/**
 * Maintenance Report endpoint — aggregates data already collected elsewhere
 * (remediation log, open findings backlog, saved notes) into the single
 * client-facing view. No new scan machinery: ScanManager::scanAll() already
 * runs every registered module, so a regular "Run Maintenance Check" is just
 * the existing POST /scans with a distinct label in the UI.
 *
 *   GET  /wp-security/v1/maintenance-report        — aggregated report payload
 *   POST /wp-security/v1/maintenance-report/notes  — save time spent / client notes / follow-up notes
 */
class MaintenanceReportController extends AbstractController {

	private const BACKLOG_LIMIT = 50;

	public function __construct(
		private RemediationLogRepository $remediationLog,
		private FindingRepository $findings,
		private MaintenanceNotesRepository $notes,
	) {}

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/maintenance-report',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'index' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/maintenance-report/notes',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'saveNotes' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
				'args'                => [
					'run_id'             => [
						'type' => 'integer',
					],
					'time_spent_minutes' => [
						'type' => 'integer',
					],
					'client_notes'       => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
					'follow_up_notes'    => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		$remediations = array_map(
			[ $this, 'withActorName' ],
			$this->remediationLog->recent( 20 )
		);

		return $this->respond(
			[
				'remediations' => $remediations,
				'backlog'      => $this->findings->topFindings( self::BACKLOG_LIMIT ),
				'notes'        => $this->notes->latest(),
			]
		);
	}

	public function saveNotes( WP_REST_Request $request ): WP_REST_Response {
		$runId = $request->get_param( 'run_id' );

		$id = $this->notes->save(
			[
				'run_id'             => null !== $runId ? (int) $runId : null,
				'time_spent_minutes' => null !== $request->get_param( 'time_spent_minutes' ) ? (int) $request->get_param( 'time_spent_minutes' ) : null,
				'client_notes'       => (string) ( $request->get_param( 'client_notes' ) ?? '' ),
				'follow_up_notes'    => (string) ( $request->get_param( 'follow_up_notes' ) ?? '' ),
			],
			get_current_user_id()
		);

		return $this->respond( [ 'id' => $id ], 201 );
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function withActorName( array $row ): array {
		$userId = (int) ( $row['user_id'] ?? 0 );
		$user   = $userId > 0 ? get_userdata( $userId ) : false;

		$row['actor_name'] = false !== $user
			? $user->display_name
			: sprintf(
				/* translators: %d: user id */
				__( 'User #%d', 'wp-security' ),
				$userId
			);

		return $row;
	}
}
