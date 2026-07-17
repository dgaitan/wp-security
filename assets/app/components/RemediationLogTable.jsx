import { __ } from '@wordpress/i18n';

/**
 * Renders the remediation audit log (updates applied, when, by whom) for
 * the Maintenance Report — a thin presentational wrapper around
 * RemediationLogRepository::recent()'s already actor-name-resolved rows.
 *
 * @param {{ entries: Array<object> }} props
 */
export function RemediationLogTable( { entries } ) {
	if ( ! entries || entries.length === 0 ) {
		return (
			<p className="wpsec-maintenance-report__empty">
				{ __( 'No remediation actions have been applied yet.', 'wp-security' ) }
			</p>
		);
	}

	return (
		<table className="wpsec-remediation-log-table">
			<thead>
				<tr>
					<th>{ __( 'Date', 'wp-security' ) }</th>
					<th>{ __( 'Action', 'wp-security' ) }</th>
					<th>{ __( 'Target', 'wp-security' ) }</th>
					<th>{ __( 'Status', 'wp-security' ) }</th>
					<th>{ __( 'By', 'wp-security' ) }</th>
				</tr>
			</thead>
			<tbody>
				{ entries.map( ( entry ) => (
					<tr key={ entry.id }>
						<td>{ entry.created_at }</td>
						<td>{ entry.action_id }</td>
						<td>{ entry.target ?? '—' }</td>
						<td data-status={ entry.status }>{ entry.status }</td>
						<td>{ entry.actor_name }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
