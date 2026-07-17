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
 * Checks that WordPress production-hardening constants are configured correctly.
 *
 * DISALLOW_FILE_EDIT should be true — prevents theme/plugin editing via wp-admin.
 * WP_DEBUG should be false — prevents stack traces from leaking in production.
 */
class WpConfigCheck implements Check {

	public function id(): string {
		return 'core_integrity.wp_config';
	}

	public function label(): string {
		return __( 'WordPress Hardening Constants', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$disallowFileEdit = $context->get( 'disallow_file_edit' );
		$wpDebug          = $context->get( 'wp_debug' );

		$issues = [];

		if ( true !== $disallowFileEdit ) {
			$issues[] = __( 'DISALLOW_FILE_EDIT is not set to true', 'wp-security' );
		}

		if ( true === $wpDebug ) {
			$issues[] = __( 'WP_DEBUG is enabled', 'wp-security' );
		}

		if ( [] === $issues ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'DISALLOW_FILE_EDIT is enabled and WP_DEBUG is disabled.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    implode( '; ', $issues ) . '.',
			recommendation: __( 'Add "define( \'DISALLOW_FILE_EDIT\', true );" and confirm "define( \'WP_DEBUG\', false );" in wp-config.php for production.', 'wp-security' ),
			evidence:       ( new Evidence() )
				->add( 'disallow_file_edit', $disallowFileEdit )
				->add( 'wp_debug', $wpDebug ),
		);
	}
}
