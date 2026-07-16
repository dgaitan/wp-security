<?php

/**
 * Feature: HttpsRedirectCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: HTTP redirects directly to HTTPS returns PASS
 *     Given a mocked Context with https_redirect_chain of one http hop (301) followed by one https hop (200)
 *     When HttpsRedirectCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Site serves content over plain HTTP with no redirect returns FAIL with HIGH severity
 *     Given a mocked Context with https_redirect_chain of a single http hop returning 200
 *     When HttpsRedirectCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *
 *   Scenario: Redirect chain passes through more than one insecure hop returns WARN with MEDIUM severity
 *     Given a mocked Context with https_redirect_chain of two http hops followed by one https hop
 *     When HttpsRedirectCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: First hop cannot be reached over HTTP returns PASS
 *     Given a mocked Context with https_redirect_chain containing a single hop with status 0
 *     When HttpsRedirectCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: https_redirect_chain null returns SKIPPED
 *     Given a mocked Context where get('https_redirect_chain') returns null
 *     When HttpsRedirectCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\HttpsRedirectCheck;
use WPSecurity\Tests\Support\MockContext;

final class HttpsRedirectCheckTest extends TestCase {

	private HttpsRedirectCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new HttpsRedirectCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.https_redirect_enforcement', $this->check->id() );
	}

	public function test_direct_redirect_to_https_returns_pass(): void {
		$context = new MockContext(
			values: [
				'https_redirect_chain' => [
					[
						'url'    => 'http://example.test/',
						'status' => 301,
						'scheme' => 'http',
					],
					[
						'url'    => 'https://example.test/',
						'status' => 200,
						'scheme' => 'https',
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_no_redirect_at_all_returns_fail_high(): void {
		$context = new MockContext(
			values: [
				'https_redirect_chain' => [
					[
						'url'    => 'http://example.test/',
						'status' => 200,
						'scheme' => 'http',
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_multiple_insecure_hops_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'https_redirect_chain' => [
					[
						'url'    => 'http://example.test/',
						'status' => 301,
						'scheme' => 'http',
					],
					[
						'url'    => 'http://www.example.test/',
						'status' => 301,
						'scheme' => 'http',
					],
					[
						'url'    => 'https://www.example.test/',
						'status' => 200,
						'scheme' => 'https',
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_unreachable_over_http_returns_pass(): void {
		$context = new MockContext(
			values: [
				'https_redirect_chain' => [
					[
						'url'    => 'http://example.test/',
						'status' => 0,
						'scheme' => 'http',
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_null_chain_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
