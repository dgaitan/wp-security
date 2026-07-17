<?php
/*
 * Feature: MaintenanceNotesRepository — Sprint 10
 *
 * Scenario: latest() returns null when no notes have been saved yet
 *
 * Scenario: A saved entry is retrievable via latest()
 *   Given notes data and a created_by user id
 *   When save() is called
 *   Then latest() returns it with all fields populated
 *
 * Scenario: latest() returns the most recently saved entry
 *   Given two saved entries
 *   When latest() is called
 *   Then the second (most recent) entry is returned
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Persistence;

use PHPUnit\Framework\TestCase;
use WPSecurity\Persistence\MaintenanceNotesRepository;
use WPSecurity\Tests\Support\FakeWpdb;

final class MaintenanceNotesRepositoryTest extends TestCase {

	private MaintenanceNotesRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new MaintenanceNotesRepository( new FakeWpdb() );
	}

	public function test_latest_returns_null_when_none_saved(): void {
		$this->assertNull( $this->repo->latest() );
	}

	public function test_saved_entry_is_retrievable_via_latest(): void {
		$this->repo->save(
			[
				'run_id'             => 5,
				'time_spent_minutes' => 45,
				'client_notes'       => 'Everything looks good.',
				'follow_up_notes'    => 'Revisit the CDN cache TTL next month.',
			],
			7
		);

		$latest = $this->repo->latest();

		$this->assertNotNull( $latest );
		$this->assertSame( 5, $latest['run_id'] );
		$this->assertSame( 45, $latest['time_spent_minutes'] );
		$this->assertSame( 'Everything looks good.', $latest['client_notes'] );
		$this->assertSame( 'Revisit the CDN cache TTL next month.', $latest['follow_up_notes'] );
		$this->assertSame( 7, $latest['created_by'] );
	}

	public function test_latest_returns_the_most_recently_saved_entry(): void {
		$this->repo->save(
			[
				'run_id'             => null,
				'time_spent_minutes' => null,
				'client_notes'       => 'First',
				'follow_up_notes'    => '',
			],
			1
		);
		$this->repo->save(
			[
				'run_id'             => null,
				'time_spent_minutes' => null,
				'client_notes'       => 'Second',
				'follow_up_notes'    => '',
			],
			1
		);

		$latest = $this->repo->latest();

		$this->assertSame( 'Second', $latest['client_notes'] );
	}
}
