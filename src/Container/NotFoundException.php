<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when no binding exists for the requested identifier.
 *
 * Extends ContainerException (so callers catching the broader container error
 * still work) and implements the PSR-11 NotFoundExceptionInterface, which
 * Container::get() is required to throw when an ID has no binding.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}
