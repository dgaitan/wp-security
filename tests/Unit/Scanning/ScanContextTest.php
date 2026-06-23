<?php
/**
 * Unit tests for ScanContext.
 *
 * Feature: ScanContext — Core domain + DI wiring
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered (with minimal WordPress stubs)
 *
 *   Scenario: ScanContext exposes the core environment accessors
 *     Given a ScanContext
 *     When each typed accessor is called
 *     Then it returns the stubbed environment value
 *
 *   Scenario: ScanContext resolves the documented dynamic keys
 *     Given a ScanContext
 *     When get() is called with plugins, themes, or active_plugins
 *     Then an array is returned
 *     And an unknown key returns null
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Context;
use WPSecurity\Scanning\ScanContext;

final class ScanContextTest extends TestCase {

	private ScanContext $context;

	protected function setUp(): void {
		parent::setUp();
		$this->context = new ScanContext();
	}

	public function test_implements_context_contract(): void {
		$this->assertInstanceOf( Context::class, $this->context );
	}

	public function test_exposes_core_environment_accessors(): void {
		$this->assertSame( ABSPATH, $this->context->wpRootPath() );
		$this->assertSame( WP_CONTENT_DIR . DIRECTORY_SEPARATOR, $this->context->contentPath() );
		$this->assertSame( 'https://example.test', $this->context->homeUrl() );
		$this->assertSame( '6.5.0', $this->context->wpVersion() );
		$this->assertSame( PHP_VERSION, $this->context->phpVersion() );
	}

	public function test_dynamic_keys_return_arrays(): void {
		$this->assertIsArray( $this->context->get( 'plugins' ) );
		$this->assertIsArray( $this->context->get( 'themes' ) );
		$this->assertIsArray( $this->context->get( 'active_plugins' ) );
	}

	public function test_unknown_key_returns_null(): void {
		$this->assertNull( $this->context->get( 'does_not_exist' ) );
	}
}
