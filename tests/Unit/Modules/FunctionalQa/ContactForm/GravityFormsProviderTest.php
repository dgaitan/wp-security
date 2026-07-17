<?php
/*
 * Feature: GravityFormsProvider — Sprint 10
 *
 * Scenario: isActive() is false in this unit-test environment
 * Scenario: testSubmit() returns a skipped result when inactive
 *
 * Note: Gravity Forms is not installed anywhere in this dev environment, so
 * unlike ContactForm7Provider, this provider's active path has been verified
 * against Gravity Forms' published API documentation only — see the class
 * docblock. Only the inactive/guard path is exercised here.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\ContactForm;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationStatus;
use WPSecurity\Modules\FunctionalQa\ContactForm\GravityFormsProvider;

final class GravityFormsProviderTest extends TestCase {

	private GravityFormsProvider $provider;

	protected function setUp(): void {
		$this->provider = new GravityFormsProvider();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'gravity_forms', $this->provider->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->provider->label() );
	}

	public function test_is_active_false_when_gfapi_class_not_loaded(): void {
		$this->assertFalse( $this->provider->isActive() );
	}

	public function test_test_submit_returns_skipped_when_inactive(): void {
		$result = $this->provider->testSubmit( [] );

		$this->assertSame( RemediationStatus::SKIPPED, $result->status );
	}
}
