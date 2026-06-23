<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

use WPSecurity\Contracts\Context;

/**
 * Concrete implementation of the Context contract for live WordPress scans.
 *
 * Wraps global WordPress state so Check implementations stay testable: unit
 * tests substitute a mock/stub that implements Context without needing a real
 * WordPress installation.
 *
 * TODO Sprint 1: expose additional environment values through get() as checks
 * require them (e.g. plugin list, DB info, options cache).
 */
class ScanContext implements Context {

	public function wpRootPath(): string {
		return ABSPATH;
	}

	public function contentPath(): string {
		return WP_CONTENT_DIR . DIRECTORY_SEPARATOR;
	}

	public function homeUrl(): string {
		return get_home_url();
	}

	public function wpVersion(): string {
		return get_bloginfo( 'version' );
	}

	public function phpVersion(): string {
		return PHP_VERSION;
	}

	/**
	 * Supported keys:
	 *   'plugins'       → array from get_plugins()
	 *   'themes'        → array from wp_get_themes()
	 *   'active_plugins'→ array from get_option('active_plugins')
	 *
	 * TODO Sprint 1: expand as new checks are added.
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		return match ( $key ) {
			'plugins'        => function_exists( 'get_plugins' ) ? get_plugins() : [],
			'themes'         => function_exists( 'wp_get_themes' ) ? wp_get_themes() : [],
			'active_plugins' => get_option( 'active_plugins', [] ),
			default          => null,
		};
	}
}
