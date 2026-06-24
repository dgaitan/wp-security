<?php
/*
 * Feature: PageTitleCheck — S7-4 (SEO page title)
 *
 * Scenario: homepage_html is null returns SKIPPED
 *   Given a MockContext with homepage_html = null
 *   When PageTitleCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: no <title> tag returns FAIL/HIGH
 *   Given a MockContext with HTML containing no <title> element
 *   When PageTitleCheck::run() is called
 *   Then the Finding status is "fail"
 *   And the Finding severity is "high"
 *
 * Scenario: title length 10–70 chars returns PASS
 *   Given a MockContext with HTML containing <title>Good Page Title</title>
 *   When PageTitleCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: title length < 10 chars returns WARN/MEDIUM
 *   Given a MockContext with HTML containing a 5-character title
 *   When PageTitleCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: title length > 70 chars returns WARN/MEDIUM
 *   Given a MockContext with HTML containing a 75-character title
 *   When PageTitleCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Seo\Checks\PageTitleCheck;
use WPSecurity\Tests\Support\MockContext;

final class PageTitleCheckTest extends TestCase {

	private PageTitleCheck $check;

	protected function setUp(): void {
		$this->check = new PageTitleCheck();
	}

	public function test_id_is_seo_page_title(): void {
		$this->assertSame( 'seo.page_title', $this->check->id() );
	}

	public function test_null_html_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_no_title_tag_returns_fail_high(): void {
		$html    = '<html><head></head><body><h1>No title tag here</h1></body></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_good_title_length_returns_pass(): void {
		$html    = '<html><head><title>Good Page Title Here</title></head></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_short_title_returns_warn_medium(): void {
		$html    = '<html><head><title>Hi</title></head></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_long_title_returns_warn_medium(): void {
		$longTitle = str_repeat( 'A', 75 );
		$html      = "<html><head><title>{$longTitle}</title></head></html>";
		$ctx       = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding   = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_evidence_includes_title_and_length_on_warn(): void {
		$html    = '<html><head><title>Hi</title></head></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertArrayHasKey( 'title', $finding->evidence );
		$this->assertArrayHasKey( 'length', $finding->evidence );
		$this->assertSame( 'Hi', $finding->evidence['title'] );
		$this->assertSame( 2, $finding->evidence['length'] );
	}
}
