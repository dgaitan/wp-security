<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks that the homepage declares a canonical URL.
 *
 * A <link rel="canonical"> prevents duplicate-content penalties when
 * the same content is accessible via multiple URLs.
 */
class CanonicalCheck implements Check {

	public function id(): string {
		return 'seo.canonical';
	}

	public function label(): string {
		return __( 'Canonical URL', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$html = $context->get( 'homepage_html' );

		if ( ! is_string( $html ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not fetch homepage HTML.' );
		}

		if ( 1 === preg_match( '/<link\b[^>]*\brel=["\']canonical["\'][^>]*>/si', $html ) ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'A canonical URL tag is present on the homepage.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::LOW,
			title:          $this->label(),
			description:    __( 'No <link rel="canonical"> tag was found on the homepage.', 'wp-security' ),
			recommendation: __( 'Add a canonical URL tag to prevent duplicate-content issues. Most SEO plugins add this automatically.', 'wp-security' ),
		);
	}
}
