<?php

/**
 * Minimal WordPress function stubs for unit tests.
 *
 * These allow the autoloader and domain classes to load without a real
 * WordPress installation.  Only stub what domain/value-object classes
 * actually call.  Integration tests use the real WP test suite instead.
 *
 * TODO Sprint 1: expand as needed when unit tests for services are added.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! function_exists( 'apply_filters' ) ) {
    /**
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function apply_filters( string $tag, mixed $value, mixed ...$args ): mixed {
        return $value;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, mixed $default = false ): mixed {
        return $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $option, mixed $value, bool|string $autoload = true ): bool {
        return true;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES );
    }
}
