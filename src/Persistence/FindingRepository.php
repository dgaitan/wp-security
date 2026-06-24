<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use WPSecurity\Domain\Finding;
use wpdb;

/**
 * Gateway for the `{prefix}_wpsec_findings` table.
 *
 * All value binding uses $wpdb->prepare(); the only interpolated token is the
 * prefixed table name, derived from $wpdb->prefix.  Evidence is stored as JSON
 * and decoded back to an array on read.  Rows never leave this class raw.
 */
class FindingRepository {

	public function __construct( private wpdb $wpdb ) {}

	private function table(): string {
		return $this->wpdb->prefix . 'wpsec_findings';
	}

	/**
	 * Persist a Finding for a specific run and module.
	 */
	public function save( int $runId, string $moduleId, Finding $finding ): void {
		$encodedEvidence = wp_json_encode( $finding->evidence );

		$this->wpdb->insert(
			$this->table(),
			[
				'run_id'         => $runId,
				'module_id'      => $moduleId,
				'check_id'       => $finding->checkId,
				'status'         => $finding->status->value,
				'severity'       => $finding->severity->value,
				'title'          => $finding->title,
				'description'    => $finding->description,
				'recommendation' => $finding->recommendation,
				'evidence'       => is_string( $encodedEvidence ) ? $encodedEvidence : null,
				'docs_url'       => $finding->docsUrl,
				'created_at'     => (string) current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * All findings for a run, optionally narrowed to one module, oldest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function forRun( int $runId, ?string $moduleId = null ): array {
		$table = $this->table();
		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		if ( null === $moduleId ) {
			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE run_id = %d ORDER BY id ASC", $runId ),
				ARRAY_A
			);
		} else {
			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE run_id = %d AND module_id = %s ORDER BY id ASC", $runId, $moduleId ),
				ARRAY_A
			);
		}
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map( [ $this, 'mapRow' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * All findings from the most recent run that contains findings for the given module.
	 * Returns an empty array when no scan has been run for that module yet.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function latestByModule( string $moduleId ): array {
		$table = $this->table();

		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM `{$table}`
				 WHERE module_id = %s
				   AND run_id = (SELECT MAX(run_id) FROM `{$table}` WHERE module_id = %s)
				 ORDER BY id ASC",
				$moduleId,
				$moduleId
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map( [ $this, 'mapRow' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * The highest-severity open (WARN/FAIL) findings across all runs.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function topFindings( int $limit = 10 ): array {
		$table = $this->table();

		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM `{$table}`
				 WHERE status IN ('warn', 'fail')
				 ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low', 'info'), id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map( [ $this, 'mapRow' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Normalise a raw row into a typed, client-safe shape with decoded evidence.
	 *
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function mapRow( array $row ): array {
		$evidence = [];
		if ( ! empty( $row['evidence'] ) && is_string( $row['evidence'] ) ) {
			$decoded  = json_decode( $row['evidence'], true );
			$evidence = is_array( $decoded ) ? $decoded : [];
		}

		return [
			'id'             => (int) ( $row['id'] ?? 0 ),
			'run_id'         => (int) ( $row['run_id'] ?? 0 ),
			'module_id'      => (string) ( $row['module_id'] ?? '' ),
			'check_id'       => (string) ( $row['check_id'] ?? '' ),
			'status'         => (string) ( $row['status'] ?? '' ),
			'severity'       => (string) ( $row['severity'] ?? '' ),
			'title'          => (string) ( $row['title'] ?? '' ),
			'description'    => (string) ( $row['description'] ?? '' ),
			'recommendation' => (string) ( $row['recommendation'] ?? '' ),
			'evidence'       => $evidence,
			'docs_url'       => null !== ( $row['docs_url'] ?? null ) ? (string) $row['docs_url'] : null,
			'created_at'     => (string) ( $row['created_at'] ?? '' ),
		];
	}
}
