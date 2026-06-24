<?php
/*
 * Feature: ScanContext memoization — lazy + cached resolver evaluation
 *
 * Scenario: response_headers, ttfb_ms, and homepage_html share one HTTP request
 *   Given the home URL stub returns a 200 response
 *   When get('response_headers'), get('ttfb_ms'), and get('homepage_html') are called
 *   Then wp_remote_get is invoked exactly once for the home URL
 *
 * Scenario: requesting the same key a second time does not re-run the resolver
 *   Given get('response_headers') has been called once
 *   When get('response_headers') is called again
 *   Then wp_remote_get is still invoked exactly once (no additional HTTP call)
 *
 * Scenario: a key that resolved to null is not re-evaluated
 *   Given the home URL stub returns a WP_Error (no stub configured)
 *   When get('response_headers') is called twice
 *   Then wp_remote_get is invoked exactly once and null is returned both times
 *
 * Scenario: ttfb_ms returns a positive float from the shared loopback timing
 *   Given the home URL stub returns a 200 response
 *   When get('ttfb_ms') is called
 *   Then the result is a float >= 0
 *
 * Scenario: homepage_html returns the body from the shared loopback response
 *   Given the home URL stub returns body '<html><head></head></html>'
 *   When get('homepage_html') is called
 *   Then the result is '<html><head></head></html>'
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Scanning\ScanContext;

final class ScanContextMemoizationTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_http_responses']  = [];
		$GLOBALS['wp_security_test_http_call_count'] = [];
		$GLOBALS['wp_security_test_filters']         = [];
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset(
			$GLOBALS['wp_security_test_http_responses'],
			$GLOBALS['wp_security_test_http_call_count'],
			$GLOBALS['wp_security_test_filters']
		);
	}

	private function stubHomeUrl( int $code = 200, string $body = '<html></html>' ): void {
		$GLOBALS['wp_security_test_http_responses']['https://example.test'] = [
			'code'    => $code,
			'body'    => $body,
			'headers' => [ 'content-encoding' => 'gzip' ],
		];
	}

	private function homeCallCount(): int {
		return $GLOBALS['wp_security_test_http_call_count']['https://example.test'] ?? 0;
	}

	public function test_three_loopback_keys_share_one_http_request(): void {
		$this->stubHomeUrl();
		$ctx = new ScanContext();

		$ctx->get( 'response_headers' );
		$ctx->get( 'ttfb_ms' );
		$ctx->get( 'homepage_html' );

		$this->assertSame( 1, $this->homeCallCount() );
	}

	public function test_repeated_key_does_not_re_run_resolver(): void {
		$this->stubHomeUrl();
		$ctx = new ScanContext();

		$ctx->get( 'response_headers' );
		$ctx->get( 'response_headers' );

		$this->assertSame( 1, $this->homeCallCount() );
	}

	public function test_null_result_is_cached_and_not_re_evaluated(): void {
		// No stub → wp_remote_get returns WP_Error → response_headers = null.
		$ctx = new ScanContext();

		$first  = $ctx->get( 'response_headers' );
		$second = $ctx->get( 'response_headers' );

		$this->assertNull( $first );
		$this->assertNull( $second );
		$this->assertSame( 1, $this->homeCallCount() );
	}

	public function test_ttfb_ms_returns_non_negative_float(): void {
		$this->stubHomeUrl( 200 );
		$ctx = new ScanContext();

		$ttfb = $ctx->get( 'ttfb_ms' );

		$this->assertIsFloat( $ttfb );
		$this->assertGreaterThanOrEqual( 0.0, $ttfb );
	}

	public function test_homepage_html_returns_body_from_shared_response(): void {
		$this->stubHomeUrl( 200, '<html><head></head></html>' );
		$ctx = new ScanContext();

		$html = $ctx->get( 'homepage_html' );

		$this->assertSame( '<html><head></head></html>', $html );
	}

	public function test_homepage_html_and_response_headers_share_one_call(): void {
		$this->stubHomeUrl( 200, '<html></html>' );
		$ctx = new ScanContext();

		$html    = $ctx->get( 'homepage_html' );
		$headers = $ctx->get( 'response_headers' );

		$this->assertSame( 1, $this->homeCallCount() );
		$this->assertSame( '<html></html>', $html );
		$this->assertArrayHasKey( 'content-encoding', (array) $headers );
	}
}
