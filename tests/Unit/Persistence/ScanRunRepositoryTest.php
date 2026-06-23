<?php
/**
 * Unit tests for ScanRunRepository.
 *
 * Feature: ScanRunRepository — Sprint 2 persistence
 *   Background:
 *     Given the WP Security plugin is installed
 *     And an in-memory wpdb double
 *
 *   Scenario: Creating a run inserts a row and returns its ID
 *     Given an empty scan_runs table
 *     When create() is called
 *     Then the returned ID is the new row's auto-increment id
 *     And the row is retrievable via find()
 *
 *   Scenario: A run's lifecycle is persisted
 *     Given a created run
 *     When total, progress, status, and score are updated
 *     Then find() reflects every change
 *     And a terminal status stamps finished_at
 *
 *   Scenario: History returns the most recent runs newest-first
 *     Given several runs
 *     When history(limit) is called
 *     Then it returns at most `limit` runs ordered by id descending
 *
 *   Scenario: Finding a missing run returns null
 *     Given an empty table
 *     When find() is called with an unknown id
 *     Then null is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Persistence;

use PHPUnit\Framework\TestCase;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Tests\Support\FakeWpdb;

final class ScanRunRepositoryTest extends TestCase {

	private ScanRunRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new ScanRunRepository( new FakeWpdb() );
	}

	public function test_create_inserts_and_returns_id(): void {
		$first  = $this->repo->create( null );
		$second = $this->repo->create( 'server' );

		$this->assertSame( 1, $first );
		$this->assertSame( 2, $second );

		$row = $this->repo->find( $second );
		$this->assertNotNull( $row );
		$this->assertSame( 'server', $row['module_id'] );
		$this->assertSame( 'queued', $row['status'] );
		$this->assertSame( 0, $row['progress'] );
		$this->assertSame( 0, $row['total'] );
	}

	public function test_full_run_lifecycle_is_persisted(): void {
		$id = $this->repo->create( null );

		$this->repo->setTotal( $id, 3 );
		$this->repo->incrementProgress( $id );
		$this->repo->incrementProgress( $id );
		$this->repo->updateStatus( $id, 'running' );

		$row = $this->repo->find( $id );
		$this->assertNotNull( $row );
		$this->assertSame( 3, $row['total'] );
		$this->assertSame( 2, $row['progress'] );
		$this->assertSame( 'running', $row['status'] );
		$this->assertNull( $row['finished_at'] );

		$this->repo->updateScore( $id, 85 );
		$this->repo->updateStatus( $id, 'complete' );

		$row = $this->repo->find( $id );
		$this->assertNotNull( $row );
		$this->assertSame( 85, $row['overall_score'] );
		$this->assertSame( 'complete', $row['status'] );
		$this->assertNotNull( $row['finished_at'] );
	}

	public function test_history_returns_recent_runs_newest_first(): void {
		$this->repo->create( null );
		$this->repo->create( null );
		$this->repo->create( null );

		$history = $this->repo->history( 2 );

		$this->assertCount( 2, $history );
		$this->assertSame( 3, $history[0]['id'] );
		$this->assertSame( 2, $history[1]['id'] );
	}

	public function test_find_returns_null_for_unknown_id(): void {
		$this->assertNull( $this->repo->find( 999 ) );
	}
}
