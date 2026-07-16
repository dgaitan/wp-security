<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that cookies set on the homepage response carry the Secure,
 * HttpOnly, and SameSite attributes.
 *
 * Missing Secure/HttpOnly exposes cookies to network sniffing and XSS-based
 * theft; a missing/weak SameSite value leaves cookies usable in cross-site
 * request forgery. The loopback request is anonymous, so only cookies an
 * anonymous visitor receives (cache/consent/session plugins, etc.) are
 * visible here — authenticated cookies (e.g. wordpress_logged_in_*) are
 * outside a loopback scanner's reach.
 */
class CookieSecurityCheck implements Check {

	public function id(): string {
		return 'headers.cookie_flags';
	}

	public function label(): string {
		return __( 'Cookie Security Flags', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$cookies = $context->get( 'session_cookies' );

		if ( ! is_array( $cookies ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not retrieve cookies from the loopback request.'
			);
		}

		if ( [] === $cookies ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No cookies were observed on the anonymous homepage request. Authenticated cookies (e.g. login sessions) are outside the reach of a loopback scan.', 'wp-security' )
			);
		}

		$insecureCookies = [];
		$weakSameSite    = [];

		foreach ( $cookies as $cookie ) {
			$name = (string) ( $cookie['name'] ?? '' );

			if ( empty( $cookie['secure'] ) || empty( $cookie['httponly'] ) ) {
				$insecureCookies[] = $name;
				continue;
			}

			$sameSite = $cookie['samesite'] ?? null;
			if ( null === $sameSite || '' === $sameSite || 'none' === strtolower( (string) $sameSite ) ) {
				$weakSameSite[] = $name;
			}
		}

		if ( [] !== $insecureCookies ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %s: comma-separated list of cookie names */
					__( 'The following cookies are missing the Secure and/or HttpOnly attribute: %s.', 'wp-security' ),
					implode( ', ', $insecureCookies )
				),
				recommendation: __( 'Set the Secure and HttpOnly attributes on all cookies so they cannot be transmitted over plain HTTP or read by JavaScript.', 'wp-security' ),
				evidence:       [
					'insecure_cookies' => $insecureCookies,
					'weak_samesite'    => $weakSameSite,
				],
			);
		}

		if ( [] !== $weakSameSite ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %s: comma-separated list of cookie names */
					__( 'The following cookies are missing a strict SameSite attribute: %s.', 'wp-security' ),
					implode( ', ', $weakSameSite )
				),
				recommendation: __( 'Set SameSite=Strict or SameSite=Lax on cookies that do not need cross-site delivery.', 'wp-security' ),
				evidence:       [ 'weak_samesite' => $weakSameSite ],
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			__( 'All observed cookies set the Secure, HttpOnly, and SameSite attributes.', 'wp-security' )
		);
	}
}
