<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Thrown when the container encounters an error while resolving a binding.
 *
 * Implements the PSR-11 ContainerExceptionInterface so consumers can catch
 * container errors against the standard interface rather than this concrete
 * class.
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {}
