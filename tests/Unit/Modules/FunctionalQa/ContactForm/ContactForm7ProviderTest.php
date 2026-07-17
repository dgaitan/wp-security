<?php
/*
 * Feature: ContactForm7Provider — Sprint 10
 *
 * Scenario: isActive() is false in this unit-test environment
 *   Given Contact Form 7's WPCF7_ContactForm class is not loaded
 *   When isActive() is called
 *   Then it returns false
 *
 * Scenario: testSubmit() returns a skipped result when inactive
 *   Given the provider is inactive
 *   When testSubmit() is called
 *   Then a RemediationResult with status SKIPPED is returned, never a thrown error
 *
 * Note: testSubmit()'s active path exercises Contact Form 7's real
 * WPCF7_ContactForm::submit(), which requires the real plugin loaded — that
 * path is covered by manual verification against the dev environment (where
 * Contact Form 7 was installed specifically to verify this integration),
 * not by PHPUnit here, mirroring the established pattern for
 * PluginUpdateRemediation::apply() in Sprint 9.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\ContactForm;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationStatus;
use WPSecurity\Modules\FunctionalQa\ContactForm\ContactForm7Provider;

final class ContactForm7ProviderTest extends TestCase {

	private ContactForm7Provider $provider;

	protected function setUp(): void {
		$this->provider = new ContactForm7Provider();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'contact_form_7', $this->provider->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->provider->label() );
	}

	public function test_is_active_false_when_cf7_class_not_loaded(): void {
		$this->assertFalse( $this->provider->isActive() );
	}

	public function test_test_submit_returns_skipped_when_inactive(): void {
		$result = $this->provider->testSubmit( [] );

		$this->assertSame( RemediationStatus::SKIPPED, $result->status );
	}
}
