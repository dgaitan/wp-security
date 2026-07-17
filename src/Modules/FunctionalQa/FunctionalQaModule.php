<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\FunctionalQa\Checks\BrokenInternalLinkCheck;
use WPSecurity\Modules\FunctionalQa\Checks\FooterLinksCheck;
use WPSecurity\Modules\FunctionalQa\Checks\HomepageReachabilityCheck;
use WPSecurity\Modules\FunctionalQa\Checks\KeyLandingPagesCheck;
use WPSecurity\Modules\FunctionalQa\Checks\MediaLoadingCheck;
use WPSecurity\Modules\FunctionalQa\Checks\PrimaryCtaCheck;
use WPSecurity\Modules\FunctionalQa\Checks\PrimaryNavigationCheck;
use WPSecurity\Modules\FunctionalQa\Checks\SearchFunctionCheck;

/**
 * Functional QA module — homepage, navigation, footer, CTAs, landing pages,
 * search, and broken link/media smoke tests. Every check runs server-side
 * via WordPress's own HTTP API; no external tooling, no theme-specific menu
 * API (see ScanContext's crawler resolvers).
 */
class FunctionalQaModule implements Module {

	public function id(): string {
		return 'functional_qa';
	}

	public function label(): string {
		return __( 'Functional QA', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-yes-alt';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new HomepageReachabilityCheck(),
			new PrimaryNavigationCheck(),
			new FooterLinksCheck(),
			new PrimaryCtaCheck(),
			new KeyLandingPagesCheck(),
			new SearchFunctionCheck(),
			new BrokenInternalLinkCheck(),
			new MediaLoadingCheck(),
		];

		/**
		 * Allow third-party code to add checks to the Functional QA module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		yield from apply_filters( 'wp_security/checks/functional_qa', $checks );
	}
}
