<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\MarketingAnalytics\Checks\CookieConsentPresenceCheck;
use WPSecurity\Modules\MarketingAnalytics\Checks\Ga4PresenceCheck;
use WPSecurity\Modules\MarketingAnalytics\Checks\GtmPresenceCheck;
use WPSecurity\Modules\MarketingAnalytics\Checks\MetaPixelPresenceCheck;

/**
 * Marketing & Analytics module — verifies tracking/consent tooling is
 * present in the homepage HTML. Kept separate from Functional QA: mixing
 * "is my site broken" severity with "is my ad pixel firing" severity in one
 * module_score would muddy the one signal the dashboard relies on.
 */
class MarketingAnalyticsModule implements Module {

	public function id(): string {
		return 'marketing_analytics';
	}

	public function label(): string {
		return __( 'Marketing & Analytics', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-megaphone';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new GtmPresenceCheck(),
			new Ga4PresenceCheck(),
			new MetaPixelPresenceCheck(),
			new CookieConsentPresenceCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Marketing & Analytics module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		yield from apply_filters( 'wp_security/checks/marketing_analytics', $checks );
	}
}
