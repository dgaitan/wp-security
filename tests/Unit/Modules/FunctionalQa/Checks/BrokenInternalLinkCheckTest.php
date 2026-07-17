<?php
/*
 * Feature: Broken Internal Link Check — Sprint 10
 *
 * Scenario: broken_internal_links is null → SKIPPED
 * Scenario: no broken links found → PASS
 * Scenario: broken links found → WARN/MEDIUM with evidence
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\BrokenInternalLinkCheck;
use WPSecurity\Tests\Support\MockContext;

final class BrokenInternalLinkCheckTest extends TestCase {

	private BrokenInternalLinkCheck $check;

	protected function setUp(): void {
		$this->check = new BrokenInternalLinkCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.broken_internal_links', $this->check->id() );
	}

	public function test_null_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'broken_internal_links' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'broken_internal_links' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_broken_links_return_warn_medium(): void {
		$broken  = [
			[
				'url'      => 'https://example.test/dead',
				'status'   => 404,
				'found_on' => 'https://example.test/',
			],
		];
		$ctx     = new MockContext( values: [ 'broken_internal_links' => $broken ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( $broken, $finding->evidence->get( 'broken' ) );
	}
}
