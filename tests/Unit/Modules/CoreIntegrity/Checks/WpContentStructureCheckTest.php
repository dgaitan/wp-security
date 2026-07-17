<?php

/**
 * Feature: WpContentStructureCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All standard directories exist returns PASS
 *     Given a wp-content directory containing plugins/, themes/, and uploads/
 *     When WpContentStructureCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Missing plugins directory returns WARN with MEDIUM severity
 *     Given a wp-content directory missing the plugins/ subdirectory
 *     When WpContentStructureCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *     And the Finding evidence contains 'missing_directories' with 'plugins'
 *
 *   Scenario: Missing themes directory returns WARN with MEDIUM severity
 *     Given a wp-content directory missing the themes/ subdirectory
 *     When WpContentStructureCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding evidence contains 'missing_directories' with 'themes'
 *
 *   Scenario: Missing uploads directory returns WARN with MEDIUM severity
 *     Given a wp-content directory missing the uploads/ subdirectory
 *     When WpContentStructureCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding evidence contains 'missing_directories' with 'uploads'
 *
 *   Scenario: wp-content itself missing returns SKIPPED
 *     Given a Context whose contentPath() points to a non-existent directory
 *     When WpContentStructureCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\WpContentStructureCheck;
use WPSecurity\Tests\Support\MockContext;

final class WpContentStructureCheckTest extends TestCase {

	private WpContentStructureCheck $check;
	private string $tmpRoot;

	protected function setUp(): void {
		parent::setUp();
		$this->check   = new WpContentStructureCheck();
		$this->tmpRoot = sys_get_temp_dir() . '/wpsec-structure-' . uniqid() . '/';
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->removeDir( $this->tmpRoot );
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.wp_content_structure', $this->check->id() );
	}

	public function test_all_standard_dirs_exist_returns_pass(): void {
		$this->createDir( 'plugins' );
		$this->createDir( 'themes' );
		$this->createDir( 'uploads' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_missing_plugins_returns_warn(): void {
		$this->createDir( 'themes' );
		$this->createDir( 'uploads' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_missing_themes_returns_warn(): void {
		$this->createDir( 'plugins' );
		$this->createDir( 'uploads' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_missing_uploads_returns_warn(): void {
		$this->createDir( 'plugins' );
		$this->createDir( 'themes' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_evidence_lists_missing_directories(): void {
		// Only themes/ is present.
		$this->createDir( 'themes' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertTrue( $finding->evidence->has( 'missing_directories' ) );
		$missing = $finding->evidence->get( 'missing_directories' );
		$this->assertContains( 'plugins', $missing );
		$this->assertContains( 'uploads', $missing );
		$this->assertNotContains( 'themes', $missing );
	}

	public function test_missing_wp_content_itself_returns_skipped(): void {
		$context = new MockContext( wpRootPath: '/nonexistent-' . uniqid() . '/' );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeContext(): MockContext {
		return new MockContext( wpRootPath: $this->tmpRoot );
	}

	private function createDir( string $name ): void {
		$path = $this->tmpRoot . 'wp-content/' . $name;
		if ( ! is_dir( $path ) ) {
			mkdir( $path, 0777, true );
		}
	}

	private function removeDir( string $path ): void {
		if ( ! is_dir( $path ) ) {
			return;
		}
		$items = scandir( $path );
		if ( false === $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$full = $path . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $full ) ) {
				$this->removeDir( $full );
			} else {
				unlink( $full );
			}
		}
		rmdir( $path );
	}
}
