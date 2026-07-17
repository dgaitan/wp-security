import apiFetch from '@wordpress/api-fetch';

/**
 * List all registered remediation actions.
 *
 * @returns {Promise<Array<{id: string, label: string, capability: string}>>}
 */
export function listRemediations() {
	return apiFetch( { path: '/wp-security/v1/remediations' } );
}

/**
 * Apply a single remediation action. Always sends confirm:true — the caller
 * is responsible for having shown the action's describe() text and gotten
 * explicit user confirmation before calling this.
 *
 * @param {string}              actionId
 * @param {Record<string, any>} params
 * @returns {Promise<{status: string, message: string, before_state: ?object, after_state: ?object}>}
 */
export function applyRemediation( actionId, params = {} ) {
	return apiFetch( {
		path:   `/wp-security/v1/remediations/${ actionId }/apply`,
		method: 'POST',
		data:   { params, confirm: true },
	} );
}

/**
 * Apply a remediation action across multiple items — fans out via Action
 * Scheduler server-side and returns a batch id to poll via getRemediationLog.
 *
 * @param {string}                    actionId
 * @param {Array<Record<string, any>>} items
 * @returns {Promise<{batch_id: string, item_count: number}>}
 */
export function applyRemediationBulk( actionId, items = [] ) {
	return apiFetch( {
		path:   `/wp-security/v1/remediations/${ actionId }/apply`,
		method: 'POST',
		data:   { items, confirm: true },
	} );
}

/**
 * Fetch remediation history, optionally narrowed to one bulk-apply batch.
 *
 * @param {{batchId?: string, limit?: number}} options
 * @returns {Promise<Array<object>>}
 */
export function getRemediationLog( { batchId, limit } = {} ) {
	const query = new URLSearchParams();
	if ( batchId ) query.set( 'batch_id', batchId );
	if ( limit ) query.set( 'limit', String( limit ) );
	const suffix = query.toString() ? `?${ query.toString() }` : '';

	return apiFetch( { path: `/wp-security/v1/remediations/log${ suffix }` } );
}
