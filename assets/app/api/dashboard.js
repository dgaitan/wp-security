import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch the aggregated dashboard payload from the REST API.
 *
 * @returns {Promise<{overall_score: number|null, module_scores: Object, top_findings: Array, last_scan_at: string|null, trend: Array}>}
 */
export function fetchDashboard() {
	return apiFetch( { path: '/wp-security/v1/dashboard' } );
}
