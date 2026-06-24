<?php
/*
 * Feature: PerformanceModule — S7-1/S7-2 (Performance module container)
 *
 * Scenario: Module ID is 'performance'
 *   Given a PerformanceModule
 *   When id() is called
 *   Then it returns "performance"
 *
 * Scenario: Built-in checks are registered
 *   Given a PerformanceModule
 *   When checks() is called
 *   Then it contains 'performance.ttfb' and 'performance.compression'
 *
 * Scenario: wp_security/checks/performance filter is applied
 *   Given a filter that appends a custom check
 *   When checks() is called
 *   Then the custom check is included in the result
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Performance;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Performance\PerformanceModule;

final class PerformanceModuleTest extends TestCase {

	private PerformanceModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new PerformanceModule();
		remove_all_filters( 'wp_security/checks/performance' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/performance' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_performance(): void {
		$this->assertSame( 'performance', $this->module->id() );
	}

	public function test_label_is_performance(): void {
		$this->assertSame( 'Performance', $this->module->label() );
	}

	public function test_icon_is_non_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_checks_returns_iterable(): void {
		$this->assertIsIterable( $this->module->checks() );
	}

	public function test_built_in_checks_implement_check_contract(): void {
		foreach ( $this->module->checks() as $check ) {
			$this->assertInstanceOf( Check::class, $check );
		}
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'performance.ttfb', $ids );
		$this->assertContains( 'performance.compression', $ids );
	}

	public function test_filter_can_add_extra_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'performance.custom' );

		add_filter(
			'wp_security/checks/performance',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'performance.custom', $ids );
	}
}
