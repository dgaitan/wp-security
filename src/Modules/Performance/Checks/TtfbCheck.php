<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Performance\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Measures server Time to First Byte (TTFB) via a loopback HTTP request.
 *
 * Thresholds based on Core Web Vitals guidance:
 *   Good   : < 200 ms
 *   Needs improvement : 200 – 800 ms
 *   Poor   : > 800 ms
 */
class TtfbCheck implements Check {

	private const WARN_THRESHOLD_MS = 200;
	private const FAIL_THRESHOLD_MS = 800;

	public function id(): string {
		return 'performance.ttfb';
	}

	public function label(): string {
		return __( 'Time to First Byte (TTFB)', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$ttfb = $context->get( 'ttfb_ms' );

		if ( ! is_int( $ttfb ) && ! is_float( $ttfb ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not measure TTFB for the homepage.' );
		}

		$ms = (float) $ttfb;

		if ( $ms >= self::FAIL_THRESHOLD_MS ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::HIGH,
				title:          $this->label(),
				/* translators: %s: TTFB in milliseconds */
				description:    sprintf( __( 'TTFB is %.0f ms — well above the 800 ms threshold. Users experience slow initial load.', 'wp-security' ), $ms ),
				recommendation: __( 'Investigate slow response times: check for slow database queries, unoptimised PHP, or missing page/object caching.', 'wp-security' ),
				evidence:       [ 'ttfb_ms' => $ms ],
			);
		}

		if ( $ms >= self::WARN_THRESHOLD_MS ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				/* translators: %s: TTFB in milliseconds */
				description:    sprintf( __( 'TTFB is %.0f ms, above the recommended 200 ms target.', 'wp-security' ), $ms ),
				recommendation: __( 'Enable page caching, object caching (Redis/Memcached), or a CDN to reduce TTFB.', 'wp-security' ),
				evidence:       [ 'ttfb_ms' => $ms ],
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			/* translators: %s: TTFB in milliseconds */
			sprintf( __( 'TTFB is %.0f ms — within the recommended 200 ms target.', 'wp-security' ), $ms )
		);
	}
}
