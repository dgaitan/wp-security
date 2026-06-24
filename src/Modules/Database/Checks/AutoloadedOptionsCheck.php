<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Database\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Warns when the total size of autoloaded options exceeds 1 MB.
 *
 * WordPress loads all autoloaded options on every page request.  A bloated
 * autoload set adds latency to every request and is a common source of
 * performance issues on mature sites.
 */
class AutoloadedOptionsCheck implements Check {

	private const THRESHOLD_BYTES = 1048576; // 1 MB

	public function id(): string {
		return 'database.autoloaded_options';
	}

	public function label(): string {
		return __( 'Autoloaded Options Size', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $sizeBytes */
		$sizeBytes = $context->get( 'autoloaded_options_size' );

		if ( null === $sizeBytes ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not query autoloaded options size from the database.'
			);
		}

		if ( $sizeBytes <= self::THRESHOLD_BYTES ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				sprintf(
					/* translators: %s: human-readable size string such as "512 KB" */
					__( 'Autoloaded options total %s — within the 1 MB threshold.', 'wp-security' ),
					$this->formatBytes( $sizeBytes )
				)
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %s: human-readable size string such as "1.5 MB" */
				__( 'Autoloaded options total %s, which exceeds the recommended 1 MB limit. Large autoloaded data slows every WordPress page load.', 'wp-security' ),
				$this->formatBytes( $sizeBytes )
			),
			recommendation: __( 'Review and remove unnecessary autoloaded options. Run SELECT option_name, LENGTH(option_value) FROM wp_options WHERE autoload="yes" ORDER BY 2 DESC to identify the largest contributors.', 'wp-security' ),
			evidence:       [ 'autoloaded_size_bytes' => $sizeBytes ],
		);
	}

	private function formatBytes( int $bytes ): string {
		if ( $bytes >= self::THRESHOLD_BYTES ) {
			return round( $bytes / self::THRESHOLD_BYTES, 2 ) . ' MB';
		}
		return round( $bytes / 1024, 2 ) . ' KB';
	}
}
