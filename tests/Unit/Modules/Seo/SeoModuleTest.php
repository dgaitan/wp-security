<?php
/*
 * Feature: SeoModule — S7-4/S7-5 (SEO module container)
 *
 * Scenario: Module ID is 'seo'
 *   Given a SeoModule
 *   When id() is called
 *   Then it returns "seo"
 *
 * Scenario: Built-in checks are registered
 *   Given a SeoModule
 *   When checks() is called
 *   Then it contains all five built-in check IDs
 *
 * Scenario: wp_security/checks/seo filter is applied
 *   Given a filter that appends a custom check
 *   When checks() is called
 *   Then the custom check is included in the result
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Seo;

use PHPUnit\Framework\TestCase;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Module;
use WPSecurity\Modules\Seo\SeoModule;

final class SeoModuleTest extends TestCase {

	private SeoModule $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = new SeoModule();
		remove_all_filters( 'wp_security/checks/seo' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_security/checks/seo' );
	}

	public function test_implements_module_contract(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	public function test_id_is_seo(): void {
		$this->assertSame( 'seo', $this->module->id() );
	}

	public function test_label_is_seo(): void {
		$this->assertSame( 'SEO', $this->module->label() );
	}

	public function test_icon_is_non_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_checks_returns_iterable(): void {
		$this->assertIsIterable( $this->module->checks() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'seo.page_title', $ids );
		$this->assertContains( 'seo.meta_description', $ids );
		$this->assertContains( 'seo.canonical', $ids );
		$this->assertContains( 'seo.robots_txt', $ids );
		$this->assertContains( 'seo.sitemap', $ids );
	}

	public function test_built_in_checks_implement_check_contract(): void {
		foreach ( $this->module->checks() as $check ) {
			$this->assertInstanceOf( Check::class, $check );
		}
	}

	public function test_filter_can_add_extra_check(): void {
		$extra = $this->createStub( Check::class );
		$extra->method( 'id' )->willReturn( 'seo.custom' );

		add_filter(
			'wp_security/checks/seo',
			static function ( array $checks ) use ( $extra ): array {
				$checks[] = $extra;
				return $checks;
			}
		);

		$ids = [];
		foreach ( $this->module->checks() as $check ) {
			$ids[] = $check->id();
		}

		$this->assertContains( 'seo.custom', $ids );
	}
}
