<?php

/**
 * Feature: SpfCheck — DNS module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: SPF record present returns PASS
 *     Given a mocked Context with dns_txt_records containing a v=spf1 entry
 *     When SpfCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: SPF record absent returns WARN with MEDIUM severity
 *     Given a mocked Context with dns_txt_records containing no v=spf1 entry
 *     When SpfCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: DNS records null returns SKIPPED
 *     Given a mocked Context where get('dns_txt_records') returns null
 *     When SpfCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Empty DNS records array returns WARN
 *     Given a mocked Context with an empty dns_txt_records array
 *     When SpfCheck::run() is called
 *     Then the Finding status is "warn"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Dns\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Dns\Checks\SpfCheck;
use WPSecurity\Tests\Support\MockContext;

final class SpfCheckTest extends TestCase {

	private SpfCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new SpfCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'dns.spf', $this->check->id() );
	}

	public function test_spf_record_present_returns_pass(): void {
		$context = new MockContext(
			values: [
				'dns_txt_records' => [
					[
						'host'    => 'example.test',
						'type'    => 'TXT',
						'txt'     => 'v=spf1 include:_spf.google.com ~all',
						'entries' => [ 'v=spf1 include:_spf.google.com ~all' ],
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_spf_record_absent_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'dns_txt_records' => [
					[
						'host'    => 'example.test',
						'type'    => 'TXT',
						'txt'     => 'google-site-verification=abc123',
						'entries' => [ 'google-site-verification=abc123' ],
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'dns.spf', $finding->checkId );
	}

	public function test_null_records_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_records_array_returns_warn(): void {
		$context = new MockContext( values: [ 'dns_txt_records' => [] ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
