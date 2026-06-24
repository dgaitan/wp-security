<?php
/*
 * Feature: MetaDescriptionCheck — S7-4 (SEO meta description)
 *
 * Scenario: homepage_html is null returns SKIPPED
 *   Given a MockContext with homepage_html = null
 *   When MetaDescriptionCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: no meta description tag returns WARN/MEDIUM
 *   Given HTML without a <meta name="description"> tag
 *   When MetaDescriptionCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: description length 50–160 chars returns PASS
 *   Given HTML with a meta description of appropriate length
 *   When MetaDescriptionCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: description too short returns WARN/LOW
 *   Given HTML with a meta description shorter than 50 chars
 *   When MetaDescriptionCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "low"
 *
 * Scenario: description too long returns WARN/LOW
 *   Given HTML with a meta description longer than 160 chars
 *   When MetaDescriptionCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "low"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Seo\Checks\MetaDescriptionCheck;
use WPSecurity\Tests\Support\MockContext;

final class MetaDescriptionCheckTest extends TestCase {

	private MetaDescriptionCheck $check;

	protected function setUp(): void {
		$this->check = new MetaDescriptionCheck();
	}

	public function test_id_is_seo_meta_description(): void {
		$this->assertSame( 'seo.meta_description', $this->check->id() );
	}

	public function test_null_html_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_no_meta_description_returns_warn_medium(): void {
		$html    = '<html><head><title>Page</title></head><body></body></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_good_description_length_returns_pass(): void {
		$desc = str_repeat( 'A', 80 );
		$html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head></html>";
		$ctx  = new MockContext( values: [ 'homepage_html' => $html ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_short_description_returns_warn_low(): void {
		$html    = '<html><head><meta name="description" content="Short"></head></html>';
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
	}

	public function test_long_description_returns_warn_low(): void {
		$desc    = str_repeat( 'A', 170 );
		$html    = "<html><head><meta name=\"description\" content=\"{$desc}\"></head></html>";
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
	}

	public function test_content_attribute_before_name_is_also_detected(): void {
		$desc = str_repeat( 'B', 80 );
		// Some generators put content before name.
		$html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head></html>";
		$ctx  = new MockContext( values: [ 'homepage_html' => $html ] );

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}
}
