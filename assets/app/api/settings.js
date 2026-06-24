import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch current plugin settings.
 *
 * @returns {Promise<Object>}
 */
export function fetchSettings() {
	return apiFetch( { path: '/wp-security/v1/settings' } );
}

/**
 * Persist updated settings.
 *
 * @param {Object} data
 * @returns {Promise<void>}
 */
export function updateSettings( data ) {
	return apiFetch( {
		path:   '/wp-security/v1/settings',
		method: 'POST',
		data,
	} );
}
