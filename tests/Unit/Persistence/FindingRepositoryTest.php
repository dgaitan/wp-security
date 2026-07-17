<?php
/**
 * Unit tests for FindingRepository.
 *
 * Feature: FindingRepository — Sprint 2 persistence
 *   Background:
 *     Given the WP Security plugin is installed
 *     And an in-memory wpdb double
 *
 *   Scenario: A saved finding can be retrieved for its run
 *     Given a scan run with id 1
 *     And a Finding with checkId "server.php_version" and status "fail"
 *     When save(1, "server", finding) is called
 *     Then forRun(1) returns an array containing the finding
 *     And the retrieved finding has status "fail"
 *
 *   Scenario: Findings can be narrowed to one module
 *     Given findings for two modules in the same run
 *     When forRun(runId, "server") is called
 *     Then only the server module's findings are returned
 *
 *   Scenario: Evidence round-trips as a decoded array
 *     Given a Finding carrying structured evidence
 *     When it is saved and read back
 *     Then the evidence is returned as the original array
 *
 *   Scenario: Top findings surface only WARN/FAIL results
 *     Given a PASS finding and a FAIL finding
 *     When topFindings() is called
 *     Then only the FAIL finding is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Persistence;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Tests\Support\FakeWpdb;

final class FindingRepositoryTest extends TestCase {

	private FindingRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new FindingRepository( new FakeWpdb() );
	}

	public function test_saved_finding_is_retrievable_for_its_run(): void {
		$this->repo->save( 1, 'server', $this->finding( 'server.php_version', Status::FAIL, Severity::HIGH ) );

		$found = $this->repo->forRun( 1 );

		$this->assertCount( 1, $found );
		$this->assertSame( 'server.php_version', $found[0]['check_id'] );
		$this->assertSame( 'fail', $found[0]['status'] );
		$this->assertSame( 'high', $found[0]['severity'] );
		$this->assertSame( 'server', $found[0]['module_id'] );
	}

	public function test_findings_can_be_narrowed_to_one_module(): void {
		$this->repo->save( 1, 'server', $this->finding( 'server.a', Status::PASS, Severity::INFO ) );
		$this->repo->save( 1, 'headers', $this->finding( 'headers.b', Status::WARN, Severity::MEDIUM ) );

		$serverOnly = $this->repo->forRun( 1, 'server' );

		$this->assertCount( 1, $serverOnly );
		$this->assertSame( 'server', $serverOnly[0]['module_id'] );
	}

	public function test_evidence_round_trips_as_array(): void {
		$finding = new Finding(
			'server.memory',
			Status::WARN,
			Severity::MEDIUM,
			'Low memory',
			'Memory limit is low.',
			'Increase the limit.',
			( new Evidence() )
				->add( 'limit', '64M' )
				->add( 'recommended', '256M' )
		);

		$this->repo->save( 7, 'server', $finding );
		$found = $this->repo->forRun( 7 );

		$this->assertSame(
			[
				[
					'key'   => 'limit',
					'label' => 'Limit',
					'type'  => 'scalar',
					'value' => '64M',
				],
				[
					'key'   => 'recommended',
					'label' => 'Recommended',
					'type'  => 'scalar',
					'value' => '256M',
				],
			],
			$found[0]['evidence']
		);
	}

	public function test_top_findings_surface_only_warn_and_fail(): void {
		$this->repo->save( 1, 'server', $this->finding( 'server.ok', Status::PASS, Severity::INFO ) );
		$this->repo->save( 1, 'server', $this->finding( 'server.bad', Status::FAIL, Severity::CRITICAL ) );

		$top = $this->repo->topFindings();

		$this->assertCount( 1, $top );
		$this->assertSame( 'server.bad', $top[0]['check_id'] );
	}

	private function finding( string $checkId, Status $status, Severity $severity ): Finding {
		return new Finding(
			$checkId,
			$status,
			$severity,
			'Title',
			'Description.',
			'Recommendation.'
		);
	}
}
