<?php

declare( strict_types=1 );

namespace WPSecurity\Container;

use WPSecurity\Persistence\FindingRepository;
use WPSecurity\Persistence\MaintenanceNotesRepository;
use WPSecurity\Persistence\Migrator;
use WPSecurity\Persistence\RemediationLogRepository;
use WPSecurity\Persistence\ScanRunRepository;

/**
 * Wires the persistence layer (migrator + repositories) into the container.
 *
 * Each binding resolves the global $wpdb lazily, so the repositories are only
 * constructed when first needed — by which point WordPress has populated $wpdb.
 */
final class PersistenceServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton(
			Migrator::class,
			static function (): Migrator {
				global $wpdb;
				return new Migrator( $wpdb );
			}
		);

		$this->container->singleton(
			ScanRunRepository::class,
			static function (): ScanRunRepository {
				global $wpdb;
				return new ScanRunRepository( $wpdb );
			}
		);

		$this->container->singleton(
			FindingRepository::class,
			static function (): FindingRepository {
				global $wpdb;
				return new FindingRepository( $wpdb );
			}
		);

		$this->container->singleton(
			RemediationLogRepository::class,
			static function (): RemediationLogRepository {
				global $wpdb;
				return new RemediationLogRepository( $wpdb );
			}
		);

		$this->container->singleton(
			MaintenanceNotesRepository::class,
			static function (): MaintenanceNotesRepository {
				global $wpdb;
				return new MaintenanceNotesRepository( $wpdb );
			}
		);
	}
}
