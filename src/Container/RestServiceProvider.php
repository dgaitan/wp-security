<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Admin\RemediationRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\Scanner;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\MaintenanceNotesRepository;
use WPSecurity\Persistence\RemediationLogRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Rest\DashboardController;
use WPSecurity\Rest\MaintenanceReportController;
use WPSecurity\Rest\ModulesController;
use WPSecurity\Rest\RemediationsController;
use WPSecurity\Rest\ScansController;
use WPSecurity\Rest\SettingsController;

/**
 * Wires the REST controllers into the container.
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

		$this->container->singleton(
			DashboardController::class,
			static fn ( Container $c ): DashboardController => new DashboardController(
				$c->get( ScanRunRepository::class )
			)
		);

		$this->container->singleton(
			ModulesController::class,
			static fn ( Container $c ): ModulesController => new ModulesController(
				$c->get( ModuleRegistry::class ),
				$c->get( FindingRepository::class ),
				$c->get( ScanRunRepository::class ),
			)
		);

		$this->container->singleton(
			SettingsController::class,
			static fn (): SettingsController => new SettingsController()
		);

		$this->container->singleton(
			RemediationsController::class,
			static fn ( Container $c ): RemediationsController => new RemediationsController(
				$c->get( RemediationRegistry::class ),
				$c->get( RemediationLogRepository::class ),
				$c->get( Context::class ),
			)
		);

		$this->container->singleton(
			MaintenanceReportController::class,
			static fn ( Container $c ): MaintenanceReportController => new MaintenanceReportController(
				$c->get( RemediationLogRepository::class ),
				$c->get( FindingRepository::class ),
				$c->get( MaintenanceNotesRepository::class ),
			)
		);
	}
}
