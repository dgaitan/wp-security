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
 * Checks whether the site's TLS certificate is expired or expiring soon.
 *
 * An expired certificate is a hard connectivity failure — modern browsers
 * hard-block visitors with no click-through — functionally equivalent to an
 * outage, not a hardening gap.
 *
 * Thresholds:
 *   Already expired    → FAIL / CRITICAL
 *   < 14 days remaining → WARN / HIGH
 *   < 30 days remaining → WARN / MEDIUM
 *   ≥ 30 days remaining → PASS
 */
class TlsCertificateExpiryCheck implements Check {

	private const CRITICAL_DAYS = 14;
	private const WARN_DAYS     = 30;

	public function id(): string {
		return 'server.tls_certificate_expiry';
	}

	public function label(): string {
		return __( 'TLS Certificate Expiry', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$certificate = $context->get( 'tls_certificate' );

		if ( ! is_array( $certificate ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not establish a TLS session to inspect the certificate — the site may not be using HTTPS, or the connection failed.'
			);
		}

		$daysUntilExpiry = (int) ( $certificate['days_until_expiry'] ?? 0 );
		$evidence        = ( new Evidence() )
			->add( 'valid_to', $certificate['valid_to'] ?? null )
			->add( 'days_until_expiry', $daysUntilExpiry )
			->add( 'subject_cn', $certificate['subject_cn'] ?? null )
			->add( 'issuer_cn', $certificate['issuer_cn'] ?? null )
			->add( 'self_signed', $certificate['self_signed'] ?? null );

		if ( $daysUntilExpiry < 0 ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::CRITICAL,
				title:          $this->label(),
				description:    __( 'The TLS certificate has expired. Modern browsers will block visitors from accessing the site.', 'wp-security' ),
				recommendation: __( 'Renew the TLS certificate immediately.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		if ( $daysUntilExpiry < self::CRITICAL_DAYS ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: number of days until certificate expiry */
					__( 'The TLS certificate expires in %d day(s).', 'wp-security' ),
					$daysUntilExpiry
				),
				recommendation: __( 'Renew the TLS certificate before it expires.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		if ( $daysUntilExpiry < self::WARN_DAYS ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: number of days until certificate expiry */
					__( 'The TLS certificate expires in %d day(s).', 'wp-security' ),
					$daysUntilExpiry
				),
				recommendation: __( 'Plan to renew the TLS certificate soon.', 'wp-security' ),
				evidence:       $evidence,
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			sprintf(
				/* translators: %d: number of days until certificate expiry */
				__( 'TLS certificate is valid for %d more day(s).', 'wp-security' ),
				$daysUntilExpiry
			)
		);
	}
}
