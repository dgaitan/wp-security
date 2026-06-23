<?php

declare( strict_types=1 );

namespace WPSecurity\Contracts;

/**
 * Execution context passed to every Check::run() call.
 *
 * The context is the single entry-point through which a check accesses the
 * environment (site URL, WP version, file system paths, etc.).  Injecting
 * the context rather than letting checks call global functions directly makes
 * unit-testing straightforward: pass a mock context with controlled values.
 *
 * Concrete implementation: WPSecurity\Scanning\ScanContext.
 */
interface Context {

    /**
     * Absolute path to the WordPress root directory (with trailing slash).
     */
    public function wpRootPath(): string;

    /**
     * Absolute path to the wp-content directory (with trailing slash).
     */
    public function contentPath(): string;

    /**
     * The site's home URL.
     */
    public function homeUrl(): string;

    /**
     * WordPress core version string, e.g. "6.5.2".
     */
    public function wpVersion(): string;

    /**
     * PHP version string, e.g. "8.1.27".
     */
    public function phpVersion(): string;

    /**
     * Retrieve an arbitrary environment value by key.
     * Allows checks to request plugin/theme lists, DB info, etc.
     * Returns null when the key is not available.
     *
     * @return mixed
     */
    public function get( string $key ): mixed;
}
