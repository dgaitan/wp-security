<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Users\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Counts administrator accounts and warns when the number is higher than expected.
 *
 * The principle of least privilege requires that administrator access is
 * granted only to those who genuinely need it.  More admins means more
 * credentials that, if compromised, give full site access.
 */
class AdminCountCheck implements Check {

	public function id(): string {
		return 'users.admin_count';
	}

	public function label(): string {
		return __( 'Administrator Account Count', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $adminCount */
		$adminCount = $context->get( 'admin_user_count' );

		if ( null === $adminCount ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not retrieve the administrator account count.'
			);
		}

		if ( 1 === $adminCount ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'One administrator account exists — this follows the principle of least privilege.', 'wp-security' )
			);
		}

		$severity = $adminCount >= 4 ? Severity::MEDIUM : Severity::LOW;

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       $severity,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of administrator accounts */
				__( '%d administrator accounts exist. Multiple administrators increase the risk of account compromise and make access auditing more difficult.', 'wp-security' ),
				$adminCount
			),
			recommendation: __( 'Review administrator accounts and downgrade any that do not require full administrator access to a lower capability role (Editor, Author, or Contributor).', 'wp-security' ),
			evidence:       [ 'admin_user_count' => $adminCount ],
		);
	}
}
