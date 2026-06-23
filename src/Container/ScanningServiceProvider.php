<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use WPSecurity\Admin\ModuleRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\Scanner;
use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\ScanRunRepository;
use WPSecurity\Scanning\ScanManager;
use WPSecurity\Scanning\Scheduler;
use WPSecurity\Scoring\ScoringService;

/**
 * Wires the scanning layer: the ScanManager (bound to the Scanner contract) and
 * the recurring-scan Scheduler.
 */
final class ScanningServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton(
			ScanManager::class,
			static fn ( Container $c ): ScanManager => new ScanManager(
				$c->get( ModuleRegistry::class ),
				$c->get( ScanRunRepository::class ),
				$c->get( FindingRepository::class ),
				$c->get( ScoringService::class ),
				$c->get( Context::class ),
			)
		);

		$this->container->singleton(
			Scanner::class,
			static fn ( Container $c ): Scanner => $c->get( ScanManager::class )
		);

		$this->container->singleton(
			Scheduler::class,
			static fn (): Scheduler => new Scheduler()
		);
	}
}
