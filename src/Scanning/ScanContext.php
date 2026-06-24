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
	 *   'plugins'              → array from get_plugins()
	 *   'themes'               → array from wp_get_themes()
	 *   'active_plugins'       → array from get_option('active_plugins')
	 *   'memory_limit'         → PHP ini memory_limit string (e.g. "256M")
	 *   'wp_memory_limit'      → WP_MEMORY_LIMIT constant (e.g. "64M")
	 *   'php_extensions'       → array of loaded extension names (get_loaded_extensions())
	 *   'opcache_enabled'      → bool — true when OPcache is active
	 *   'disk_free_bytes'      → int|false — free bytes on the WP install partition
	 *   'disk_total_bytes'     → int|false — total bytes on the WP install partition
	 *   'response_headers'     → array<string,string>|null — lowercase HTTP response headers from a loopback GET
	 *   'dns_txt_records'      → array|null — DNS TXT records for the home domain
	 *   'dns_dmarc_records'    → array|null — DNS TXT records for _dmarc.{domain}
	 *   'core_checksums'       → array|null — file => md5 map from api.wordpress.org
	 *   'disallow_file_edit'   → bool — value of DISALLOW_FILE_EDIT constant
	 *   'wp_debug'             → bool — value of WP_DEBUG constant
	 *   'xmlrpc_enabled'       → bool — whether XML-RPC is enabled (via filter)
	 *   'rest_users_public'    → bool|null — whether /wp/v2/users is publicly accessible
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		return match ( $key ) {
			'plugins'            => function_exists( 'get_plugins' ) ? get_plugins() : [],
			'themes'             => function_exists( 'wp_get_themes' ) ? wp_get_themes() : [],
			'active_plugins'     => get_option( 'active_plugins', [] ),
			'memory_limit'       => ini_get( 'memory_limit' ),
			'wp_memory_limit'    => defined( 'WP_MEMORY_LIMIT' ) ? constant( 'WP_MEMORY_LIMIT' ) : '40M',
			'php_extensions'     => get_loaded_extensions(),
			'opcache_enabled'    => $this->resolveOpcacheEnabled(),
			'disk_free_bytes'    => function_exists( 'disk_free_space' ) ? disk_free_space( $this->wpRootPath() ) : false,
			'disk_total_bytes'   => function_exists( 'disk_total_space' ) ? disk_total_space( $this->wpRootPath() ) : false,
			'response_headers'   => $this->resolveResponseHeaders(),
			'dns_txt_records'    => $this->resolveDnsTxtRecords(),
			'dns_dmarc_records'  => $this->resolveDmarcRecords(),
			'core_checksums'     => $this->resolveCoreChecksums(),
			'disallow_file_edit' => defined( 'DISALLOW_FILE_EDIT' ) && (bool) constant( 'DISALLOW_FILE_EDIT' ),
			'wp_debug'           => defined( 'WP_DEBUG' ) && (bool) constant( 'WP_DEBUG' ),
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WP hook, not ours.
			'xmlrpc_enabled'     => (bool) apply_filters( 'xmlrpc_enabled', true ),
			'rest_users_public'  => $this->resolveRestUsersPublic(),
			default              => null,
		};
	}

	private function resolveOpcacheEnabled(): bool {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return (bool) ini_get( 'opcache.enable' );
		}
		$status = opcache_get_status( false );
		return false !== $status && ! empty( $status['opcache_enabled'] );
	}

	/** @return array<string, string>|null */
	private function resolveResponseHeaders(): ?array {
		$response = wp_remote_get(
			$this->homeUrl(),
			[
				'timeout'     => 10,
				'sslverify'   => false,
				'redirection' => 5,
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$raw    = wp_remote_retrieve_headers( $response );
		$result = [];

		foreach ( $raw as $name => $value ) {
			$result[ strtolower( (string) $name ) ] = (string) $value;
		}

		return $result;
	}

	/** @return array<int, array<string, mixed>>|null */
	private function resolveDnsTxtRecords(): ?array {
		$domain = (string) wp_parse_url( $this->homeUrl(), PHP_URL_HOST );
		if ( '' === $domain ) {
			return null;
		}
		$records = dns_get_record( $domain, DNS_TXT );
		return false === $records ? null : $records;
	}

	/** @return array<int, array<string, mixed>>|null */
	private function resolveDmarcRecords(): ?array {
		$domain = (string) wp_parse_url( $this->homeUrl(), PHP_URL_HOST );
		if ( '' === $domain ) {
			return null;
		}
		$records = dns_get_record( '_dmarc.' . $domain, DNS_TXT );
		return false === $records ? null : $records;
	}

	/** @return array<string, string>|null */
	private function resolveCoreChecksums(): ?array {
		$version  = $this->wpVersion();
		$url      = 'https://api.wordpress.org/core/checksums/1.0/?version=' . rawurlencode( $version ) . '&locale=en_US';
		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! is_array( $body['checksums'] ?? null ) ) {
			return null;
		}

		/** @var array<string, string> */
		return $body['checksums'];
	}

	private function resolveRestUsersPublic(): ?bool {
		$url      = rest_url( 'wp/v2/users' );
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => 10,
				'sslverify' => false,
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $body ) && count( $body ) > 0;
	}
}
