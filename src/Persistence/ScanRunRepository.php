<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use wpdb;

/**
 * Gateway for the `{prefix}_wpsec_scan_runs` table.
 *
 * All value binding uses $wpdb->prepare(); the only interpolated token is the
 * prefixed table name, which is derived from $wpdb->prefix and never from user
 * input.  Rows never leave this class raw — every read is mapped to a typed,
 * predictable array shape.
 *
 * Schema:
 *   id            BIGINT UNSIGNED PK
 *   module_id     VARCHAR(64) NULL  (null → full scan)
 *   status        VARCHAR(16) DEFAULT 'queued' (queued|running|complete|failed)
 *   overall_score TINYINT UNSIGNED NULL
 *   progress      SMALLINT UNSIGNED  (modules completed so far)
 *   total         SMALLINT UNSIGNED  (modules in this run)
 *   started_at    DATETIME NULL
 *   finished_at   DATETIME NULL
 */
class ScanRunRepository {

	public function __construct( private wpdb $wpdb ) {}

	private function table(): string {
		return $this->wpdb->prefix . 'wpsec_scan_runs';
	}

	/**
	 * Insert a queued run and return its new ID.
	 */
	public function create( ?string $moduleId ): int {
		$this->wpdb->insert(
			$this->table(),
			[
				'module_id'  => $moduleId,
				'status'     => 'queued',
				'progress'   => 0,
				'total'      => 0,
				'started_at' => (string) current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%d', '%d', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Record how many modules this run will scan.
	 */
	public function setTotal( int $id, int $total ): void {
		$this->wpdb->update(
			$this->table(),
			[ 'total' => $total ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Move the run to a new lifecycle status, stamping finished_at on terminal states.
	 */
	public function updateStatus( int $id, string $status ): void {
		$data   = [ 'status' => $status ];
		$format = [ '%s' ];

		if ( 'complete' === $status || 'failed' === $status ) {
			$data['finished_at'] = (string) current_time( 'mysql', true );
			$format[]            = '%s';
		}

		$this->wpdb->update( $this->table(), $data, [ 'id' => $id ], $format, [ '%d' ] );
	}

	/**
	 * Store the computed overall score for the run.
	 */
	public function updateScore( int $id, int $score ): void {
		$this->wpdb->update(
			$this->table(),
			[ 'overall_score' => $score ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Atomically advance the progress counter by one completed module.
	 */
	public function incrementProgress( int $id ): void {
		$table = $this->table();

		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$this->wpdb->query(
			$this->wpdb->prepare( "UPDATE `{$table}` SET progress = progress + 1 WHERE id = %d", $id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Fetch a single run by ID, mapped to a typed array, or null if absent.
	 *
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		$table = $this->table();

		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return is_array( $row ) ? $this->mapRow( $row ) : null;
	}

	/**
	 * The most recent runs, newest first, for history/trend display.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function history( int $limit = 30 ): array {
		$table = $this->table();

		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d", $limit ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map( [ $this, 'mapRow' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Normalise a raw row into a typed, client-safe shape.
	 *
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function mapRow( array $row ): array {
		return [
			'id'            => (int) ( $row['id'] ?? 0 ),
			'module_id'     => null !== ( $row['module_id'] ?? null ) ? (string) $row['module_id'] : null,
			'status'        => (string) ( $row['status'] ?? 'unknown' ),
			'overall_score' => null !== ( $row['overall_score'] ?? null ) ? (int) $row['overall_score'] : null,
			'progress'      => (int) ( $row['progress'] ?? 0 ),
			'total'         => (int) ( $row['total'] ?? 0 ),
			'started_at'    => null !== ( $row['started_at'] ?? null ) ? (string) $row['started_at'] : null,
			'finished_at'   => null !== ( $row['finished_at'] ?? null ) ? (string) $row['finished_at'] : null,
		];
	}
}
