<?php
/*
 * Feature: SitemapCheck — S7-5 (XML sitemap)
 *
 * Scenario: sitemap_reachable is null returns SKIPPED
 *   Given a MockContext with sitemap_reachable = null
 *   When SitemapCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: sitemap_reachable = true returns PASS
 *   Given a MockContext with sitemap_reachable = true
 *   When SitemapCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: sitemap_reachable = false returns WARN/MEDIUM
 *   Given a MockContext with sitemap_reachable = false
 *   When SitemapCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Seo\Checks\SitemapCheck;
use WPSecurity\Tests\Support\MockContext;

final class SitemapCheckTest extends TestCase {

	private SitemapCheck $check;

	protected function setUp(): void {
		$this->check = new SitemapCheck();
	}

	public function test_id_is_seo_sitemap(): void {
		$this->assertSame( 'seo.sitemap', $this->check->id() );
	}

	public function test_null_reachable_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'sitemap_reachable' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_reachable_true_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'sitemap_reachable' => true ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_reachable_false_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'sitemap_reachable' => false ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}
}
