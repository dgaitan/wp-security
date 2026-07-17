<?php

/**
 * Feature: SriCheck — Security Headers module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All external assets carry an integrity attribute returns PASS
 *     Given a mocked Context with page_asset_tags where every external:true entry has a non-empty integrity
 *     When SriCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: No external assets returns PASS
 *     Given a mocked Context with page_asset_tags where every entry is external:false
 *     When SriCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: External asset missing integrity returns WARN with HIGH severity
 *     Given a mocked Context with an external:true entry that has integrity:null
 *     When SriCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "high"
 *     And the evidence lists the asset URL under missing_sri
 *
 *   Scenario: page_asset_tags null returns SKIPPED
 *     Given a mocked Context where get('page_asset_tags') returns null
 *     When SriCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Headers\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Headers\Checks\SriCheck;
use WPSecurity\Tests\Support\MockContext;

final class SriCheckTest extends TestCase {

	private SriCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new SriCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'headers.subresource_integrity', $this->check->id() );
	}

	public function test_all_external_assets_have_integrity_returns_pass(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => 'https://cdn.example.com/lib.js',
						'integrity'   => 'sha384-abc123',
						'crossorigin' => 'anonymous',
						'external'    => true,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_no_external_assets_returns_pass(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => '/wp-includes/js/jquery/jquery.min.js',
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

	public function test_external_asset_missing_integrity_returns_warn_high(): void {
		$context = new MockContext(
			values: [
				'page_asset_tags' => [
					[
						'type'        => 'script',
						'url'         => 'https://cdn.example.com/unprotected.js',
						'integrity'   => null,
						'crossorigin' => null,
						'external'    => true,
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertContains( 'https://cdn.example.com/unprotected.js', $finding->evidence->get( 'missing_sri' ) );
	}

	public function test_null_page_asset_tags_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}
}
