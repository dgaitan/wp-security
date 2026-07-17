<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use wpdb;

/**
 * Gateway for the `{prefix}_wpsec_maintenance_notes` table — free-text
 * fields (time spent, client notes, follow-up notes) an admin attaches to a
 * maintenance review. Kept as its own table rather than widening
 * `wpsec_scan_runs`, which is the generic engine for every scan type, not
 * just maintenance reviews.
 */
class MaintenanceNotesRepository {

	public function __construct( private wpdb $wpdb ) {}

	private function table(): string {
		return $this->wpdb->prefix . 'wpsec_maintenance_notes';
	}

	/**
	 * @param array{run_id: ?int, time_spent_minutes: ?int, client_notes: string, follow_up_notes: string} $data
	 * @return int The inserted row's id.
	 */
	public function save( array $data, int $createdBy ): int {
		$this->wpdb->insert(
			$this->table(),
			[
				'run_id'             => $data['run_id'],
				'time_spent_minutes' => $data['time_spent_minutes'],
				'client_notes'       => $data['client_notes'],
				'follow_up_notes'    => $data['follow_up_notes'],
				'created_by'         => $createdBy,
				'created_at'         => (string) current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s', '%s', '%d', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * The most recently saved maintenance notes row, or null if none exist yet.
	 *
	 * @return array<string, mixed>|null
	 */
	public function latest(): ?array {
		$table = $this->table();
		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d", 1 ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return is_array( $row ) ? $this->mapRow( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function mapRow( array $row ): array {
		return [
			'id'                 => (int) ( $row['id'] ?? 0 ),
			'run_id'             => null !== ( $row['run_id'] ?? null ) ? (int) $row['run_id'] : null,
			'time_spent_minutes' => null !== ( $row['time_spent_minutes'] ?? null ) ? (int) $row['time_spent_minutes'] : null,
			'client_notes'       => (string) ( $row['client_notes'] ?? '' ),
			'follow_up_notes'    => (string) ( $row['follow_up_notes'] ?? '' ),
			'created_by'         => (int) ( $row['created_by'] ?? 0 ),
			'created_at'         => (string) ( $row['created_at'] ?? '' ),
		];
	}
}
