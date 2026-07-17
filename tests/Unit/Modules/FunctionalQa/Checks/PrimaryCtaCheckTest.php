<?php
/*
 * Feature: Primary CTA Check — Sprint 10
 *
 * Scenario: no CTAs configured → SKIPPED (not WARN)
 * Scenario: all configured CTAs respond successfully → PASS
 * Scenario: one configured CTA is broken → WARN/MEDIUM
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\PrimaryCtaCheck;
use WPSecurity\Tests\Support\MockContext;

final class PrimaryCtaCheckTest extends TestCase {

	private PrimaryCtaCheck $check;

	protected function setUp(): void {
		$this->check = new PrimaryCtaCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.primary_cta', $this->check->id() );
	}

	public function test_empty_list_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'cta_link_statuses' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_all_successful_returns_pass(): void {
		$items   = [
			[
				'label'  => 'Quote',
				'url'    => 'https://example.test/quote',
				'status' => 200,
			],
		];
		$ctx     = new MockContext( values: [ 'cta_link_statuses' => $items ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_one_broken_returns_warn_medium(): void {
		$items   = [
			[
				'label'  => 'Quote',
				'url'    => 'https://example.test/quote',
				'status' => 200,
			],
			[
				'label'  => 'Contact',
				'url'    => 'https://example.test/contact',
				'status' => 404,
			],
		];
		$ctx     = new MockContext( values: [ 'cta_link_statuses' => $items ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertCount( 1, $finding->evidence->get( 'broken' ) );
	}
}
