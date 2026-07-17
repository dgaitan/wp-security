<?php
/**
 * Unit tests for the Finding value object.
 *
 * Feature: Finding value object — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: A scoring finding reports its penalty
 *     Given a FAIL finding with HIGH severity
 *     When penalty() is called
 *     Then it returns 20
 *     And affectsScore() returns true
 *
 *   Scenario: A passing finding never penalises the score
 *     Given a PASS finding
 *     When penalty() is called
 *     Then it returns 0
 *     And affectsScore() returns false
 *
 *   Scenario: Finding serialises for REST
 *     Given a populated finding
 *     When toArray() is called
 *     Then it returns snake_case keys with the finding data
 *
 *   Scenario: Factory helpers build PASS and SKIPPED findings
 *     Given the pass() and skipped() factories
 *     When each is called
 *     Then the resulting Status matches and severity is INFO
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

final class FindingTest extends TestCase {

	public function test_failing_finding_reports_penalty(): void {
		$finding = new Finding(
			'server.php_version',
			Status::FAIL,
			Severity::HIGH,
			'PHP version',
			'PHP is end of life.',
			'Upgrade PHP.'
		);

		$this->assertTrue( $finding->affectsScore() );
		$this->assertSame( 20, $finding->penalty() );
	}

	public function test_passing_finding_has_no_penalty(): void {
		$finding = new Finding(
			'server.php_version',
			Status::PASS,
			Severity::INFO,
			'PHP version',
			'PHP is current.',
			''
		);

		$this->assertFalse( $finding->affectsScore() );
		$this->assertSame( 0, $finding->penalty() );
	}

	public function test_warn_finding_uses_severity_penalty(): void {
		$finding = new Finding(
			'server.opcache',
			Status::WARN,
			Severity::MEDIUM,
			'OPcache',
			'OPcache is disabled.',
			'Enable OPcache.'
		);

		$this->assertSame( 10, $finding->penalty() );
	}

	public function test_to_array_uses_snake_case_keys(): void {
		$finding = new Finding(
			'server.php_version',
			Status::FAIL,
			Severity::HIGH,
			'PHP version',
			'PHP is end of life.',
			'Upgrade PHP.',
			( new Evidence() )->add( 'current', '7.4' ),
			'https://example.test/docs'
		);

		$this->assertSame(
			[
				'check_id'       => 'server.php_version',
				'status'         => 'fail',
				'severity'       => 'high',
				'title'          => 'PHP version',
				'description'    => 'PHP is end of life.',
				'recommendation' => 'Upgrade PHP.',
				'evidence'       => [
					[
						'key'   => 'current',
						'label' => 'Current',
						'type'  => 'scalar',
						'value' => '7.4',
					],
				],
				'docs_url'       => 'https://example.test/docs',
			],
			$finding->toArray()
		);
	}

	public function test_pass_factory_produces_pass_status(): void {
		$finding = Finding::pass( 'server.https', 'HTTPS' );

		$this->assertSame( Status::PASS, $finding->status );
		$this->assertSame( Severity::INFO, $finding->severity );
		$this->assertSame( 0, $finding->penalty() );
	}

	public function test_skipped_factory_produces_skipped_status(): void {
		$finding = Finding::skipped( 'server.disk', 'Disk space', 'Unavailable on this host.' );

		$this->assertSame( Status::SKIPPED, $finding->status );
		$this->assertSame( 'Unavailable on this host.', $finding->description );
		$this->assertSame( 0, $finding->penalty() );
	}
}
