<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

/**
 * Base class for service providers.
 *
 * Each feature area (Admin, Rest, Scanning, …) gets one ServiceProvider that
 * wires its own classes into the container.  Providers are registered in
 * Plugin::registerProviders() and called before any hook runs.
 */
abstract class ServiceProvider {

    public function __construct( protected Container $container ) {}

    /**
     * Register bindings into the container.
     * Must not have side effects (no WordPress hooks here — that happens in boot()).
     */
    abstract public function register(): void;
}
