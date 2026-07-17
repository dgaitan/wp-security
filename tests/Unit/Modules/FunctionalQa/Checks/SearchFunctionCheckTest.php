<?php
/*
 * Feature: Search Function Check — Sprint 10
 *
 * Scenario: search_check_status is null → SKIPPED
 * Scenario: status is 2xx/3xx → PASS
 * Scenario: status is 4xx/5xx → WARN/MEDIUM
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\FunctionalQa\Checks\SearchFunctionCheck;
use WPSecurity\Tests\Support\MockContext;

final class SearchFunctionCheckTest extends TestCase {

	private SearchFunctionCheck $check;

	protected function setUp(): void {
		$this->check = new SearchFunctionCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.search_function', $this->check->id() );
	}

	public function test_null_status_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'search_check_status' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_status_200_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'search_check_status' => 200 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_status_404_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'search_check_status' => 404 ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}
}
