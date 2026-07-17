<?php

declare( strict_types=1 );

namespace WPSecurity\Contracts;

use WPSecurity\Domain\RemediationResult;

/**
 * A RemediationAction is the plugin's one mutating-action primitive.
 *
 * Unlike a Check, apply() is explicitly allowed to change site state — but
 * only when triggered through the confirm-gated REST flow, never during an
 * automatic/scheduled scan. Every implementation declares the real WP
 * capability it requires (not just 'manage_options'), re-validates itself
 * immediately before running, and reports back a RemediationResult rather
 * than throwing.
 */
interface RemediationAction {

	/**
	 * Stable, dot-namespaced identifier, e.g. "plugins_themes.update_plugin".
	 * Must be unique across the entire plugin.
	 */
	public function id(): string;

	/**
	 * Human-readable label shown in the remediation confirmation UI.
	 */
	public function label(): string;

	/**
	 * The WordPress capability required to trigger this action,
	 * e.g. 'update_plugins', 'update_themes', 'update_core'.
	 *
	 * Checked in addition to (never instead of) the REST layer's own
	 * 'manage_options' floor.
	 */
	public function capability(): string;

	/**
	 * Human-readable confirmation text shown to the admin before apply(),
	 * describing exactly what this invocation will do.
	 *
	 * @param array<string, mixed> $params
	 */
	public function describe( array $params ): string;

	/**
	 * Re-validate that this action still applies given the current
	 * environment and the supplied params — called immediately before
	 * apply(), so a stale UI state (e.g. an update already applied by
	 * someone else) is caught rather than silently re-run.
	 *
	 * @param array<string, mixed> $params
	 */
	public function isAvailable( Context $context, array $params ): bool;

	/**
	 * Perform the mutating action.
	 *
	 * Implementations must not throw for expected failure modes (missing
	 * filesystem credentials, target no longer present, etc.) — catch and
	 * return a RemediationResult::failure() instead. An unexpected \Throwable
	 * escaping apply() is still the caller's responsibility to contain.
	 *
	 * @param array<string, mixed> $params
	 */
	public function apply( Context $context, array $params ): RemediationResult;
}
