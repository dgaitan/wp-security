<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Dns\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that an SPF TXT record exists for the site's domain.
 *
 * SPF prevents other mail servers from sending email as if it originated
 * from your domain.  The context provides the raw DNS TXT records via
 * 'dns_txt_records'; the check filters for 'v=spf1' prefixed entries.
 */
class SpfCheck implements Check {

	public function id(): string {
		return 'dns.spf';
	}

	public function label(): string {
		return __( 'SPF Record', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$records = $context->get( 'dns_txt_records' );

		if ( ! is_array( $records ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'DNS TXT records could not be retrieved for the domain.'
			);
		}

		foreach ( $records as $record ) {
			$txt = is_array( $record ) ? ( (string) ( $record['txt'] ?? '' ) ) : '';
			if ( str_starts_with( $txt, 'v=spf1' ) ) {
				return Finding::pass(
					$this->id(),
					$this->label(),
					__( 'An SPF TXT record is present for the domain.', 'wp-security' )
				);
			}
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'No SPF TXT record was found for your domain. Without SPF, anyone can send email that appears to originate from your domain.', 'wp-security' ),
			recommendation: __( 'Add an SPF TXT record to your DNS zone. Example: "v=spf1 include:_spf.yourmailprovider.com ~all".', 'wp-security' ),
		);
	}
}
