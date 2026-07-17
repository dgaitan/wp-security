<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that wp-content contains the three standard subdirectories:
 * plugins, themes, and uploads.
 */
class WpContentStructureCheck implements Check {

	/**
	 * The three directories WordPress ships with inside wp-content.
	 *
	 * @var list<string>
	 */
	private const REQUIRED_DIRS = [
		'plugins',
		'themes',
		'uploads',
	];

	public function id(): string {
		return 'core_integrity.wp_content_structure';
	}

	public function label(): string {
		return __( 'wp-content Directory Structure', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$base = rtrim( $context->contentPath(), '/\\' ) . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $base ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'The wp-content directory could not be found or is not accessible.'
			);
		}

		$missing = [];

		foreach ( self::REQUIRED_DIRS as $dir ) {
			if ( ! is_dir( $base . $dir ) ) {
				$missing[] = $dir;
			}
		}

		if ( [] === $missing ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'wp-content contains the expected plugins, themes, and uploads directories.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %s: comma-separated list of missing wp-content subdirectory names */
				__( 'The following expected wp-content subdirectories are missing: %s.', 'wp-security' ),
				implode( ', ', $missing )
			),
			recommendation: __( 'Ensure your WordPress installation has the standard wp-content/plugins, wp-content/themes, and wp-content/uploads directories. Their absence may indicate a misconfiguration or a directory that has been renamed or removed.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'missing_directories', $missing ),
		);
	}
}
