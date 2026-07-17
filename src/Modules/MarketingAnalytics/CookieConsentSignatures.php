<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\MarketingAnalytics;

/**
 * Pure lookup of {platform label => detection regex} for cookie consent
 * platforms. Kept as a static utility (not a Check) so it can be reused by
 * both CookieConsentPresenceCheck and, potentially, other consumers, and so
 * it stays trivially testable without a Context.
 */
final class CookieConsentSignatures {

	/** @var array<string, string> */
	private const BUILT_IN = [
		'Cookiebot' => '/cookiebot\.com|CookieConsent\s*=/i',
		'OneTrust'  => '/onetrust\.com|otSDKStub|OptanonConsent/i',
		'Complianz' => '/complianz|cmplz-cookiebanner/i',
		'CookieYes' => '/cookieyes\.com|cli_cookiebar/i',
		'Termly'    => '/termly\.io/i',
		'Iubenda'   => '/iubenda\.com|_iub\.csConfiguration/i',
	];

	/**
	 * @param string|null $customSignature Free-text signature from settings
	 *                                     (`cookie_consent_custom_signature`);
	 *                                     matched as a literal substring, not
	 *                                     a raw regex, so an admin can type a
	 *                                     script filename/string without
	 *                                     needing to know regex syntax.
	 *
	 * @return array<string, string> platform label => detection regex
	 */
	public static function signatures( ?string $customSignature = null ): array {
		/**
		 * Allow third-party code to add cookie-consent detection signatures.
		 *
		 * @param array<string, string> $signatures
		 */
		$signatures = apply_filters( 'wp_security/cookie_consent_signatures', self::BUILT_IN );

		if ( null !== $customSignature && '' !== $customSignature ) {
			$signatures['Custom'] = '/' . preg_quote( $customSignature, '/' ) . '/i';
		}

		return $signatures;
	}
}
