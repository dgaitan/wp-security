<?php

declare( strict_types=1 );

namespace WPSecurity\Contracts;

/**
 * A Module maps one-to-one to a dashboard navigation section.
 *
 * It is a thin container that declares its identity and yields an iterable
 * of Check objects.  Modules are registered — not hard-coded — through the
 * `wp_security/modules` filter so third-party code can add whole sections
 * without touching plugin core.
 */
interface Module {

    /**
     * Stable, URL-safe identifier, e.g. "server".
     */
    public function id(): string;

    /**
     * Human-readable label shown in the navigation, e.g. "Server Health".
     */
    public function label(): string;

    /**
     * Dashicon slug or inline SVG key used in the sidebar, e.g. "dashicons-shield".
     */
    public function icon(): string;

    /**
     * Return all Check objects that belong to this module.
     *
     * @return iterable<Check>
     */
    public function checks(): iterable;
}
