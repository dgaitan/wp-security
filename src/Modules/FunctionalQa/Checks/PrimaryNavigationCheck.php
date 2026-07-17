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
 * Confirms the homepage renders a primary navigation block with links.
 *
 * Detection is theme-agnostic by design: it parses <nav>...</nav> out of the
 * rendered HTML rather than depending on wp_get_nav_menu_items() and a
 * theme-specific registered menu location, so it works identically across
 * any theme that emits semantic HTML5 markup. Individual link reachability
 * is covered separately by BrokenInternalLinkCheck, which already crawls
 * every link on the page (including nav) — this check only confirms
 * navigation is structurally present.
 */
class PrimaryNavigationCheck implements Check {

	public function id(): string {
		return 'functional_qa.primary_navigation';
	}

	public function label(): string {
		return __( 'Primary Navigation', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var array<int, array{url: string, text: string}>|null $links */
		$links = $context->get( 'nav_links' );

		if ( null === $links ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch the homepage to check for navigation links.'
			);
		}

		if ( [] === $links ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::LOW,
				title:          $this->label(),
				description:    __( 'No <nav> element with links was found on the homepage.', 'wp-security' ),
				recommendation: __( 'Confirm the site has a working primary navigation menu. If the theme renders navigation without a <nav> tag, this may be a detection limitation rather than a real issue — verify manually.', 'wp-security' ),
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::PASS,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of navigation links found */
				__( 'Primary navigation found with %d link(s).', 'wp-security' ),
				count( $links )
			),
			recommendation: '',
			evidence:       ( new Evidence() )->add( 'nav_links', $links ),
		);
	}
}
