<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use WPSecurity\Admin\AdminPage;
use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Alerting\AlertService;
use WPSecurity\Contracts\Context;
use WPSecurity\Scanning\ScanContext;
use WPSecurity\Scoring\ScoringService;

/**
 * Wires the plugin's core, framework-level services into the container.
 *
 * These are the services that every feature area depends on: the module
 * registry, the scoring engine, and the live scan context.  Feature-specific
 * providers (Admin, Rest, Scanning) are registered alongside this one as those
 * sprints land.
 */
final class CoreServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton(
			AdminPage::class,
			static fn(): AdminPage => new AdminPage()
		);

		$this->container->singleton(
			ModuleRegistry::class,
			static fn(): ModuleRegistry => new ModuleRegistry()
		);

		$this->container->singleton(
			ScoringService::class,
			static fn(): ScoringService => new ScoringService()
		);

		$this->container->singleton(
			Context::class,
			static fn(): Context => new ScanContext()
		);

		$this->container->singleton(
			AlertService::class,
			static fn(): AlertService => new AlertService()
		);
	}
}
