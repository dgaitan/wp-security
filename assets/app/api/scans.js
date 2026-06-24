import apiFetch from '@wordpress/api-fetch';

/**
 * Start a full or module-specific scan.
 *
 * @param {string} module  'all' or a module ID such as 'server'.
 * @returns {Promise<{run_id: number}>}
 */
export function startScan( module = 'all' ) {
	return apiFetch( {
		path:   '/wp-security/v1/scans',
		method: 'POST',
		data:   { module },
	} );
}

/**
 * Poll the status of a running scan.
 *
 * @param {number} runId
 * @returns {Promise<{status: string, progress: number, total: number}>}
 */
export function getScanStatus( runId ) {
	return apiFetch( { path: `/wp-security/v1/scans/${ runId }` } );
}
