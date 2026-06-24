<?php

declare( strict_types=1 );

namespace WPSecurity\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WPSecurity\Persistence\ScanRunRepository;

/**
 * GET /wp-security/v1/dashboard
 *
 * Returns the aggregated dashboard payload:
 *   - overall_score  (int + grade)
 *   - module_scores  (per-module scores, keyed by module ID)
 *   - top_findings   (highest-severity findings across all modules)
 *   - last_scan_at   (ISO 8601 timestamp of the most recent completed run)
 *   - trend          (array of { date, score } for the sparkline, oldest first)
 */
class DashboardController extends AbstractController {

	public function __construct( private ?ScanRunRepository $runs = null ) {}

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/dashboard',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get' ],
				'permission_callback' => [ $this, 'permissionCheck' ],
			]
		);
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$trend = $this->buildTrend();

		return $this->respond(
			[
				'overall_score' => null,
				'module_scores' => [],
				'top_findings'  => [],
				'last_scan_at'  => null,
				'trend'         => $trend,
			]
		);
	}

	/**
	 * Build the trend array from the last 30 completed scan runs, oldest first.
	 *
	 * @return array<int, array{date: string, score: int}>
	 */
	private function buildTrend(): array {
		if ( null === $this->runs ) {
			return [];
		}

		$trend = [];
		foreach ( $this->runs->history( 30 ) as $run ) {
			if ( 'complete' !== $run['status'] || null === $run['overall_score'] ) {
				continue;
			}
			$trend[] = [
				'date'  => (string) ( $run['finished_at'] ?? $run['started_at'] ?? '' ),
				'score' => (int) $run['overall_score'],
			];
		}

		return array_reverse( $trend ); // oldest → newest for chart x-axis.
	}
}
