<?php

/**
 * Feature: ServerModule — Server Health module container
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Module ID is 'server'
 *     Given a ServerModule
 *     When id() is called
 *     Then it returns "server"
 *
 *   Scenario: Module label is 'Server Health'
 *     Given a ServerModule
 *     When label() is called
 *     Then it returns "Server Health"
 *
 *   Scenario: checks() returns an iterable of Check instances
 *     Given a ServerModule
 *     When checks() is called
 *     Then the result is iterable
 *     And each element implements the Check contract
 *
 *   Scenario: checks() applies the wp_security/checks/server filter
 *     Given a filter registered on 'wp_security/checks/server'
 *     When checks() is called
 *     Then the filtered checks list is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Server;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Server\ServerModule;

final class ServerModuleTest extends TestCase {

	private ServerModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new ServerModule();
		remove_all_filters( 'wp_security/checks/server' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/server' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_server(): void {
		$this->assertSame( 'server', $this->module->id() );
	}

	public function test_label_is_server_health(): void {
		$this->assertSame( 'Server Health', $this->module->label() );
	}

	public function test_icon_is_a_non_empty_string(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_checks_returns_iterable(): void {
		$checks = $this->module->checks();

		$this->assertIsIterable( $checks );
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

		$this->assertSame( $ids, array_unique( $ids ), 'All check IDs must be unique.' );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'server.php_version', $ids );
		$this->assertContains( 'server.memory_limit', $ids );
		$this->assertContains( 'server.php_extensions', $ids );
		$this->assertContains( 'server.opcache', $ids );
		$this->assertContains( 'server.disk_space', $ids );
		$this->assertContains( 'server.https', $ids );
	}

	public function test_filter_can_add_extra_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'server.custom' );

		add_filter(
			'wp_security/checks/server',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'server.custom', $ids );
	}
}
