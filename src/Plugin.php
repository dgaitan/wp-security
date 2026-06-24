<?php

declare( strict_types=1 );

namespace WPSecurity;

use WPSecurity\Admin\AdminPage;
use WPSecurity\Alerting\AlertService;
use WPSecurity\Container\Container;
use WPSecurity\Container\CoreServiceProvider;
use WPSecurity\Container\PersistenceServiceProvider;
use WPSecurity\Container\RestServiceProvider;
use WPSecurity\Container\ScanningServiceProvider;
use WPSecurity\Container\ServiceProvider;
use WPSecurity\Persistence\Migrator;
use WPSecurity\Rest\DashboardController;
use WPSecurity\Rest\ModulesController;
use WPSecurity\Rest\ScansController;
use WPSecurity\Rest\SettingsController;
use WPSecurity\Modules\Accessibility\AccessibilityModule;
use WPSecurity\Modules\CoreIntegrity\CoreIntegrityModule;
use WPSecurity\Modules\Database\DatabaseModule;
use WPSecurity\Modules\Dns\DnsModule;
use WPSecurity\Modules\Headers\HeadersModule;
use WPSecurity\Modules\Performance\PerformanceModule;
use WPSecurity\Modules\PluginsThemes\PluginsThemesModule;
use WPSecurity\Modules\Seo\SeoModule;
use WPSecurity\Modules\Server\ServerModule;
use WPSecurity\Modules\Users\UsersModule;
use WPSecurity\Scanning\ScanManager;
use WPSecurity\Scanning\Scheduler;

/**
 * Composition root and singleton entry-point.
 *
 * Responsibilities:
 *   1. Build the dependency-injection container once.
 *   2. Let each ServiceProvider register its bindings.
 *   3. Register WordPress hooks in boot() — called on `plugins_loaded`.
 *
 * Nothing else should call `new` on service classes; resolve everything
 * through the container so the dependency graph stays testable.
 */
final class Plugin {

	private static ?self $instance = null;

	private Container $container;

	private bool $booted = false;

	private function __construct() {
		$this->container = new Container();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all service providers and then register WP hooks.
	 * Safe to call multiple times — boots only once.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->registerProviders();
		$this->registerHooks();

		$this->booted = true;
	}

	/**
	 * Retrieve a resolved service from the container.
	 *
	 * @template T
	 * @param class-string<T> $id
	 * @return T
	 */
	public function make( string $id ): mixed {
		return $this->container->get( $id );
	}

	// -------------------------------------------------------------------------
	// Activation / deactivation (called from plugin bootstrap, not from here).
	// -------------------------------------------------------------------------

	public static function activate(): void {
		global $wpdb;

		( new Migrator( $wpdb ) )->run();
		( new Scheduler() )->schedule();

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		( new Scheduler() )->unschedule();

		flush_rewrite_rules();
	}

	// -------------------------------------------------------------------------
	// Internals
	// -------------------------------------------------------------------------

	private function registerProviders(): void {
		$providers = [
			CoreServiceProvider::class,
			PersistenceServiceProvider::class,
			ScanningServiceProvider::class,
			RestServiceProvider::class,
		];

		foreach ( $providers as $providerClass ) {
			/** @var ServiceProvider $provider */
			$provider = new $providerClass( $this->container );
			$provider->register();
		}
	}

	private function registerHooks(): void {
		$container = $this->container;

		// Register built-in audit modules.
		add_filter(
			'wp_security/modules',
			static fn ( array $modules ): array => array_merge(
				$modules,
				[
					new ServerModule(),
					new HeadersModule(),
					new DnsModule(),
					new CoreIntegrityModule(),
					new PluginsThemesModule(),
					new DatabaseModule(),
					new UsersModule(),
					new PerformanceModule(),
					new AccessibilityModule(),
					new SeoModule(),
				]
			)
		);

		// Admin page — React SPA mount and asset enqueueing.
		$container->get( AdminPage::class )->register();

		// REST routes.
		add_action(
			'rest_api_init',
			static function () use ( $container ): void {
				$container->get( ScansController::class )->register();
				$container->get( DashboardController::class )->register();
				$container->get( ModulesController::class )->register();
				$container->get( SettingsController::class )->register();
			}
		);

		// Action Scheduler callbacks.
		add_action(
			ScanManager::ACTION_RUN_MODULE,
			static function ( int $runId, string $moduleId ) use ( $container ): void {
				$container->get( ScanManager::class )->runModuleJob( $runId, $moduleId );
			},
			10,
			2
		);

		add_action(
			Scheduler::ACTION_HOOK,
			static function () use ( $container ): void {
				$container->get( ScanManager::class )->scanAll();
			}
		);

		// Alert on CRITICAL findings when a scan completes.
		add_action(
			'wp_security/scan_complete',
			static function ( int $runId, array $criticalFindings ) use ( $container ): void {
				$container->get( AlertService::class )->maybeAlert( $runId, $criticalFindings );
			},
			10,
			2
		);

		// Load the sample extension so developers can see how third-party modules work.
		$sampleBootstrap = WP_SECURITY_DIR . 'examples/WpSecuritySampleModule/bootstrap.php';
		if ( file_exists( $sampleBootstrap ) ) {
			require_once $sampleBootstrap;
		}
	}
}
