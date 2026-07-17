<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Performance\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Detects whether HTTP compression (GZIP, Brotli, Deflate) is enabled.
 *
 * Uses the `response_headers` context key which carries the lowercase headers
 * from a loopback GET to the homepage.
 */
class CompressionCheck implements Check {

	/** @var array<int, string> */
	private const COMPRESSED_ENCODINGS = [ 'gzip', 'br', 'deflate', 'zstd' ];

	public function id(): string {
		return 'performance.compression';
	}

	public function label(): string {
		return __( 'HTTP Compression', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$headers = $context->get( 'response_headers' );

		if ( ! is_array( $headers ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not retrieve response headers.' );
		}

		$encoding = strtolower( (string) ( $headers['content-encoding'] ?? '' ) );

		foreach ( self::COMPRESSED_ENCODINGS as $comp ) {
			if ( str_contains( $encoding, $comp ) ) {
				return Finding::pass(
					$this->id(),
					$this->label(),
					/* translators: %s: encoding type (e.g. gzip, br) */
					sprintf( __( 'HTTP compression is enabled (%s).', 'wp-security' ), $encoding )
				);
			}
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'HTTP compression (GZIP or Brotli) is not enabled.', 'wp-security' ),
			recommendation: __( 'Enable GZIP or Brotli compression in your server or via a caching plugin to reduce page weight and improve load time.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'content_encoding', '' === $encoding ? 'none' : $encoding ),
		);
	}
}
