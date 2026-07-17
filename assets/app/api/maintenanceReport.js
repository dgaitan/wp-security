import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch the aggregated Maintenance Report payload: recent remediation log
 * (with human-readable actor names), the open findings backlog, and the
 * most recently saved notes.
 *
 * @returns {Promise<{remediations: Array<object>, backlog: Array<object>, notes: object|null}>}
 */
export function fetchMaintenanceReport() {
	return apiFetch( { path: '/wp-security/v1/maintenance-report' } );
}

/**
 * Save time spent / client notes / follow-up notes for a maintenance review.
 *
 * @param {{run_id?: number, time_spent_minutes?: number, client_notes?: string, follow_up_notes?: string}} data
 * @returns {Promise<{id: number}>}
 */
export function saveMaintenanceNotes( data ) {
	return apiFetch( {
		path:   '/wp-security/v1/maintenance-report/notes',
		method: 'POST',
		data,
	} );
}
