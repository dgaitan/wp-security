<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Seo\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Evidence;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Checks that the homepage has a <title> tag with an appropriate length.
 *
 * Google typically shows the first 50–70 characters of a title in search
 * results; anything outside 10–70 characters is flagged.
 */
class PageTitleCheck implements Check {

	private const MIN_LENGTH = 10;
	private const MAX_LENGTH = 70;

	public function id(): string {
		return 'seo.page_title';
	}

	public function label(): string {
		return __( 'Page Title', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$html = $context->get( 'homepage_html' );

		if ( ! is_string( $html ) ) {
			return Finding::skipped( $this->id(), $this->label(), 'Could not fetch homepage HTML.' );
		}

		if ( 1 !== preg_match( '/<title[^>]*>(.*?)<\/title>/si', $html, $matches ) ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::FAIL,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    __( 'No <title> tag was found on the homepage.', 'wp-security' ),
				recommendation: __( 'Add a descriptive <title> tag to your homepage. Aim for 10–70 characters.', 'wp-security' ),
			);
		}

		$titleText = trim( wp_strip_all_tags( (string) $matches[1] ) );
		$length    = mb_strlen( $titleText );

		if ( $length < self::MIN_LENGTH || $length > self::MAX_LENGTH ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::MEDIUM,
				title:          $this->label(),
				/* translators: %d: character count */
				description:    sprintf( __( 'Page title is %d characters long. Recommended length is 10–70 characters.', 'wp-security' ), $length ),
				recommendation: __( 'Adjust your page title to 10–70 characters for best search-engine visibility.', 'wp-security' ),
				evidence:       ( new Evidence() )
					->add( 'title', $titleText )
					->add( 'length', $length ),
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			/* translators: %d: character count */
			sprintf( __( 'Page title is %d characters — within the recommended range.', 'wp-security' ), $length )
		);
	}
}
