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
		if ( isset( $GLOBALS['wp_security_test_options'] ) && array_key_exists( $option, $GLOBALS['wp_security_test_options'] ) ) {
			return $GLOBALS['wp_security_test_options'][ $option ];
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, mixed $value, bool|string $autoload = true ): bool {
		$GLOBALS['wp_security_test_options'][ $option ] = $value;
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

// -----------------------------------------------------------------------------
// Sprint 2 — persistence, scanning, and REST stubs.
//
// These mirror the WordPress / Action Scheduler functions that the persistence
// and scanning layers call.  Several are *recordable*: they push their arguments
// into a $GLOBALS bucket so unit tests can assert what the production code asked
// WordPress to do (e.g. which Action Scheduler jobs were enqueued).  Tests reset
// these buckets in setUp().
// -----------------------------------------------------------------------------

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		$GLOBALS['wp_security_test_deleted_options'][] = $option;
		return true;
	}
}

if ( ! function_exists( 'delete_metadata' ) ) {
	/**
	 * @param mixed $meta_value
	 */
	function delete_metadata( string $meta_type, int $object_id, string $meta_key, $meta_value = '', bool $delete_all = false ): bool {
		$GLOBALS['wp_security_test_deleted_metadata'][] = [
			'type'       => $meta_type,
			'key'        => $meta_key,
			'delete_all' => $delete_all,
		];
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * @param int|bool $gmt
	 * @return string|int
	 */
	function current_time( string $type, int|bool $gmt = 0 ): string|int {
		if ( 'timestamp' === $type || 'U' === $type ) {
			return time();
		}
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $str ) ) ?? '' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data
	 * @return string|false
	 */
	function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	/**
	 * Recordable stub: capture the CREATE TABLE statements handed to dbDelta().
	 *
	 * @param string|array<string> $queries
	 * @return array<string, string>
	 */
	function dbDelta( string|array $queries, bool $execute = true ): array {
		$GLOBALS['wp_security_test_dbdelta'][] = $queries;
		return [];
	}
}

if ( ! function_exists( 'as_enqueue_async_action' ) ) {
	/**
	 * Recordable stub: capture an enqueued one-off Action Scheduler job.
	 *
	 * @param array<int|string, mixed> $args
	 */
	function as_enqueue_async_action( string $hook, array $args = [], string $group = '' ): int {
		$GLOBALS['wp_security_test_as_actions'][] = [
			'hook'  => $hook,
			'args'  => $args,
			'group' => $group,
		];
		return count( $GLOBALS['wp_security_test_as_actions'] );
	}
}

if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
	/**
	 * Recordable stub: capture a scheduled recurring Action Scheduler job.
	 *
	 * @param array<int|string, mixed> $args
	 */
	function as_schedule_recurring_action( int $timestamp, int $interval_in_seconds, string $hook, array $args = [], string $group = '' ): int {
		$GLOBALS['wp_security_test_as_recurring'][] = [
			'timestamp' => $timestamp,
			'interval'  => $interval_in_seconds,
			'hook'      => $hook,
			'args'      => $args,
			'group'     => $group,
		];
		return count( $GLOBALS['wp_security_test_as_recurring'] );
	}
}

if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
	/**
	 * Recordable stub: capture an unschedule-all request.
	 *
	 * @param array<int|string, mixed> $args
	 */
	function as_unschedule_all_actions( string $hook, array $args = [], string $group = '' ): void {
		$GLOBALS['wp_security_test_as_unscheduled'][] = [
			'hook'  => $hook,
			'args'  => $args,
			'group' => $group,
		];
	}
}

if ( ! function_exists( 'as_next_scheduled_action' ) ) {
	/**
	 * Stub: tests toggle $GLOBALS['wp_security_test_as_next'] to simulate an
	 * action already being scheduled.
	 *
	 * @param array<int|string, mixed>|null $args
	 * @return int|bool
	 */
	function as_next_scheduled_action( string $hook, ?array $args = null, string $group = '' ): int|bool {
		return $GLOBALS['wp_security_test_as_next'] ?? false;
	}
}
