<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks the PHP memory_limit against WordPress's recommended minimum (64 MB).
 */
class MemoryLimitCheck implements Check {

	private const MIN_BYTES = 64 * 1024 * 1024;

	public function id(): string {
		return 'server.memory_limit';
	}

	public function label(): string {
		return __( 'Memory Limit', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		try {
			$raw = $context->get( 'memory_limit' );
		} catch ( \Throwable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not read memory limit.' );
		}

		if ( null === $raw || false === $raw ) {
			return Finding::skipped( $this->id(), $this->label(), 'Memory limit is not available in this environment.' );
		}

		$raw   = (string) $raw;
		$bytes = $this->parseBytes( $raw );

		if ( -1 === $bytes ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'PHP memory limit is set to unlimited (-1).', 'wp-security' )
			);
		}

		if ( $bytes < self::MIN_BYTES ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				/* translators: %s: memory limit string such as "32M" */
				description:    sprintf( __( 'PHP memory limit is %s, which is below the recommended 64 MB.', 'wp-security' ), $raw ),
				recommendation: __( 'Set memory_limit to at least 64M in php.ini or wp-config.php (define WP_MEMORY_LIMIT).', 'wp-security' ),
				evidence:       ( new Evidence() )
					->add( 'current', $raw )
					->add( 'recommended_minimum', '64M' ),
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			/* translators: %s: memory limit string such as "256M" */
			sprintf( __( 'PHP memory limit is %s.', 'wp-security' ), $raw )
		);
	}

	private function parseBytes( string $value ): int {
		$value = trim( $value );

		if ( '-1' === $value ) {
			return -1;
		}

		$last = strtolower( substr( $value, -1 ) );
		$num  = (int) $value;

		return match ( $last ) {
			'g'     => $num * 1024 * 1024 * 1024,
			'm'     => $num * 1024 * 1024,
			'k'     => $num * 1024,
			default => $num,
		};
	}
}
