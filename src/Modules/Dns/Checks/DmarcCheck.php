<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Dns\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that a DMARC TXT record exists at _dmarc.{domain}.
 *
 * DMARC builds on SPF and DKIM to instruct receiving mail servers on what to
 * do with messages that fail authentication, and provides reporting.  The
 * context provides records via 'dns_dmarc_records' (a separate DNS lookup
 * against the _dmarc subdomain).
 */
class DmarcCheck implements Check {

	public function id(): string {
		return 'dns.dmarc';
	}

	public function label(): string {
		return __( 'DMARC Record', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$records = $context->get( 'dns_dmarc_records' );

		if ( ! is_array( $records ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'DMARC DNS records could not be retrieved. DNS resolution may be unavailable.'
			);
		}

		foreach ( $records as $record ) {
			$txt = is_array( $record ) ? ( (string) ( $record['txt'] ?? '' ) ) : '';
			if ( str_starts_with( $txt, 'v=DMARC1' ) ) {
				return Finding::pass(
					$this->id(),
					$this->label(),
					__( 'A DMARC record is present at _dmarc.{domain}.', 'wp-security' )
				);
			}
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'No DMARC TXT record was found at _dmarc.{domain}. Without DMARC, email spoofing of your domain is harder to detect and mitigate.', 'wp-security' ),
			recommendation: __( 'Add a DMARC TXT record at _dmarc.yourdomain.com. Example: "v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com".', 'wp-security' ),
		);
	}
}
