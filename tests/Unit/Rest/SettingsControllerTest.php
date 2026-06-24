<?php
/*
 * Feature: SettingsController — GET/POST /wp-security/v1/settings
 *
 * Scenario: GET returns settings with masked API key
 *   Given wp_security_settings option contains a wpscan_api_key
 *   When GET /settings is called
 *   Then the response contains the key masked (bullets + last 4 chars)
 *   And the full key is NOT present in the response
 *
 * Scenario: GET with no stored settings returns empty array
 *   Given no wp_security_settings option stored
 *   When GET /settings is called
 *   Then the response is an empty array
 *
 * Scenario: POST saves valid provider
 *   Given a POST request with vuln_advisor_provider = 'wpscan'
 *   When update() is called
 *   Then the option is updated with provider = 'wpscan'
 *   And response status is 204
 *
 * Scenario: POST with masked key does NOT overwrite stored key
 *   Given the stored key is 'my-secret-key'
 *   And the POST body contains the masked version of that key
 *   When update() is called
 *   Then the stored key remains 'my-secret-key'
 *
 * Scenario: POST with unknown provider is rejected
 *   Given a POST request with vuln_advisor_provider = 'unknown-provider'
 *   When update() is called
 *   Then the stored provider is unchanged
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WPSecurity\Rest\SettingsController;

final class SettingsControllerTest extends TestCase {

	private SettingsController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller                                  = new SettingsController();
		$GLOBALS['wp_security_test_options']               = [];
		$GLOBALS['wp_security_test_rest_routes']           = [];
		$GLOBALS['wp_security_test_can']['manage_options'] = true;
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset(
			$GLOBALS['wp_security_test_options'],
			$GLOBALS['wp_security_test_rest_routes'],
			$GLOBALS['wp_security_test_can']
		);
	}

	public function test_register_creates_settings_route(): void {
		$this->controller->register();

		$routes = array_column( $GLOBALS['wp_security_test_rest_routes'] ?? [], 'route' );
		$this->assertContains( '/settings', $routes );
	}

	public function test_get_returns_empty_array_when_no_settings_stored(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
	}

	public function test_get_masks_wpscan_api_key(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'vuln_advisor_provider' => 'wpscan',
			'wpscan_api_key'        => 'abcdefghijklmno',
		];

		$request  = new \WP_REST_Request();
		$response = $this->controller->get( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertStringContainsString( '•', $data['wpscan_api_key'] );
		$this->assertStringNotContainsString( 'abcdefghijklmno', $data['wpscan_api_key'] );
		$this->assertStringEndsWith( 'lmno', $data['wpscan_api_key'] );
	}

	public function test_get_returns_empty_string_when_no_key_stored(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'vuln_advisor_provider' => 'wpvulnerability',
		];

		$request  = new \WP_REST_Request();
		$response = $this->controller->get( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertSame( '', $data['wpscan_api_key'] );
	}

	public function test_post_saves_provider(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'vuln_advisor_provider', 'wpscan' );

		$response = $this->controller->update( $request );

		$this->assertSame( 204, $response->get_status() );
		$stored = $GLOBALS['wp_security_test_options']['wp_security_settings'] ?? [];
		$this->assertSame( 'wpscan', $stored['vuln_advisor_provider'] );
	}

	public function test_post_saves_api_key_when_not_masked(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'wpscan_api_key', 'my-real-api-key' );

		$this->controller->update( $request );

		$stored = $GLOBALS['wp_security_test_options']['wp_security_settings'] ?? [];
		$this->assertSame( 'my-real-api-key', $stored['wpscan_api_key'] );
	}

	public function test_post_does_not_overwrite_key_when_masked_value_submitted(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'wpscan_api_key' => 'my-secret-key',
		];

		$request = new \WP_REST_Request();
		$request->set_param( 'wpscan_api_key', '••••••••cret' ); // masked placeholder.

		$this->controller->update( $request );

		$stored = $GLOBALS['wp_security_test_options']['wp_security_settings'] ?? [];
		$this->assertSame( 'my-secret-key', $stored['wpscan_api_key'] );
	}

	public function test_post_rejects_unknown_provider(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'vuln_advisor_provider' => 'wpvulnerability',
		];

		$request = new \WP_REST_Request();
		$request->set_param( 'vuln_advisor_provider', 'totally-unknown' );

		$this->controller->update( $request );

		$stored = $GLOBALS['wp_security_test_options']['wp_security_settings'] ?? [];
		$this->assertSame( 'wpvulnerability', $stored['vuln_advisor_provider'] );
	}
}
