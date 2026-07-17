<?php
/*
 * Feature: Meta Pixel Presence Check — Sprint 10
 *
 * Scenario: only the init call is present (no fbevents.js signature) → not detected
 * Scenario: both the init call and fbevents.js signature are present → PASS
 * Scenario: absent + expect_meta_pixel=true → WARN/MEDIUM
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\MarketingAnalytics\Checks\MetaPixelPresenceCheck;
use WPSecurity\Tests\Support\MockContext;

final class MetaPixelPresenceCheckTest extends TestCase {

	private MetaPixelPresenceCheck $check;

	protected function setUp(): void {
		$this->check = new MetaPixelPresenceCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'marketing_analytics.meta_pixel_presence', $this->check->id() );
	}

	public function test_init_call_alone_is_not_detected(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html'     => "fbq('init', '123456')",
				'expect_meta_pixel' => false,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::INFO, $finding->status );
	}

	public function test_init_and_signature_together_return_pass(): void {
		$html    = "fbq('init', '123456'); <script src=\"https://connect.facebook.net/en_US/fbevents.js\"></script>";
		$ctx     = new MockContext( values: [ 'homepage_html' => $html ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_absent_and_expected_returns_warn(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html'     => '<html></html>',
				'expect_meta_pixel' => true,
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
