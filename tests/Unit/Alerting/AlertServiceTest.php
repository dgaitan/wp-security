<?php
/*
 * Feature: AlertService — CRITICAL finding notifications
 *
 * Scenario: email sent when alert_email configured and CRITICAL findings exist
 *   Given alert_email = 'admin@example.com' in wp_security_settings
 *   And criticalFindings contains one CRITICAL Finding
 *   When AlertService::maybeAlert() is called with runId = 1
 *   Then wp_mail is called with 'admin@example.com'
 *   And the email subject mentions the run ID
 *
 * Scenario: no email sent when criticalFindings is empty
 *   Given alert_email is configured
 *   And criticalFindings = []
 *   When AlertService::maybeAlert() is called
 *   Then wp_mail is NOT called
 *
 * Scenario: no email sent when alert_email is not configured
 *   Given no alert_email in settings
 *   And criticalFindings contains one CRITICAL Finding
 *   When AlertService::maybeAlert() is called
 *   Then wp_mail is NOT called
 *
 * Scenario: Slack POST sent when slack_webhook_url configured and CRITICAL findings exist
 *   Given slack_webhook_url = 'https://hooks.slack.com/T000/xxx' in settings
 *   And criticalFindings contains one CRITICAL Finding
 *   When AlertService::maybeAlert() is called
 *   Then wp_remote_post is called with the Slack webhook URL
 *
 * Scenario: no Slack POST sent when criticalFindings is empty
 *   Given slack_webhook_url is configured
 *   And criticalFindings = []
 *   When AlertService::maybeAlert() is called
 *   Then wp_remote_post is NOT called
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Alerting;

use PHPUnit\Framework\TestCase;
use WPSecurity\Alerting\AlertService;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

final class AlertServiceTest extends TestCase {

	private AlertService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service                          = new AlertService();
		$GLOBALS['wp_security_test_options']    = [];
		$GLOBALS['wp_security_test_mail_sent']  = [];
		$GLOBALS['wp_security_test_http_posts'] = [];
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset(
			$GLOBALS['wp_security_test_options'],
			$GLOBALS['wp_security_test_mail_sent'],
			$GLOBALS['wp_security_test_http_posts']
		);
	}

	private function criticalFinding(): Finding {
		return new Finding(
			checkId:        'server.php_version',
			status:         Status::FAIL,
			severity:       Severity::CRITICAL,
			title:          'PHP version outdated',
			description:    'PHP 7.4 is end-of-life.',
			recommendation: 'Upgrade to PHP 8.1+.',
		);
	}

	public function test_email_sent_when_configured_and_critical_findings_present(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'alert_email' => 'admin@example.com',
		];

		$this->service->maybeAlert( 1, [ $this->criticalFinding() ] );

		$this->assertCount( 1, $GLOBALS['wp_security_test_mail_sent'] );
		$this->assertSame( 'admin@example.com', $GLOBALS['wp_security_test_mail_sent'][0]['to'] );
		$this->assertStringContainsString( '#1', $GLOBALS['wp_security_test_mail_sent'][0]['subject'] );
	}

	public function test_no_email_sent_when_findings_empty(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'alert_email' => 'admin@example.com',
		];

		$this->service->maybeAlert( 1, [] );

		$this->assertCount( 0, $GLOBALS['wp_security_test_mail_sent'] );
	}

	public function test_no_email_sent_when_alert_email_not_configured(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [];

		$this->service->maybeAlert( 1, [ $this->criticalFinding() ] );

		$this->assertCount( 0, $GLOBALS['wp_security_test_mail_sent'] );
	}

	public function test_slack_post_sent_when_configured_and_critical_findings_present(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'slack_webhook_url' => 'https://hooks.slack.com/T000/xxx',
		];

		$this->service->maybeAlert( 2, [ $this->criticalFinding() ] );

		$this->assertCount( 1, $GLOBALS['wp_security_test_http_posts'] );
		$this->assertSame( 'https://hooks.slack.com/T000/xxx', $GLOBALS['wp_security_test_http_posts'][0]['url'] );
	}

	public function test_slack_post_not_sent_when_findings_empty(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'slack_webhook_url' => 'https://hooks.slack.com/T000/xxx',
		];

		$this->service->maybeAlert( 2, [] );

		$this->assertCount( 0, $GLOBALS['wp_security_test_http_posts'] );
	}

	public function test_both_channels_fire_when_both_configured(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'alert_email'       => 'admin@example.com',
			'slack_webhook_url' => 'https://hooks.slack.com/T000/xxx',
		];

		$this->service->maybeAlert( 3, [ $this->criticalFinding() ] );

		$this->assertCount( 1, $GLOBALS['wp_security_test_mail_sent'] );
		$this->assertCount( 1, $GLOBALS['wp_security_test_http_posts'] );
	}
}
