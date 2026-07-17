<?php
/*
 * Feature: GA4 Presence Check — Sprint 10
 *
 * Scenario: only the measurement-ID pattern is present (no gtag signature) → not detected
 * Scenario: both the measurement ID and gtag signature are present → PASS
 * Scenario: absent + expect_ga4=true → WARN/MEDIUM
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\MarketingAnalytics\Checks\Ga4PresenceCheck;
use WPSecurity\Tests\Support\MockContext;

final class Ga4PresenceCheckTest extends TestCase {

	private Ga4PresenceCheck $check;

	protected function setUp(): void {
		$this->check = new Ga4PresenceCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'marketing_analytics.ga4_presence', $this->check->id() );
	}

	public function test_measurement_id_alone_is_not_detected(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html' => 'G-ABCDEF1234',
				'expect_ga4'    => false,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::INFO, $finding->status );
	}

	public function test_id_and_signature_together_return_pass(): void {
		$html    = "gtag('config', 'G-ABCDEF1234'); <script src=\"https://www.googletagmanager.com/gtag/js?id=G-ABCDEF1234\"></script>";
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_absent_and_expected_returns_warn(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html' => '<html></html>',
				'expect_ga4'    => true,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
