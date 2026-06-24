<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo;

use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Seo\Checks\CanonicalCheck;
use WPSecurity\Modules\Seo\Checks\MetaDescriptionCheck;
use WPSecurity\Modules\Seo\Checks\PageTitleCheck;
use WPSecurity\Modules\Seo\Checks\RobotsTxtCheck;
use WPSecurity\Modules\Seo\Checks\SitemapCheck;

/**
 * SEO module — Sprint 7.
 *
 * Runs lightweight SEO hygiene checks via loopback HTTP requests and HTML parsing.
 */
class SeoModule implements Module {

	public function id(): string {
		return 'seo';
	}

	public function label(): string {
		return __( 'SEO', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-search';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		$checks = [
			new PageTitleCheck(),
			new MetaDescriptionCheck(),
			new CanonicalCheck(),
			new RobotsTxtCheck(),
			new SitemapCheck(),
		];

		/**
		 * Allow third-party code to add checks to the SEO module.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/seo', $checks );
	}
}
