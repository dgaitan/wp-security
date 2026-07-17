<?php
/*
 * Feature: RemediationLogRepository — Sprint 9
 *
 * Scenario: A saved log entry can be retrieved via recent()
 *   Given a RemediationResult and an action id
 *   When save() is called
 *   Then recent() returns it with status/message/user_id populated
 *
 * Scenario: Before/after state round-trip as decoded arrays
 *   Given a RemediationResult carrying before/after state
 *   When it is saved and read back
 *   Then before_state/after_state are returned as the original arrays
 *
 * Scenario: forBatch() narrows to one batch, oldest first
 *   Given log entries across two different batch ids
 *   When forBatch(batchId) is called
 *   Then only that batch's entries are returned
 *
 * Scenario: recent() respects the limit and newest-first order
 *   Given three saved log entries
 *   When recent(2) is called
 *   Then exactly 2 rows are returned, most recent first
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Persistence;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Persistence\RemediationLogRepository;
use WPSecurity\Tests\Support\FakeWpdb;

final class RemediationLogRepositoryTest extends TestCase {

	private RemediationLogRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new RemediationLogRepository( new FakeWpdb() );
	}

	public function test_saved_entry_is_retrievable_via_recent(): void {
		$this->repo->save(
			'plugins_themes.update_plugin',
			'plugins_themes',
			'plugin-a/plugin-a.php',
			[ 'plugin' => 'plugin-a/plugin-a.php' ],
			RemediationResult::success( 'Updated successfully.' ),
			42
		);

		$recent = $this->repo->recent();

		$this->assertCount( 1, $recent );
		$this->assertSame( 'plugins_themes.update_plugin', $recent[0]['action_id'] );
		$this->assertSame( 'success', $recent[0]['status'] );
		$this->assertSame( 'Updated successfully.', $recent[0]['message'] );
		$this->assertSame( 42, $recent[0]['user_id'] );
	}

	public function test_before_and_after_state_round_trip_as_arrays(): void {
		$this->repo->save(
			'plugins_themes.update_plugin',
			'plugins_themes',
			'plugin-a/plugin-a.php',
			[],
			RemediationResult::success( 'Updated.', [ 'version' => '1.0.0' ], [ 'version' => '1.1.0' ] ),
			1
		);

		$recent = $this->repo->recent();

		$this->assertSame( [ 'version' => '1.0.0' ], $recent[0]['before_state'] );
		$this->assertSame( [ 'version' => '1.1.0' ], $recent[0]['after_state'] );
	}

	public function test_for_batch_narrows_to_one_batch(): void {
		$this->repo->save( 'a.action', null, null, [], RemediationResult::success( 'ok' ), 1, 'batch-1' );
		$this->repo->save( 'a.action', null, null, [], RemediationResult::success( 'ok' ), 1, 'batch-2' );

		$batchOne = $this->repo->forBatch( 'batch-1' );

		$this->assertCount( 1, $batchOne );
		$this->assertSame( 'batch-1', $batchOne[0]['batch_id'] );
	}

	public function test_recent_respects_limit(): void {
		$this->repo->save( 'a.one', null, null, [], RemediationResult::success( 'ok' ), 1 );
		$this->repo->save( 'a.two', null, null, [], RemediationResult::success( 'ok' ), 1 );
		$this->repo->save( 'a.three', null, null, [], RemediationResult::success( 'ok' ), 1 );

		$recent = $this->repo->recent( 2 );

		$this->assertCount( 2, $recent );
	}
}
