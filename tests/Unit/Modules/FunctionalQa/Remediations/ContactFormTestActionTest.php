<?php
/*
 * Feature: ContactFormTestAction — Sprint 10
 *
 * Scenario: isAvailable() is false when no supported provider is active
 *   Given neither Contact Form 7 nor Gravity Forms is loaded in this test env
 *   When isAvailable() is called
 *   Then it returns false
 *
 * Scenario: apply() returns a skipped (not failed/thrown) result when no provider is active
 *
 * Scenario: describe() reflects the skip_mail param
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa\Remediations;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\RemediationStatus;
use WPSecurity\Modules\FunctionalQa\Remediations\ContactFormTestAction;
use WPSecurity\Tests\Support\MockContext;

final class ContactFormTestActionTest extends TestCase {

	private ContactFormTestAction $action;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_filters'] = [];
		$this->action                        = new ContactFormTestAction();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_security_test_filters'] );
		parent::tearDown();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'functional_qa.contact_form_test', $this->action->id() );
	}

	public function test_capability_is_manage_options(): void {
		$this->assertSame( 'manage_options', $this->action->capability() );
	}

	public function test_describe_mentions_real_email_by_default(): void {
		$this->assertStringContainsString( 'real test email', $this->action->describe( [] ) );
	}

	public function test_describe_mentions_skipped_mail_when_requested(): void {
		$this->assertStringNotContainsString(
			'sending a real test email',
			$this->action->describe( [ 'skip_mail' => true ] )
		);
	}

	public function test_is_available_false_when_no_provider_active(): void {
		$ctx = new MockContext();

		$this->assertFalse( $this->action->isAvailable( $ctx, [] ) );
	}

	public function test_apply_returns_skipped_when_no_provider_active(): void {
		$ctx    = new MockContext();
		$result = $this->action->apply( $ctx, [] );

		$this->assertSame( RemediationStatus::SKIPPED, $result->status );
	}
}
