<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks whether the /wp/v2/users REST endpoint is accessible without
 * authentication, which exposes usernames for use in targeted attacks.
 */
class RestUserEnumerationCheck implements Check {

	public function id(): string {
		return 'core_integrity.rest_user_enumeration';
	}

	public function label(): string {
		return __( 'REST API User Enumeration', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$exposed = $context->get( 'rest_users_public' );

		if ( null === $exposed ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine whether the REST API /wp/v2/users endpoint is publicly accessible.'
			);
		}

		if ( ! $exposed ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'The /wp/v2/users endpoint is not accessible to unauthenticated requests.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'The /wp/v2/users REST endpoint returns user data without authentication, exposing usernames that can be leveraged in brute-force and targeted attacks.', 'wp-security' ),
			recommendation: __( 'Restrict the /wp/v2/users endpoint using a security plugin or custom code to require authentication.', 'wp-security' ),
		);
	}
}
