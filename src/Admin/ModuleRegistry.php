<?php

declare( strict_types=1 );

namespace WPSecurity\Admin;

use WPSecurity\Contracts\Module;

/**
 * Collects all registered modules via the `wp_security/modules` filter.
 *
 * Modules are never hard-coded here.  Built-in modules and third-party modules
 * are all registered through the same WordPress filter, so the loading
 * mechanism is identical for both.
 *
 * Usage:
 *   add_filter( 'wp_security/modules', function ( array $modules ): array {
 *       $modules[] = new \MyPlugin\MyModule();
 *       return $modules;
 *   } );
 *
 * TODO Sprint 1: wire built-in module registration here.
 */
class ModuleRegistry {

	/** @var array<string, Module>|null Keyed by module ID; null until first load. */
	private ?array $modules = null;

	/**
	 * Return all registered modules, keyed by their ID.
	 *
	 * @return array<string, Module>
	 */
	public function all(): array {
		if ( null === $this->modules ) {
			$this->modules = $this->load();
		}
		return $this->modules;
	}

	/**
	 * Retrieve a single module by ID, or null if not found.
	 */
	public function get( string $id ): ?Module {
		return $this->all()[ $id ] ?? null;
	}

	/**
	 * Check whether a module with the given ID is registered.
	 */
	public function has( string $id ): bool {
		return isset( $this->all()[ $id ] );
	}

	/**
	 * Invalidate the cached module list (useful in tests).
	 */
	public function flush(): void {
		$this->modules = null;
	}

	// -------------------------------------------------------------------------
	// Internals
	// -------------------------------------------------------------------------

	/**
	 * Run the filter and index the resulting modules by ID.
	 *
	 * @return array<string, Module>
	 */
	private function load(): array {
		/** @var array<Module> $raw */
		$raw = apply_filters( 'wp_security/modules', [] );

		$indexed = [];
		foreach ( $raw as $module ) {
			$indexed[ $module->id() ] = $module;
		}
		return $indexed;
	}
}
