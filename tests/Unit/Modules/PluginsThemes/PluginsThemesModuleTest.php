<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\PluginsThemes\PluginsThemesModule;

class PluginsThemesModuleTest extends TestCase {

	private PluginsThemesModule $module;

	protected function setUp(): void {
		$this->module = new PluginsThemesModule();
	}

	public function test_id_is_plugins_themes(): void {
		$this->assertSame( 'plugins_themes', $this->module->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );

		$this->assertContains( 'plugins_themes.plugin_updates', $ids );
		$this->assertContains( 'plugins_themes.inactive_plugins', $ids );
		$this->assertContains( 'plugins_themes.vulnerability_advisory', $ids );
	}

	public function test_check_ids_are_unique(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );
		$this->assertSame( $ids, array_unique( $ids ) );
	}
}
