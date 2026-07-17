<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use WPSecurity\Domain\RemediationResult;
use wpdb;

/**
 * Gateway for the `{prefix}_wpsec_remediation_log` table.
 *
 * All value binding uses $wpdb->prepare(); the only interpolated token is the
 * prefixed table name, derived from $wpdb->prefix — same pattern as
 * FindingRepository. Rows never leave this class raw.
 */
class RemediationLogRepository {

	public function __construct( private wpdb $wpdb ) {}

	private function table(): string {
		return $this->wpdb->prefix . 'wpsec_remediation_log';
	}

	/**
	 * Persist the outcome of one RemediationAction::apply() call.
	 *
	 * @param array<string, mixed> $params
	 * @return int The inserted row's id.
	 */
	public function save(
		string $actionId,
		?string $moduleId,
		?string $target,
		array $params,
		RemediationResult $result,
		int $userId,
		?string $batchId = null
	): int {
		$encodedParams = wp_json_encode( $params );
		$encodedBefore = null !== $result->beforeState ? wp_json_encode( $result->beforeState ) : null;
		$encodedAfter  = null !== $result->afterState ? wp_json_encode( $result->afterState ) : null;

		$this->wpdb->insert(
			$this->table(),
			[
				'action_id'    => $actionId,
				'module_id'    => $moduleId,
				'target'       => $target,
				'params'       => is_string( $encodedParams ) ? $encodedParams : null,
				'status'       => $result->status->value,
				'message'      => $result->message,
				'before_state' => is_string( $encodedBefore ) ? $encodedBefore : null,
				'after_state'  => is_string( $encodedAfter ) ? $encodedAfter : null,
				'user_id'      => $userId,
				'batch_id'     => $batchId,
				'created_at'   => (string) current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Most recent remediation log entries, newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function recent( int $limit = 20 ): array {
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
	 * All log entries belonging to one bulk-apply batch, oldest first — used
	 * by the REST layer to report per-item progress while a batch is running.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function forBatch( string $batchId ): array {
		$table = $this->table();
		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE batch_id = %s ORDER BY id ASC", $batchId ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map( [ $this, 'mapRow' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Normalise a raw row into a typed, client-safe shape with decoded JSON columns.
	 *
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function mapRow( array $row ): array {
		return [
			'id'           => (int) ( $row['id'] ?? 0 ),
			'action_id'    => (string) ( $row['action_id'] ?? '' ),
			'module_id'    => null !== ( $row['module_id'] ?? null ) ? (string) $row['module_id'] : null,
			'target'       => null !== ( $row['target'] ?? null ) ? (string) $row['target'] : null,
			'params'       => $this->decodeJson( $row['params'] ?? null ),
			'status'       => (string) ( $row['status'] ?? '' ),
			'message'      => (string) ( $row['message'] ?? '' ),
			'before_state' => $this->decodeJson( $row['before_state'] ?? null ),
			'after_state'  => $this->decodeJson( $row['after_state'] ?? null ),
			'user_id'      => (int) ( $row['user_id'] ?? 0 ),
			'batch_id'     => null !== ( $row['batch_id'] ?? null ) ? (string) $row['batch_id'] : null,
			'created_at'   => (string) ( $row['created_at'] ?? '' ),
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function decodeJson( mixed $value ): ?array {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
