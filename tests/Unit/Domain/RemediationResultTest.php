<?php
/*
 * Feature: RemediationResult value object — Sprint 9
 *
 * Scenario: success() carries before/after state
 *   Given RemediationResult::success() with before/after arrays
 *   When toArray() is called
 *   Then status is "success" and both states are present
 *
 * Scenario: failure() carries only before state
 *   Given RemediationResult::failure() with a before array
 *   When toArray() is called
 *   Then status is "failed" and after_state is null
 *
 * Scenario: skipped() and queued() carry no state
 *   Given RemediationResult::skipped()/queued()
 *   When toArray() is called
 *   Then before_state and after_state are both null
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Domain\RemediationStatus;

final class RemediationResultTest extends TestCase {

	public function test_success_carries_before_and_after_state(): void {
		$result = RemediationResult::success(
			'Updated successfully.',
			[ 'version' => '1.0.0' ],
			[ 'version' => '1.1.0' ]
		);

		$this->assertSame( RemediationStatus::SUCCESS, $result->status );
		$this->assertSame(
			[
				'status'       => 'success',
				'message'      => 'Updated successfully.',
				'before_state' => [ 'version' => '1.0.0' ],
				'after_state'  => [ 'version' => '1.1.0' ],
			],
			$result->toArray()
		);
	}

	public function test_failure_carries_only_before_state(): void {
		$result = RemediationResult::failure( 'It broke.', [ 'version' => '1.0.0' ] );

		$this->assertSame( RemediationStatus::FAILED, $result->status );
		$this->assertSame( [ 'version' => '1.0.0' ], $result->beforeState );
		$this->assertNull( $result->afterState );
	}

	public function test_skipped_and_queued_carry_no_state(): void {
		$skipped = RemediationResult::skipped( 'Nothing to do.' );
		$queued  = RemediationResult::queued( 'Enqueued.' );

		$this->assertSame( RemediationStatus::SKIPPED, $skipped->status );
		$this->assertNull( $skipped->beforeState );
		$this->assertNull( $skipped->afterState );

		$this->assertSame( RemediationStatus::QUEUED, $queued->status );
		$this->assertNull( $queued->beforeState );
		$this->assertNull( $queued->afterState );
	}
}
