<?php
/*
 * Feature: Inactive Plugins Check — S6-2
 *
 * Scenario: plugins data null → SKIPPED
 *   Given a MockContext where get('plugins') returns null
 *   When InactivePluginsCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: active_plugins null → SKIPPED
 *   Given a MockContext where get('active_plugins') returns null
 *   When InactivePluginsCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: all plugins active → PASS
 *   Given all plugins appear in active_plugins
 *   When InactivePluginsCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: one inactive plugin → WARN/MEDIUM with evidence
 *   Given one plugin is not in active_plugins
 *   When InactivePluginsCheck::run() is called
 *   Then the Finding status is "warn" and severity is "medium"
 *   And evidence contains the inactive slug
 *
 * Scenario: multiple inactive plugins → WARN/MEDIUM
 *   Given two plugins are not in active_plugins
 *   When InactivePluginsCheck::run() is called
 *   Then the Finding status is "warn" and evidence contains both slugs
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\PluginsThemes\Checks\InactivePluginsCheck;
use WPSecurity\Tests\Support\MockContext;

class InactivePluginsCheckTest extends TestCase {

	private InactivePluginsCheck $check;

	protected function setUp(): void {
		$this->check = new InactivePluginsCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.inactive_plugins', $this->check->id() );
	}

	public function test_null_plugins_returns_skipped(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => null,
				'active_plugins' => [],
			]
		);

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_null_active_plugins_returns_skipped(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => [ 'plugin-a/plugin-a.php' => [ 'Name' => 'Plugin A' ] ],
				'active_plugins' => null,
			]
		);

		$this->assertSame( Status::SKIPPED, $this->check->run( $ctx )->status );
	}

	public function test_all_plugins_active_returns_pass(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => [
					'plugin-a/plugin-a.php' => [ 'Name' => 'Plugin A' ],
					'plugin-b/plugin-b.php' => [ 'Name' => 'Plugin B' ],
				],
				'active_plugins' => [ 'plugin-a/plugin-a.php', 'plugin-b/plugin-b.php' ],
			]
		);

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}

	public function test_one_inactive_plugin_returns_warn_medium(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => [
					'plugin-a/plugin-a.php' => [ 'Name' => 'Plugin A' ],
					'plugin-b/plugin-b.php' => [ 'Name' => 'Plugin B' ],
				],
				'active_plugins' => [ 'plugin-a/plugin-a.php' ],
			]
		);

		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertContains( 'plugin-b/plugin-b.php', $finding->evidence['inactive_plugins'] );
	}

	public function test_multiple_inactive_plugins_returns_warn_with_all_slugs(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => [
					'plugin-a/plugin-a.php' => [ 'Name' => 'Plugin A' ],
					'plugin-b/plugin-b.php' => [ 'Name' => 'Plugin B' ],
					'plugin-c/plugin-c.php' => [ 'Name' => 'Plugin C' ],
				],
				'active_plugins' => [ 'plugin-a/plugin-a.php' ],
			]
		);

		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertCount( 2, $finding->evidence['inactive_plugins'] );
	}

	public function test_no_plugins_installed_returns_pass(): void {
		$ctx = new MockContext(
			values: [
				'plugins'        => [],
				'active_plugins' => [],
			]
		);

		$this->assertSame( Status::PASS, $this->check->run( $ctx )->status );
	}
}
