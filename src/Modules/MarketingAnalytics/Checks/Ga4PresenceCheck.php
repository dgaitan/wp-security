<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics\Checks;

/**
 * Requires both a GA4 measurement-ID pattern and a gtag.js signature —
 * either alone is too weak a signal (e.g. "G-" prefixed strings appear in
 * unrelated contexts; gtag() alone doesn't confirm a GA4 property is wired).
 */
class Ga4PresenceCheck extends AbstractPresenceCheck {

	public function id(): string {
		return 'marketing_analytics.ga4_presence';
	}

	public function label(): string {
		return __( 'Google Analytics 4', 'wp-security' );
	}

	protected function isPresent( string $html ): bool {
		$hasMeasurementId = 1 === preg_match( '/G-[A-Z0-9]{6,}/', $html );
		$hasSignature     = 1 === preg_match( '/gtag\s*\(|googletagmanager\.com\/gtag\/js/i', $html );

		return $hasMeasurementId && $hasSignature;
	}

	protected function expectContextKey(): string {
		return 'expect_ga4';
	}
}
