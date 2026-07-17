<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics\Checks;

class GtmPresenceCheck extends AbstractPresenceCheck {

	public function id(): string {
		return 'marketing_analytics.gtm_presence';
	}

	public function label(): string {
		return __( 'Google Tag Manager', 'wp-security' );
	}

	protected function isPresent( string $html ): bool {
		return 1 === preg_match( '/GTM-[A-Z0-9]+/', $html );
	}

	protected function expectContextKey(): string {
		return 'expect_gtm';
	}
}
