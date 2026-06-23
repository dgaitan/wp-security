<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use WPSecurity\Contracts\Scanner;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\ScansController;

/**
 * Wires the REST controllers into the container.
 *
 * Only the scan-lifecycle controller is registered in Sprint 2; the dashboard,
 * modules, and settings controllers are wired in their respective sprints.
 */
final class RestServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton(
			ScansController::class,
			static fn ( Container $c ): ScansController => new ScansController(
				$c->get( Scanner::class ),
				$c->get( ScanRunRepository::class ),
			)
		);
	}
}
