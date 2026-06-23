<?php

declare( strict_types=1 );

namespace WPSecurity\Scanning;

/**
 * Manages recurring scan schedules via Action Scheduler.
 *
 * TODO Sprint 2: implement schedule(), unschedule(), and the recurring
 * Action Scheduler callback that triggers the configured automatic scan
 * (daily / weekly as per plugin settings).
 */
class Scheduler {

    public const ACTION_HOOK = 'wp_security/run_scheduled_scan';

    /**
     * Schedule a recurring scan at the configured frequency.
     * Called from Plugin::activate().
     */
    public function schedule(): void {
        // TODO Sprint 2.
    }

    /**
     * Remove the recurring scan from the Action Scheduler queue.
     * Called from Plugin::deactivate().
     */
    public function unschedule(): void {
        // TODO Sprint 2.
    }
}
