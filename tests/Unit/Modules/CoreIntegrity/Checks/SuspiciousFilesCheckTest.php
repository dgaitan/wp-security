<?php

/**
 * Feature: SuspiciousFilesCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: Clean wp-content with no suspicious files returns PASS
 *     Given a wp-content directory with only safe theme files and no PHP in uploads
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: PHP file in uploads directory returns FAIL with CRITICAL severity
 *     Given a wp-content/uploads/ directory containing a .php file
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "critical"
 *     And the Finding evidence contains 'php_in_uploads'
 *
 *   Scenario: Known blacklisted filename returns FAIL with CRITICAL severity
 *     Given a wp-content/plugins/ directory containing 'c99.php'
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "critical"
 *     And the Finding evidence contains 'blacklisted'
 *
 *   Scenario: Blacklisted extension (.phtml) returns FAIL with CRITICAL severity
 *     Given a wp-content/plugins/ directory containing 'page.phtml'
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "critical"
 *     And the Finding evidence contains 'blacklisted'
 *
 *   Scenario: Double-extension file (image.php.jpg) returns FAIL with HIGH severity
 *     Given a wp-content/uploads/ directory containing 'image.php.jpg'
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "high"
 *     And the Finding evidence contains 'double_extension'
 *
 *   Scenario: Unexpected extension in theme directory returns FAIL with MEDIUM severity
 *     Given a wp-content/themes/my-theme/ directory containing 'binary.exe'
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "medium"
 *     And the Finding evidence contains 'theme_violations'
 *
 *   Scenario: Expected extension in theme directory is not flagged
 *     Given a wp-content/themes/my-theme/ directory containing only 'style.css' and 'index.php'
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: wp-content directory not accessible returns SKIPPED
 *     Given a Context whose contentPath() points to a non-existent directory
 *     When SuspiciousFilesCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\SuspiciousFilesCheck;
use WPSecurity\Tests\Support\MockContext;

final class SuspiciousFilesCheckTest extends TestCase {

	private SuspiciousFilesCheck $check;
	private string $tmpRoot;

	protected function setUp(): void {
		parent::setUp();
		$this->check   = new SuspiciousFilesCheck();
		$this->tmpRoot = sys_get_temp_dir() . '/wpsec-suspicious-' . uniqid() . '/';
		// Create the base wp-content structure.
		$this->createDir( 'uploads' );
		$this->createDir( 'plugins' );
		$this->createDir( 'themes/my-theme' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->removeDir( $this->tmpRoot );
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.suspicious_files', $this->check->id() );
	}

	public function test_clean_directory_returns_pass(): void {
		$this->createFile( 'themes/my-theme/style.css', '/* theme */' );
		$this->createFile( 'themes/my-theme/index.php', '<?php // entry' );
		$this->createFile( 'uploads/photo.jpg', 'JPEG' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_php_in_uploads_returns_fail_critical(): void {
		$this->createFile( 'uploads/shell.php', '<?php system($_GET["cmd"]); ?>' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
		$this->assertTrue( $finding->evidence->has( 'php_in_uploads' ) );
		$this->assertStringContainsString( 'shell.php', implode( ' ', $finding->evidence->get( 'php_in_uploads' ) ) );
	}

	public function test_blacklisted_filename_returns_fail_critical(): void {
		$this->createFile( 'plugins/c99.php', '<?php // known shell' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
		$this->assertTrue( $finding->evidence->has( 'blacklisted' ) );
	}

	public function test_blacklisted_extension_returns_fail_critical(): void {
		$this->createFile( 'plugins/page.phtml', '<? echo shell_exec($_GET["c"]); ?>' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
		$this->assertTrue( $finding->evidence->has( 'blacklisted' ) );
	}

	public function test_double_extension_returns_fail_high(): void {
		// A file named image.php.jpg uses a double extension to bypass naive MIME checks.
		$this->createFile( 'uploads/image.php.jpg', 'JPEG' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::HIGH, $finding->severity );
		$this->assertTrue( $finding->evidence->has( 'double_extension' ) );
	}

	public function test_unexpected_extension_in_theme_returns_fail_medium(): void {
		$this->createFile( 'themes/my-theme/binary.exe', "\x4D\x5A" );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertTrue( $finding->evidence->has( 'theme_violations' ) );
		$this->assertStringContainsString( 'binary.exe', implode( ' ', $finding->evidence->get( 'theme_violations' ) ) );
	}

	public function test_expected_theme_extensions_are_not_flagged(): void {
		$this->createFile( 'themes/my-theme/style.css', '' );
		$this->createFile( 'themes/my-theme/index.php', '' );
		$this->createFile( 'themes/my-theme/screenshot.png', '' );
		$this->createFile( 'themes/my-theme/fonts/icon.woff2', '' );

		$finding = $this->check->run( $this->makeContext() );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_wp_content_missing_returns_skipped(): void {
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

	private function createDir( string $relPath ): void {
		$path = $this->tmpRoot . 'wp-content/' . ltrim( $relPath, '/' );
		if ( ! is_dir( $path ) ) {
			mkdir( $path, 0777, true );
		}
	}

	private function createFile( string $relPath, string $content ): void {
		$path = $this->tmpRoot . 'wp-content/' . ltrim( $relPath, '/' );
		$dir  = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		file_put_contents( $path, $content );
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
