<?php

/**
 * PHPStan bootstrap — loads stubs so the analyser can resolve WordPress
 * function signatures without a real WordPress installation.
 *
 * The actual WordPress stubs come from szepeviktor/phpstan-wordpress which is
 * auto-registered by phpstan/extension-installer; this file only loads the
 * minimal test-suite stubs needed for the domain classes.
 */

declare( strict_types=1 );

require_once __DIR__ . '/stubs/wordpress-stubs.php';
require_once __DIR__ . '/stubs/third-party-plugins.stub.php';
