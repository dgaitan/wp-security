<?php
/*
 * Feature: Plugin Updates Check — S6-1
 *
 * Scenario: plugin_update_slugs is null → SKIPPED
 *   Given a MockContext where get('plugin_update_slugs') returns null
 *   When PluginUpdatesCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: no plugins need updating → PASS
 *   Given a MockContext with plugin_update_slugs = []
 *   When PluginUpdatesCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: one plugin needs an update → WARN/HIGH
 *   Given a MockContext with plugin_update_slugs = ['plugin-a/plugin-a.php']
 *   When PluginUpdatesCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "high"
 *   And evidence contains 'plugins_needing_update' = ['plugin-a/plugin-a.php']
 *
 * Scenario: multiple plugins need updates → WARN/HIGH with plural message
 *   Given a MockContext with plugin_update_slugs = ['plugin-a/plugin-a.php', 'plugin-b/plugin-b.php']
 *   When PluginUpdatesCheck::run() is called
 *   Then the Finding status is "warn"
 *   And evidence contains both slugs
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\PluginsThemes\Checks\PluginUpdatesCheck;
use WPSecurity\Tests\Support\MockContext;

class PluginUpdatesCheckTest extends TestCase {

	private PluginUpdatesCheck $check;

	protected function setUp(): void {
		$this->check = new PluginUpdatesCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.plugin_updates', $this->check->id() );
	}

	public function test_null_slugs_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'plugin_update_slugs' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_slugs_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'plugin_update_slugs' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_one_plugin_needs_update_returns_warn_high(): void {
		$ctx     = new MockContext( values: [ 'plugin_update_slugs' => [ 'plugin-a/plugin-a.php' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( [ 'plugin-a/plugin-a.php' ], $finding->evidence['plugins_needing_update'] );
	}

	public function test_multiple_plugins_need_updates_returns_warn_high(): void {
		$slugs   = [ 'plugin-a/plugin-a.php', 'plugin-b/plugin-b.php' ];
		$ctx     = new MockContext( values: [ 'plugin_update_slugs' => $slugs ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertCount( 2, $finding->evidence['plugins_needing_update'] );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext( values: [ 'plugin_update_slugs' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
