<?php

/**
 * Integration tests for TlsCertificateInspector.
 *
 * This is the one collaborator in the scanning layer that performs a real
 * raw TLS socket handshake (stream_socket_client + capture_peer_cert)
 * rather than going through wp_remote_get(), so it cannot be exercised by
 * the recordable-HTTP-response test stub used everywhere else in this
 * codebase. The consuming Check (TlsCertificateExpiryCheck) remains fully
 * unit-testable via MockContext — see
 * tests/Unit/Modules/Server/Checks/TlsCertificateExpiryCheckTest.php.
 *
 * Feature: TlsCertificateInspector — raw TLS certificate inspection
 *   Scenario: An unreachable host/port returns null without hanging
 *     Given a host/port combination that refuses the connection instantly (127.0.0.1:1)
 *     When inspect() is called
 *     Then null is returned
 *
 *   Scenario: An empty host returns null
 *     Given an empty host string
 *     When inspect() is called
 *     Then null is returned without attempting a connection
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Integration\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Scanning\TlsCertificateInspector;

final class TlsCertificateInspectorTest extends TestCase {

	private TlsCertificateInspector $inspector;

	protected function setUp(): void {
		parent::setUp();
		$this->inspector = new TlsCertificateInspector();
	}

	public function test_empty_host_returns_null(): void {
		$this->assertNull( $this->inspector->inspect( '' ) );
	}

	/**
	 * 127.0.0.1:1 is a real, no-network-dependency connection attempt that
	 * refuses instantly (port 1 has no TLS listener on any CI/dev machine),
	 * exercising the failure path without requiring egress or a live host.
	 */
	public function test_unreachable_host_returns_null(): void {
		$this->assertNull( $this->inspector->inspect( '127.0.0.1', 1, 2 ) );
	}
}
