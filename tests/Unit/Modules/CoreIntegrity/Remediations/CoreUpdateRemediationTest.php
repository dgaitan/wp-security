<?php
/*
 * Feature: CoreUpdateRemediation — Sprint 9
 *
 * Scenario: isAvailable() is false when the opt-in setting is disabled,
 *   even if a core update is available.
 *
 * Scenario: isAvailable() is true only when the setting is enabled AND
 *   core_update_available.response is "upgrade".
 *
 * Note: apply() exercises WordPress core's real Core_Upgrader, covered by
 * the manual REST smoke test, not by PHPUnit here — see PluginUpdateRemediationTest.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\CoreIntegrity\Remediations;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\CoreIntegrity\Remediations\CoreUpdateRemediation;
use WPSecurity\Tests\Support\MockContext;

final class CoreUpdateRemediationTest extends TestCase {

	private CoreUpdateRemediation $action;

	protected function setUp(): void {
		parent::setUp();
		$this->action                        = new CoreUpdateRemediation();
		$GLOBALS['wp_security_test_options'] = [];
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_security_test_options'] );
		parent::tearDown();
	}

	public function test_id_is_stable(): void {
		$this->assertSame( 'core_integrity.update_core', $this->action->id() );
	}

	public function test_capability_is_update_core(): void {
		$this->assertSame( 'update_core', $this->action->capability() );
	}

	public function test_is_available_false_when_setting_disabled(): void {
		$ctx = new MockContext(
			values: [
				'core_update_available' => [
					'current'  => '6.4.0',
					'latest'   => '6.5.0',
					'response' => 'upgrade',
				],
			]
		);

		$this->assertFalse( $this->action->isAvailable( $ctx, [] ) );
	}

	public function test_is_available_true_when_setting_enabled_and_upgrade_available(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'enable_core_update_remediation' => true,
		];
		$ctx = new MockContext(
			values: [
				'core_update_available' => [
					'current'  => '6.4.0',
					'latest'   => '6.5.0',
					'response' => 'upgrade',
				],
			]
		);

		$this->assertTrue( $this->action->isAvailable( $ctx, [] ) );
	}

	public function test_is_available_false_when_enabled_but_no_update(): void {
		$GLOBALS['wp_security_test_options']['wp_security_settings'] = [
			'enable_core_update_remediation' => true,
		];
		$ctx = new MockContext( values: [ 'core_update_available' => null ] );

		$this->assertFalse( $this->action->isAvailable( $ctx, [] ) );
	}
}
