<?php

/**
 * Feature: TlsCertificateExpiryCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Certificate with 90 days remaining returns PASS
 *     Given a mocked Context with tls_certificate days_until_expiry: 90
 *     When TlsCertificateExpiryCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Expired certificate returns FAIL with CRITICAL severity
 *     Given a mocked Context with tls_certificate days_until_expiry: -5
 *     When TlsCertificateExpiryCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "critical"
 *
 *   Scenario: Certificate expiring within 14 days returns WARN with HIGH severity
 *     Given a mocked Context with tls_certificate days_until_expiry: 7
 *     When TlsCertificateExpiryCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "high"
 *
 *   Scenario: Certificate expiring within 30 days returns WARN with MEDIUM severity
 *     Given a mocked Context with tls_certificate days_until_expiry: 20
 *     When TlsCertificateExpiryCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: tls_certificate null returns SKIPPED
 *     Given a mocked Context where get('tls_certificate') returns null
 *     When TlsCertificateExpiryCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\TlsCertificateExpiryCheck;
use WPSecurity\Tests\Support\MockContext;

final class TlsCertificateExpiryCheckTest extends TestCase {

	private TlsCertificateExpiryCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new TlsCertificateExpiryCheck();
	}

	/** @param array<string, mixed> $overrides */
	private function certificate( array $overrides = [] ): array {
		return array_merge(
			[
				'valid_from'        => time() - 60 * 60 * 24 * 30,
				'valid_to'          => time() + 60 * 60 * 24 * 90,
				'days_until_expiry' => 90,
				'subject_cn'        => 'example.test',
				'issuer_cn'         => "Let's Encrypt",
				'self_signed'       => false,
			],
			$overrides
		);
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.tls_certificate_expiry', $this->check->id() );
	}

	public function test_ninety_days_remaining_returns_pass(): void {
		$context = new MockContext( values: [ 'tls_certificate' => $this->certificate() ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_expired_certificate_returns_fail_critical(): void {
		$context = new MockContext(
			values: [ 'tls_certificate' => $this->certificate( [ 'days_until_expiry' => -5 ] ) ]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
	}

	public function test_expiring_within_fourteen_days_returns_warn_high(): void {
		$context = new MockContext(
			values: [ 'tls_certificate' => $this->certificate( [ 'days_until_expiry' => 7 ] ) ]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_expiring_within_thirty_days_returns_warn_medium(): void {
		$context = new MockContext(
			values: [ 'tls_certificate' => $this->certificate( [ 'days_until_expiry' => 20 ] ) ]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_null_tls_certificate_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
