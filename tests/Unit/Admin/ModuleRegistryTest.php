<?php
/**
 * Unit tests for ModuleRegistry.
 *
 * Feature: ModuleRegistry — Core domain + DI wiring
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Modules register through the wp_security/modules filter
 *     Given a module added via the wp_security/modules filter
 *     When all() is called
 *     Then the returned array is keyed by module ID
 *
 *   Scenario: A single module can be fetched by ID
 *     Given a registered module "alpha"
 *     When get("alpha") is called
 *     Then the module is returned
 *     And get() for an unknown ID returns null
 *
 *   Scenario: has() reports membership by ID
 *     Given a registered module "alpha"
 *     When has() is queried
 *     Then it returns true for "alpha" and false otherwise
 *
 *   Scenario: With no filters the registry is empty
 *     Given no module filters registered
 *     When all() is called
 *     Then an empty array is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;

final class ModuleRegistryTest extends TestCase {

	protected function tearDown(): void {
		remove_all_filters( 'wp_security/modules' );
		parent::tearDown();
	}

	public function test_all_is_keyed_by_module_id(): void {
		add_filter(
			'wp_security/modules',
			function ( array $modules ): array {
				$modules[] = $this->module( 'alpha' );
				$modules[] = $this->module( 'beta' );
				return $modules;
			}
		);

		$registry = new ModuleRegistry();
		$all      = $registry->all();

		$this->assertSame( [ 'alpha', 'beta' ], array_keys( $all ) );
		$this->assertSame( 'alpha', $all['alpha']->id() );
	}

	public function test_get_returns_module_or_null(): void {
		add_filter(
			'wp_security/modules',
			fn ( array $modules ): array => array_merge( $modules, [ $this->module( 'alpha' ) ] )
		);

		$registry = new ModuleRegistry();

		$this->assertInstanceOf( Module::class, $registry->get( 'alpha' ) );
		$this->assertNull( $registry->get( 'missing' ) );
	}

	public function test_has_reports_membership(): void {
		add_filter(
			'wp_security/modules',
			fn ( array $modules ): array => array_merge( $modules, [ $this->module( 'alpha' ) ] )
		);

		$registry = new ModuleRegistry();

		$this->assertTrue( $registry->has( 'alpha' ) );
		$this->assertFalse( $registry->has( 'missing' ) );
	}

	public function test_empty_without_filters(): void {
		$registry = new ModuleRegistry();

		$this->assertSame( [], $registry->all() );
	}

	/**
	 * Build a minimal Module test double with the given ID.
	 */
	private function module( string $id ): Module {
		return new class( $id ) implements Module {

			public function __construct( private string $id ) {}

			public function id(): string {
				return $this->id;
			}

			public function label(): string {
				return ucfirst( $this->id );
			}

			public function icon(): string {
				return 'dashicons-shield';
			}

			/**
			 * @return iterable<Check>
			 */
			public function checks(): iterable {
				return [];
			}
		};
	}
}
