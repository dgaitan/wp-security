<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that a valid robots.txt file is accessible at the site root.
 *
 * Uses the `robots_txt_status` context key (HTTP status code).
 */
class RobotsTxtCheck implements Check {

	public function id(): string {
		return 'seo.robots_txt';
	}

	public function label(): string {
		return __( 'robots.txt', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$status = $context->get( 'robots_txt_status' );

		if ( ! is_int( $status ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not fetch robots.txt.' );
		}

		if ( 200 === $status ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'robots.txt is accessible and returns HTTP 200.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			/* translators: %d: HTTP status code */
			description:    sprintf( __( 'robots.txt returned HTTP %d. Search engines expect a 200 response.', 'wp-security' ), $status ),
			recommendation: __( 'Ensure a valid robots.txt file is accessible at the root of your domain.', 'wp-security' ),
			evidence:       [ 'http_status' => $status ],
		);
	}
}
