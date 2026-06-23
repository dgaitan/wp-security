<?php
/**
 * Unit tests for the PSR-11 Container.
 *
 * Feature: Container — Core domain + DI wiring
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Resolving an unbound ID throws a container exception
 *     Given an empty container
 *     When get() is called with an unbound ID
 *     Then a ContainerException is thrown
 *     And the exception is a PSR-11 NotFoundExceptionInterface
 *
 *   Scenario: A singleton resolves to the same instance every time
 *     Given a singleton binding
 *     When get() is called twice
 *     Then the same object is returned both times
 *
 *   Scenario: A plain binding resolves a fresh value each time
 *     Given a bind() factory returning a new object
 *     When get() is called twice
 *     Then a different object is returned each time
 *
 *   Scenario: A pre-built instance is returned as-is
 *     Given an instance() registration
 *     When get() is called
 *     Then the exact registered object is returned
 *
 *   Scenario: has() reflects whether an ID is registered
 *     Given a container with one binding
 *     When has() is queried
 *     Then it returns true for bound IDs and false otherwise
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use WPSecurity\Container\Container;
use WPSecurity\Container\ContainerException;
use WPSecurity\Container\NotFoundException;

final class ContainerTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		parent::setUp();
		$this->container = new Container();
	}

	public function test_get_throws_container_exception_for_unbound_id(): void {
		$this->expectException( ContainerException::class );
		$this->container->get( 'missing.service' );
	}

	public function test_not_found_implements_psr_interface(): void {
		try {
			$this->container->get( 'missing.service' );
			$this->fail( 'Expected NotFoundException was not thrown.' );
		} catch ( NotFoundException $exception ) {
			$this->assertInstanceOf( NotFoundExceptionInterface::class, $exception );
		}
	}

	public function test_singleton_returns_same_instance(): void {
		$this->container->singleton(
			'service',
			static fn(): object => new \stdClass()
		);

		$first  = $this->container->get( 'service' );
		$second = $this->container->get( 'service' );

		$this->assertSame( $first, $second );
	}

	public function test_bind_returns_fresh_value_each_time(): void {
		$this->container->bind(
			'service',
			static fn(): object => new \stdClass()
		);

		$first  = $this->container->get( 'service' );
		$second = $this->container->get( 'service' );

		$this->assertNotSame( $first, $second );
	}

	public function test_instance_returns_registered_object(): void {
		$object = new \stdClass();
		$this->container->instance( 'service', $object );

		$this->assertSame( $object, $this->container->get( 'service' ) );
	}

	public function test_has_reflects_registration(): void {
		$this->assertFalse( $this->container->has( 'service' ) );

		$this->container->instance( 'service', new \stdClass() );

		$this->assertTrue( $this->container->has( 'service' ) );
	}
}
