<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies WordPress core files against the official checksums from api.wordpress.org.
 *
 * The context provides the checksum map via 'core_checksums' (an array of
 * relative_path => expected_md5_hash).  Files absent from the local install
 * are silently skipped; only files whose hash differs from the published value
 * are flagged as modified.
 */
class CoreFilesCheck implements Check {

	public function id(): string {
		return 'core_integrity.core_files';
	}

	public function label(): string {
		return __( 'Core File Integrity', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$checksums = $context->get( 'core_checksums' );

		if ( ! is_array( $checksums ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Official WordPress checksums could not be retrieved from api.wordpress.org.'
			);
		}

		$wpRoot   = rtrim( $context->wpRootPath(), '/\\' ) . DIRECTORY_SEPARATOR;
		$modified = [];

		foreach ( $checksums as $file => $expectedHash ) {
			$path = $wpRoot . $file;

			if ( ! file_exists( $path ) ) {
				// Missing core files are a separate concern; skip here.
				continue;
			}

			$actualHash = md5_file( $path );

			if ( false === $actualHash ) {
				// Cannot read file — skip rather than false-positive.
				continue;
			}

			if ( $actualHash !== (string) $expectedHash ) {
				$modified[] = $file;
			}
		}

		if ( [] === $modified ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All audited core files match the official WordPress checksums.', 'wp-security' )
			);
		}

		$count = count( $modified );

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       Severity::CRITICAL,
			title:          $this->label(),
			description:    1 === $count
				? __( '1 core file has been modified and does not match the official WordPress checksum.', 'wp-security' )
				: sprintf(
					/* translators: %d: number of modified core files */
					__( '%d core files have been modified and do not match the official WordPress checksums.', 'wp-security' ),
					$count
				),
			recommendation: __( 'Reinstall WordPress core via Dashboard → Updates or WP-CLI ("wp core download --force"). Investigate the changes — they may indicate malware or tampering.', 'wp-security' ),
			evidence:       [ 'modified_files' => $modified ],
		);
	}
}
