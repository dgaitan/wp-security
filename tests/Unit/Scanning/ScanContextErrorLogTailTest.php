<?php
/*
 * Feature: ScanContext php_error_log_tail — Sprint 9 bounded log read
 *
 * Scenario: no readable error log configured → null
 *   Given ini's error_log points at a path that doesn't exist
 *   When get('php_error_log_tail') is called
 *   Then null is returned
 *
 * Scenario: a small log returns all of its lines
 *   Given a temp file with 5 lines
 *   When get('php_error_log_tail') is called
 *   Then all 5 lines are returned, in order
 *
 * Scenario: a log far larger than the line cap is still bounded
 *   Given a temp file with 500 lines
 *   When get('php_error_log_tail') is called
 *   Then at most 100 lines are returned
 *   And the returned lines are the most recent ones (tail), not the earliest
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Scanning;

use PHPUnit\Framework\TestCase;
use WPSecurity\Scanning\ScanContext;

final class ScanContextErrorLogTailTest extends TestCase {

	private string $originalErrorLog;

	/** @var array<int, string> */
	private array $tempFiles = [];

	protected function setUp(): void {
		parent::setUp();
		$this->originalErrorLog = (string) ini_get( 'error_log' );
	}

	protected function tearDown(): void {
		ini_set( 'error_log', $this->originalErrorLog ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- test-only, restores the original ini value it temporarily overrides.
		foreach ( $this->tempFiles as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
		parent::tearDown();
	}

	public function test_missing_log_returns_null(): void {
		ini_set( 'error_log', sys_get_temp_dir() . '/wp-security-does-not-exist-' . uniqid() . '.log' ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- test-only, points error_log at a controlled path to isolate the resolver.

		$this->assertNull( ( new ScanContext() )->get( 'php_error_log_tail' ) );
	}

	public function test_small_log_returns_all_lines_in_order(): void {
		$lines = [ 'line one', 'line two', 'line three', 'line four', 'line five' ];
		ini_set( 'error_log', $this->writeTempLog( $lines ) ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- test-only, points error_log at a controlled temp file to isolate the resolver.

		$tail = ( new ScanContext() )->get( 'php_error_log_tail' );

		$this->assertSame( $lines, $tail );
	}

	public function test_large_log_is_bounded_to_the_most_recent_lines(): void {
		$lines = array_map( static fn ( int $i ): string => "log line {$i}", range( 1, 500 ) );
		ini_set( 'error_log', $this->writeTempLog( $lines ) ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- test-only, points error_log at a controlled temp file to isolate the resolver.

		$tail = ( new ScanContext() )->get( 'php_error_log_tail' );

		$this->assertIsArray( $tail );
		$this->assertLessThanOrEqual( 100, count( $tail ) );
		// The tail must be the END of the file, not the beginning.
		$this->assertSame( 'log line 500', $tail[ count( $tail ) - 1 ] );
		$this->assertNotContains( 'log line 1', $tail );
	}

	/**
	 * @param array<int, string> $lines
	 */
	private function writeTempLog( array $lines ): string {
		$path              = sys_get_temp_dir() . '/wp-security-error-log-test-' . uniqid() . '.log';
		$this->tempFiles[] = $path;
		file_put_contents( $path, implode( "\n", $lines ) );
		return $path;
	}
}
