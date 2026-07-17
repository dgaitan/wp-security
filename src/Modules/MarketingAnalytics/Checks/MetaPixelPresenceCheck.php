<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics\Checks;

/**
 * Requires both an fbq('init', ...) call and the fbevents.js loader —
 * mirrors Ga4PresenceCheck's two-signal confirmation approach.
 */
class MetaPixelPresenceCheck extends AbstractPresenceCheck {

	public function id(): string {
		return 'marketing_analytics.meta_pixel_presence';
	}

	public function label(): string {
		return __( 'Meta Pixel', 'wp-security' );
	}

	protected function isPresent( string $html ): bool {
		$hasInitCall  = 1 === preg_match( '/fbq\s*\(\s*[\'"]init[\'"]/i', $html );
		$hasSignature = 1 === preg_match( '/fbevents\.js/i', $html );

		return $hasInitCall && $hasSignature;
	}

	protected function expectContextKey(): string {
		return 'expect_meta_pixel';
	}
}
