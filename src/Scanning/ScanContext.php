<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

use WPSecurity\Contracts\Context;
use WPSecurity\VulnerabilityAdvisor\VulnerabilityAdvisor;

/**
 * Concrete implementation of the Context contract for live WordPress scans.
 *
 * Wraps global WordPress state so Check implementations stay testable: unit
 * tests substitute a mock/stub that implements Context without needing a real
 * WordPress installation.
 */
class ScanContext implements Context {

	/** @var array<string, mixed> */
	private array $resolved = [];

	/** @var array<string, mixed>|\WP_Error|null */
	private mixed $loopbackResponse  = null;
	private bool $loopbackResolved   = false;
	private float $loopbackElapsedMs = 0.0;

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
	 *   'plugins'                 → array from get_plugins()
	 *   'themes'                  → array from wp_get_themes()
	 *   'active_plugins'          → array from get_option('active_plugins')
	 *   'memory_limit'            → PHP ini memory_limit string (e.g. "256M")
	 *   'wp_memory_limit'         → WP_MEMORY_LIMIT constant (e.g. "64M")
	 *   'php_extensions'          → array of loaded extension names (get_loaded_extensions())
	 *   'opcache_enabled'         → bool — true when OPcache is active
	 *   'disk_free_bytes'         → int|false — free bytes on the WP install partition
	 *   'disk_total_bytes'        → int|false — total bytes on the WP install partition
	 *   'response_headers'        → array<string,string>|null — lowercase HTTP response headers from a loopback GET
	 *   'dns_txt_records'         → array|null — DNS TXT records for the home domain
	 *   'dns_dmarc_records'       → array|null — DNS TXT records for _dmarc.{domain}
	 *   'core_checksums'          → array|null — file => md5 map from api.wordpress.org
	 *   'disallow_file_edit'      → bool — value of DISALLOW_FILE_EDIT constant
	 *   'wp_debug'                → bool — value of WP_DEBUG constant
	 *   'xmlrpc_enabled'          → bool — whether XML-RPC is enabled (via filter)
	 *   'rest_users_public'       → bool|null — whether /wp/v2/users is publicly accessible
	 *   'plugin_update_slugs'     → array<int,string>|null — plugin files that have pending updates
	 *   'vulnerability_advisor'   → VulnerabilityAdvisor — configured provider instance
	 *   'active_theme_slug'       → string|null — stylesheet name of the active theme
	 *   'active_theme_version'    → string|null — version of the active theme
	 *   'autoloaded_options_size' → int|null — total byte size of autoloaded options (uses $wpdb->prepare())
	 *   'suspicious_option_count' → int|null — options containing eval()/base64_decode() (uses $wpdb->prepare())
	 *   'suspicious_post_count'   → int|null — published posts containing eval()/base64_decode() (uses $wpdb->prepare())
	 *   'dormant_user_count'      → int|null — users with last_login_at older than 90 days (uses $wpdb->prepare())
	 *   'admin_user_count'        → int|null — number of users with the administrator role
	 *   'ttfb_ms'                 → float|null — time to first byte in milliseconds (loopback GET to homeUrl)
	 *   'homepage_html'           → string|null — raw HTML body of homeUrl (loopback GET)
	 *   'robots_txt_status'       → int|null — HTTP status code for GET {homeUrl}/robots.txt
	 *   'sitemap_reachable'       → bool|null — true when /sitemap.xml returns 2xx/3xx
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		if ( array_key_exists( $key, $this->resolved ) ) {
			return $this->resolved[ $key ];
		}

		$value = match ( $key ) {
			'plugins'                 => function_exists( 'get_plugins' ) ? get_plugins() : [],
			'themes'                  => function_exists( 'wp_get_themes' ) ? wp_get_themes() : [],
			'active_plugins'          => get_option( 'active_plugins', [] ),
			'memory_limit'            => ini_get( 'memory_limit' ),
			'wp_memory_limit'         => defined( 'WP_MEMORY_LIMIT' ) ? constant( 'WP_MEMORY_LIMIT' ) : '40M',
			'php_extensions'          => get_loaded_extensions(),
			'opcache_enabled'         => $this->resolveOpcacheEnabled(),
			'disk_free_bytes'         => function_exists( 'disk_free_space' ) ? disk_free_space( $this->wpRootPath() ) : false,
			'disk_total_bytes'        => function_exists( 'disk_total_space' ) ? disk_total_space( $this->wpRootPath() ) : false,
			'response_headers'        => $this->resolveResponseHeaders(),
			'dns_txt_records'         => $this->resolveDnsTxtRecords(),
			'dns_dmarc_records'       => $this->resolveDmarcRecords(),
			'core_checksums'          => $this->resolveCoreChecksums(),
			'disallow_file_edit'      => defined( 'DISALLOW_FILE_EDIT' ) && (bool) constant( 'DISALLOW_FILE_EDIT' ),
			'wp_debug'                => defined( 'WP_DEBUG' ) && (bool) constant( 'WP_DEBUG' ),
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WP hook, not ours.
			'xmlrpc_enabled'          => (bool) apply_filters( 'xmlrpc_enabled', true ),
			'rest_users_public'       => $this->resolveRestUsersPublic(),
			'plugin_update_slugs'     => $this->resolvePluginUpdateSlugs(),
			'vulnerability_advisor'   => new VulnerabilityAdvisor( (array) get_option( 'wp_security_settings', [] ) ),
			'active_theme_slug'       => function_exists( 'wp_get_theme' ) ? wp_get_theme()->get_stylesheet() : null,
			'active_theme_version'    => function_exists( 'wp_get_theme' ) ? wp_get_theme()->get( 'Version' ) : null,
			'autoloaded_options_size' => $this->resolveAutoloadedOptionsSize(),
			'suspicious_option_count' => $this->resolveSuspiciousCount( 'options' ),
			'suspicious_post_count'   => $this->resolveSuspiciousCount( 'posts' ),
			'dormant_user_count'      => $this->resolveDormantUserCount(),
			'admin_user_count'        => $this->resolveAdminUserCount(),
			'ttfb_ms'                 => $this->resolveTtfb(),
			'homepage_html'           => $this->resolveHomepageHtml(),
			'robots_txt_status'       => $this->resolveRobotsTxtStatus(),
			'sitemap_reachable'       => $this->resolveSitemapReachable(),
			default                   => null,
		};

