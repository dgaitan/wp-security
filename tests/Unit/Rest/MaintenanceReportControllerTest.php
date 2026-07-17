<?php
/*
 * Feature: MaintenanceReportController — Sprint 10
 *
 * Scenario: index() aggregates remediation log, backlog, and notes
 *   Given a saved remediation log entry, an open (WARN) finding, and saved notes
 *   When GET /maintenance-report is called
 *   Then the response contains all three, with the remediation entry's
 *   actor_name resolved from get_userdata()
 *
 * Scenario: index() falls back to "User #id" when get_userdata() returns false
 *
 * Scenario: saveNotes() persists and a subsequent index() reflects the saved values
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\MaintenanceNotesRepository;
use WPSecurity\Persistence\RemediationLogRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\MaintenanceReportController;
use WPSecurity\Tests\Support\FakeWpdb;

final class MaintenanceReportControllerTest extends TestCase {

	private FakeWpdb $wpdb;
	private RemediationLogRepository $remediationLog;
	private FindingRepository $findings;
	private MaintenanceNotesRepository $notes;

	protected function setUp(): void {
		parent::setUp();

		$this->wpdb           = new FakeWpdb();
		$this->remediationLog = new RemediationLogRepository( $this->wpdb );
		$this->findings       = new FindingRepository( $this->wpdb );
		$this->notes          = new MaintenanceNotesRepository( $this->wpdb );

		$GLOBALS['wp_security_test_can']['manage_options'] = true;
		$GLOBALS['wp_security_test_users']                 = [];
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_security_test_can'], $GLOBALS['wp_security_test_users'] );
		parent::tearDown();
	}

	private function controller(): MaintenanceReportController {
		return new MaintenanceReportController( $this->remediationLog, $this->findings, $this->notes );
	}

	public function test_index_includes_remediation_log_with_actor_name(): void {
		$GLOBALS['wp_security_test_users'][7] = 'Jane Doe';

		$this->remediationLog->save(
			'plugins_themes.update_plugin',
			'plugins_themes',
			'plugin-a/plugin-a.php',
			[],
			RemediationResult::success( 'Updated.' ),
			7
		);

		$response = $this->controller()->index( new WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertCount( 1, $data['remediations'] );
		$this->assertSame( 'Jane Doe', $data['remediations'][0]['actor_name'] );
	}

	public function test_index_falls_back_to_user_id_when_user_unknown(): void {
		$this->remediationLog->save( 'a.action', null, null, [], RemediationResult::success( 'ok' ), 99 );

		$response = $this->controller()->index( new WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 'User #99', $data['remediations'][0]['actor_name'] );
	}

	public function test_index_includes_open_findings_backlog(): void {
		$scanRuns = new ScanRunRepository( $this->wpdb );
		$runId    = $scanRuns->create( 'functional_qa' );

		$this->findings->save(
			$runId,
			'functional_qa',
			new Finding(
				checkId:        'functional_qa.homepage_reachability',
				status:         \WPSecurity\Domain\Status::WARN,
				severity:       \WPSecurity\Domain\Severity::CRITICAL,
				title:          'Homepage Reachability',
				description:    'The homepage returned HTTP 500.',
				recommendation: 'Investigate immediately.',
				evidence:       new Evidence(),
			)
		);

		$response = $this->controller()->index( new WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertCount( 1, $data['backlog'] );
		$this->assertSame( 'functional_qa.homepage_reachability', $data['backlog'][0]['check_id'] );
	}

	public function test_index_includes_null_notes_when_none_saved(): void {
		$response = $this->controller()->index( new WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertNull( $data['notes'] );
	}

	public function test_save_notes_persists_and_index_reflects_it(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'time_spent_minutes', 30 );
		$request->set_param( 'client_notes', 'All good this month.' );
		$request->set_param( 'follow_up_notes', 'Watch disk usage.' );

		$this->controller()->saveNotes( $request );

		$response = $this->controller()->index( new WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 30, $data['notes']['time_spent_minutes'] );
		$this->assertSame( 'All good this month.', $data['notes']['client_notes'] );
		$this->assertSame( 'Watch disk usage.', $data['notes']['follow_up_notes'] );
	}
}
