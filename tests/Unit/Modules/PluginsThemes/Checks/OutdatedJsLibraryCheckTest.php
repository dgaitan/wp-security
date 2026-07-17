<?php

/**
 * Feature: OutdatedJsLibraryCheck — Plugins & Themes module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: No recognized or outdated libraries returns PASS
 *     Given a mocked Context with page_asset_tags containing only unrecognized script URLs
 *     When OutdatedJsLibraryCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: An outdated jQuery version returns WARN with HIGH severity
 *     Given a mocked Context with a page_asset_tags script URL matching jquery-1.8.3.min.js
 *     When OutdatedJsLibraryCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "high"
 *     And the evidence names jQuery and its CVE reference
 *
 *   Scenario: A recognized library with no parseable version does not fail
 *     Given a mocked Context with a page_asset_tags script URL matching "jquery" but no version number
 *     When OutdatedJsLibraryCheck::run() is called
 *     Then the Finding status is "pass"
 *     And the evidence lists the library under version_unknown
 *
 *   Scenario: An outdated jQuery UI URL is attributed to jQuery UI, not jQuery
 *     Given a mocked Context with a page_asset_tags script URL matching jquery-ui-1.11.4.min.js
 *     When OutdatedJsLibraryCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the evidence names "jQuery UI" (not "jQuery") as the outdated library
 *
 *   Scenario: page_asset_tags null returns SKIPPED
 *     Given a mocked Context where get('page_asset_tags') returns null
 *     When OutdatedJsLibraryCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\PluginsThemes\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\PluginsThemes\Checks\OutdatedJsLibraryCheck;
use WPSecurity\Tests\Support\MockContext;

final class OutdatedJsLibraryCheckTest extends TestCase {

	private OutdatedJsLibraryCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new OutdatedJsLibraryCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'plugins_themes.outdated_js_libraries', $this->check->id() );
	}

	public function test_no_recognized_or_outdated_libraries_returns_pass(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => '/wp-content/themes/mytheme/assets/app.min.js',
						'integrity'   => null,
						'crossorigin' => null,
						'external'    => false,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_outdated_jquery_returns_warn_high(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => 'https://example.test/wp-includes/js/jquery/jquery-1.8.3.min.js',
						'integrity'   => null,
						'crossorigin' => null,
						'external'    => false,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertSame( 'jQuery', $finding->evidence->get( 'outdated' )[0]['library'] );
		$this->assertSame( 'CVE-2020-11022', $finding->evidence->get( 'outdated' )[0]['reference'] );
	}

	public function test_outdated_jquery_ui_is_attributed_to_jquery_ui_not_jquery(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => 'https://example.test/wp-includes/js/jquery/ui/jquery-ui-1.11.4.min.js',
						'integrity'   => null,
						'crossorigin' => null,
						'external'    => false,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( 'jQuery UI', $finding->evidence->get( 'outdated' )[0]['library'] );
	}

	public function test_unparseable_version_does_not_fail(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => 'https://cdn.example.com/jquery.custom-build.js',
						'integrity'   => null,
						'crossorigin' => null,
						'external'    => true,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertTrue( $finding->evidence->has( 'version_unknown' ) );
	}

	public function test_null_page_asset_tags_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
