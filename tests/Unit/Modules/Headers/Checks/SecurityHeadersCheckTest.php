<?php

/**
 * Feature: SecurityHeadersCheck — Security Headers module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All security headers present returns PASS
 *     Given a mocked Context with response_headers containing all five security headers
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Missing CSP returns WARN with MEDIUM severity
 *     Given a mocked Context with response_headers that does NOT contain 'content-security-policy'
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Missing X-Frame-Options returns WARN with MEDIUM severity
 *     Given a mocked Context with response_headers that does NOT contain 'x-frame-options'
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Missing only LOW-severity headers returns WARN with LOW severity
 *     Given a mocked Context missing only referrer-policy, x-content-type-options, permissions-policy
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "low"
 *
 *   Scenario: Response headers null returns SKIPPED
 *     Given a mocked Context where get('response_headers') returns null
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Missing headers recorded in evidence
 *     Given a mocked Context missing 'content-security-policy'
 *     When SecurityHeadersCheck::run() is called
 *     Then the Finding evidence contains 'missing' with 'content-security-policy'
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Headers\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Headers\Checks\SecurityHeadersCheck;
use WPSecurity\Tests\Support\MockContext;

final class SecurityHeadersCheckTest extends TestCase {

	private SecurityHeadersCheck $check;

	/** All five headers that the check audits */
	private const ALL_HEADERS = [
		'content-security-policy' => "default-src 'self'",
		'x-frame-options'         => 'SAMEORIGIN',
		'x-content-type-options'  => 'nosniff',
		'referrer-policy'         => 'no-referrer-when-downgrade',
		'permissions-policy'      => 'geolocation=()',
	];

	protected function setUp(): void {
		parent::setUp();
		$this->check = new SecurityHeadersCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'headers.security_headers', $this->check->id() );
	}

	public function test_all_headers_present_returns_pass(): void {
		$context = new MockContext( values: [ 'response_headers' => self::ALL_HEADERS ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_missing_csp_returns_warn_medium(): void {
		$headers = self::ALL_HEADERS;
		unset( $headers['content-security-policy'] );
		$context = new MockContext( values: [ 'response_headers' => $headers ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_missing_x_frame_options_returns_warn_medium(): void {
		$headers = self::ALL_HEADERS;
		unset( $headers['x-frame-options'] );
		$context = new MockContext( values: [ 'response_headers' => $headers ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_missing_only_low_severity_headers_returns_warn_low(): void {
		$context = new MockContext(
			values: [
				'response_headers' => [
					'content-security-policy' => "default-src 'self'",
					'x-frame-options'         => 'SAMEORIGIN',
					// referrer-policy, x-content-type-options, permissions-policy absent.
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
	}

	public function test_null_headers_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_missing_csp_recorded_in_evidence(): void {
		$headers = self::ALL_HEADERS;
		unset( $headers['content-security-policy'] );
		$context = new MockContext( values: [ 'response_headers' => $headers ] );
		$finding = $this->check->run( $context );

		$this->assertArrayHasKey( 'missing', $finding->evidence );
		$this->assertArrayHasKey( 'content-security-policy', $finding->evidence['missing'] );
	}
}
