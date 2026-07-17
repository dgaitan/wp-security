<?php
/*
 * Feature: Cookie Consent Presence Check — Sprint 10
 *
 * Scenario: homepage_html is null → SKIPPED
 * Scenario: a known platform signature is present → PASS
 * Scenario: no signature matches → INFO, never WARN (no expect_* gate)
 * Scenario: a custom signature from settings matches → PASS
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\MarketingAnalytics\Checks\CookieConsentPresenceCheck;
use WPSecurity\Tests\Support\MockContext;

final class CookieConsentPresenceCheckTest extends TestCase {

	private CookieConsentPresenceCheck $check;

	protected function setUp(): void {
		$this->check = new CookieConsentPresenceCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'marketing_analytics.cookie_consent_presence', $this->check->id() );
	}

	public function test_null_html_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_known_platform_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => '<script src="https://consent.cookiebot.com/uc.js"></script>' ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertStringContainsString( 'Cookiebot', $finding->description );
	}

	public function test_no_match_returns_info_not_warn(): void {
		$ctx     = new MockContext( values: [ 'homepage_html' => '<html></html>' ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::INFO, $finding->status );
	}

	public function test_custom_signature_from_settings_matches(): void {
		$ctx     = new MockContext(
			values: [
				'homepage_html'                   => '<script src="/my-consent-script.js"></script>',
				'cookie_consent_custom_signature' => 'my-consent-script.js',
			]
		);
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}
}
