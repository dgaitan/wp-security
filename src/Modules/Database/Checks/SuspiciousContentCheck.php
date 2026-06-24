<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Database\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Scans database options and post content for suspicious PHP patterns.
 *
 * The presence of eval() or base64_decode() in stored content is a strong
 * indicator of malware injection and warrants immediate investigation.
 */
class SuspiciousContentCheck implements Check {

	public function id(): string {
		return 'database.suspicious_content';
	}

	public function label(): string {
		return __( 'Suspicious Database Content', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		/** @var int|null $optionCount */
		$optionCount = $context->get( 'suspicious_option_count' );
		/** @var int|null $postCount */
		$postCount = $context->get( 'suspicious_post_count' );

		if ( null === $optionCount || null === $postCount ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not scan database content for suspicious patterns.'
			);
		}

		$total = $optionCount + $postCount;

		if ( 0 === $total ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No suspicious code patterns (eval, base64_decode) found in options or post content.', 'wp-security' )
			);
		}

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       Severity::HIGH,
			title:          $this->label(),
			description:    sprintf(
				/* translators: 1: number of suspicious options, 2: number of suspicious posts */
				__( 'Suspicious code patterns found: %1$d option(s) and %2$d post(s) contain eval() or base64_decode() calls.', 'wp-security' ),
				$optionCount,
				$postCount
			),
			recommendation: __( 'Investigate these database entries immediately — they may indicate a malware injection. Compare against a known-good backup and restore from a clean snapshot if malware is confirmed.', 'wp-security' ),
			evidence:       [
				'suspicious_option_count' => $optionCount,
				'suspicious_post_count'   => $postCount,
			],
		);
	}
}
