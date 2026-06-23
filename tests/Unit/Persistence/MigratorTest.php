<?php
/**
 * Unit tests for Migrator.
 *
 * Feature: Migrator — Sprint 2 persistence
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Activation creates all three custom tables
 *     Given a fresh install with no stored schema version
 *     When Migrator::run() is called
 *     Then dbDelta() receives one CREATE TABLE statement per plugin table
 *     And the schema version option is stored
 *
 *   Scenario: Migration is skipped when the schema is current
 *     Given the stored schema version equals the current SCHEMA_VERSION
 *     When Migrator::run() is called
 *     Then dbDelta() is not called
 *
 *   Scenario: Table definitions cover every plugin table
 *     Given a Migrator
 *     When tableDefinitions() is called
 *     Then it returns CREATE TABLE statements for scan_runs, findings, and logins
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Persistence;

use PHPUnit\Framework\TestCase;
use WPSecurity\Persistence\Migrator;
use WPSecurity\Tests\Support\FakeWpdb;

final class MigratorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_dbdelta'] = [];
		$GLOBALS['wp_security_test_options'] = [];
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_security_test_dbdelta'], $GLOBALS['wp_security_test_options'] );
		parent::tearDown();
	}

	public function test_run_creates_all_three_tables_via_dbdelta(): void {
		$migrator = new Migrator( new FakeWpdb( 'wp_' ) );

		$migrator->run();

		$this->assertCount( 1, $GLOBALS['wp_security_test_dbdelta'] );

		$statements = $GLOBALS['wp_security_test_dbdelta'][0];
		$this->assertIsArray( $statements );
		$this->assertCount( 3, $statements );

		$joined = implode( "\n", $statements );
		$this->assertStringContainsString( 'wp_wpsec_scan_runs', $joined );
		$this->assertStringContainsString( 'wp_wpsec_findings', $joined );
		$this->assertStringContainsString( 'wp_wpsec_logins', $joined );
	}

	public function test_run_stores_the_schema_version(): void {
		$migrator = new Migrator( new FakeWpdb() );

		$migrator->run();

		$this->assertSame(
			Migrator::SCHEMA_VERSION,
			$GLOBALS['wp_security_test_options'][ Migrator::OPTION_VERSION ] ?? null
		);
	}

	public function test_run_is_a_noop_when_schema_is_current(): void {
		$GLOBALS['wp_security_test_options'][ Migrator::OPTION_VERSION ] = Migrator::SCHEMA_VERSION;

		( new Migrator( new FakeWpdb() ) )->run();

		$this->assertSame( [], $GLOBALS['wp_security_test_dbdelta'] );
	}

	public function test_table_definitions_cover_every_table(): void {
		$definitions = ( new Migrator( new FakeWpdb( 'wp_' ) ) )->tableDefinitions();

		$this->assertCount( 3, $definitions );
		foreach ( $definitions as $sql ) {
			$this->assertStringContainsString( 'CREATE TABLE', $sql );
			$this->assertStringContainsString( 'PRIMARY KEY', $sql );
		}
	}
}
