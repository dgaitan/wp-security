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

	/**
	 * Site-crawl tuning. All bounded — never an unbounded fetch/link-check loop.
	 * Everything below runs inside an Action Scheduler job (see ScanManager),
	 * not a synchronous HTTP request, so these are generous relative to the
	 * ~10s timeouts used elsewhere in this class, but still capped so one
	 * module job can't run indefinitely against a very large site.
	 */
	private const CRAWL_MAX_PAGES       = 20;
	private const CRAWL_REQUEST_TIMEOUT = 8;
	private const CRAWL_NAV_LINK_CAP    = 15;
	private const CRAWL_FOOTER_LINK_CAP = 20;
	private const CRAWL_LINK_CHECK_CAP  = 30;
	private const CRAWL_MEDIA_CHECK_CAP = 20;

	/** @var array<string, mixed> */
	private array $resolved = [];

	/** @var array<string, mixed>|\WP_Error|null */
	private mixed $loopbackResponse  = null;
	private bool $loopbackResolved   = false;
	private float $loopbackElapsedMs = 0.0;

	/** @var array<int, array{url: string, html: string}>|null */
	private ?array $crawledPagesCache  = null;
	private bool $crawledPagesResolved = false;

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
	 *   'theme_update_slugs'      → array<int,string>|null — theme stylesheet slugs that have pending updates
	 *   'core_update_available'   → array{current:string,latest:string,response:string}|null — pending core update, if any
	 *   'wp_cron_disabled'        → bool — value of the DISABLE_WP_CRON constant
	 *   'cron_pending_count'      → int|null — WP-Cron events with a timestamp in the past (via _get_cron_array())
	 *   'action_scheduler_overdue_count' → int|null — Action Scheduler pending/failed actions past their scheduled time
	 *   'php_error_log_tail'      → array<int,string>|null — last ~100 lines of the resolved PHP error log, bounded read
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
	 *   'session_cookies'         → array<int,array{name:string,secure:bool,httponly:bool,samesite:?string}>|null — parsed Set-Cookie headers from the loopback response
	 *   'page_asset_tags'         → array<int,array{type:string,url:string,integrity:?string,crossorigin:?string,external:bool}>|null — <script src>/<link rel="stylesheet" href> tags parsed from homepage_html
	 *   'https_redirect_chain'    → array<int,array{url:string,status:int,scheme:string}>|null — hop-by-hop trace of an explicit http:// request to the home host, redirects followed manually (redirection=>0)
	 *   'tls_certificate'         → array{valid_from:int,valid_to:int,days_until_expiry:int,subject_cn:string,issuer_cn:string,self_signed:bool}|null — peer certificate parsed from a raw TLS handshake to the home host
	 *   'homepage_http_status'    → int|null — raw HTTP status code from the shared loopback GET, even on non-200 (unlike homepage_html, which is null on any non-200)
	 *   'crawled_page_urls'       → array<int,string>|null — URLs of homepage + published pages/posts successfully crawled (bounded, see CRAWL_MAX_PAGES); no theme or menu API involved
	 *   'nav_links'               → array<int,array{url:string,text:string}>|null — links parsed out of all <nav>...</nav> blocks in homepage_html; theme-agnostic (no wp_get_nav_menu_items())
	 *   'footer_links'            → array<int,array{url:string,text:string}>|null — links parsed out of all <footer>...</footer> blocks in homepage_html
	 *   'broken_internal_links'   → array<int,array{url:string,status:int,found_on:string}>|null — same-host links across crawled pages that returned a non-2xx/3xx status
	 *   'broken_media'            → array<int,array{url:string,status:int,found_on:string}>|null — <img src> URLs across crawled pages that returned a non-2xx/3xx status
	 *   'search_check_status'     → int|null — HTTP status for the configured (or default {home}/?s=test) search URL
	 *   'cta_link_statuses'       → array<int,array{label:string,url:string,status:int}> — settings `cta_urls`, each checked; empty array when none configured (never null)
	 *   'key_landing_page_statuses' → array<int,array{label:string,url:string,status:int}> — settings `key_landing_pages`, each checked; empty array when none configured (never null)
	 *   'expect_gtm'              → bool — settings `expect_gtm`
	 *   'expect_ga4'              → bool — settings `expect_ga4`
	 *   'expect_meta_pixel'       → bool — settings `expect_meta_pixel`
	 *   'cookie_consent_custom_signature' → string|null — settings `cookie_consent_custom_signature`, trimmed; null when unset
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
			'theme_update_slugs'      => $this->resolveThemeUpdateSlugs(),
			'core_update_available'   => $this->resolveCoreUpdateAvailable(),
			'wp_cron_disabled'        => defined( 'DISABLE_WP_CRON' ) && (bool) constant( 'DISABLE_WP_CRON' ),
			'cron_pending_count'      => $this->resolveCronPendingCount(),
			'action_scheduler_overdue_count' => $this->resolveActionSchedulerOverdueCount(),
			'php_error_log_tail'      => $this->resolvePhpErrorLogTail(),
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
			'session_cookies'         => $this->resolveSessionCookies(),
			'page_asset_tags'         => $this->resolvePageAssetTags(),
			'https_redirect_chain'    => $this->resolveHttpsRedirectChain(),
			'tls_certificate'         => $this->resolveTlsCertificate(),
			'homepage_http_status'    => $this->resolveHomepageHttpStatus(),
			'crawled_page_urls'       => $this->resolveCrawledPageUrls(),
			'nav_links'               => $this->resolveNavLinks(),
			'footer_links'            => $this->resolveFooterLinks(),
			'broken_internal_links'   => $this->resolveBrokenInternalLinks(),
			'broken_media'            => $this->resolveBrokenMedia(),
			'search_check_status'     => $this->resolveSearchCheckStatus(),
			'cta_link_statuses'       => $this->resolveSettingsUrlListStatuses( 'cta_urls' ),
			'key_landing_page_statuses' => $this->resolveSettingsUrlListStatuses( 'key_landing_pages' ),
			'expect_gtm'              => $this->resolveSettingsBool( 'expect_gtm' ),
			'expect_ga4'              => $this->resolveSettingsBool( 'expect_ga4' ),
			'expect_meta_pixel'       => $this->resolveSettingsBool( 'expect_meta_pixel' ),
			'cookie_consent_custom_signature' => $this->resolveCookieConsentCustomSignature(),
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

	/** @return array<int, string>|null */
	private function resolveThemeUpdateSlugs(): ?array {
		if ( ! function_exists( 'get_site_transient' ) ) {
			return null;
		}
		$update = get_site_transient( 'theme_updates' );
		if ( ! is_object( $update ) || ! property_exists( $update, 'response' ) ) {
			return [];
		}
		/** @var array<string, mixed> $response */
		$response = (array) $update->response;
		return array_keys( $response );
	}

	/** @return array{current: string, latest: string, response: string}|null */
	private function resolveCoreUpdateAvailable(): ?array {
		if ( ! function_exists( 'get_site_transient' ) ) {
			return null;
		}
		$update = get_site_transient( 'update_core' );
		if ( ! is_object( $update ) || ! property_exists( $update, 'updates' ) || ! is_array( $update->updates ) ) {
			return null;
		}
		$first = $update->updates[0] ?? null;
		if ( ! is_object( $first ) ) {
			return null;
		}
		return [
			'current'  => $this->wpVersion(),
			'latest'   => isset( $first->version ) ? (string) $first->version : '',
			'response' => isset( $first->response ) ? (string) $first->response : '',
		];
	}

	/**
	 * Counts WP-Cron events whose scheduled timestamp has already passed —
	 * a signal that the site's cron runner (real or DISABLE_WP_CRON-driven
	 * pseudo-cron) isn't actually firing.
	 */
	private function resolveCronPendingCount(): ?int {
		if ( ! function_exists( '_get_cron_array' ) ) {
			return null;
		}
		$cron = _get_cron_array();
		if ( ! is_array( $cron ) ) {
			return null;
		}
		$now   = time();
		$count = 0;
		foreach ( array_keys( $cron ) as $timestamp ) {
			if ( (int) $timestamp < $now ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Counts Action Scheduler actions that are still PENDING despite their
	 * scheduled date being in the past — the AS-equivalent of an overdue
	 * WP-Cron event. Action Scheduler is bundled with the plugin, but this
	 * still guards with class_exists() in case a host strips it.
	 */
	private function resolveActionSchedulerOverdueCount(): ?int {
		if ( ! class_exists( '\ActionScheduler_Store' ) || ! function_exists( 'as_get_datetime_object' ) ) {
			return null;
		}

		try {
			$store = \ActionScheduler_Store::instance();
			$count = $store->query_actions(
				[
					'status'       => [ \ActionScheduler_Store::STATUS_PENDING ],
					'date'         => as_get_datetime_object(),
					'date_compare' => '<=',
					'per_page'     => -1,
				],
				'count'
			);
			return is_numeric( $count ) ? (int) $count : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Bounded tail-read of the resolved PHP error log — never a full
	 * file_get_contents(), which would be unsafe against a multi-GB log.
	 *
	 * @return array<int, string>|null
	 */
	private function resolvePhpErrorLogTail(): ?array {
		$path = $this->resolveErrorLogPath();

		if ( null === $path || ! is_readable( $path ) ) {
			return null;
		}

		return $this->tailFile( $path, 100, 200 * 1024 );
	}

	private function resolveErrorLogPath(): ?string {
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			// WP_DEBUG_LOG is documented as accepting bool *or* a custom log file
			// path string — narrower than the stub's fixed bool type, so read it
			// through an explicitly mixed-typed local to avoid a false-positive
			// "always false" from static analysis of the stub's declared type.
			/** @var mixed $debugLog */
			$debugLog = constant( 'WP_DEBUG_LOG' );

			if ( is_string( $debugLog ) ) {
				return $debugLog;
			}
			if ( true === $debugLog ) {
				return $this->contentPath() . 'debug.log';
			}
		}
		$iniPath = ini_get( 'error_log' );
		return is_string( $iniPath ) && '' !== $iniPath ? $iniPath : null;
	}

	/**
	 * Reads at most $maxLines lines / $maxBytes bytes from the end of a file
	 * by seeking backward from EOF, rather than loading the whole file into
	 * memory — safe against arbitrarily large logs.
	 *
	 * @return array<int, string>
	 */
	private function tailFile( string $path, int $maxLines, int $maxBytes ): array {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		// WP_Filesystem has no seek-from-end/partial-read primitive, so a bounded
		// tail-read of a potentially multi-GB log needs direct, low-level file
		// access rather than loading the whole file through WP_Filesystem::get_contents().
		$handle = @fopen( $path, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- deliberately non-fatal on an unreadable/vanished log file.

		if ( false === $handle ) {
			return [];
		}

		$fileSize = filesize( $path );
		$readSize = false !== $fileSize ? min( $fileSize, $maxBytes ) : $maxBytes;

		fseek( $handle, -$readSize, SEEK_END );
		$chunk = fread( $handle, $readSize );
		fclose( $handle );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( false === $chunk || '' === trim( $chunk ) ) {
			return [];
		}

		$lines = preg_split( '/\R/', trim( $chunk ) );
		if ( false === $lines ) {
			return [];
		}

		return array_slice( $lines, -$maxLines );
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

	/**
	 * Parses Set-Cookie headers from the shared loopback response.
	 *
	 * Reuses the existing homepage request — no additional HTTP call.
	 *
	 * @return array<int, array{name: string, secure: bool, httponly: bool, samesite: ?string}>|null
	 */
	private function resolveSessionCookies(): ?array {
		$response = $this->sharedLoopback();

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			return null;
		}

		$rawHeaders = wp_remote_retrieve_headers( $response );

		// Real WordPress may return WpOrg\Requests\Response\Headers, whose
		// default iteration/offsetGet comma-flattens repeated headers. Since
		// a Set-Cookie's own Expires attribute contains commas, that would
		// corrupt multi-cookie responses, so prefer the un-flattened raw
		// values when available. The test stub always returns a plain array.
		if ( is_object( $rawHeaders ) && method_exists( $rawHeaders, 'getValues' ) ) {
			/** @var array<int, string> $cookieLines */
			$cookieLines = $rawHeaders->getValues( 'set-cookie' ) ?? [];
		} else {
			$cookieLines = [];
			foreach ( (array) $rawHeaders as $name => $value ) {
				if ( 'set-cookie' === strtolower( (string) $name ) ) {
					$cookieLines = array_merge( $cookieLines, (array) $value );
				}
			}
		}

		$cookies = [];
		foreach ( $cookieLines as $line ) {
			$cookie = $this->parseCookieLine( (string) $line );
			if ( null !== $cookie ) {
				$cookies[] = $cookie;
			}
		}

		return $cookies;
	}

	/** @return array{name: string, secure: bool, httponly: bool, samesite: ?string}|null */
	private function parseCookieLine( string $line ): ?array {
		$parts     = array_map( 'trim', explode( ';', $line ) );
		$nameValue = (string) array_shift( $parts );

		if ( ! str_contains( $nameValue, '=' ) ) {
			return null;
		}

		[ $name ] = explode( '=', $nameValue, 2 );
		$name     = trim( $name );

		if ( '' === $name ) {
			return null;
		}

		$secure   = false;
		$httponly = false;
		$sameSite = null;

		foreach ( $parts as $attribute ) {
			if ( '' === $attribute ) {
				continue;
			}

			if ( str_contains( $attribute, '=' ) ) {
				[ $attrName, $attrValue ] = array_map( 'trim', explode( '=', $attribute, 2 ) );
			} else {
				$attrName  = trim( $attribute );
				$attrValue = null;
			}

			switch ( strtolower( $attrName ) ) {
				case 'secure':
					$secure = true;
					break;
				case 'httponly':
					$httponly = true;
					break;
				case 'samesite':
					$sameSite = $attrValue;
					break;
			}
		}

		return [
			'name'     => $name,
			'secure'   => $secure,
			'httponly' => $httponly,
			'samesite' => $sameSite,
		];
	}

	/**
	 * Parses <script src> / <link rel="stylesheet" href> tags out of the
	 * homepage HTML, flagging integrity/crossorigin attributes and whether
	 * the asset is cross-origin relative to the home URL.
	 *
	 * Reuses the already-fetched homepage_html — no additional HTTP call.
	 * This single key backs two independent Checks (SRI, outdated JS
	 * libraries) in two different modules: one HTML parse, two findings.
	 *
	 * @return array<int, array{type: string, url: string, integrity: ?string, crossorigin: ?string, external: bool}>|null
	 */
	private function resolvePageAssetTags(): ?array {
		$html = $this->resolveHomepageHtml();

		if ( null === $html ) {
			return null;
		}

		$homeHost = (string) wp_parse_url( $this->homeUrl(), PHP_URL_HOST );
		$tags     = [];

		if ( preg_match_all( '/<script\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i', $html, $scriptMatches, PREG_SET_ORDER ) ) {
			foreach ( $scriptMatches as $match ) {
				$tags[] = $this->buildAssetTag( 'script', $match[0], $match[1], $homeHost );
			}
		}

		if ( preg_match_all( '/<link\b[^>]*>/i', $html, $linkMatches ) ) {
			foreach ( $linkMatches[0] as $tag ) {
				if ( 1 !== preg_match( '/\brel=["\']stylesheet["\']/i', $tag ) ) {
					continue;
				}
				if ( 1 !== preg_match( '/\bhref=["\']([^"\']+)["\']/i', $tag, $hrefMatch ) ) {
					continue;
				}
				$tags[] = $this->buildAssetTag( 'style', $tag, $hrefMatch[1], $homeHost );
			}
		}

		return $tags;
	}

	/** @return array{type: string, url: string, integrity: ?string, crossorigin: ?string, external: bool} */
	private function buildAssetTag( string $type, string $tag, string $url, string $homeHost ): array {
		preg_match( '/\bintegrity=["\']([^"\']+)["\']/i', $tag, $integrityMatch );
		preg_match( '/\bcrossorigin=["\']?([^"\'>\s]*)/i', $tag, $crossoriginMatch );

		return [
			'type'        => $type,
			'url'         => $url,
			'integrity'   => '' !== ( $integrityMatch[1] ?? '' ) ? $integrityMatch[1] : null,
			'crossorigin' => '' !== ( $crossoriginMatch[1] ?? '' ) ? $crossoriginMatch[1] : null,
			'external'    => $this->isExternalAssetUrl( $url, $homeHost ),
		];
	}

	private function isExternalAssetUrl( string $url, string $homeHost ): bool {
		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		$assetHost = (string) wp_parse_url( $url, PHP_URL_HOST );

		// Root-relative / relative URLs have no host component → same-origin.
		if ( '' === $assetHost ) {
			return false;
		}

		return '' !== $homeHost && strtolower( $assetHost ) !== strtolower( $homeHost );
	}

	/**
	 * Traces an explicit http:// request to the home host, following
	 * redirects manually (redirection => 0) so intermediate hops are
	 * observable. This is a new, separate request from sharedLoopback(),
	 * which always requests the already-https:// home URL and hides
	 * intermediate hops by following up to 5 redirects internally.
	 *
	 * @return array<int, array{url: string, status: int, scheme: string}>|null
	 */
	private function resolveHttpsRedirectChain(): ?array {
		$host = (string) wp_parse_url( $this->homeUrl(), PHP_URL_HOST );

		if ( '' === $host ) {
			return null;
		}

		$url   = 'http://' . $host . '/';
		$chain = [];

		for ( $hop = 0; $hop < 5; $hop++ ) {
			$response = wp_remote_get(
				$url,
				[
					'timeout'     => 10,
					'sslverify'   => false,
					'redirection' => 0,
				]
			);

			if ( is_wp_error( $response ) ) {
				// Connection failure is itself a meaningful, non-null result
				// (e.g. a host that doesn't listen on port 80 at all) — not
				// the same as "couldn't determine anything".
				$chain[] = [
					'url'    => $url,
					'status' => 0,
					'scheme' => (string) wp_parse_url( $url, PHP_URL_SCHEME ),
				];
				break;
			}

			$code    = (int) wp_remote_retrieve_response_code( $response );
			$chain[] = [
				'url'    => $url,
				'status' => $code,
				'scheme' => (string) wp_parse_url( $url, PHP_URL_SCHEME ),
			];

			if ( $code < 300 || $code >= 400 ) {
				break;
			}

			$headers  = (array) wp_remote_retrieve_headers( $response );
			$location = $headers['location'] ?? null;

			if ( null === $location || '' === $location ) {
				break;
			}

			$url = $this->resolveRedirectUrl( (string) $location, $url );
		}

		return $chain;
	}

	private function resolveRedirectUrl( string $location, string $previousUrl ): string {
		if ( str_starts_with( $location, 'http://' ) || str_starts_with( $location, 'https://' ) ) {
			return $location;
		}

		$scheme = (string) wp_parse_url( $previousUrl, PHP_URL_SCHEME );
		$host   = (string) wp_parse_url( $previousUrl, PHP_URL_HOST );

		if ( str_starts_with( $location, '//' ) ) {
			return $scheme . ':' . $location;
		}

		if ( str_starts_with( $location, '/' ) ) {
			return $scheme . '://' . $host . $location;
		}

		return $scheme . '://' . $host . '/' . ltrim( $location, '/' );
	}

	/**
	 * Delegates to TlsCertificateInspector for a raw TLS handshake — cannot
	 * reuse sharedLoopback(), which forces sslverify=>false and never
	 * exposes the peer certificate via wp_remote_get()/cURL regardless.
	 *
	 * @return array{valid_from: int, valid_to: int, days_until_expiry: int, subject_cn: string, issuer_cn: string, self_signed: bool}|null
	 */
	private function resolveTlsCertificate(): ?array {
		$url = $this->homeUrl();

		if ( ! str_starts_with( $url, 'https://' ) ) {
			return null;
		}

		$host = (string) wp_parse_url( $url, PHP_URL_HOST );

		if ( '' === $host ) {
			return null;
		}

		return ( new TlsCertificateInspector() )->inspect( $host );
	}

	private function resolveHomepageHttpStatus(): ?int {
		$response = $this->sharedLoopback();

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Crawls the homepage plus a bounded set of published pages/posts via
	 * WP_Query — no settings, no external tooling, works identically on any
	 * install. Shared by every crawl-derived key below (nav/footer links,
	 * broken links/media) so the site is only fetched once per scan.
	 *
	 * @return array<int, array{url: string, html: string}>
	 */
	private function resolveCrawledPages(): array {
		if ( $this->crawledPagesResolved ) {
			return $this->crawledPagesCache ?? [];
		}
		$this->crawledPagesResolved = true;

		$pages = [];

		$homepageHtml = $this->resolveHomepageHtml();
		if ( null !== $homepageHtml ) {
			$pages[] = [
				'url'  => $this->homeUrl(),
				'html' => $homepageHtml,
			];
		}

		if ( ! function_exists( 'get_posts' ) ) {
			$this->crawledPagesCache = $pages;
			return $pages;
		}

		// CRAWL_MAX_PAGES is 20 and $pages has at most one element (the
		// homepage) at this point, so this is always a positive limit —
		// get_posts() with posts_per_page <= 0 would mean "unlimited",
		// which is what the subtraction guards against if that ever changes.
		$remaining = self::CRAWL_MAX_PAGES - count( $pages );

		/** @var array<int, int> $postIds */
		$postIds = get_posts(
			[
				'post_type'      => [ 'page', 'post' ],
				'post_status'    => 'publish',
				'posts_per_page' => $remaining,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		foreach ( $postIds as $postId ) {
			$url = get_permalink( $postId );
			if ( ! is_string( $url ) || '' === $url ) {
				continue;
			}

			$response = wp_remote_get(
				$url,
				[
					'timeout'     => self::CRAWL_REQUEST_TIMEOUT,
					'sslverify'   => false,
					'redirection' => 5,
				]
			);

			if ( is_wp_error( $response ) ) {
				continue;
			}

			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$pages[] = [
				'url'  => $url,
				'html' => wp_remote_retrieve_body( $response ),
			];
		}

		$this->crawledPagesCache = $pages;
		return $pages;
	}

	/** @return array<int, string>|null */
	private function resolveCrawledPageUrls(): ?array {
		$pages = $this->resolveCrawledPages();

		if ( [] === $pages ) {
			return null;
		}

		return array_column( $pages, 'url' );
	}

	/** @return array<int, array{url: string, text: string}>|null */
	private function resolveNavLinks(): ?array {
		$html = $this->resolveHomepageHtml();

		if ( null === $html ) {
			return null;
		}

		return $this->extractLinksFromHtml( $this->extractTagBlocks( $html, 'nav' ), self::CRAWL_NAV_LINK_CAP );
	}

	/** @return array<int, array{url: string, text: string}>|null */
	private function resolveFooterLinks(): ?array {
		$html = $this->resolveHomepageHtml();

		if ( null === $html ) {
			return null;
		}

		return $this->extractLinksFromHtml( $this->extractTagBlocks( $html, 'footer' ), self::CRAWL_FOOTER_LINK_CAP );
	}

	/**
	 * Concatenates the inner content of every non-nested occurrence of a tag
	 * (e.g. all <nav>...</nav> blocks) — semantic-HTML5 parsing, theme-agnostic
	 * by design: no dependency on a theme's registered menu location.
	 */
	private function extractTagBlocks( string $html, string $tag ): string {
		if ( ! preg_match_all( '/<' . $tag . '\b[^>]*>(.*?)<\/' . $tag . '>/is', $html, $matches ) ) {
			return '';
		}

		return implode( "\n", $matches[1] );
	}

	/**
	 * Extracts <a href> links from an HTML fragment, deduped by URL and
	 * skipping in-page anchors / non-navigable schemes.
	 *
	 * @return array<int, array{url: string, text: string}>
	 */
	private function extractLinksFromHtml( string $html, int $cap ): array {
		if ( '' === $html || ! preg_match_all( '/<a\b[^>]*\bhref=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER ) ) {
			return [];
		}

		$links = [];
		$seen  = [];

		foreach ( $matches as $match ) {
			$url = trim( $match[1] );

			if ( '' === $url || str_starts_with( $url, '#' ) || str_starts_with( $url, 'javascript:' ) || str_starts_with( $url, 'mailto:' ) || str_starts_with( $url, 'tel:' ) ) {
				continue;
			}

			if ( isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;

			$links[] = [
				'url'  => $url,
				'text' => trim( wp_strip_all_tags( $match[2] ) ),
			];

			if ( count( $links ) >= $cap ) {
				break;
			}
		}

		return $links;
	}

	/**
	 * Checks a single URL's reachability, preferring a HEAD request and
	 * falling back to GET when the host doesn't support HEAD (405/501) —
	 * the same "curl every link, confirm 200" approach requested for the
	 * broken-link/media checks, using WordPress's own HTTP API.
	 */
	private function checkUrlStatus( string $url ): int {
		$response = wp_remote_head(
			$url,
			[
				'timeout'     => self::CRAWL_REQUEST_TIMEOUT,
				'sslverify'   => false,
				'redirection' => 5,
			]
		);

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 405 === $code || 501 === $code ) {
			$response = wp_remote_get(
				$url,
				[
					'timeout'     => self::CRAWL_REQUEST_TIMEOUT,
					'sslverify'   => false,
					'redirection' => 5,
				]
			);
			$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		}

		return $code;
	}

	/**
	 * @return array<int, array{url: string, status: int, found_on: string}>|null
	 */
	private function resolveBrokenInternalLinks(): ?array {
		$pages = $this->resolveCrawledPages();

		if ( [] === $pages ) {
			return null;
		}

		$homeHost = strtolower( (string) wp_parse_url( $this->homeUrl(), PHP_URL_HOST ) );
		$checked  = [];
		$broken   = [];
		$count    = 0;

		foreach ( $pages as $page ) {
			if ( $count >= self::CRAWL_LINK_CHECK_CAP ) {
				break;
			}

			$links = $this->extractLinksFromHtml( $page['html'], self::CRAWL_LINK_CHECK_CAP );

			foreach ( $links as $link ) {
				if ( $count >= self::CRAWL_LINK_CHECK_CAP ) {
					break;
				}

				$absoluteUrl = $this->resolveRedirectUrl( $link['url'], $page['url'] );
				$linkHost    = strtolower( (string) wp_parse_url( $absoluteUrl, PHP_URL_HOST ) );

				if ( '' !== $homeHost && $linkHost !== $homeHost ) {
					continue;
				}

				if ( isset( $checked[ $absoluteUrl ] ) ) {
					continue;
				}
				$checked[ $absoluteUrl ] = true;
				++$count;

				$status = $this->checkUrlStatus( $absoluteUrl );
				if ( $status < 200 || $status >= 400 ) {
					$broken[] = [
						'url'      => $absoluteUrl,
						'status'   => $status,
						'found_on' => $page['url'],
					];
				}
			}
		}

		return $broken;
	}

	/**
	 * @return array<int, array{url: string, status: int, found_on: string}>|null
	 */
	private function resolveBrokenMedia(): ?array {
		$pages = $this->resolveCrawledPages();

		if ( [] === $pages ) {
			return null;
		}

		$checked = [];
		$broken  = [];
		$count   = 0;

		foreach ( $pages as $page ) {
			if ( $count >= self::CRAWL_MEDIA_CHECK_CAP ) {
				break;
			}

			if ( ! preg_match_all( '/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $page['html'], $matches ) ) {
				continue;
			}

			foreach ( $matches[1] as $src ) {
				if ( $count >= self::CRAWL_MEDIA_CHECK_CAP ) {
					break;
				}

				$src = trim( $src );
				if ( '' === $src || str_starts_with( $src, 'data:' ) ) {
					continue;
				}

				$absoluteUrl = $this->resolveRedirectUrl( $src, $page['url'] );

				if ( isset( $checked[ $absoluteUrl ] ) ) {
					continue;
				}
				$checked[ $absoluteUrl ] = true;
				++$count;

				$status = $this->checkUrlStatus( $absoluteUrl );
				if ( $status < 200 || $status >= 400 ) {
					$broken[] = [
						'url'      => $absoluteUrl,
						'status'   => $status,
						'found_on' => $page['url'],
					];
				}
			}
		}

		return $broken;
	}

	private function resolveSearchCheckStatus(): ?int {
		$settings  = (array) get_option( 'wp_security_settings', [] );
		$searchUrl = isset( $settings['search_url'] ) && '' !== trim( (string) $settings['search_url'] )
			? (string) $settings['search_url']
			: rtrim( $this->homeUrl(), '/' ) . '/?s=test';

		$response = wp_remote_get(
			$searchUrl,
			[
				'timeout'     => self::CRAWL_REQUEST_TIMEOUT,
				'sslverify'   => false,
				'redirection' => 5,
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Checks every {label,url} entry from a settings list (`cta_urls` /
	 * `key_landing_pages`), capped the same as the link-crawl checks. Always
	 * returns an array (empty when the setting is unset) — the Check layer
	 * distinguishes "nothing configured" (SKIPPED) from "configured and
	 * broken" (WARN) by checking emptiness, not nullness, matching the
	 * plan's SKIPPED-not-WARN acceptance criterion for these two checks.
	 *
	 * @return array<int, array{label: string, url: string, status: int}>
	 */
	private function resolveSettingsUrlListStatuses( string $settingsKey ): array {
		$settings = (array) get_option( 'wp_security_settings', [] );
		$list     = is_array( $settings[ $settingsKey ] ?? null ) ? $settings[ $settingsKey ] : [];

		$results = [];
		$count   = 0;

		foreach ( $list as $item ) {
			if ( $count >= self::CRAWL_LINK_CHECK_CAP ) {
				break;
			}

			if ( ! is_array( $item ) || empty( $item['url'] ) ) {
				continue;
			}

			$url   = (string) $item['url'];
			$label = ! empty( $item['label'] ) ? (string) $item['label'] : $url;

			$results[] = [
				'label'  => $label,
				'url'    => $url,
				'status' => $this->checkUrlStatus( $url ),
			];
			++$count;
		}

		return $results;
	}

	private function resolveSettingsBool( string $settingsKey ): bool {
		$settings = (array) get_option( 'wp_security_settings', [] );
		return ! empty( $settings[ $settingsKey ] );
	}

	private function resolveCookieConsentCustomSignature(): ?string {
		$settings = (array) get_option( 'wp_security_settings', [] );
		$value    = isset( $settings['cookie_consent_custom_signature'] ) ? trim( (string) $settings['cookie_consent_custom_signature'] ) : '';
		return '' === $value ? null : $value;
	}
}
