<?php

/**
 * Unit tests for AdminPage.
 *
 * Feature: AdminPage — React SPA mount (Sprint 3)
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: register() wires admin_menu and admin_enqueue_scripts hooks
 *     Given AdminPage is instantiated
 *     When register() is called
 *     Then the admin_menu action has addMenuPage as a callback
 *     And the admin_enqueue_scripts action has enqueueAssets as a callback
 *
 *   Scenario: enqueueAssets() registers and enqueues the script on the correct hook
 *     Given the hook is "toplevel_page_wp-security"
 *     When enqueueAssets("toplevel_page_wp-security") is called
 *     Then wp_register_script is called with handle "wp-security-app"
 *     And wp_enqueue_script is called with "wp-security-app"
 *     And wp_add_inline_script is called with bootstrap data containing restRoot and nonce
 *
 *   Scenario: enqueueAssets() is a no-op on other admin pages
 *     Given the hook is "index_page_dashboard"
 *     When enqueueAssets("index_page_dashboard") is called
 *     Then wp_register_script is NOT called
 *     And wp_enqueue_script is NOT called
 *
 *   Scenario: renderPage() outputs the React mount point
 *     Given the current user has "manage_options" capability
 *     When renderPage() is called
 *     Then the output contains '<div id="wp-security-root">'
 *
 *   Scenario: renderPage() calls wp_die when user lacks capability
 *     Given the current user does NOT have "manage_options" capability
 *     When renderPage() is called
 *     Then a RuntimeException is thrown
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use WPSecurity\Admin\AdminPage;

final class AdminPageTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_security_test_filters']            = [];
		$GLOBALS['wp_security_test_registered_scripts'] = [];
		$GLOBALS['wp_security_test_enqueued_scripts']   = [];
		$GLOBALS['wp_security_test_inline_scripts']     = [];
		$GLOBALS['wp_security_test_registered_styles']  = [];
		$GLOBALS['wp_security_test_enqueued_styles']    = [];
		$GLOBALS['wp_security_test_can']                = [];
	}

	protected function tearDown(): void {
		remove_all_filters( 'admin_menu' );
		remove_all_filters( 'admin_enqueue_scripts' );
		parent::tearDown();
	}

	public function test_register_wires_admin_menu_hook(): void {
		$page = new AdminPage();
		$page->register();

		$filters = $GLOBALS['wp_security_test_filters'];
		$this->assertArrayHasKey( 'admin_menu', $filters );
	}

	public function test_register_wires_admin_enqueue_scripts_hook(): void {
		$page = new AdminPage();
		$page->register();

		$filters = $GLOBALS['wp_security_test_filters'];
		$this->assertArrayHasKey( 'admin_enqueue_scripts', $filters );
	}

	public function test_enqueue_assets_registers_script_on_correct_hook(): void {
		$page = new AdminPage();
		$page->enqueueAssets( 'toplevel_page_wp-security' );

		$handles = array_column( $GLOBALS['wp_security_test_registered_scripts'], 'handle' );
		$this->assertContains( 'wp-security-app', $handles );
	}

	public function test_enqueue_assets_enqueues_script_on_correct_hook(): void {
		$page = new AdminPage();
		$page->enqueueAssets( 'toplevel_page_wp-security' );

		$this->assertContains( 'wp-security-app', $GLOBALS['wp_security_test_enqueued_scripts'] );
	}

	public function test_enqueue_assets_adds_inline_bootstrap_data(): void {
		$page = new AdminPage();
		$page->enqueueAssets( 'toplevel_page_wp-security' );

		$this->assertNotEmpty( $GLOBALS['wp_security_test_inline_scripts'] );
		$inline = $GLOBALS['wp_security_test_inline_scripts'][0];
		$this->assertSame( 'wp-security-app', $inline['handle'] );
		$this->assertStringContainsString( 'restRoot', $inline['data'] );
		$this->assertStringContainsString( 'nonce', $inline['data'] );
	}

	public function test_enqueue_assets_is_noop_on_other_pages(): void {
		$page = new AdminPage();
		$page->enqueueAssets( 'index_page_dashboard' );

		$this->assertEmpty( $GLOBALS['wp_security_test_registered_scripts'] );
		$this->assertEmpty( $GLOBALS['wp_security_test_enqueued_scripts'] );
	}

	public function test_render_page_outputs_mount_div(): void {
		$GLOBALS['wp_security_test_can']['manage_options'] = true;

		$page = new AdminPage();

		ob_start();
		$page->renderPage();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '<div id="wp-security-root">', $output );
	}

	public function test_render_page_dies_without_capability(): void {
		$GLOBALS['wp_security_test_can']['manage_options'] = false;

		$page = new AdminPage();

		$this->expectException( \RuntimeException::class );
		$page->renderPage();
	}
}
