<?php
/*
 * Feature: Key Landing Pages Check — Sprint 10
 *
 * Scenario: no landing pages configured → SKIPPED (not WARN)
 * Scenario: all configured pages respond successfully → PASS
 * Scenario: one configured page is broken → WARN/MEDIUM
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\KeyLandingPagesCheck;
use WPSecurity\Tests\Support\MockContext;

final class KeyLandingPagesCheckTest extends TestCase {

	private KeyLandingPagesCheck $check;

	protected function setUp(): void {
		$this->check = new KeyLandingPagesCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.key_landing_pages', $this->check->id() );
	}

	public function test_empty_list_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'key_landing_page_statuses' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_all_successful_returns_pass(): void {
		$items   = [
			[
				'label'  => 'Pricing',
				'url'    => 'https://example.test/pricing',
				'status' => 200,
			],
		];
		$ctx     = new MockContext( values: [ 'key_landing_page_statuses' => $items ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_one_broken_returns_warn_medium(): void {
		$items   = [
			[
				'label'  => 'Pricing',
				'url'    => 'https://example.test/pricing',
				'status' => 200,
			],
			[
				'label'  => 'Docs',
				'url'    => 'https://example.test/docs',
				'status' => 500,
			],
		];
		$ctx     = new MockContext( values: [ 'key_landing_page_statuses' => $items ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}
}