		$this->resolved[ $key ] = $value;
		return $value;
	}

	private function resolveOpcacheEnabled(): bool {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return (bool) ini_get( 'opcache.enable' );
		}
		$status = opcache_get_status( false );
		return false !== $status && ! empty( $status['opcache_enabled'] );
	}

	/**
	 * Makes a single loopback GET to the home URL per ScanContext instance.
	 * All three loopback-backed keys (response_headers, ttfb_ms, homepage_html)
	 * share this one request so the site is only hit once per scan.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private function sharedLoopback(): mixed {
		if ( ! $this->loopbackResolved ) {
			$start                   = microtime( true );
			$this->loopbackResponse  = wp_remote_get(
				$this->homeUrl(),
				[
					'timeout'     => 10,
					'sslverify'   => false,
					'redirection' => 5,
				]
			);
			$this->loopbackElapsedMs = ( microtime( true ) - $start ) * 1000.0;
			$this->loopbackResolved  = true;
		}
		return $this->loopbackResponse;
	}

	/** @return array<string, string>|null */
	private function resolveResponseHeaders(): ?array {
		$response = $this->sharedLoopback();

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

	/** @return array<int, string>|null */
	private function resolvePluginUpdateSlugs(): ?array {
		if ( ! function_exists( 'get_site_transient' ) ) {
			return null;
		}
		$update = get_site_transient( 'update_plugins' );
		if ( ! is_object( $update ) || ! property_exists( $update, 'response' ) ) {
			return [];
		}
		/** @var array<string, mixed> $response */
		$response = (array) $update->response;
		return array_keys( $response );
	}

	private function resolveAutoloadedOptionsSize(): ?int {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->get_var(
			$wpdb->prepare( 'SELECT SUM(LENGTH(option_value)) FROM ' . $wpdb->options . ' WHERE autoload = %s', 'yes' )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return null !== $result ? (int) $result : null;
	}

	/**
	 * Count rows in wp_options (type='options') or wp_posts (type='posts') that
	 * contain eval() or base64_decode() calls — both are common injection markers.
	 *
	 * All value binding uses $wpdb->prepare(); only the (trusted) table name is
	 * interpolated, consistent with the FindingRepository pattern.
	 */
	private function resolveSuspiciousCount( string $type ): ?int {
		global $wpdb;

		if ( 'options' === $type ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $wpdb->options . ' WHERE (option_value LIKE %s OR option_value LIKE %s)',
					'%eval(%',
					'%base64_decode(%'
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			return null !== $result ? (int) $result : null;
		}

		if ( 'posts' === $type ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE (post_content LIKE %s OR post_content LIKE %s) AND post_status = %s',
					'%eval(%',
					'%base64_decode(%',
					'publish'
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			return null !== $result ? (int) $result : null;
		}

		return null;
	}

	private function resolveDormantUserCount(): ?int {
		global $wpdb;
		$table  = $wpdb->prefix . 'wpsec_logins';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table . ' WHERE last_login_at < %s', $cutoff )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return null !== $result ? (int) $result : null;
	}

	private function resolveAdminUserCount(): ?int {
		if ( ! function_exists( 'get_users' ) ) {
			return null;
		}
		/** @var array<int, mixed> $admins */
		$admins = get_users(
			[
				'role'   => 'administrator',
				'fields' => 'ID',
			]
		);
		return count( $admins );
	}

	private function resolveTtfb(): ?float {
		$response = $this->sharedLoopback();

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			return null;
		}

		return round( $this->loopbackElapsedMs, 1 );
	}

	private function resolveHomepageHtml(): ?string {
		$response = $this->sharedLoopback();

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	private function resolveRobotsTxtStatus(): ?int {
		$url      = rtrim( $this->homeUrl(), '/' ) . '/robots.txt';
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

		return (int) wp_remote_retrieve_response_code( $response );
	}

	private function resolveSitemapReachable(): ?bool {
		$url      = rtrim( $this->homeUrl(), '/' ) . '/sitemap.xml';
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
		return $code >= 200 && $code < 400;
	}
}
