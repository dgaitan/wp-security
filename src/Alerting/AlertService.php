<?php

declare( strict_types=1 );

namespace WPSecurity\Alerting;

use WPSecurity\Domain\Finding;

/**
 * Sends email and/or Slack notifications when CRITICAL findings are detected.
 *
 * Triggered via the `wp_security/scan_complete` action, which ScanManager fires
 * after every run with the list of CRITICAL findings found.  If that list is
 * empty — or no alert channels are configured — nothing is sent.
 */
class AlertService {

	private const OPTION_KEY = 'wp_security_settings';

	/**
	 * Inspect settings and dispatch alerts for any CRITICAL findings.
	 *
	 * @param int            $runId            Completed scan run identifier.
	 * @param array<int, Finding> $criticalFindings CRITICAL-severity findings from the run.
	 */
	public function maybeAlert( int $runId, array $criticalFindings ): void {
		if ( [] === $criticalFindings ) {
			return;
		}

		$settings = (array) get_option( self::OPTION_KEY, [] );

		$email = sanitize_email( (string) ( $settings['alert_email'] ?? '' ) );
		if ( '' !== $email ) {
			$this->sendEmail( $email, $runId, $criticalFindings );
		}

		$webhookUrl = esc_url_raw( (string) ( $settings['slack_webhook_url'] ?? '' ) );
		if ( '' !== $webhookUrl ) {
			$this->sendSlack( $webhookUrl, $runId, $criticalFindings );
		}
	}

	/**
	 * @param array<int, Finding> $criticalFindings
	 */
	private function sendEmail( string $to, int $runId, array $criticalFindings ): void {
		$count   = count( $criticalFindings );
		$subject = sprintf(
			/* translators: 1: count, 2: scan run ID */
			__( '[WP Security] %1$d critical finding(s) in Scan #%2$d', 'wp-security' ),
			$count,
			$runId
		);

		$lines = [];
		foreach ( $criticalFindings as $finding ) {
			$lines[] = '• ' . $finding->title . ': ' . $finding->description;
		}

		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}

	/**
	 * @param array<int, Finding> $criticalFindings
	 */
	private function sendSlack( string $webhookUrl, int $runId, array $criticalFindings ): void {
		$count = count( $criticalFindings );
		$text  = sprintf(
			/* translators: 1: count, 2: scan run ID */
			__( ':rotating_light: *WP Security Alert* — %1$d critical finding(s) in Scan #%2$d', 'wp-security' ),
			$count,
			$runId
		);

		wp_remote_post(
			$webhookUrl,
			[
				'body'    => wp_json_encode( [ 'text' => $text ] ),
				'headers' => [ 'Content-Type' => 'application/json' ],
				'timeout' => 10,
			]
		);
	}
}
