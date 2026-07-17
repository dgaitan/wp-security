<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Remediations;

use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Modules\FunctionalQa\ContactForm\ContactForm7Provider;
use WPSecurity\Modules\FunctionalQa\ContactForm\ContactFormProviderInterface;
use WPSecurity\Modules\FunctionalQa\ContactForm\GravityFormsProvider;

/**
 * Tests contact-form submission end-to-end by delegating to whichever
 * registered ContactFormProviderInterface reports isActive() first — never
 * hardcoded to one form plugin. Implements Sprint 9's RemediationAction
 * contract: admin-triggered only (never run during automatic scans),
 * confirm-gated at the REST layer.
 */
class ContactFormTestAction implements RemediationAction {

	public function id(): string {
		return 'functional_qa.contact_form_test';
	}

	public function label(): string {
		return __( 'Test Contact Form Submission', 'wp-security' );
	}

	/**
	 * No narrower WP capability applies to "submit a test form entry" the
	 * way update_plugins/update_themes/update_core do for updates, so this
	 * intentionally matches the REST layer's own manage_options floor — the
	 * RemediationAction contract still requires declaring one explicitly.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	public function describe( array $params ): string {
		$skipMail = ! empty( $params['skip_mail'] );

		return $skipMail
			? __( 'This will submit a test entry through your site\'s detected contact form plugin without sending a real email.', 'wp-security' )
			: __( 'This will submit a real test entry through your site\'s detected contact form plugin, including sending a real test email through its configured mail template.', 'wp-security' );
	}

	public function isAvailable( Context $context, array $params ): bool {
		return null !== $this->detectProvider();
	}

	public function apply( Context $context, array $params ): RemediationResult {
		$provider = $this->detectProvider();

		if ( null === $provider ) {
			return RemediationResult::skipped( __( 'No supported contact form plugin (Contact Form 7, Gravity Forms) is active on this site.', 'wp-security' ) );
		}

		try {
			return $provider->testSubmit( $params );
		} catch ( \Throwable $e ) {
			return RemediationResult::failure(
				sprintf(
					/* translators: %s: exception message */
					__( 'Contact form test failed unexpectedly: %s', 'wp-security' ),
					$e->getMessage()
				)
			);
		}
	}

	private function detectProvider(): ?ContactFormProviderInterface {
		/**
		 * Allow third-party code to register additional contact-form
		 * providers (e.g. WPForms, Ninja Forms) without touching this class.
		 *
		 * @param array<ContactFormProviderInterface> $providers
		 */
		$providers = apply_filters(
			'wp_security/contact_form_providers',
			[
				new ContactForm7Provider(),
				new GravityFormsProvider(),
			]
		);

		foreach ( $providers as $provider ) {
			if ( $provider instanceof ContactFormProviderInterface && $provider->isActive() ) {
				return $provider;
			}
		}

		return null;
	}
}
