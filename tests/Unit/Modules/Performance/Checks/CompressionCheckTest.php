<?php
/*
 * Feature: CompressionCheck — S7-2 (HTTP Compression)
 *
 * Scenario: response_headers is null returns SKIPPED
 *   Given a MockContext with response_headers = null
 *   When CompressionCheck::run() is called
 *   Then the Finding status is "skipped"
 *
 * Scenario: content-encoding is gzip returns PASS
 *   Given a MockContext with response_headers = ['content-encoding' => 'gzip']
 *   When CompressionCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: content-encoding is br (Brotli) returns PASS
 *   Given a MockContext with response_headers = ['content-encoding' => 'br']
 *   When CompressionCheck::run() is called
 *   Then the Finding status is "pass"
 *
 * Scenario: no content-encoding header returns WARN/MEDIUM
 *   Given a MockContext with response_headers = [] (no content-encoding key)
 *   When CompressionCheck::run() is called
 *   Then the Finding status is "warn"
 *   And the Finding severity is "medium"
 *
 * Scenario: empty content-encoding header returns WARN
 *   Given a MockContext with response_headers = ['content-encoding' => '']
 *   When CompressionCheck::run() is called
 *   Then the Finding status is "warn"
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Performance\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Performance\Checks\CompressionCheck;
use WPSecurity\Tests\Support\MockContext;

final class CompressionCheckTest extends TestCase {

	private CompressionCheck $check;

	protected function setUp(): void {
		$this->check = new CompressionCheck();
	}

	public function test_id_is_performance_compression(): void {
		$this->assertSame( 'performance.compression', $this->check->id() );
	}

	public function test_null_headers_returns_skipped(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => null ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_gzip_encoding_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => [ 'content-encoding' => 'gzip' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_brotli_encoding_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => [ 'content-encoding' => 'br' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_deflate_encoding_returns_pass(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => [ 'content-encoding' => 'deflate' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_no_content_encoding_returns_warn_medium(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => [ 'content-type' => 'text/html' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
	}

	public function test_empty_content_encoding_returns_warn(): void {
		$ctx     = new MockContext( values: [ 'response_headers' => [ 'content-encoding' => '' ] ] );
		$finding = $this->check->run( $ctx );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
