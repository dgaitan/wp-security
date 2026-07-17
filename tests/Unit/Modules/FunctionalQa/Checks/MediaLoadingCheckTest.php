<?php
/*
 * Feature: Media Loading Check — Sprint 10
 *
 * Scenario: broken_media is null → SKIPPED
 * Scenario: no broken media found → PASS
 * Scenario: broken media found → WARN/MEDIUM with evidence
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\MediaLoadingCheck;
use WPSecurity\Tests\Support\MockContext;

final class MediaLoadingCheckTest extends TestCase {

	private MediaLoadingCheck $check;

	protected function setUp(): void {
		$this->check = new MediaLoadingCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.media_loading', $this->check->id() );
	}

	public function test_null_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'broken_media' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'broken_media' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_broken_media_returns_warn_medium(): void {
		$broken  = [
			[
				'url'      => 'https://example.test/img.jpg',
				'status'   => 404,
				'found_on' => 'https://example.test/',
			],
		];
		$ctx     = new MockContext( values: [ 'broken_media' => $broken ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}
}
