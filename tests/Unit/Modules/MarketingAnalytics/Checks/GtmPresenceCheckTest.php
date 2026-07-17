<?php
/*
 * Feature: GTM Presence Check — Sprint 10
 *
 * Scenario: homepage_html is null → SKIPPED
 * Scenario: GTM container present → PASS
 * Scenario: absent + expect_gtm=true → WARN/MEDIUM
 * Scenario: absent + expect_gtm=false → INFO (unscored)
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\MarketingAnalytics\Checks\GtmPresenceCheck;
use WPSecurity\Tests\Support\MockContext;

final class GtmPresenceCheckTest extends TestCase {

	private GtmPresenceCheck $check;

	protected function setUp(): void {
		$this->check = new GtmPresenceCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'marketing_analytics.gtm_presence', $this->check->id() );
	}

	public function test_null_html_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_gtm_present_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => '<script>GTM-ABC123</script>' ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_absent_and_expected_returns_warn_medium(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html' => '<html></html>',
				'expect_gtm'    => true,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_absent_and_not_expected_returns_info(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html' => '<html></html>',
				'expect_gtm'    => false,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::INFO, $finding->status );
		$this->assertFalse( $finding->affectsScore() );
	}
}
