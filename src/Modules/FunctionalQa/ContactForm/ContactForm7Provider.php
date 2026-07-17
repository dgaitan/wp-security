<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\ContactForm;

use WPSecurity\Domain\RemediationResult;

/**
 * Submits a test entry through Contact Form 7's own native in-process API
 * (WPCF7_ContactForm::submit()) — no HTTP round-trip, no REST call, no
 * browser. Verified against the installed CF7 6.1.6 source:
 *
 *   - WPCF7_Submission::setup_posted_data() reads directly from $_POST, so
 *     field values are supplied by populating $_POST before calling submit(),
 *     which is CF7's own documented approach for programmatic submissions.
 *   - submit(['skip_mail' => bool]) is a first-class option; skip_mail=false
 *     (the default here) sends a real email through the form's configured
 *     mail template, genuinely testing delivery end-to-end.
 *   - submit() never throws; it returns a result array whose 'status' is one
 *     of mail_sent|mail_failed|validation_failed|acceptance_missing|spam|
 *     aborted|error.
 *   - spam() (submission.php) short-circuits entirely when the
 *     'wpcf7_skip_spam_check' filter returns true, which also bypasses its
 *     nonce/user-agent checks — both are meaningless for an in-process call
 *     with no real browser request behind it. Since this only ever runs
 *     through the confirm-gated, capability-checked RemediationAction flow
 *     (an authenticated admin explicitly triggering a test), bypassing CF7's
 *     anti-*spam* heuristics here is safe: it is not a public attack surface.
 */
class ContactForm7Provider implements ContactFormProviderInterface {

	private const TEST_PREFIX = '[WP Security Test]';

	public function id(): string {
		return 'contact_form_7';
	}

	public function label(): string {
		return __( 'Contact Form 7', 'wp-security' );
	}

	public function isActive(): bool {
		return class_exists( 'WPCF7_ContactForm' );
	}

	public function testSubmit( array $params ): RemediationResult {
		if ( ! $this->isActive() ) {
			return RemediationResult::skipped( __( 'Contact Form 7 is not active.', 'wp-security' ) );
		}

		$contactForm = $this->resolveForm( $params );

		if ( null === $contactForm ) {
			return RemediationResult::skipped( __( 'No published Contact Form 7 forms were found to test.', 'wp-security' ) );
		}

		$skipMail = ! empty( $params['skip_mail'] );

		$originalPost = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- not a real HTTP request; this simulates one in-process per CF7's own documented submit() usage.

		try {
			$_POST = array_merge( // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$_POST, // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$this->buildPostedData( $contactForm ),
				[ '_wpcf7_unit_tag' => 'wpcf7-f' . $contactForm->id() . '-p0-o1' ]
			);

			add_filter( 'wpcf7_skip_spam_check', '__return_true' );
			try {
				/** @var array<string, mixed> $result */
				$result = $contactForm->submit( [ 'skip_mail' => $skipMail ] );
			} finally {
				remove_filter( 'wpcf7_skip_spam_check', '__return_true' );
			}
		} catch ( \Throwable $e ) {
			$_POST = $originalPost; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return RemediationResult::failure(
				sprintf(
					/* translators: %s: exception message */
					__( 'Contact Form 7 test submission failed unexpectedly: %s', 'wp-security' ),
					$e->getMessage()
				)
			);
		}

		$_POST = $originalPost; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return $this->toRemediationResult( $result, $skipMail );
	}

	/**
	 * Resolves the target form: an explicit `form_id` param, or the first
	 * published Contact Form 7 form when none is specified.
	 *
	 * @param array<string, mixed> $params
	 */
	private function resolveForm( array $params ): ?\WPCF7_ContactForm {
		$formId = isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0;

		if ( $formId > 0 ) {
			return \wpcf7_contact_form( $formId );
		}

		$posts = get_posts(
			[
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		$firstId = $posts[0] ?? null;

		return null !== $firstId ? \wpcf7_contact_form( (int) $firstId ) : null;
	}

	/**
	 * Builds $_POST-shaped field values from the form's own scanned tags —
	 * agnostic to whatever field names the site owner has configured, not
	 * hardcoded to CF7's default template's "your-name"/"your-email" tags.
	 *
	 * @return array<string, mixed>
	 */
	private function buildPostedData( \WPCF7_ContactForm $contactForm ): array {
		$data = [];

		foreach ( $contactForm->scan_form_tags() as $tag ) {
			$name = (string) $tag->name;

			if ( '' === $name ) {
				continue;
			}

			$data[ $name ] = $this->valueForTag( $tag );
		}

		return $data;
	}

	private function valueForTag( \WPCF7_FormTag $tag ): mixed {
		return match ( (string) $tag->basetype ) {
			'email'                => 'wp-security-test@example.com',
			'tel'                  => '5555555555',
			'number', 'range'      => '1',
			'url'                  => 'https://example.com',
			'date'                 => gmdate( 'Y-m-d' ),
			'checkbox', 'radio', 'select' => (string) ( $tag->values[0] ?? '' ),
			'acceptance'           => '1',
			'textarea'             => self::TEST_PREFIX . ' This is an automated test submission generated by the WP Security plugin maintenance check.',
			default                => self::TEST_PREFIX . ' ' . $tag->name,
		};
	}

	/**
	 * @param array<string, mixed> $result
	 */
	private function toRemediationResult( array $result, bool $skipMail ): RemediationResult {
		$status  = (string) ( $result['status'] ?? 'error' );
		$message = (string) ( $result['message'] ?? '' );

		if ( 'mail_sent' === $status ) {
			return RemediationResult::success(
				$skipMail
					? __( 'Test submission passed validation (mail sending was skipped).', 'wp-security' )
					: __( 'Test submission succeeded and a real test email was sent through the form\'s configured mail template.', 'wp-security' ),
				null,
				[ 'status' => $status ]
			);
		}

		return RemediationResult::failure(
			sprintf(
				/* translators: 1: CF7 status slug, 2: CF7's own message for that status */
				__( 'Test submission did not succeed (status: %1$s). %2$s', 'wp-security' ),
				$status,
				$message
			),
			[ 'status' => $status ]
		);
	}
}
