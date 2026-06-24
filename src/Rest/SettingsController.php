<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WPSecurity\VulnerabilityAdvisor\VulnerabilityAdvisor;

/**
 * Settings read/write endpoint.
 *
 *   GET  /wp-security/v1/settings  — retrieve current configuration
 *   POST /wp-security/v1/settings  — update configuration
 *
 * Settings are stored in the options API under `wp_security_settings`
 * WITHOUT autoload so they don't hit every page request.  API keys are
 * stored as-is but NEVER returned in full to the client — only a masked
 * preview is returned (last 4 chars visible, rest replaced with bullet chars).
 */
class SettingsController extends AbstractController {

	private const OPTION_KEY = 'wp_security_settings';

	/** @var string[] */
	private const VALID_FREQUENCIES = [ 'hourly', 'daily', 'weekly' ];

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get' ],
					'permission_callback' => [ $this, 'permissionCheck' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update' ],
					'permission_callback' => [ $this, 'permissionCheck' ],
					'args'                => [
						'vuln_advisor_provider' => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						],
						'wpscan_api_key'        => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'scan_frequency'        => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						],
						'alert_email'           => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_email',
						],
						'slack_webhook_url'     => [
							'type'              => 'string',
							'sanitize_callback' => 'esc_url_raw',
						],
					],
				],
			]
		);
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$settings                        = (array) get_option( self::OPTION_KEY, [] );
		$settings['wpscan_api_key']      = $this->maskApiKey( (string) ( $settings['wpscan_api_key'] ?? '' ) );
		$settings['available_providers'] = VulnerabilityAdvisor::getAvailableAdvisors();
		return $this->respond( $settings );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$current           = (array) get_option( self::OPTION_KEY, [] );
		$allowed_providers = array_keys( VulnerabilityAdvisor::getAvailableAdvisors() );

		$provider = $request->get_param( 'vuln_advisor_provider' );
		if ( null !== $provider ) {
			$clean = sanitize_key( (string) $provider );
			if ( in_array( $clean, $allowed_providers, true ) ) {
				$current['vuln_advisor_provider'] = $clean;
			}
		}

		$key = $request->get_param( 'wpscan_api_key' );
		if ( null !== $key && ! str_contains( (string) $key, '•' ) ) {
			$current['wpscan_api_key'] = sanitize_text_field( (string) $key );
		}

		$frequency = $request->get_param( 'scan_frequency' );
		if ( null !== $frequency ) {
			$clean = sanitize_key( (string) $frequency );
			if ( in_array( $clean, self::VALID_FREQUENCIES, true ) ) {
				$current['scan_frequency'] = $clean;
			}
		}

		$email = $request->get_param( 'alert_email' );
		if ( null !== $email ) {
			$clean = sanitize_email( (string) $email );
			if ( '' !== $clean ) {
				$current['alert_email'] = $clean;
			} else {
				unset( $current['alert_email'] );
			}
		}

		$slackUrl = $request->get_param( 'slack_webhook_url' );
		if ( null !== $slackUrl ) {
			$clean = esc_url_raw( (string) $slackUrl );
			if ( '' !== $clean ) {
				$current['slack_webhook_url'] = $clean;
			} else {
				unset( $current['slack_webhook_url'] );
			}
		}

		update_option( self::OPTION_KEY, $current, false );

		return $this->respond( null, 204 );
	}

	private function maskApiKey( string $key ): string {
		if ( '' === $key ) {
			return '';
		}
		$visible = substr( $key, -4 );
		$bullets = str_repeat( '•', max( 8, strlen( $key ) - 4 ) );
		return $bullets . $visible;
	}
}
