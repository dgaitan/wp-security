<?php

/**
 * Feature: CookieSecurityCheck — Security Headers module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All cookies set Secure, HttpOnly, and SameSite returns PASS
 *     Given a mocked Context with session_cookies where every cookie has secure/httponly true and a samesite value
 *     When CookieSecurityCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Cookie missing Secure or HttpOnly returns WARN with HIGH severity
 *     Given a mocked Context with a session_cookies entry missing secure or httponly
 *     When CookieSecurityCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "high"
 *     And the evidence lists the cookie name under insecure_cookies
 *
 *   Scenario: Cookie missing SameSite only returns WARN with MEDIUM severity
 *     Given a mocked Context with a session_cookies entry that has secure/httponly true but no samesite
 *     When CookieSecurityCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: No cookies observed returns PASS with a caveat
 *     Given a mocked Context with session_cookies as an empty array
 *     When CookieSecurityCheck::run() is called
 *     Then the Finding status is "pass"
 *     And the description mentions the loopback request is anonymous
 *
 *   Scenario: session_cookies null returns SKIPPED
 *     Given a mocked Context where get('session_cookies') returns null
 *     When CookieSecurityCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Headers\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Headers\Checks\CookieSecurityCheck;
use WPSecurity\Tests\Support\MockContext;

final class CookieSecurityCheckTest extends TestCase {

	private CookieSecurityCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new CookieSecurityCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'headers.cookie_flags', $this->check->id() );
	}

	public function test_all_flags_correct_returns_pass(): void {
		$context = new MockContext(
			values: [
				'session_cookies' => [
					[
						'name'     => 'wp_session',
						'secure'   => true,
						'httponly' => true,
						'samesite' => 'Strict',
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_missing_secure_or_httponly_returns_warn_high(): void {
		$context = new MockContext(
			values: [
				'session_cookies' => [
					[
						'name'     => 'insecure_cookie',
						'secure'   => false,
						'httponly' => false,
						'samesite' => null,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertContains( 'insecure_cookie', $finding->evidence->get( 'insecure_cookies' ) );
	}

	public function test_weak_samesite_only_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'session_cookies' => [
					[
						'name'     => 'partial_cookie',
						'secure'   => true,
						'httponly' => true,
						'samesite' => null,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertContains( 'partial_cookie', $finding->evidence->get( 'weak_samesite' ) );
	}

	public function test_no_cookies_observed_returns_pass_with_caveat(): void {
		$context = new MockContext( values: [ 'session_cookies' => [] ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertStringContainsString( 'anonymous', $finding->description );
	}

	public function test_null_session_cookies_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
