<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

/**
 * Gateway for the `{prefix}_wpsec_scan_runs` table.
 *
 * All database access uses $wpdb->prepare() — no string-built SQL.
 *
 * Schema (created in Sprint 2 via dbDelta):
 *   id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT
 *   module_id     VARCHAR(64) NULL     (nullable → full scan)
 *   status        VARCHAR(16) NOT NULL DEFAULT 'queued'
 *                 (queued|running|complete|failed)
 *   overall_score TINYINT UNSIGNED NULL
 *   started_at    DATETIME NULL
 *   finished_at   DATETIME NULL
 *   PRIMARY KEY (id)
 *   KEY status (status)
 *
 * TODO Sprint 2: implement all methods.
 */
class ScanRunRepository {

	public function create( ?string $moduleId ): int {
		// TODO Sprint 2.
		throw new \RuntimeException( 'ScanRunRepository::create() not yet implemented.' );
	}

	public function updateStatus( int $id, string $status ): void {
		// TODO Sprint 2.
	}

	public function updateScore( int $id, int $score ): void {
		// TODO Sprint 2.
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		// TODO Sprint 2.
		return null;
	}

	/**
	 * @return array<array<string, mixed>>
	 */
	public function history( int $limit = 30 ): array {
		// TODO Sprint 2.
		return [];
	}
}
