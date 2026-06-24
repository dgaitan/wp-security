<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks that an XML sitemap is reachable at /sitemap.xml.
 *
 * Uses the `sitemap_reachable` context key (bool|null).
 */
class SitemapCheck implements Check {

	public function id(): string {
		return 'seo.sitemap';
	}

	public function label(): string {
		return __( 'XML Sitemap', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$reachable = $context->get( 'sitemap_reachable' );

		if ( null === $reachable ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not check sitemap availability.' );
		}

		if ( $reachable ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'XML sitemap is accessible at /sitemap.xml.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    __( 'No XML sitemap found at /sitemap.xml.', 'wp-security' ),
			recommendation: __( 'Generate and submit an XML sitemap. Most SEO plugins (Yoast, Rank Math) create one automatically.', 'wp-security' ),
		);
	}
}
