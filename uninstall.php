<?php

/**
 * Uninstall routine.
 *
 * Drops all custom tables, options, user meta, and scheduled actions
 * created by WP Security.  Runs only when the plugin is deleted via
 * the WordPress admin — never on deactivation.
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Tables created by the plugin.
$wp_security_tables = [
	$wpdb->prefix . 'wpsec_scan_runs',
	$wpdb->prefix . 'wpsec_findings',
	$wpdb->prefix . 'wpsec_logins',
];

foreach ( $wp_security_tables as $wp_security_table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wp_security_table}`" );
}

// Plugin options.
$wp_security_options = [
	'wp_security_settings',
	'wp_security_db_version',
];

foreach ( $wp_security_options as $wp_security_option ) {
	delete_option( $wp_security_option );
}

// User meta keys.
delete_metadata( 'user', 0, 'wp_security_last_login', '', true );

// Action Scheduler scheduled actions — cancel all wp-security hooks.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( '', [], 'wp-security' );
}

// Database schema version stored separately.
delete_option( 'wp_security_schema_version' );
