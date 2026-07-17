<?php

declare( strict_types=1 );

namespace WPSecurity\Admin;

use WPSecurity\Contracts\RemediationAction;

/**
 * Collects all registered remediation actions via the `wp_security/remediations`
 * filter — the mutating-action twin of ModuleRegistry.
 *
 * Remediation actions are never hard-coded here, and RemediationAction is
 * deliberately not part of the Module interface: adding a method to Module
 * would be a breaking change for every existing/third-party Module
 * implementation. Instead this mirrors ModuleRegistry's own filter-based
 * loading exactly, just against a parallel filter and a parallel contract.
 *
 * Usage:
 *   add_filter( 'wp_security/remediations', function ( array $actions ): array {
 *       $actions[] = new \MyPlugin\MyRemediation();
 *       return $actions;
 *   } );
 */
class RemediationRegistry {

	/** @var array<string, RemediationAction>|null Keyed by action ID; null until first load. */
	private ?array $actions = null;

	/**
	 * Return all registered remediation actions, keyed by their ID.
	 *
	 * @return array<string, RemediationAction>
	 */
	public function all(): array {
		if ( null === $this->actions ) {
			$this->actions = $this->load();
		}
		return $this->actions;
	}

	/**
	 * Retrieve a single remediation action by ID, or null if not found.
	 */
	public function get( string $id ): ?RemediationAction {
		return $this->all()[ $id ] ?? null;
	}

	/**
	 * Check whether a remediation action with the given ID is registered.
	 */
	public function has( string $id ): bool {
		return isset( $this->all()[ $id ] );
	}

	/**
	 * Invalidate the cached action list (useful in tests).
	 */
	public function flush(): void {
		$this->actions = null;
	}

	// -------------------------------------------------------------------------
	// Internals
	// -------------------------------------------------------------------------

	/**
	 * Run the filter and index the resulting actions by ID.
	 *
	 * @return array<string, RemediationAction>
	 */
	private function load(): array {
		/** @var array<RemediationAction> $raw */
		$raw = apply_filters( 'wp_security/remediations', [] );

		$indexed = [];
		foreach ( $raw as $action ) {
			$indexed[ $action->id() ] = $action;
		}
		return $indexed;
	}
}
