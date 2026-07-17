<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\MarketingAnalytics\CookieConsentSignatures;

final class CookieConsentSignaturesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_filters'] = [];
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_security_test_filters'] );
		parent::tearDown();
	}

	public function test_built_in_platforms_are_present(): void {
		$signatures = CookieConsentSignatures::signatures();

		$this->assertArrayHasKey( 'Cookiebot', $signatures );
		$this->assertArrayHasKey( 'OneTrust', $signatures );
		$this->assertArrayHasKey( 'Complianz', $signatures );
	}

	public function test_custom_signature_is_added_and_escaped(): void {
		$signatures = CookieConsentSignatures::signatures( 'my.consent(script)' );

		$this->assertArrayHasKey( 'Custom', $signatures );
		$this->assertSame( 1, preg_match( $signatures['Custom'], 'loaded my.consent(script) now' ) );
	}

	public function test_null_custom_signature_adds_no_custom_entry(): void {
		$signatures = CookieConsentSignatures::signatures( null );

		$this->assertArrayNotHasKey( 'Custom', $signatures );
	}

	public function test_third_party_filter_can_add_a_signature(): void {
		add_filter(
			'wp_security/cookie_consent_signatures',
			static function ( array $signatures ): array {
				$signatures['MyPlatform'] = '/my-platform\.js/i';
				return $signatures;
			}
		);

		$signatures = CookieConsentSignatures::signatures();

		$this->assertArrayHasKey( 'MyPlatform', $signatures );
	}
}
