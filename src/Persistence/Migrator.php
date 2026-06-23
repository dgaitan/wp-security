<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

/**
 * Creates and upgrades the plugin's custom database tables using dbDelta().
 *
 * Tables:
 *   {prefix}_wpsec_scan_runs   — one row per scan execution
 *   {prefix}_wpsec_findings    — findings linked to a run
 *   {prefix}_wpsec_logins      — last-login tracking per user
 *
 * The current schema version is stored in the `wp_security_schema_version`
 * option.  run() is a no-op when the stored version equals SCHEMA_VERSION.
 *
 * TODO Sprint 2: implement run() with the full CREATE TABLE statements.
 */
class Migrator {

	public const SCHEMA_VERSION = 1;

	public function run(): void {
		$current = (int) get_option( 'wp_security_schema_version', 0 );

		if ( $current >= self::SCHEMA_VERSION ) {
			return;
		}

		// TODO Sprint 2: call dbDelta() with the CREATE TABLE statements.

		update_option( 'wp_security_schema_version', self::SCHEMA_VERSION, false );
	}
}
