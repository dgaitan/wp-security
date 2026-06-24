<?php

/**
 * Feature: DmarcCheck — DNS module
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: DMARC record present returns PASS
 *     Given a mocked Context with dns_dmarc_records containing a v=DMARC1 entry
 *     When DmarcCheck::run() is called
 *     Then the Finding status is "pass"
 *
 *   Scenario: DMARC record absent returns WARN with MEDIUM severity
 *     Given a mocked Context with dns_dmarc_records containing no v=DMARC1 entry
 *     When DmarcCheck::run() is called
 *     Then the Finding status is "warn"
 *     And the Finding severity is "medium"
 *
 *   Scenario: DMARC records null returns SKIPPED
 *     Given a mocked Context where get('dns_dmarc_records') returns null
 *     When DmarcCheck::run() is called
 *     Then the Finding status is "skipped"
 *
 *   Scenario: Empty DMARC records array returns WARN
 *     Given a mocked Context with an empty dns_dmarc_records array
 *     When DmarcCheck::run() is called
 *     Then the Finding status is "warn"
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Dns\Checks;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;
use WPSecurity\Modules\Dns\Checks\DmarcCheck;
use WPSecurity\Tests\Support\MockContext;

final class DmarcCheckTest extends TestCase {

	private DmarcCheck $check;

	protected function setUp(): void {
		parent::setUp();
		$this->check = new DmarcCheck();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'dns.dmarc', $this->check->id() );
	}

	public function test_dmarc_record_present_returns_pass(): void {
		$context = new MockContext(
			values: [
				'dns_dmarc_records' => [
					[
						'host'    => '_dmarc.example.test',
						'type'    => 'TXT',
						'txt'     => 'v=DMARC1; p=quarantine; rua=mailto:dmarc@example.test',
						'entries' => [ 'v=DMARC1; p=quarantine; rua=mailto:dmarc@example.test' ],
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::PASS, $finding->status );
	}

	public function test_dmarc_record_absent_returns_warn_medium(): void {
		$context = new MockContext(
			values: [
				'dns_dmarc_records' => [
					[
						'host'    => '_dmarc.example.test',
						'type'    => 'TXT',
						'txt'     => 'some-other-record',
						'entries' => [ 'some-other-record' ],
					],
				],
			]
		);
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
		$this->assertSame( Severity::MEDIUM, $finding->severity );
		$this->assertSame( 'dns.dmarc', $finding->checkId );
	}

	public function test_null_records_returns_skipped(): void {
		$context = new MockContext( values: [] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::SKIPPED, $finding->status );
	}

	public function test_empty_records_array_returns_warn(): void {
		$context = new MockContext( values: [ 'dns_dmarc_records' => [] ] );
		$finding = $this->check->run( $context );

		$this->assertSame( Status::WARN, $finding->status );
	}
}
