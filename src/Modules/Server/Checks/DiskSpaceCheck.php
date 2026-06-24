<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks free disk space on the WordPress install partition.
 *
 * Thresholds:
 *   < 100 MB free  → FAIL / HIGH   (file-system failures imminent)
 *   < 500 MB free  → WARN / MEDIUM (take action soon)
 *   ≥ 500 MB free  → PASS
 */
class DiskSpaceCheck implements Check {

	private const CRITICAL_BYTES = 100 * 1024 * 1024;
	private const WARN_BYTES     = 500 * 1024 * 1024;

	public function id(): string {
		return 'server.disk_space';
	}

	public function label(): string {
		return __( 'Disk Space', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$free  = $context->get( 'disk_free_bytes' );
			$total = $context->get( 'disk_total_bytes' );
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not read disk space information.' );
		}

		if ( null === $free || false === $free ) {
			return Finding::skipped( $this->id(), $this->label(), 'Disk space information is unavailable in this environment.' );
		}

		$freeBytes  = (int) $free;
		$totalBytes = null !== $total && false !== $total ? (int) $total : 0;
		$evidence   = [
			'free_mb'  => round( $freeBytes / ( 1024 * 1024 ), 1 ),
			'total_mb' => $totalBytes > 0 ? round( $totalBytes / ( 1024 * 1024 ), 1 ) : null,
		];

		if ( $freeBytes < self::CRITICAL_BYTES ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    __( 'Less than 100 MB of free disk space remains. File-system errors are imminent.', 'wp-security' ),
				recommendation: __( 'Free up disk space immediately by removing unused files, log archives, or backup copies.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		if ( $freeBytes < self::WARN_BYTES ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    __( 'Less than 500 MB of free disk space remains.', 'wp-security' ),
				recommendation: __( 'Monitor disk usage and free up space before it becomes critical.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			sprintf(
				/* translators: %s: free disk space in MB */
				__( '%s MB of free disk space available.', 'wp-security' ),
				number_format( $evidence['free_mb'] )
			)
		);
	}
}
