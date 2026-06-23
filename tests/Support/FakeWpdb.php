<?php

/**
 * In-memory wpdb test double.
 *
 * Models just enough of wpdb for the persistence layer's unit tests: rows are
 * stored per table, INSERTs auto-increment an id and populate $insert_id, and
 * the SELECT helpers interpret the small, known shape of queries this plugin
 * issues (filter by id / run_id / module_id / status, ORDER BY, LIMIT) without
 * a real SQL engine.  It is deliberately not a general-purpose SQL emulator.
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Support;

use wpdb;

final class FakeWpdb extends wpdb {

	/** @var array<string, array<int, array<string, mixed>>> Rows keyed by table name. */
	private array $store = [];

	/** @var array<string, int> Auto-increment counters keyed by table name. */
	private array $autoIncrement = [];

	/** @var array<int, string> Every raw query() string, in order (for assertions). */
	public array $queries = [];

	public function __construct( string $prefix = 'wp_' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Interpolate WordPress placeholders so id/values surface in the final query
	 * string the SELECT helpers parse.  Handles %d, %s, and %f in order.
	 *
	 * @param mixed ...$args
	 */
	public function prepare( string $query, ...$args ): string {
		$index = 0;
		return (string) preg_replace_callback(
			'/%[dsf]/',
			static function ( array $match ) use ( &$index, $args ): string {
				$value = $args[ $index ] ?? '';
				++$index;
				return match ( $match[0] ) {
					'%d'    => (string) (int) $value,
					'%f'    => (string) (float) $value,
					default => "'" . (string) $value . "'",
				};
			},
			$query
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<int, string>|string|null $format
	 * @return int|false
	 */
	public function insert( string $table, array $data, $format = null ) {
		$id                            = ( $this->autoIncrement[ $table ] ?? 0 ) + 1;
		$this->autoIncrement[ $table ] = $id;
		$this->insert_id               = $id;

		$row       = $data;
		$row['id'] = $id;

		$this->store[ $table ][] = $row;

		return 1;
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $where
	 * @param array<int, string>|string|null $format
	 * @param array<int, string>|string|null $where_format
	 * @return int|false
	 */
	public function update( string $table, array $data, array $where, $format = null, $where_format = null ) {
		$affected = 0;
		foreach ( $this->store[ $table ] ?? [] as $i => $row ) {
			foreach ( $where as $column => $value ) {
				if ( (string) ( $row[ $column ] ?? '' ) !== (string) $value ) {
					continue 2;
				}
			}
			$this->store[ $table ][ $i ] = array_merge( $row, $data );
			++$affected;
		}
		return $affected;
	}

	/**
	 * Handles the single raw query the repositories issue: an atomic progress
	 * increment of the form "... SET progress = progress + 1 WHERE id = N".
	 *
	 * @return int|false
	 */
	public function query( string $query ) {
		$this->queries[] = $query;

		$table = $this->tableFromQuery( $query );
		if ( null === $table ) {
			return 0;
		}

		if ( preg_match( '/progress\s*=\s*progress\s*\+\s*1/i', $query ) && preg_match( '/\bid = (\d+)/', $query, $m ) ) {
			$id = (int) $m[1];
			foreach ( $this->store[ $table ] ?? [] as $i => $row ) {
				if ( (int) ( $row['id'] ?? 0 ) === $id ) {
					$this->store[ $table ][ $i ]['progress'] = (int) ( $row['progress'] ?? 0 ) + 1;
					return 1;
				}
			}
		}

		return 0;
	}

	/**
	 * @return array<string, mixed>|object|null
	 */
	public function get_row( string $query, string $output = ARRAY_A, int $y = 0 ) {
		$table = $this->tableFromQuery( $query );
		if ( null === $table ) {
			return null;
		}

		$rows = $this->store[ $table ] ?? [];
		if ( preg_match( '/\bid = (\d+)/', $query, $m ) ) {
			$id = (int) $m[1];
			foreach ( $rows as $row ) {
				if ( (int) ( $row['id'] ?? 0 ) === $id ) {
					return $row;
				}
			}
			return null;
		}

		return $rows[0] ?? null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_results( string $query, string $output = ARRAY_A ): array {
		$table = $this->tableFromQuery( $query );
		if ( null === $table ) {
			return [];
		}

		$rows = $this->store[ $table ] ?? [];

		if ( preg_match( '/run_id = (\d+)/', $query, $m ) ) {
			$runId = (int) $m[1];
			$rows  = array_values( array_filter( $rows, static fn ( array $r ): bool => (int) ( $r['run_id'] ?? 0 ) === $runId ) );
		}

		if ( preg_match( "/module_id = '([^']*)'/", $query, $m ) ) {
			$moduleId = $m[1];
			$rows     = array_values( array_filter( $rows, static fn ( array $r ): bool => (string) ( $r['module_id'] ?? '' ) === $moduleId ) );
		}

		if ( preg_match( '/status IN \(([^)]*)\)/i', $query, $m ) ) {
			$statuses = array_map( static fn ( string $s ): string => trim( $s, " '" ), explode( ',', $m[1] ) );
			$rows     = array_values( array_filter( $rows, static fn ( array $r ): bool => in_array( (string) ( $r['status'] ?? '' ), $statuses, true ) ) );
		}

		if ( preg_match( '/ORDER BY id DESC/i', $query ) ) {
			usort( $rows, static fn ( array $a, array $b ): int => (int) $b['id'] <=> (int) $a['id'] );
		}

		if ( preg_match( '/LIMIT (\d+)/', $query, $m ) ) {
			$rows = array_slice( $rows, 0, (int) $m[1] );
		}

		return $rows;
	}

	/**
	 * Match the prefixed table name against the registered table buckets.
	 */
	private function tableFromQuery( string $query ): ?string {
		$known = [
			$this->prefix . 'wpsec_findings',
			$this->prefix . 'wpsec_scan_runs',
			$this->prefix . 'wpsec_logins',
		];
		foreach ( $known as $table ) {
			if ( str_contains( $query, $table ) ) {
				return $table;
			}
		}
		return null;
	}
}
