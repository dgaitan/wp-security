<?php

/**
 * Minimal WordPress function stubs for unit tests.
 *
 * These allow the autoloader and domain classes to load without a real
 * WordPress installation.  Only stub what domain/value-object classes and
 * services actually call.  Integration tests use the real WP test suite.
 *
 * The filter functions below are a deliberately minimal but *functional*
 * re-implementation of the WordPress hook system (priority-ordered callbacks),
 * which is enough to unit-test filter-driven services such as ModuleRegistry
 * without loading WordPress.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

/**
 * Registry of filter callbacks for the test hook system: tag => priority => list.
 *
 * @var array<string, array<int, array<int, callable>>> $wp_security_test_filters
 */
$GLOBALS['wp_security_test_filters'] = [];

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['wp_security_test_filters'][ $tag ][ $priority ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param mixed $value
	 * @param mixed ...$args
	 * @return mixed
	 */
	function apply_filters( string $tag, mixed $value, mixed ...$args ): mixed {
		if ( empty( $GLOBALS['wp_security_test_filters'][ $tag ] ) ) {
			return $value;
		}

		$callbacks = $GLOBALS['wp_security_test_filters'][ $tag ];
		ksort( $callbacks );

		foreach ( $callbacks as $list ) {
			foreach ( $list as $callback ) {
				$value = $callback( $value, ...$args );
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'remove_all_filters' ) ) {
	function remove_all_filters( string $tag ): bool {
		unset( $GLOBALS['wp_security_test_filters'][ $tag ] );
		return true;
	}
}

if ( ! function_exists( 'get_home_url' ) ) {
	function get_home_url( ?int $blog_id = null, string $path = '', ?string $scheme = null ): string {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show = '' ): string {
		return 'version' === $show ? '6.5.0' : '';
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
