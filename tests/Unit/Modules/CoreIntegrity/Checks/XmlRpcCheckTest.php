<?php

/**
 * Feature: XmlRpcCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: XML-RPC disabled returns PASS
 *     Given a mocked Context with xmlrpc_enabled = false
 *     When XmlRpcCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: XML-RPC enabled returns WARN with LOW severity
 *     Given a mocked Context with xmlrpc_enabled = true
 *     When XmlRpcCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "low"
 *
 *   Scenario: XML-RPC status null returns SKIPPED
 *     Given a mocked Context where get('xmlrpc_enabled') returns null
 *     When XmlRpcCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\XmlRpcCheck;
use WPSecurity\Tests\Support\MockContext;

final class XmlRpcCheckTest extends TestCase {

	private XmlRpcCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new XmlRpcCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.xmlrpc', $this->check->id() );
	}

	public function test_xmlrpc_disabled_returns_pass(): void {
		$context = new MockContext( values: [ 'xmlrpc_enabled' => false ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_xmlrpc_enabled_returns_warn_low(): void {
		$context = new MockContext( values: [ 'xmlrpc_enabled' => true ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::LOW, $finding->severity );
		$this->assertSame( 'core_integrity.xmlrpc', $finding->checkId );
	}

	public function test_null_status_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
