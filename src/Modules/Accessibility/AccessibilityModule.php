<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Accessibility;

use WPSecurity\Contracts\Module;

/**
 * Accessibility module — Sprint 7.
 *
 * Server-side this module has no built-in checks. Findings are generated
 * browser-side by the axe-core runner in the React SPA and ingested via
 * POST /wp-security/v1/findings/external.
 *
 * Third-party code can still register server-side checks via the filter.
 */
class AccessibilityModule implements Module {

	public function id(): string {
		return 'accessibility';
	}

	public function label(): string {
		return __( 'Accessibility', 'wp-security' );
	}

	public function icon(): string {
		return 'dashicons-universal-access-alt';
	}

	/**
	 * @return iterable<\WPSecurity\Contracts\Check>
	 */
	public function checks(): iterable {
		/**
		 * Allow third-party code to add server-side accessibility checks.
		 *
		 * @param array<\WPSecurity\Contracts\Check> $checks
		 */
		return apply_filters( 'wp_security/checks/accessibility', [] );
	}
}
