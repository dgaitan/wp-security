<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Users\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags user accounts that have not logged in for more than 90 days.
 *
 * Login timestamps are recorded in wp_wpsec_logins via the wp_login hook.
 * Dormant accounts increase the risk of undetected compromise and should
 * be reviewed and disabled if no longer needed.
 */
class DormantUsersCheck implements Check {

	public function id(): string {
		return 'users.dormant_users';
	}

	public function label(): string {
		return __( 'Dormant User Accounts', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $dormantCount */
		$dormantCount = $context->get( 'dormant_user_count' );

		if ( null === $dormantCount ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not determine dormant user accounts. Login timestamps may not be tracked yet — login activity will be recorded from now on.'
			);
		}

		if ( 0 === $dormantCount ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'All tracked users have logged in within the last 90 days.', 'wp-security' )
			);
		}

		$message = 1 === $dormantCount
			? __( '1 user account has not logged in for over 90 days.', 'wp-security' )
			: sprintf(
				/* translators: %d: number of dormant user accounts */
				__( '%d user accounts have not logged in for over 90 days.', 'wp-security' ),
				$dormantCount
			);

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    $message,
			recommendation: __( 'Review dormant accounts and disable or delete any that are no longer needed. Dormant accounts are a common vector for unauthorized access if credentials have been compromised.', 'wp-security' ),
			evidence:       [ 'dormant_user_count' => $dormantCount ],
		);
	}
}
