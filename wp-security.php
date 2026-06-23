<?php

/**
 * Plugin Name: WP Security
 * Plugin URI:  https://github.com/dgaitan/wp-security
 * Description: An all-in-one auditing suite that inspects a WordPress installation across nine domains and surfaces prioritized, actionable findings through a powered dashboard.
 * Version:     0.1.0
 * Author:      David Gaitan
 * Author URI:  https://profiles.wordpress.org/david-gaitan/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-security
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Tested up to: 6.9
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'WP_SECURITY_VERSION', '0.1.0' );
define( 'WP_SECURITY_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_SECURITY_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_SECURITY_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader.
if ( file_exists( WP_SECURITY_DIR . 'vendor/autoload.php' ) ) {
    require_once WP_SECURITY_DIR . 'vendor/autoload.php';
}

// Activation / deactivation hooks must be registered at the top level.
register_activation_hook( __FILE__, static function (): void {
    if ( class_exists( \WPSecurity\Plugin::class ) ) {
        \WPSecurity\Plugin::activate();
    }
} );

register_deactivation_hook( __FILE__, static function (): void {
    if ( class_exists( \WPSecurity\Plugin::class ) ) {
        \WPSecurity\Plugin::deactivate();
    }
} );

// Boot the plugin after all plugins are loaded so service providers can depend on other plugins.
add_action( 'plugins_loaded', static function (): void {
    if ( ! class_exists( \WPSecurity\Plugin::class ) ) {
        return;
    }
    \WPSecurity\Plugin::instance()->boot();
}, 10 );
