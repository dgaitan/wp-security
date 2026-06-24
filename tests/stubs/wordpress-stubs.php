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

// -----------------------------------------------------------------------------
// Sprint 3 — admin page and REST bootstrap stubs.
//
// These cover the WordPress functions called by AdminPage::enqueueAssets() and
// the REST bootstrap helpers used in DashboardController and Plugin.
// Recordable stubs push their arguments into $GLOBALS buckets so tests can
// assert what the production code asked WordPress to do.
// -----------------------------------------------------------------------------

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return add_filter( $tag, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, mixed ...$args ): bool {
		return $GLOBALS['wp_security_test_can'][ $capability ] ?? false;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * @param string|\WP_Error $message
	 * @param array<string, mixed> $args
	 * @throws \RuntimeException Always — unit tests catch this to assert wp_die was called.
	 */
	function wp_die( $message = '', string $title = '', array $args = [] ): void {
		throw new \RuntimeException( is_string( $message ) ? $message : $message->get_error_message() );
	}
}

if ( ! function_exists( 'wp_register_script' ) ) {
	/**
	 * @param string[]             $deps
	 * @param string|bool|null     $ver
	 */
	function wp_register_script( string $handle, string|bool $src, array $deps = [], $ver = false, bool $in_footer = false ): bool {
		$GLOBALS['wp_security_test_registered_scripts'][] = compact( 'handle', 'src', 'deps' );
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	/**
	 * @param string[]             $deps
	 * @param string|bool|null     $ver
	 */
	function wp_enqueue_script( string $handle, string|bool $src = false, array $deps = [], $ver = false, bool $in_footer = false ): void {
		$GLOBALS['wp_security_test_enqueued_scripts'][] = $handle;
	}
}

if ( ! function_exists( 'wp_add_inline_script' ) ) {
	function wp_add_inline_script( string $handle, string $data, string $position = 'after' ): bool {
		$GLOBALS['wp_security_test_inline_scripts'][] = compact( 'handle', 'data', 'position' );
		return true;
	}
}

if ( ! function_exists( 'wp_register_style' ) ) {
	/**
	 * @param string[]             $deps
	 * @param string|bool|null     $ver
	 */
	function wp_register_style( string $handle, string|bool $src, array $deps = [], $ver = false, string $media = 'all' ): bool {
		$GLOBALS['wp_security_test_registered_styles'][] = compact( 'handle', 'src' );
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	/**
	 * @param string[]             $deps
	 * @param string|bool|null     $ver
	 */
	function wp_enqueue_style( string $handle, string|bool $src = false, array $deps = [], $ver = false, string $media = 'all' ): void {
		$GLOBALS['wp_security_test_enqueued_styles'][] = $handle;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( string $path = '' ): string {
		return 'https://example.test/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action = '-1' ): string {
		return 'test_nonce_' . $action;
	}
}

if ( ! defined( 'WP_SECURITY_VERSION' ) ) {
	define( 'WP_SECURITY_VERSION', '0.1.0-test' );
}

if ( ! defined( 'WP_SECURITY_URL' ) ) {
	define( 'WP_SECURITY_URL', 'https://example.test/wp-content/plugins/wp-security/' );
}

if ( ! defined( 'WP_SECURITY_DIR' ) ) {
	define( 'WP_SECURITY_DIR', dirname( __DIR__, 2 ) . '/' );
}

// -----------------------------------------------------------------------------
// HTTP stubs — wp_remote_get, wp_remote_retrieve_body, etc.
//
// Tests configure responses by pushing entries into:
// $GLOBALS['wp_security_test_http_responses'] = [
// 'https://example.com/...' => [ 'body' => '...', 'code' => 200 ],
// ];
// Any unmatched URL returns a WP_Error.
// -----------------------------------------------------------------------------

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * Recordable HTTP stub — returns a pre-configured response or WP_Error.
	 *
	 * Increments $GLOBALS['wp_security_test_http_call_count'][$url] on every
	 * invocation so tests can assert how many times a URL was fetched.
	 *
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>|WP_Error
	 */
	function wp_remote_get( string $url, array $args = [] ) {
		$GLOBALS['wp_security_test_http_call_count'][ $url ] =
			( $GLOBALS['wp_security_test_http_call_count'][ $url ] ?? 0 ) + 1;

		$responses = $GLOBALS['wp_security_test_http_responses'] ?? [];

		// Strip query string for lookup when an exact match is not found.
		foreach ( $responses as $pattern => $response ) {
			if ( str_starts_with( $url, $pattern ) || $url === $pattern ) {
				return [
					'response' => [ 'code' => $response['code'] ?? 200 ],
					'body'     => $response['body'] ?? '',
					'headers'  => $response['headers'] ?? [],
				];
			}
		}

		return new WP_Error( 'http_request_failed', 'No stub configured for: ' . $url );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * @param array<string, mixed>|WP_Error $response
	 */
	function wp_remote_retrieve_body( $response ): string {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return '';
		}
		return (string) ( $response['body'] ?? '' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * @param array<string, mixed>|WP_Error $response
	 * @return int|string
	 */
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return '';
		}
		return $response['response']['code'] ?? '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
	/**
	 * @param array<string, mixed>|WP_Error $response
	 * @return array<string, string>|\Requests_Utility_CaseInsensitiveDictionary
	 */
	function wp_remote_retrieve_headers( $response ): array {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return [];
		}
		return (array) ( $response['headers'] ?? [] );
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * Recordable stub for register_rest_route.
	 *
	 * @param array<int, array<string, mixed>> $args
	 */
	function register_rest_route( string $namespace, string $route, array $args = [], bool $override = false ): bool {
		$GLOBALS['wp_security_test_rest_routes'][] = compact( 'namespace', 'route', 'args' );
		return true;
	}
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	function rest_authorization_required_code(): int {
		return 401;
	}
}

// -----------------------------------------------------------------------------
// wp_get_theme() stub — returns an object whose get_stylesheet() and get()
// methods can be configured via $GLOBALS['wp_security_test_theme'].
// -----------------------------------------------------------------------------

if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme( ?string $stylesheet = null ): object {
		$theme = $GLOBALS['wp_security_test_theme'] ?? [
			'stylesheet' => 'twentytwentyfour',
			'Version'    => '1.0.0',
		];
		return new class( $theme ) {

			/** @param array<string, string> $data */
			public function __construct( private array $data ) {}

			public function get_stylesheet(): string {
				return $this->data['stylesheet'] ?? '';
			}

			/**
			 * @return mixed
			 */
			public function get( string $header ) {
				return $this->data[ $header ] ?? '';
			}
		};
	}
}

// -----------------------------------------------------------------------------
// Sprint 8 stubs — alerting, email, Slack, and hook firing.
// -----------------------------------------------------------------------------

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Fire registered action callbacks. Recordable: arguments pushed to
	 * $GLOBALS['wp_security_test_actions_fired'] for assertion in tests.
	 *
	 * @param mixed ...$args
	 */
	function do_action( string $tag, mixed ...$args ): void {
		$GLOBALS['wp_security_test_actions_fired'][ $tag ][] = $args;

		if ( empty( $GLOBALS['wp_security_test_filters'][ $tag ] ) ) {
			return;
		}

		$callbacks = $GLOBALS['wp_security_test_filters'][ $tag ];
		ksort( $callbacks );
		foreach ( $callbacks as $list ) {
			foreach ( $list as $callback ) {
				$callback( ...$args );
			}
		}
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	/**
	 * Recordable stub: pushes sent mail into $GLOBALS['wp_security_test_mail_sent'].
	 *
	 * @param string|string[] $to
	 * @param string|string[] $headers
	 * @param string[]        $attachments
	 */
	function wp_mail( $to, string $subject, string $message, $headers = '', array $attachments = [] ): bool {
		$GLOBALS['wp_security_test_mail_sent'][] = [
			'to'      => $to,
			'subject' => $subject,
			'message' => $message,
		];
		return true;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	/**
	 * Recordable stub: pushes outgoing POST requests into
	 * $GLOBALS['wp_security_test_http_posts'].
	 *
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>|\WP_Error
	 */
	function wp_remote_post( string $url, array $args = [] ) {
		$GLOBALS['wp_security_test_http_posts'][] = compact( 'url', 'args' );
		return [
			'response' => [ 'code' => 200 ],
			'body'     => '',
		];
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		$email = strtolower( trim( $email ) );
		return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param string[] $protocols
	 */
	function esc_url_raw( string $url, array $protocols = [] ): string {
		$clean = filter_var( trim( $url ), FILTER_SANITIZE_URL );
		return false !== $clean ? $clean : '';
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}
