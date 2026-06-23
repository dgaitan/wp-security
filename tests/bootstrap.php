<?php

/**
 * PHPUnit bootstrap for unit tests.
 *
 * Unit tests run without a WordPress installation — they test domain logic
 * (Finding, Status, Severity, Score, ScoringService) against a mocked Context.
 *
 * Integration tests (tests/Integration/) require the WordPress test suite.
 * See tests/Integration/bootstrap.php once that suite is added.
 *
 * TODO Sprint 1: configure the Yoast PHPUnit polyfills path.
 */

declare( strict_types=1 );

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Minimal WordPress stubs for unit tests — keeps the autoloader from blowing
// up on functions like get_option(), apply_filters(), etc.
require_once __DIR__ . '/stubs/wordpress-stubs.php';

// Runtime class stubs (wpdb, WP_REST_*, WP_Error).  Loaded only here, never by
// the PHPStan bootstrap, so they don't clash with the analyser's WordPress stubs.
require_once __DIR__ . '/stubs/wp-class-stubs.php';
