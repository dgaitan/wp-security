<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use wpdb;

/**
 * Creates and upgrades the plugin's custom database tables using dbDelta().
 *
 * Tables:
 *   {prefix}_wpsec_scan_runs         — one row per scan execution
 *   {prefix}_wpsec_findings          — findings linked to a run
 *   {prefix}_wpsec_logins            — last-login tracking per user
 *   {prefix}_wpsec_remediation_log   — audit trail of applied remediation actions (v2)
 *   {prefix}_wpsec_maintenance_notes — admin-authored Maintenance Report notes (v3)
 *
 * The current schema version is stored in the `wp_security_schema_version`
 * option.  run() is a no-op when the stored version equals SCHEMA_VERSION,
 * so it is safe to call on every activation and on every plugin boot.
 */
class Migrator {

	public const SCHEMA_VERSION = 3;

	public const OPTION_VERSION = 'wp_security_schema_version';

	public function __construct( private wpdb $wpdb ) {}

	/**
	 * Create or upgrade the schema when the stored version is behind.
	 */
	public function run(): void {
		$current = (int) get_option( self::OPTION_VERSION, 0 );

		if ( $current >= self::SCHEMA_VERSION ) {
			return;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $this->tableDefinitions() );

		update_option( self::OPTION_VERSION, self::SCHEMA_VERSION, false );
	}

	/**
	 * The dbDelta-compatible CREATE TABLE statements for every plugin table.
	 *
	 * Exposed (rather than inlined in run()) so the schema can be asserted in
	 * isolation without touching the database.
	 *
	 * @return array<int, string>
	 */
	public function tableDefinitions(): array {
		$charset_collate  = $this->wpdb->get_charset_collate();
		$runs             = $this->wpdb->prefix . 'wpsec_scan_runs';
		$findings         = $this->wpdb->prefix . 'wpsec_findings';
		$logins           = $this->wpdb->prefix . 'wpsec_logins';
		$remediationLog   = $this->wpdb->prefix . 'wpsec_remediation_log';
		$maintenanceNotes = $this->wpdb->prefix . 'wpsec_maintenance_notes';

		return [
			"CREATE TABLE {$runs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				module_id varchar(64) DEFAULT NULL,
				status varchar(16) NOT NULL DEFAULT 'queued',
				overall_score tinyint(3) unsigned DEFAULT NULL,
				progress smallint(5) unsigned NOT NULL DEFAULT 0,
				total smallint(5) unsigned NOT NULL DEFAULT 0,
				started_at datetime DEFAULT NULL,
				finished_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY status (status)
			) {$charset_collate};",

			"CREATE TABLE {$findings} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_id bigint(20) unsigned NOT NULL,
				module_id varchar(64) NOT NULL,
				check_id varchar(128) NOT NULL,
				status varchar(16) NOT NULL,
				severity varchar(16) NOT NULL,
				title varchar(255) NOT NULL,
				description text NOT NULL,
				recommendation text NOT NULL,
				evidence longtext DEFAULT NULL,
				docs_url varchar(512) DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY run_module (run_id, module_id),
				KEY severity_status (severity, status)
			) {$charset_collate};",

			"CREATE TABLE {$logins} (
				user_id bigint(20) unsigned NOT NULL,
				last_login_at datetime DEFAULT NULL,
				last_login_ip varchar(64) DEFAULT NULL,
				login_count int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (user_id)
			) {$charset_collate};",

			"CREATE TABLE {$remediationLog} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				action_id varchar(128) NOT NULL,
				module_id varchar(64) DEFAULT NULL,
				target varchar(255) DEFAULT NULL,
				params longtext DEFAULT NULL,
				status varchar(16) NOT NULL,
				message text NOT NULL,
				before_state longtext DEFAULT NULL,
				after_state longtext DEFAULT NULL,
				user_id bigint(20) unsigned NOT NULL,
				batch_id varchar(64) DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY action_status (action_id, status),
				KEY module_id (module_id),
				KEY batch_id (batch_id)
			) {$charset_collate};",

			"CREATE TABLE {$maintenanceNotes} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_id bigint(20) unsigned DEFAULT NULL,
				time_spent_minutes int(10) unsigned DEFAULT NULL,
				client_notes longtext DEFAULT NULL,
				follow_up_notes longtext DEFAULT NULL,
				created_by bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY run_id (run_id)
			) {$charset_collate};",
		];
	}
}
