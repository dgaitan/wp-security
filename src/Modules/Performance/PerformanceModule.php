<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Performance;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Performance\Checks\CompressionCheck;
use WPSecurity\Modules\Performance\Checks\TtfbCheck;

/**
 * Performance module — Sprint 7.
 *
 * Runs loopback-request checks for TTFB and HTTP compression.
 */
class PerformanceModule implements Module {

	public function id(): string {
		return 'performance';
	}

	public function label(): string {
		return __( 'Performance', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-performance';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new TtfbCheck(),
			new CompressionCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Performance module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/performance', $checks );
	}
}
