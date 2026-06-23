<?php
/**
 * Unit tests for Finding::fromArray().
 *
 * Feature: Finding rehydration — Sprint 2 scoring of persisted findings
 *   Background:
 *     Given the WP Security plugin is installed
 *
 *   Scenario: A finding survives a toArray()/fromArray() round-trip
 *     Given a Finding with evidence and a docs URL
 *     When it is serialised with toArray() and rebuilt with fromArray()
 *     Then every field and its score penalty are preserved
 *
 *   Scenario: A persisted row shape rehydrates correctly
 *     Given a database-style row with string status/severity and null docs_url
 *     When fromArray() is called
 *     Then the enums and evidence are reconstructed
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

final class FindingFromArrayTest extends TestCase {

	public function test_round_trips_through_to_array(): void {
		$original = new Finding(
			'server.php_version',
			Status::FAIL,
			Severity::HIGH,
			'Outdated PHP',
			'PHP 7.4 is end-of-life.',
			'Upgrade to PHP 8.1+.',
			[ 'current' => '7.4.33' ],
			'https://example.test/docs'
		);

		$rebuilt = Finding::fromArray( $original->toArray() );

		$this->assertSame( $original->checkId, $rebuilt->checkId );
		$this->assertSame( $original->status, $rebuilt->status );
		$this->assertSame( $original->severity, $rebuilt->severity );
		$this->assertSame( $original->title, $rebuilt->title );
		$this->assertSame( $original->evidence, $rebuilt->evidence );
		$this->assertSame( $original->docsUrl, $rebuilt->docsUrl );
		$this->assertSame( $original->penalty(), $rebuilt->penalty() );
	}

	public function test_rehydrates_persisted_row_shape(): void {
		$rebuilt = Finding::fromArray(
			[
				'check_id'       => 'headers.hsts',
				'status'         => 'warn',
				'severity'       => 'medium',
				'title'          => 'Missing HSTS',
				'description'    => 'No Strict-Transport-Security header.',
				'recommendation' => 'Add the HSTS header.',
				'evidence'       => [],
				'docs_url'       => null,
			]
		);

		$this->assertSame( Status::WARN, $rebuilt->status );
		$this->assertSame( Severity::MEDIUM, $rebuilt->severity );
		$this->assertNull( $rebuilt->docsUrl );
		$this->assertSame( [], $rebuilt->evidence );
	}
}
