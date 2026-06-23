<?php

declare( strict_types=1 );

namespace WPSecurity;

use WPSecurity\Container\Container;
use WPSecurity\Container\ServiceProvider;

/**
 * Composition root and singleton entry-point.
 *
 * Responsibilities:
 *   1. Build the dependency-injection container once.
 *   2. Let each ServiceProvider register its bindings.
 *   3. Register WordPress hooks in boot() — called on `plugins_loaded`.
 *
 * Nothing else should call `new` on service classes; resolve everything
 * through the container so the dependency graph stays testable.
 */
final class Plugin {

    private static ?self $instance = null;

    private Container $container;

    private bool $booted = false;

    private function __construct() {
        $this->container = new Container();
    }

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register all service providers and then register WP hooks.
     * Safe to call multiple times — boots only once.
     */
    public function boot(): void {
        if ( $this->booted ) {
            return;
        }

        $this->registerProviders();
        $this->registerHooks();

        $this->booted = true;
    }

    /**
     * Retrieve a resolved service from the container.
     *
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function make( string $id ): mixed {
        return $this->container->get( $id );
    }

    // -------------------------------------------------------------------------
    // Activation / deactivation (called from plugin bootstrap, not from here).
    // -------------------------------------------------------------------------

    public static function activate(): void {
        // TODO Sprint 2: run dbDelta migrations, schedule recurring scan action.
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        // TODO Sprint 2: unschedule recurring Action Scheduler actions.
        flush_rewrite_rules();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function registerProviders(): void {
        $providers = [
            // TODO Sprint 1: add service providers as they are created.
            // Admin\AdminServiceProvider::class,
            // Rest\RestServiceProvider::class,
            // Scanning\ScanningServiceProvider::class,
        ];

        foreach ( $providers as $providerClass ) {
            /** @var ServiceProvider $provider */
            $provider = new $providerClass( $this->container );
            $provider->register();
        }
    }

    private function registerHooks(): void {
        // TODO Sprint 1+: resolve registered services and attach WP hooks.
        // e.g. add_action( 'rest_api_init', [ $this->make( Rest\Router::class ), 'register' ] );
        // e.g. add_action( 'admin_menu',    [ $this->make( Admin\AdminPage::class ), 'register' ] );
    }
}
