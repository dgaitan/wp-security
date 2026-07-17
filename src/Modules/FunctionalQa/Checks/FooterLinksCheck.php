<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Confirms the homepage renders a footer block with links (privacy policy,
 * terms, social, etc.) — parsed from <footer>...</footer> in rendered HTML,
 * same theme-agnostic approach as PrimaryNavigationCheck.
 */
class FooterLinksCheck implements Check {

	public function id(): string {
		return 'functional_qa.footer_links';
	}

	public function label(): string {
		return __( 'Footer Links', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, array{url: string, text: string}>|null $links */
		$links = $context->get( 'footer_links' );

		if ( null === $links ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch the homepage to check for footer links.'
			);
		}

		if ( [] === $links ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::LOW,
				title:          $this->label(),
				description:    __( 'No <footer> element with links was found on the homepage.', 'wp-security' ),
				recommendation: __( 'Confirm footer links (privacy policy, terms, social) are present. If the theme renders the footer without a <footer> tag, this may be a detection limitation — verify manually.', 'wp-security' ),
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::PASS,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of footer links found */
				__( 'Footer links found: %d.', 'wp-security' ),
				count( $links )
			),
			recommendation: '',
			evidence:       ( new Evidence() )->add( 'footer_links', $links ),
		);
	}
}
