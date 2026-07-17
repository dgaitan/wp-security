<?php
/*
 * Feature: Primary Navigation Check — Sprint 10
 *
 * Scenario: nav_links is null → SKIPPED
 * Scenario: nav_links is empty → WARN/LOW
 * Scenario: nav_links has entries → PASS with evidence
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\PrimaryNavigationCheck;
use WPSecurity\Tests\Support\MockContext;

final class PrimaryNavigationCheckTest extends TestCase {

	private PrimaryNavigationCheck $check;

	protected function setUp(): void {
		$this->check = new PrimaryNavigationCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.primary_navigation', $this->check->id() );
	}

	public function test_null_links_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'nav_links' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_links_returns_warn_low(): void {
		$ctx     = new MockContext( values: [ 'nav_links' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
	}

	public function test_links_present_returns_pass_with_evidence(): void {
		$links   = [
			[
				'url'  => '/about/',
				'text' => 'About',
			],
		];
		$ctx     = new MockContext( values: [ 'nav_links' => $links ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertSame( $links, $finding->evidence->get( 'nav_links' ) );
	}
}
