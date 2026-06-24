<?php
/*
 * Feature: CanonicalCheck — S7-4 (SEO canonical URL)
 *
 * Scenario: homepage_html is null returns SKIPPED
 *   Given a MockContext with homepage_html = null
 *   When CanonicalCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: canonical link tag present returns PASS
 *   Given HTML with <link rel="canonical" href="...">
 *   When CanonicalCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: no canonical link tag returns WARN/LOW
 *   Given HTML without any canonical link tag
 *   When CanonicalCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "low"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Seo\Checks\CanonicalCheck;
use WPSecurity\Tests\Support\MockContext;

final class CanonicalCheckTest extends TestCase {

	private CanonicalCheck $check;

	protected function setUp(): void {
		$this->check = new CanonicalCheck();
	}

	public function test_id_is_seo_canonical(): void {
		$this->assertSame( 'seo.canonical', $this->check->id() );
	}

	public function test_null_html_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_canonical_tag_present_returns_pass(): void {
		$html    = '<html><head><link rel="canonical" href="https://example.test/"></head></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_no_canonical_tag_returns_warn_low(): void {
		$html    = '<html><head><title>Page</title></head><body></body></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
	}
}
