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
 * Confirms the site's search function responds successfully. Uses the
 * settings `search_url` override when configured, otherwise the sane
 * WordPress-core default of {home}/?s=test — no theme-specific query var
 * assumptions, works on any install.
 */
class SearchFunctionCheck implements Check {

	public function id(): string {
		return 'functional_qa.search_function';
	}

	public function label(): string {
		return __( 'Search Function', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $status */
		$status = $context->get( 'search_check_status' );

		if ( null === $status ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not reach the search results page to check its response status.'
			);
		}

		if ( $status >= 200 && $status < 400 ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Search results page responded successfully (HTTP %d).', 'wp-security' ),
					$status
				)
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::WARN,
			severity:       Severity::MEDIUM,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: HTTP status code */
				__( 'Search results page returned HTTP %d.', 'wp-security' ),
				$status
			),
			recommendation: __( 'Verify the site search still works. If a custom search page is used, set the "search_url" setting to the correct URL.', 'wp-security' ),
			evidence:       ( new Evidence() )->add( 'http_status', $status ),
		);
	}
}
