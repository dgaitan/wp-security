<?php
/**
 * Unit tests for CoreServiceProvider.
 *
 * Feature: CoreServiceProvider — Core domain + DI wiring
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Core services resolve from the container after registration
 *     Given a container and a CoreServiceProvider
 *     When register() is called
 *     Then ModuleRegistry, ScoringService, and Context resolve as singletons
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Container\Container;
use WPSecurity\Container\CoreServiceProvider;
use WPSecurity\Contracts\Context;
use WPSecurity\Scoring\ScoringService;

final class CoreServiceProviderTest extends TestCase {

	public function test_registers_core_services_as_singletons(): void {
		$container = new Container();
		( new CoreServiceProvider( $container ) )->register();

		$this->assertInstanceOf( ModuleRegistry::class, $container->get( ModuleRegistry::class ) );
		$this->assertInstanceOf( ScoringService::class, $container->get( ScoringService::class ) );
		$this->assertInstanceOf( Context::class, $container->get( Context::class ) );

		$this->assertSame(
			$container->get( ScoringService::class ),
			$container->get( ScoringService::class )
		);
	}
}
