<?php

/**
 * Feature: PhpExtensionsCheck — Server Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All required extensions present returns PASS
 *     Given a mocked Context with php_extensions containing all required extensions
 *     When PhpExtensionsCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Missing curl returns FAIL with HIGH severity
 *     Given a mocked Context with php_extensions that does NOT contain 'curl'
 *     When PhpExtensionsCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *     And the Finding checkId is "server.php_extensions"
 *
 *   Scenario: Missing only gd (MEDIUM) returns WARN with MEDIUM severity
 *     Given a mocked Context with php_extensions that does NOT contain 'gd' but has all HIGH extensions
 *     When PhpExtensionsCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: Null extensions list returns SKIPPED
 *     Given a mocked Context where get('php_extensions') returns null
 *     When PhpExtensionsCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Evidence includes map of missing extensions
 *     Given a mocked Context with php_extensions missing 'curl'
 *     When PhpExtensionsCheck::run() is called
 *     Then the Finding evidence contains 'missing' with 'curl'
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Server\Checks\PhpExtensionsCheck;
use WPSecurity\Tests\Support\MockContext;

final class PhpExtensionsCheckTest extends TestCase {

	private PhpExtensionsCheck $check;

	/** Extensions that satisfy all HIGH requirements */
	private const HIGH_REQUIRED = [ 'curl', 'json', 'mbstring', 'openssl' ];

	protected function setUp(): void {
		parent::setUp();
		$this->check = new PhpExtensionsCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'server.php_extensions', $this->check->id() );
	}

	public function test_all_required_present_returns_pass(): void {
		$context = new MockContext(
			values: [
				'php_extensions' => array_merge( self::HIGH_REQUIRED, [ 'gd' ] ),
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_missing_curl_returns_fail_high(): void {
		$extensions = array_diff( self::HIGH_REQUIRED, [ 'curl' ] );
		$context    = new MockContext( values: [ 'php_extensions' => array_values( $extensions ) ] );
		$finding    = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( 'server.php_extensions', $finding->checkId );
	}

	public function test_missing_mbstring_returns_fail_high(): void {
		$extensions = array_diff( self::HIGH_REQUIRED, [ 'mbstring' ] );
		$context    = new MockContext( values: [ 'php_extensions' => array_values( $extensions ) ] );
		$finding    = $this->check->run( $context );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
	}

	public function test_missing_only_gd_returns_warn_medium(): void {
		$context = new MockContext( values: [ 'php_extensions' => self::HIGH_REQUIRED ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_null_extensions_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_missing_curl_evidence_contains_missing_map(): void {
		$extensions = array_diff( self::HIGH_REQUIRED, [ 'curl' ] );
		$context    = new MockContext( values: [ 'php_extensions' => array_values( $extensions ) ] );
		$finding    = $this->check->run( $context );

		$this->assertTrue( $finding->evidence->has( 'missing' ) );
		$this->assertArrayHasKey( 'curl', $finding->evidence->get( 'missing' ) );
	}

	public function test_extension_check_is_case_insensitive(): void {
		$context = new MockContext(
			values: [
				'php_extensions' => array_merge(
					array_map( 'strtoupper', self::HIGH_REQUIRED ),
					[ 'GD' ]
				),
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}
}
