<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\Server;

use WPSecurity\Contracts\Module;

/**
 * Server Health module.
 *
 * Confirms the hosting environment is current, capable, and correctly
 * configured.  This is the reference implementation of the Module contract
 * and the first complete vertical slice (Sprint 4).
 *
 * Checks:
 *   - PHP version (supported/secure — 8.1+ pass, EOL fail)
 *   - Memory limits (WP_MEMORY_LIMIT and memory_limit)
 *   - Required PHP extensions (curl, mbstring, gd/imagick, etc.)
 *   - OPcache enabled and sized
 *   - Persistent object cache present
 *   - HTTPS / TLS
 *   - Cron mode (DISABLE_WP_CRON + real system cron)
 *   - Disk space on install partition
 *   - Write permissions on wp-content / uploads
 *
 * TODO Sprint 4: implement all Check classes and register them below.
 */
class ServerModule implements Module {

    public function id(): string {
        return 'server';
    }

    public function label(): string {
        return __( 'Server Health', 'wp-security' );
    }

    public function icon(): string {
        return 'dashicons-desktop';
    }

    /**
     * @return iterable<\WPSecurity\Contracts\Check>
     */
    public function checks(): iterable {
        $checks = [];

        // TODO Sprint 4: add Check instances.
        // $checks[] = new Checks\PhpVersionCheck();
        // $checks[] = new Checks\MemoryLimitCheck();
        // $checks[] = new Checks\RequiredExtensionsCheck();
        // $checks[] = new Checks\OpcacheCheck();
        // $checks[] = new Checks\ObjectCacheCheck();
        // $checks[] = new Checks\HttpsCheck();
        // $checks[] = new Checks\CronModeCheck();
        // $checks[] = new Checks\DiskSpaceCheck();
        // $checks[] = new Checks\WritePermissionsCheck();

        /**
         * Allow third-party code to add checks to the Server module.
         *
         * @param array<\WPSecurity\Contracts\Check> $checks
         */
        return apply_filters( 'wp_security/checks/server', $checks );
    }
}
