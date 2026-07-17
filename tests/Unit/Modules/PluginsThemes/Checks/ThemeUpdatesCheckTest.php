<?php
/*
 * Feature: Theme Updates Check — Sprint 9
 *
 * Scenario: theme_update_slugs is null → SKIPPED
 *   Given a MockContext where get('theme_update_slugs') returns null
 *   When ThemeUpdatesCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: no themes need updating → PASS
 *   Given a MockContext with theme_update_slugs = []
 *   When ThemeUpdatesCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: one theme needs an update → WARN/HIGH
 *   Given a MockContext with theme_update_slugs = ['twentytwentyfour']
 *   When ThemeUpdatesCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "high"
 *   And evidence contains 'themes_needing_update' = ['twentytwentyfour']
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\PluginsThemes\Checks\ThemeUpdatesCheck;
use WPSecurity\Tests\Support\MockContext;

class ThemeUpdatesCheckTest extends TestCase {

	private ThemeUpdatesCheck $check;

	protected function setUp(): void {
		$this->check = new ThemeUpdatesCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.theme_updates', $this->check->id() );
	}

	public function test_null_slugs_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'theme_update_slugs' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_slugs_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'theme_update_slugs' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_one_theme_needs_update_returns_warn_high(): void {
		$ctx     = new MockContext( values: [ 'theme_update_slugs' => [ 'twentytwentyfour' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( [ 'twentytwentyfour' ], $finding->evidence->get( 'themes_needing_update' ) );
	}

	public function test_multiple_themes_need_updates_returns_warn_high(): void {
		$slugs   = [ 'twentytwentyfour', 'twentytwentythree' ];
		$ctx     = new MockContext( values: [ 'theme_update_slugs' => $slugs ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertCount( 2, $finding->evidence->get( 'themes_needing_update' ) );
	}

	public function test_check_id_matches_finding(): void {
		$ctx     = new MockContext( values: [ 'theme_update_slugs' => [] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( $this->check->id(), $finding->checkId );
	}
}
