<?php

/**
 * Feature: CoreFilesCheck — WordPress Health module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: All core files match checksums returns PASS
 *     Given a mocked Context with core_checksums containing a file whose md5 matches its content
 *     When CoreFilesCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: Modified core file returns FAIL with CRITICAL severity
 *     Given a mocked Context with core_checksums containing a file whose md5 does NOT match
 *     When CoreFilesCheck::run() is called
 *     Then the Finding status is "fail"
 *     And the Finding severity is "critical"
 *     And the Finding evidence contains 'modified_files'
 *
 *   Scenario: Core checksums null returns SKIPPED
 *     Given a mocked Context where get('core_checksums') returns null
 *     When CoreFilesCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Missing core file is not flagged (only modified existing files are reported)
 *     Given a mocked Context with core_checksums referencing a file that does not exist
 *     When CoreFilesCheck::run() is called
 *     Then the Finding status is "pass"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\CoreIntegrity\Checks\CoreFilesCheck;
use WPSecurity\Tests\Support\MockContext;

final class CoreFilesCheckTest extends TestCase {

	private CoreFilesCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new CoreFilesCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.core_files', $this->check->id() );
	}

	public function test_checksums_null_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_all_files_match_returns_pass(): void {
		$tmpFile = sys_get_temp_dir() . '/wpsec-core-pass-' . uniqid() . '.php';
		$content = '<?php // original core file';
		file_put_contents( $tmpFile, $content );

		$filename = basename( $tmpFile );
		$context  = new MockContext(
			wpRootPath: sys_get_temp_dir() . '/',
			values: [
				'core_checksums' => [ $filename => md5( $content ) ],
			]
		);

		$finding = $this->check->run( $context );

		unlink( $tmpFile );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_modified_core_file_returns_fail_critical(): void {
		$tmpFile = sys_get_temp_dir() . '/wpsec-core-modified-' . uniqid() . '.php';
		file_put_contents( $tmpFile, '<?php // modified by attacker' );

		$filename  = basename( $tmpFile );
		$wrongHash = md5( '<?php // original' );
		$context   = new MockContext(
			wpRootPath: sys_get_temp_dir() . '/',
			values: [
				'core_checksums' => [ $filename => $wrongHash ],
			]
		);

		$finding = $this->check->run( $context );

		unlink( $tmpFile );

		$this->assertSame( Status::FAIL, $finding->status );
		$this->assertSame( Severity::CRITICAL, $finding->severity );
		$this->assertSame( 'core_integrity.core_files', $finding->checkId );
	}

	public function test_modified_file_recorded_in_evidence(): void {
		$tmpFile = sys_get_temp_dir() . '/wpsec-core-evidence-' . uniqid() . '.php';
		file_put_contents( $tmpFile, '<?php // tampered' );

		$filename = basename( $tmpFile );
		$context  = new MockContext(
			wpRootPath: sys_get_temp_dir() . '/',
			values: [
				'core_checksums' => [ $filename => md5( '<?php // original' ) ],
			]
		);

		$finding = $this->check->run( $context );

		unlink( $tmpFile );

		$this->assertArrayHasKey( 'modified_files', $finding->evidence );
		$this->assertContains( $filename, $finding->evidence['modified_files'] );
	}

	public function test_missing_core_file_is_not_flagged(): void {
		$context = new MockContext(
			wpRootPath: '/nonexistent-wp-root-' . uniqid() . '/',
			values: [
				'core_checksums' => [ 'wp-login.php' => 'abc123' ],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}
}
