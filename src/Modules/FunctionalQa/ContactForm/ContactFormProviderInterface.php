<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\ContactForm;

use WPSecurity\Domain\RemediationResult;

/**
 * A pluggable adapter for one contact-form plugin's own native submission
 * API. ContactFormTestAction auto-detects the active plugin by trying each
 * registered provider's isActive() in turn — never hardcoded to one plugin.
 *
 * Mirrors the existing VulnerabilityAdvisor/AdvisoryProviderInterface
 * pattern already used elsewhere in this codebase for the same reason:
 * one capability, many interchangeable implementations, admin-agnostic.
 */
interface ContactFormProviderInterface {

	/**
	 * Stable identifier, e.g. "contact_form_7".
	 */
	public function id(): string;

	/**
	 * Human-readable label, e.g. "Contact Form 7".
	 */
	public function label(): string;

	/**
	 * Whether this provider's target plugin is installed and active.
	 */
	public function isActive(): bool;

	/**
	 * Submits a clearly-marked test payload through the form plugin's own
	 * native (non-HTTP, in-process) submission API and reports the outcome.
	 *
	 * Implementations must not throw — catch and return
	 * RemediationResult::failure()/skipped() instead.
	 *
	 * @param array<string, mixed> $params
	 */
	public function testSubmit( array $params ): RemediationResult;
}
