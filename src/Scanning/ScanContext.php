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
	 *   'plugins'         → array from get_plugins()
	 *   'themes'          → array from wp_get_themes()
	 *   'active_plugins'  → array from get_option('active_plugins')
	 *   'memory_limit'    → PHP ini memory_limit string (e.g. "256M")
	 *   'wp_memory_limit' → WP_MEMORY_LIMIT constant (e.g. "64M")
	 *   'php_extensions'  → array of loaded extension names (get_loaded_extensions())
	 *   'opcache_enabled' → bool — true when OPcache is active
	 *   'disk_free_bytes' → int|false — free bytes on the WP install partition
	 *   'disk_total_bytes'→ int|false — total bytes on the WP install partition
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		return match ( $key ) {
			'plugins'          => function_exists( 'get_plugins' ) ? get_plugins() : [],
			'themes'           => function_exists( 'wp_get_themes' ) ? wp_get_themes() : [],
			'active_plugins'   => get_option( 'active_plugins', [] ),
			'memory_limit'     => ini_get( 'memory_limit' ),
			'wp_memory_limit'  => defined( 'WP_MEMORY_LIMIT' ) ? constant( 'WP_MEMORY_LIMIT' ) : '40M',
			'php_extensions'   => get_loaded_extensions(),
			'opcache_enabled'  => $this->resolveOpcacheEnabled(),
			'disk_free_bytes'  => function_exists( 'disk_free_space' ) ? disk_free_space( $this->wpRootPath() ) : false,
			'disk_total_bytes' => function_exists( 'disk_total_space' ) ? disk_total_space( $this->wpRootPath() ) : false,
			default            => null,
		};
	}

	private function resolveOpcacheEnabled(): bool {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return (bool) ini_get( 'opcache.enable' );
		}
		$status = opcache_get_status( false );
		return false !== $status && ! empty( $status['opcache_enabled'] );
	}
}
