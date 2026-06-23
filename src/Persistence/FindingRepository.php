<?php

declare( strict_types=1 );

namespace WPSecurity\Persistence;

use WPSecurity\Domain\Finding;

/**
 * Gateway for the `{prefix}_wpsec_findings` table.
 *
 * All database access uses $wpdb->prepare() — no string-built SQL.
 *
 * Schema (created in Sprint 2 via dbDelta):
 *   id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT
 *   run_id         BIGINT(20) UNSIGNED NOT NULL
 *   module_id      VARCHAR(64)  NOT NULL
 *   check_id       VARCHAR(128) NOT NULL
 *   status         VARCHAR(16)  NOT NULL
 *   severity       VARCHAR(16)  NOT NULL
 *   title          VARCHAR(255) NOT NULL
 *   description    TEXT         NOT NULL
 *   recommendation TEXT         NOT NULL
 *   evidence       LONGTEXT     NULL (JSON)
 *   docs_url       VARCHAR(512) NULL
 *   created_at     DATETIME     NOT NULL
 *   PRIMARY KEY (id)
 *   KEY run_module (run_id, module_id)
 *   KEY severity_status (severity, status)
 *
 * TODO Sprint 2: implement all methods.
 */
class FindingRepository {

    /**
     * Persist a Finding for a specific run and module.
     */
    public function save( int $runId, string $moduleId, Finding $finding ): void {
        // TODO Sprint 2.
    }

    /**
     * Retrieve all findings for a given run (optionally filtered by module).
     *
     * @return array<array<string, mixed>>
     */
    public function forRun( int $runId, ?string $moduleId = null ): array {
        // TODO Sprint 2.
        return [];
    }

    /**
     * Retrieve the most severe open findings across all runs (for the dashboard).
     *
     * @return array<array<string, mixed>>
     */
    public function topFindings( int $limit = 10 ): array {
        // TODO Sprint 2.
        return [];
    }
}
