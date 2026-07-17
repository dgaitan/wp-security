<?php
/*
 * Feature: PluginUpdateRemediation — Sprint 9
 *
 * Scenario: isAvailable() is true only when the plugin is in plugin_update_slugs
 *   Given a MockContext with plugin_update_slugs = ['plugin-a/plugin-a.php']
 *   When isAvailable() is called with params = { plugin: 'plugin-a/plugin-a.php' }
 *   Then it returns true; for any other plugin it returns false
 *
 * Note: apply() exercises WordPress core's real Plugin_Upgrader, which requires
 * a live WordPress filesystem/upgrader stack this unit-test environment
 * deliberately doesn't provide (see tests/stubs/). That path is covered by
 * the manual REST smoke test called out in the sprint's verification section,
 * not by PHPUnit here.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Remediations;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\PluginsThemes\Remediations\PluginUpdateRemediation;
use WPSecurity\Tests\Support\MockContext;

final class PluginUpdateRemediationTest extends TestCase {

	private PluginUpdateRemediation $action;

	protected function setUp(): void {
		$this->action = new PluginUpdateRemediation();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.update_plugin', $this->action->id() );
	}

	public function test_capability_is_update_plugins(): void {
		$this->assertSame( 'update_plugins', $this->action->capability() );
	}

	public function test_describe_mentions_the_plugin(): void {
		$this->assertStringContainsString(
			'plugin-a/plugin-a.php',
			$this->action->describe( [ 'plugin' => 'plugin-a/plugin-a.php' ] )
		);
	}

	public function test_is_available_true_when_plugin_needs_update(): void {
		$ctx = new MockContext( values: [ 'plugin_update_slugs' => [ 'plugin-a/plugin-a.php' ] ] );

		$this->assertTrue( $this->action->isAvailable( $ctx, [ 'plugin' => 'plugin-a/plugin-a.php' ] ) );
	}

	public function test_is_available_false_for_a_different_plugin(): void {
		$ctx = new MockContext( values: [ 'plugin_update_slugs' => [ 'plugin-a/plugin-a.php' ] ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [ 'plugin' => 'plugin-b/plugin-b.php' ] ) );
	}

	public function test_is_available_false_without_a_plugin_param(): void {
		$ctx = new MockContext( values: [ 'plugin_update_slugs' => [ 'plugin-a/plugin-a.php' ] ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [] ) );
	}

	public function test_is_available_false_when_slugs_unknown(): void {
		$ctx = new MockContext( values: [ 'plugin_update_slugs' => null ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [ 'plugin' => 'plugin-a/plugin-a.php' ] ) );
	}
}
