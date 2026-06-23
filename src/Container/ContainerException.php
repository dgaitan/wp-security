<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use RuntimeException;

/**
 * Thrown when the container cannot resolve a binding.
 *
 * Implements Psr\Container\ContainerExceptionInterface once psr/container
 * is installed via Composer (Sprint 1).
 *
 * TODO Sprint 1: add `implements \Psr\Container\ContainerExceptionInterface`.
 */
class ContainerException extends RuntimeException {}
