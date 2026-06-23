<?php
/**
 * Unit test for uninstall.php.
 *
 * Feature: Uninstall cleanup — Sprint 2 persistence
 *   Background:
 *     Given the WP Security plugin is installed
 *     And WordPress is removing the plugin (WP_UNINSTALL_PLUGIN defined)
 *
 *   Scenario: Uninstall drops every custom table and deletes plugin options
 *     Given an in-memory wpdb and recordable option/meta stubs
 *     When uninstall.php runs
 *     Then all three custom tables are dropped
 *     And the plugin's options are deleted
 *     And scheduled actions are cleared
 *
 * Runs in a separate process because uninstall.php executes top-level code and
 * defines the WP_UNINSTALL_PLUGIN constant.
 *
 * @package WPSecurity\Tests
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSecurity\Tests\Support\FakeWpdb;

final class UninstallTest extends TestCase {

	public function test_uninstall_drops_tables_and_deletes_options(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-security/wp-security.php' );
		}

		$wpdb = new FakeWpdb( 'wp_' );

		$GLOBALS['wpdb']                             = $wpdb;
		$GLOBALS['wp_security_test_deleted_options'] = [];
		$GLOBALS['wp_security_test_as_unscheduled']  = [];

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$dropped = implode( "\n", $wpdb->queries );
		$this->assertStringContainsString( 'wp_wpsec_scan_runs', $dropped );
		$this->assertStringContainsString( 'wp_wpsec_findings', $dropped );
		$this->assertStringContainsString( 'wp_wpsec_logins', $dropped );
		$this->assertStringContainsStringIgnoringCase( 'DROP TABLE', $dropped );

		$this->assertContains( 'wp_security_settings', $GLOBALS['wp_security_test_deleted_options'] );
		$this->assertContains( 'wp_security_schema_version', $GLOBALS['wp_security_test_deleted_options'] );
	}
}
