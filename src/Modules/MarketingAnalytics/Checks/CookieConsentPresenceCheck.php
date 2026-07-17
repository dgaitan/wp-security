<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Status;
use WPSecurity\Domain\Severity;
use WPSecurity\Modules\MarketingAnalytics\CookieConsentSignatures;

/**
 * Detects a known cookie consent platform on the homepage. Unlike the
 * GTM/GA4/Meta Pixel checks, there's no `expect_*` gate here — a consent
 * banner isn't universally required, so absence is always informational,
 * never a WARN.
 */
class CookieConsentPresenceCheck implements Check {

	public function id(): string {
		return 'marketing_analytics.cookie_consent_presence';
	}

	public function label(): string {
		return __( 'Cookie Consent Platform', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var string|null $html */
		$html = $context->get( 'homepage_html' );

		if ( null === $html ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch the homepage to check for a cookie consent platform.'
			);
		}

		/** @var string|null $customSignature */
		$customSignature = $context->get( 'cookie_consent_custom_signature' );

		foreach ( CookieConsentSignatures::signatures( $customSignature ) as $platform => $pattern ) {
			if ( 1 === preg_match( $pattern, $html ) ) {
				return Finding::pass(
					$this->id(),
					$this->label(),
					sprintf(
						/* translators: %s: cookie consent platform name */
						__( 'Cookie consent platform detected: %s.', 'wp-security' ),
						$platform
					)
				);
			}
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::INFO,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    __( 'No known cookie consent platform was detected on the homepage.', 'wp-security' ),
			recommendation: __( 'If a consent platform is expected, confirm it loads on the homepage, or add a custom detection signature in settings.', 'wp-security' ),
		);
	}
}
