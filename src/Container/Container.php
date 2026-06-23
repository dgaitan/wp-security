<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Minimal PSR-11 dependency-injection container.
 *
 * Supports:
 *   - Binding a factory closure to an abstract ID.
 *   - Binding a concrete singleton (resolved once, cached).
 *   - Binding a pre-built instance directly.
 *
 * Intentionally simple: if the project ever needs a full-featured container
 * (autowiring, tagged bindings, etc.) swap this class for a Composer package
 * that implements ContainerInterface — nothing outside this class will change.
 *
 * TODO Sprint 1: install psr/container via Composer and add to composer.json.
 */
class Container implements ContainerInterface {

    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    /**
     * Bind a factory that is called every time the ID is resolved.
     *
     * @param callable(self): mixed $factory
     */
    public function bind( string $id, callable $factory ): void {
        $this->bindings[ $id ] = $factory;
        unset( $this->resolved[ $id ] );
    }

    /**
     * Bind a factory that is called once; the result is cached.
     *
     * @param callable(self): mixed $factory
     */
    public function singleton( string $id, callable $factory ): void {
        $this->bind(
            $id,
            function ( self $c ) use ( $id, $factory ): mixed {
                if ( ! array_key_exists( $id, $this->resolved ) ) {
                    $this->resolved[ $id ] = $factory( $c );
                }
                return $this->resolved[ $id ];
            }
        );
    }

    /**
     * Bind a pre-built instance (treated as a singleton).
     */
    public function instance( string $id, mixed $instance ): void {
        $this->resolved[ $id ] = $instance;
        $this->bindings[ $id ] = static fn() => $instance;
    }

    /**
     * @throws ContainerException
     */
    public function get( string $id ): mixed {
        if ( isset( $this->bindings[ $id ] ) ) {
            return ( $this->bindings[ $id ] )( $this );
        }

        throw new ContainerException( "No binding found for [{$id}]." );
    }

    public function has( string $id ): bool {
        return isset( $this->bindings[ $id ] ) || array_key_exists( $id, $this->resolved );
    }
}
