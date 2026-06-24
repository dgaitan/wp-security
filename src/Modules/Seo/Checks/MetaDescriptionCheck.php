<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks that the homepage has a meta description within the ideal character range.
 *
 * Google typically displays 50–160 characters of a meta description in search results.
 */
class MetaDescriptionCheck implements Check {

	private const MIN_LENGTH = 50;
	private const MAX_LENGTH = 160;

	public function id(): string {
		return 'seo.meta_description';
	}

	public function label(): string {
		return __( 'Meta Description', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$html = $context->get( 'homepage_html' );

		if ( ! is_string( $html ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not fetch homepage HTML.' );
		}

		// Locate the meta description tag (name attr may precede or follow content attr).
		if ( 1 !== preg_match( '/<meta\b[^>]*\bname=["\']description["\'][^>]*>/si', $html, $metaTag ) ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				description:    __( 'No <meta name="description"> tag was found on the homepage.', 'wp-security' ),
				recommendation: __( 'Add a meta description to improve search-engine snippet text and click-through rates.', 'wp-security' ),
			);
		}

		// Extract the content attribute value from the matched tag.
		$desc = '';
		if ( 1 === preg_match( '/\bcontent=["\']([^"\']*)["\']/', $metaTag[0], $contentMatch ) ) {
			$desc = trim( (string) $contentMatch[1] );
		}

		$length = mb_strlen( $desc );

		if ( $length < self::MIN_LENGTH || $length > self::MAX_LENGTH ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::LOW,
				title:          $this->label(),
				/* translators: %d: character count */
				description:    sprintf( __( 'Meta description is %d characters. Recommended: 50–160 characters.', 'wp-security' ), $length ),
				recommendation: __( 'Adjust the meta description to 50–160 characters for best search-engine display.', 'wp-security' ),
				evidence:       [ 'length' => $length ],
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			/* translators: %d: character count */
			sprintf( __( 'Meta description is %d characters — within the recommended range.', 'wp-security' ), $length )
		);
	}
}
