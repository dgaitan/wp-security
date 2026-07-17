<?php
/*
 * Feature: ThemeUpdateRemediation — Sprint 9
 *
 * Scenario: isAvailable() is true only when the theme is in theme_update_slugs
 *
 * Note: apply() exercises WordPress core's real Theme_Upgrader, covered by
 * the manual REST smoke test, not by PHPUnit here — see PluginUpdateRemediationTest.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Remediations;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\PluginsThemes\Remediations\ThemeUpdateRemediation;
use WPSecurity\Tests\Support\MockContext;

final class ThemeUpdateRemediationTest extends TestCase {

	private ThemeUpdateRemediation $action;

	protected function setUp(): void {
		$this->action = new ThemeUpdateRemediation();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.update_theme', $this->action->id() );
	}

	public function test_capability_is_update_themes(): void {
		$this->assertSame( 'update_themes', $this->action->capability() );
	}

	public function test_describe_mentions_the_theme(): void {
		$this->assertStringContainsString(
			'twentytwentyfour',
			$this->action->describe( [ 'theme' => 'twentytwentyfour' ] )
		);
	}

	public function test_is_available_true_when_theme_needs_update(): void {
		$ctx = new MockContext( values: [ 'theme_update_slugs' => [ 'twentytwentyfour' ] ] );

		$this->assertTrue( $this->action->isAvailable( $ctx, [ 'theme' => 'twentytwentyfour' ] ) );
	}

	public function test_is_available_false_for_a_different_theme(): void {
		$ctx = new MockContext( values: [ 'theme_update_slugs' => [ 'twentytwentyfour' ] ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [ 'theme' => 'twentytwentythree' ] ) );
	}

	public function test_is_available_false_without_a_theme_param(): void {
		$ctx = new MockContext( values: [ 'theme_update_slugs' => [ 'twentytwentyfour' ] ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [] ) );
	}
}
