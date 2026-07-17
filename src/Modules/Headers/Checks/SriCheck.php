<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Headers\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Verifies that externally-hosted <script>/<link rel="stylesheet"> tags on
 * the homepage carry a Subresource Integrity (SRI) hash.
 *
 * Without SRI, a compromise of any third-party/CDN host used by the site
 * turns into guaranteed site-wide script injection with no additional
 * attacker effort.
 */
class SriCheck implements Check {

	public function id(): string {
		return 'headers.subresource_integrity';
	}

	public function label(): string {
		return __( 'Subresource Integrity', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$tags = $context->get( 'page_asset_tags' );

		if ( ! is_array( $tags ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch homepage HTML to inspect external assets.'
			);
		}

		$externalTags = array_values(
			array_filter(
				$tags,
				static fn ( array $tag ): bool => ! empty( $tag['external'] )
			)
		);

		if ( [] === $externalTags ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No externally-hosted scripts or stylesheets were found on the homepage.', 'wp-security' )
			);
		}

		$missingSri = array_values(
			array_map(
				static fn ( array $tag ): string => (string) $tag['url'],
				array_filter(
					$externalTags,
					static fn ( array $tag ): bool => empty( $tag['integrity'] )
				)
			)
		);

		if ( [] !== $missingSri ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %d: number of external assets missing an integrity attribute */
					__( '%d externally-hosted script(s)/stylesheet(s) are loaded without a Subresource Integrity (SRI) hash.', 'wp-security' ),
					count( $missingSri )
				),
				recommendation: __( 'Add an integrity attribute (and crossorigin="anonymous") to every externally-hosted script tag and stylesheet link tag.', 'wp-security' ),
				evidence:       ( new Evidence() )->add( 'missing_sri', $missingSri ),
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			__( 'All externally-hosted scripts and stylesheets carry a Subresource Integrity hash.', 'wp-security' )
		);
	}
}
