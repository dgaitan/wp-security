<?php
/*
 * Feature: AccessibilityModule — S7-3 (Accessibility module container)
 *
 * Scenario: Module ID is 'accessibility'
 *   Given an AccessibilityModule
 *   When id() is called
 *   Then it returns "accessibility"
 *
 * Scenario: No built-in checks (findings come from browser-side axe-core)
 *   Given an AccessibilityModule
 *   When checks() is called
 *   Then the result is an empty iterable
 *
 * Scenario: wp_security/checks/accessibility filter can add server-side checks
 *   Given a filter that appends a custom check
 *   When checks() is called
 *   Then the custom check is included in the result
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Accessibility;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Accessibility\AccessibilityModule;

final class AccessibilityModuleTest extends TestCase {

	private AccessibilityModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new AccessibilityModule();
		remove_all_filters( 'wp_security/checks/accessibility' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/accessibility' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_accessibility(): void {
		$this->assertSame( 'accessibility', $this->module->id() );
	}

	public function test_label_is_accessibility(): void {
		$this->assertSame( 'Accessibility', $this->module->label() );
	}

	public function test_icon_is_non_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_is_empty(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertEmpty( $ids );
	}

	public function test_filter_can_add_server_side_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'accessibility.custom' );

		add_filter(
			'wp_security/checks/accessibility',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'accessibility.custom', $ids );
	}
}
