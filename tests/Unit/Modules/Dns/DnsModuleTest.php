<?php

/**
 * Feature: DnsModule — DNS module container
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Module ID is 'dns'
 *     Given a DnsModule
 *     When id() is called
 *     Then it returns "dns"
 *
 *   Scenario: checks() returns built-in SPF and DMARC checks
 *     Given a DnsModule
 *     When checks() is called
 *     Then the result contains check IDs "dns.spf" and "dns.dmarc"
 *
 *   Scenario: checks() applies the wp_security/checks/dns filter
 *     Given a filter registered on 'wp_security/checks/dns'
 *     When checks() is called
 *     Then the filtered checks list is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Dns;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Dns\DnsModule;

final class DnsModuleTest extends TestCase {

	private DnsModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new DnsModule();
		remove_all_filters( 'wp_security/checks/dns' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/dns' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_dns(): void {
		$this->assertSame( 'dns', $this->module->id() );
	}

	public function test_label_is_non_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_non_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_checks_returns_iterable(): void {
		$this->assertIsIterable( $this->module->checks() );
	}

	public function test_built_in_checks_all_implement_check_interface(): void {
		foreach ( $this->module->checks() as $check ) {
			$this->assertInstanceOf( Check::class, $check );
		}
	}

	public function test_built_in_checks_have_unique_ids(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}
		$this->assertSame( $ids, array_unique( $ids ) );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}
		$this->assertContains( 'dns.spf', $ids );
		$this->assertContains( 'dns.dmarc', $ids );
	}

	public function test_filter_can_add_extra_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'dns.custom' );

		add_filter(
			'wp_security/checks/dns',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}
		$this->assertContains( 'dns.custom', $ids );
	}
}
