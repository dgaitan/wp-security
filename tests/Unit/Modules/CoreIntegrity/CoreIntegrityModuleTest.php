<?php

/**
 * Feature: CoreIntegrityModule — WordPress Health module container
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Module ID is 'core_integrity'
 *     Given a CoreIntegrityModule
 *     When id() is called
 *     Then it returns "core_integrity"
 *
 *   Scenario: checks() returns all four built-in checks
 *     Given a CoreIntegrityModule
 *     When checks() is called
 *     Then the result contains check IDs for all four checks
 *
 *   Scenario: checks() applies the wp_security/checks/core_integrity filter
 *     Given a filter registered on 'wp_security/checks/core_integrity'
 *     When checks() is called
 *     Then the filtered checks list is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\CoreIntegrity\CoreIntegrityModule;

final class CoreIntegrityModuleTest extends TestCase {

	private CoreIntegrityModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new CoreIntegrityModule();
		remove_all_filters( 'wp_security/checks/core_integrity' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/core_integrity' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_core_integrity(): void {
		$this->assertSame( 'core_integrity', $this->module->id() );
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
		$this->assertContains( 'core_integrity.core_files', $ids );
		$this->assertContains( 'core_integrity.wp_content_structure', $ids );
		$this->assertContains( 'core_integrity.suspicious_files', $ids );
		$this->assertContains( 'core_integrity.wp_config', $ids );
		$this->assertContains( 'core_integrity.xmlrpc', $ids );
		$this->assertContains( 'core_integrity.rest_user_enumeration', $ids );
		$this->assertContains( 'core_integrity.vulnerability_advisory', $ids );
		$this->assertContains( 'core_integrity.core_update_available', $ids );
		$this->assertContains( 'core_integrity.cron_health', $ids );
		$this->assertContains( 'core_integrity.dashboard_notices', $ids );
	}

	public function test_filter_can_add_extra_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'core_integrity.custom' );

		add_filter(
			'wp_security/checks/core_integrity',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}
		$this->assertContains( 'core_integrity.custom', $ids );
	}
}
