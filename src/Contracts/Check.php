<?php

declare( strict_types=1 );

namespace WPSecurity\Contracts;

use WPSecurity\Domain\Finding;

/**
 * A Check is the atomic unit of work.
 *
 * It runs one focused test and returns a single Finding.  Implementations
 * MUST be side-effect free: a check inspects the environment but never
 * mutates it.  All built-in and third-party checks implement this interface,
 * which is what allows the scoring engine and REST serializer to stay
 * completely decoupled from what any individual check does.
 */
interface Check {

    /**
     * Stable, dot-namespaced identifier, e.g. "server.php_version".
     * Must be unique across the entire plugin.
     */
    public function id(): string;

    /**
     * Human-readable label shown in the findings list, e.g. "PHP Version".
     */
    public function label(): string;

    /**
     * Execute the check and return its result.
     *
     * Implementations must not throw; return a Finding with Status::SKIPPED
     * and a description of the failure if the check cannot run.
     */
    public function run( Context $context ): Finding;
}
