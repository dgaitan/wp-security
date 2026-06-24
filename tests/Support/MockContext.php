<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Support;

use WPSecurity\Contracts\Context;

/**
 * Configurable Context stub for unit tests.
 *
 * Pass controlled values through the constructor; checks are fully isolated
 * from the real WordPress environment.
 */
final class MockContext implements Context {

	/**
	 * @param array<string, mixed> $values Map of keys returned by get().
	 */
	public function __construct(
		private readonly string $phpVersion = '8.2.0',
		private readonly string $homeUrl = 'https://example.test',
		private readonly string $wpRootPath = '/var/www/html/',
		private readonly array $values = [],
	) {}

	public function wpRootPath(): string {
		return $this->wpRootPath;
	}

	public function contentPath(): string {
		return $this->wpRootPath . 'wp-content/';
	}

	public function homeUrl(): string {
		return $this->homeUrl;
	}

	public function wpVersion(): string {
		return '6.5.0';
	}

	public function phpVersion(): string {
		return $this->phpVersion;
	}

	public function get( string $key ): mixed {
		return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : null;
	}
}
