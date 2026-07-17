<?php

/**
 * Feature: HttpsCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: HTTPS URL returns PASS
 *     Given a mocked Context with homeUrl "https://example.test"
 *     When HttpsCheck::run() is called
 *     Then the Finding status is "pass"
 *     And the Finding checkId is "server.https"
 *
 *   Scenario: HTTP URL returns FAIL with HIGH severity
 *     Given a mocked Context with homeUrl "http://example.test"
 *     When HttpsCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *
 *   Scenario: FAIL finding includes home_url evidence
 *     Given a mocked Context with homeUrl "http://example.test"
 *     When HttpsCheck::run() is called
 *     Then the Finding evidence contains 'home_url' with value "http://example.test"
 *
 *   Scenario: Empty URL returns SKIPPED
 *     Given a mocked Context with homeUrl ""
 *     When HttpsCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\HttpsCheck;
use WPSecurity\Tests\Support\MockContext;

final class HttpsCheckTest extends TestCase {

	private HttpsCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new HttpsCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.https', $this->check->id() );
	}

	public function test_https_url_returns_pass(): void {
		$context = new MockContext( homeUrl: 'https://example.test' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertSame( 'server.https', $finding->checkId );
	}

	public function test_http_url_returns_fail_high(): void {
		$context = new MockContext( homeUrl: 'http://example.test' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_http_finding_includes_home_url_evidence(): void {
		$context = new MockContext( homeUrl: 'http://example.test' );
		$finding = $this->check->run( $context );

		$this->assertTrue( $finding->evidence->has( 'home_url' ) );
		$this->assertSame( 'http://example.test', $finding->evidence->get( 'home_url' ) );
	}

	public function test_empty_url_returns_skipped(): void {
		$context = new MockContext( homeUrl: '' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_https_has_no_score_penalty(): void {
		$context = new MockContext( homeUrl: 'https://example.test' );
		$finding = $this->check->run( $context );

		$this->assertSame( 0, $finding->penalty() );
	}

	public function test_http_has_high_penalty(): void {
		$context = new MockContext( homeUrl: 'http://example.test' );
		$finding = $this->check->run( $context );

		$this->assertSame( 20, $finding->penalty() );
	}
}
