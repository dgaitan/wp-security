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
 * Shared logic for checks that verify an admin-configured list of
 * {label,url} entries (CTAs, key landing pages) — neither is
 * auto-detectable from WP data the way nav/footer links are, so both are
 * SKIPPED (not WARN) when the corresponding setting is empty, per the
 * plan's acceptance criteria. PrimaryCtaCheck and KeyLandingPagesCheck only
 * differ in which Context key/labels they read, so the comparison and
 * Finding-building logic lives here once.
 */
abstract class AbstractUrlListCheck implements Check {

	abstract public function id(): string;

	abstract public function label(): string;

	/** The ScanContext key holding this list's checked statuses. */
	abstract protected function contextKey(): string;

	/** Shown when the setting is unconfigured (SKIPPED, not WARN). */
	abstract protected function emptyMessage(): string;

	public function run( Context $context ): Finding {
		/** @var array<int, array{label: string, url: string, status: int}> $items */
		$items = (array) $context->get( $this->contextKey() );

		if ( [] === $items ) {
			return Finding::skipped( $this->id(), $this->label(), $this->emptyMessage() );
		}

		$broken = array_values(
			array_filter(
				$items,
				static fn ( array $item ): bool => $item['status'] < 200 || $item['status'] >= 400
			)
		);

		if ( [] !== $broken ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    sprintf(
					/* translators: 1: number of broken URLs, 2: total configured URLs */
					__( '%1$d of %2$d configured URL(s) did not respond successfully.', 'wp-security' ),
					count( $broken ),
					count( $items )
				),
				recommendation: __( 'Fix or update the broken URLs below.', 'wp-security' ),
				evidence:       ( new Evidence() )->add( 'broken', $broken )->add( 'checked', $items ),
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::PASS,
			severity:       Severity::INFO,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of configured URLs */
				__( 'All %d configured URL(s) responded successfully.', 'wp-security' ),
				count( $items )
			),
			recommendation: '',
			evidence:       ( new Evidence() )->add( 'checked', $items ),
		);
	}
}
